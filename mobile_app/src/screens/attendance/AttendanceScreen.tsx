import React, { useCallback, useEffect, useRef, useState } from 'react';
import { ActivityIndicator, Alert, Linking, RefreshControl, StyleSheet, Text, TouchableOpacity, View } from 'react-native';
import * as Location from 'expo-location';
import * as ImagePicker from 'expo-image-picker';
import { Ionicons } from '@expo/vector-icons';
import { Screen } from '../../components/Screen';
import { Badge, Button, Card, EmptyState, SectionTitle } from '../../components/ui';
import { Field } from '../../components/Field';
import { useFaceCapture } from '../../components/FaceCaptureProvider';
import { attendanceApi } from '../../api/endpoints';
import { apiMessage } from '../../api/client';
import type { Attendance } from '../../types';
import { colors, radius, spacing, statusColor } from '../../theme';

type TodayData = Awaited<ReturnType<typeof attendanceApi.today>>['data'];

type FaceOutcome =
  | { status: 'captured'; photoData: string; faceScore: number }
  | { status: 'cancelled' }
  | { status: 'unavailable'; message?: string };

export const AttendanceScreen = () => {
  const [tab, setTab] = useState<'today' | 'history'>('today');
  const [data, setData] = useState<TodayData | null>(null);
  const [history, setHistory] = useState<Attendance[]>([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [submitting, setSubmitting] = useState(false);
  const [reasonId, setReasonId] = useState<number | null>(null);
  const [notes, setNotes] = useState('');
  const [distanceToOffice, setDistanceToOffice] = useState<number | null>(null);
  const positionWatch = useRef<Location.LocationSubscription | null>(null);
  const lastPosition = useRef<Location.LocationObject | null>(null);

  const ensureCameraPermission = useCallback(async () => {
    const permission = await ImagePicker.requestCameraPermissionsAsync();

    if (permission.granted) {
      return true;
    }

    Alert.alert(
      'Izin kamera diperlukan',
      'Aktifkan izin kamera untuk aplikasi ini agar verifikasi wajah dapat berjalan.',
      [
        { text: 'Batal', style: 'cancel' },
        { text: 'Buka Pengaturan', onPress: () => Linking.openSettings() },
      ],
    );

    return false;
  }, []);

  const { startFaceCapture } = useFaceCapture();
  const faceRequired = Boolean(data?.face_verification_required);

  const captureFace = useCallback(
    async (mode: 'in' | 'out'): Promise<FaceOutcome> => {
      const allowed = await ensureCameraPermission();

      if (!allowed) {
        return { status: 'cancelled' };
      }

      const result = await startFaceCapture(mode);

      if (result.status === 'captured') {
        return {
          status: 'captured',
          photoData: result.photoData,
          faceScore: result.faceScore,
        };
      }

      if (result.status === 'cancelled') {
        return { status: 'cancelled' };
      }

      if (result.status === 'no_avatar') {
        if (faceRequired) {
          Alert.alert(
            'Verifikasi wajah',
            'Avatar wajah belum diatur. Hubungi admin untuk mengunggah foto wajah Anda.',
          );

          return { status: 'cancelled' };
        }

        return { status: 'unavailable' };
      }

      if (faceRequired) {
        Alert.alert(
          'Verifikasi wajah gagal',
          `${result.message}\n\nPastikan izin kamera aktif, lalu coba lagi.`,
        );

        return { status: 'cancelled' };
      }

      return { status: 'unavailable' };
    },
    [ensureCameraPermission, faceRequired, startFaceCapture],
  );

  const load = useCallback(async () => {
    try {
      const [today, historyResponse] = await Promise.all([
        attendanceApi.today(),
        attendanceApi.history({ per_page: 20 }),
      ]);
      setData(today.data);
      setHistory(historyResponse.data.data);
    } catch (err) {
      Alert.alert('Gagal memuat', apiMessage(err));
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  }, []);

  useEffect(() => {
    load();
  }, [load]);

  const startPositionWatch = useCallback(async () => {
    if (positionWatch.current) return;

    const permission = await Location.getForegroundPermissionsAsync();

    if (!permission.granted) return;

    const subscription = await Location.watchPositionAsync(
      {
        accuracy: Location.Accuracy.High,
        timeInterval: 10000,
        distanceInterval: 5,
      },
      (position) => {
        lastPosition.current = position;
      },
    );

    if (positionWatch.current) {
      subscription.remove();
    } else {
      positionWatch.current = subscription;
    }
  }, []);

  useEffect(() => {
    startPositionWatch().catch(() => {});

    return () => {
      positionWatch.current?.remove();
      positionWatch.current = null;
    };
  }, [startPositionWatch]);

  const locate = async () => {
    const permission = await Location.requestForegroundPermissionsAsync();

    if (permission.status !== 'granted') {
      throw new Error('Izin lokasi ditolak. Aktifkan GPS dan izin lokasi.');
    }

    startPositionWatch().catch(() => {});

    const cached = lastPosition.current;
    const age = cached ? Date.now() - (cached.timestamp || 0) : Infinity;
    const accuracy = cached?.coords.accuracy ?? null;
    const usable = Boolean(cached) && age < 60000 && (accuracy === null || accuracy <= 100);

    // Pakai fix GPS terbaru dari watch; kalau tidak ada, baru minta posisi baru.
    const position = usable && cached
      ? cached
      : await Location.getCurrentPositionAsync({ accuracy: Location.Accuracy.High });

    lastPosition.current = position;

    const latitude = position.coords.latitude;
    const longitude = position.coords.longitude;

    return {
      latitude,
      longitude,
      // Reverse geocode dibuang: server hanya butuh koordinat, dan ini
      // mempercepat proses absensi secara signifikan.
      address: `${latitude}, ${longitude}`,
    };
  };

  const takePhoto = async (): Promise<string | undefined> => {
    const permission = await ImagePicker.requestCameraPermissionsAsync();
    if (permission.status !== 'granted') return undefined;

    const result = await ImagePicker.launchCameraAsync({
      mediaTypes: ImagePicker.MediaTypeOptions.Images,
      quality: 0.5,
      base64: true,
      cameraType: ImagePicker.CameraType.front,
    });

    if (result.canceled || !result.assets?.length) return undefined;
    const asset = result.assets[0];
    return asset.base64 ? `data:image/jpeg;base64,${asset.base64}` : undefined;
  };

  const officeDistance = (lat1: number, lng1: number, lat2: number, lng2: number) => {
    const toRad = (value: number) => (value * Math.PI) / 180;
    const earthRadius = 6371000;
    const dLat = toRad(lat2 - lat1);
    const dLng = toRad(lng2 - lng1);
    const a =
      Math.sin(dLat / 2) ** 2 +
      Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) * Math.sin(dLng / 2) ** 2;

    return Math.round(earthRadius * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a)));
  };

  const submit = async (mode: 'in' | 'out') => {
    setSubmitting(true);
    try {
      // Cek lokasi lebih dulu supaya saat di luar radius user memilih alasan
      // sebelum mengambil selfie (tidak perlu foto ulang).
      const location = await locate();

      // Verifikasi lokasi juga di client: WFO di luar radius wajib memilih alasan.
      // Server tetap menjadi penentu akhir (lihat AttendanceService).
      const office = data?.office;
      const distance =
        office && isWfo
          ? officeDistance(location.latitude, location.longitude, office.latitude, office.longitude)
          : null;

      setDistanceToOffice(distance);

      if (distance !== null && office && distance > office.radius && !reasonId) {
        Alert.alert(
          'Di luar zona kantor',
          `Anda berada ${distance} m dari ${office.name} (batas ${office.radius} m). Pilih alasan kehadiran terlebih dahulu.`,
        );

        return;
      }

      const face = await captureFace(mode);

      if (face.status === 'cancelled') {
        return;
      }

      let photo: string | undefined;
      let faceScore: number | undefined;

      if (face.status === 'captured') {
        photo = face.photoData;
        faceScore = face.faceScore;
      } else {
        // Avatar belum diatur atau kamera verifikasi tidak tersedia:
        // pakai kamera biasa, server tetap yang menentukan validasi wajah.
        photo = await takePhoto();
        if (!photo) return;
      }

      const payload: Record<string, unknown> = {
        ...location,
        attendance_reason_id: reasonId ?? undefined,
        notes: notes || undefined,
      };
      if (photo) payload.photo_data = photo;
      if (faceScore !== undefined) payload.face_score = faceScore;

      const response =
        mode === 'in' ? await attendanceApi.checkIn(payload) : await attendanceApi.checkOut(payload);

      Alert.alert('Berhasil', response.data.message ?? 'Presensi tersimpan.');
      setNotes('');
      setReasonId(null);
      setDistanceToOffice(null);
      await load();
    } catch (err) {
      Alert.alert('Presensi gagal', apiMessage(err, 'Tidak dapat menyimpan presensi.'));
    } finally {
      setSubmitting(false);
    }
  };

  if (loading) {
    return (
      <Screen>
        <View style={styles.loading}>
          <ActivityIndicator color={colors.primary} />
          <Text style={styles.muted}>Memuat presensi...</Text>
        </View>
      </Screen>
    );
  }

  const attendance = data?.attendance;
  const workMode = data?.work_mode ?? 'WFO';
  const isWfo = workMode === 'WFO';
  const workIcon = workMode === 'WFH' ? 'home' : workMode === 'WFA' ? 'globe' : 'location';

  return (
    <Screen
      scroll
    >
      <View style={styles.tabs}>
        <TabButton label="Hari Ini" active={tab === 'today'} onPress={() => setTab('today')} />
        <TabButton label="Riwayat" active={tab === 'history'} onPress={() => setTab('history')} />
      </View>

      {tab === 'today' ? (
        <>
          <Card style={styles.todayCard}>
            <View style={styles.rowBetween}>
              <View>
                <Text style={styles.muted}>Status presensi</Text>
                <Text style={styles.bigStatus}>
                  {attendance?.check_in ? (attendance?.check_out ? 'Presensi selesai' : 'Sudah check-in') : 'Belum check-in'}
                </Text>
                <Text style={styles.muted}>
                  {attendance?.check_in ? `Masuk ${String(attendance.check_in).slice(0, 5)}` : '—'}
                  {attendance?.check_out ? ` · Pulang ${String(attendance.check_out).slice(0, 5)}` : ''}
                </Text>
              </View>
              <Badge
                label={attendance?.status?.toUpperCase() ?? 'BELUM'}
                bg={statusColor(attendance?.status).bg}
                color={statusColor(attendance?.status).text}
              />
            </View>

            <View style={styles.officeBox}>
              <Ionicons name={workIcon as any} size={16} color={colors.primary} />
              <View style={{ flex: 1 }}>
                <Text style={styles.officeName}>Mode kerja: {workMode}</Text>
                <Text style={styles.muted}>
                  {isWfo
                    ? `${data?.office?.name ?? 'Kantor belum diatur'} · Radius ${data?.office?.radius ?? 0} m`
                    : 'Bebas lokasi — presensi dapat dilakukan dari mana saja'}
                </Text>
                <Text style={styles.muted}>
                  Jam kerja {data?.work_start_time} - {data?.work_end_time}
                </Text>
                {isWfo && distanceToOffice !== null ? (
                  <Text
                    style={[
                      styles.muted,
                      distanceToOffice > (data?.office?.radius ?? 0) && styles.outsideText,
                    ]}
                  >
                    Jarak Anda {distanceToOffice} m —{' '}
                    {distanceToOffice > (data?.office?.radius ?? 0)
                      ? 'di luar zona, pilih alasan'
                      : 'di dalam zona kantor'}
                  </Text>
                ) : null}
              </View>
            </View>
          </Card>

          <Card style={{ gap: spacing.md }}>
            {isWfo ? (
              <>
                <Text style={styles.sectionLabel}>Alasan kehadiran (wajib jika di luar zona)</Text>
                <View style={styles.chips}>
                  {(data?.reasons ?? []).map((reason) => (
                    <TouchableOpacity
                      key={reason.id}
                      style={[styles.chip, reasonId === reason.id && styles.chipActive]}
                      onPress={() => setReasonId(reasonId === reason.id ? null : reason.id)}
                    >
                      <Text style={[styles.chipText, reasonId === reason.id && styles.chipTextActive]}>{reason.name}</Text>
                    </TouchableOpacity>
                  ))}
                </View>
              </>
            ) : (
              <View style={styles.officeBox}>
                <Ionicons name={workIcon as any} size={16} color={colors.primary} />
                <Text style={styles.muted}>
                  Mode {workMode}: presensi bebas lokasi, tidak perlu memilih alasan luar kantor.
                </Text>
              </View>
            )}

            <Field label="Catatan (opsional)" value={notes} onChangeText={setNotes} placeholder="Catatan presensi" />

            <Button
              label={submitting ? 'Memproses absensi...' : 'Check-in sekarang'}
              onPress={() => submit('in')}
              disabled={submitting || Boolean(attendance?.check_in)}
            />
            <Button
              label={submitting ? 'Memproses absensi...' : 'Check-out sekarang'}
              onPress={() => submit('out')}
              variant="secondary"
              disabled={submitting || Boolean(attendance?.check_out)}
            />
            <Text style={styles.hint}>
              {isWfo
                ? 'Kamera mendeteksi wajah otomatis dan mencocokkannya dengan avatar. Jika di luar radius, pilih alasan kehadiran agar masuk verifikasi.'
                : `Mode ${workMode} bebas lokasi. Wajah diverifikasi otomatis untuk arsip presensi.`}
            </Text>
          </Card>
        </>
      ) : (
        <>
          <SectionTitle title="Riwayat Presensi" />
          {history.length === 0 ? (
            <EmptyState message="Belum ada riwayat presensi." />
          ) : (
            history.map((item) => (
              <Card key={item.id} style={styles.historyItem}>
                <View style={styles.rowBetween}>
                  <View>
                    <Text style={styles.date}>{new Date(item.date).toLocaleDateString('id-ID', { dateStyle: 'medium' })}</Text>
                    <Text style={styles.muted}>
                      Masuk {item.check_in ? String(item.check_in).slice(0, 5) : '—'} · Pulang{' '}
                      {item.check_out ? String(item.check_out).slice(0, 5) : '—'}
                    </Text>
                  </View>
                  <Badge
                    label={item.status?.toUpperCase() ?? '-'}
                    bg={statusColor(item.status).bg}
                    color={statusColor(item.status).text}
                  />
                </View>
              </Card>
            ))
          )}
        </>
      )}

    </Screen>
  );
};

const TabButton = ({ label, active, onPress }: { label: string; active: boolean; onPress: () => void }) => (
  <TouchableOpacity onPress={onPress} style={[styles.tabButton, active && styles.tabButtonActive]}>
    <Text style={[styles.tabText, active && styles.tabTextActive]}>{label}</Text>
  </TouchableOpacity>
);

const styles = StyleSheet.create({
  loading: { flex: 1, alignItems: 'center', justifyContent: 'center', gap: 8, paddingVertical: 80 },
  muted: { fontSize: 12, color: colors.muted },
  tabs: { flexDirection: 'row', backgroundColor: '#eef2f7', borderRadius: radius.md, padding: 4, gap: 4 },
  tabButton: { flex: 1, paddingVertical: 10, alignItems: 'center', borderRadius: radius.sm },
  tabButtonActive: { backgroundColor: '#fff' },
  tabText: { fontSize: 13, fontWeight: '600', color: colors.muted },
  tabTextActive: { color: colors.primary },
  todayCard: { gap: spacing.md },
  rowBetween: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'flex-start', gap: spacing.md },
  bigStatus: { fontSize: 20, fontWeight: '800', color: colors.text, marginVertical: 2 },
  officeBox: {
    flexDirection: 'row',
    gap: spacing.sm,
    alignItems: 'center',
    backgroundColor: colors.primarySoft,
    borderRadius: radius.md,
    padding: spacing.md,
  },
  officeName: { fontWeight: '700', color: colors.text },
  outsideText: { color: colors.danger, fontWeight: '600' },
  sectionLabel: { fontSize: 13, fontWeight: '700', color: colors.text },
  chips: { flexDirection: 'row', flexWrap: 'wrap', gap: spacing.sm },
  chip: { borderWidth: 1, borderColor: colors.border, borderRadius: radius.full, paddingHorizontal: 12, paddingVertical: 6 },
  chipActive: { backgroundColor: colors.primary, borderColor: colors.primary },
  chipText: { fontSize: 12, color: colors.muted },
  chipTextActive: { color: '#fff', fontWeight: '700' },
  hint: { fontSize: 11, color: colors.subtle, lineHeight: 16 },
  historyItem: {},
  date: { fontWeight: '700', color: colors.text },
});
