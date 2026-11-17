import React, { useCallback, useEffect, useState } from 'react';
import { Alert, Modal, RefreshControl, StyleSheet, Text, TouchableOpacity, View } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { Screen } from '../../components/Screen';
import { Badge, Button, Card, EmptyState, SectionTitle } from '../../components/ui';
import { Field } from '../../components/Field';
import { leaveApi } from '../../api/endpoints';
import { apiMessage } from '../../api/client';
import type { Leave, LeaveType } from '../../types';
import { colors, radius, spacing, statusColor } from '../../theme';

export const LeaveScreen = () => {
  const [leaves, setLeaves] = useState<Leave[]>([]);
  const [types, setTypes] = useState<LeaveType[]>([]);
  const [loading, setLoading] = useState(true);
  const [open, setOpen] = useState(false);
  const [saving, setSaving] = useState(false);
  const [typeId, setTypeId] = useState<number | null>(null);
  const [startDate, setStartDate] = useState('');
  const [endDate, setEndDate] = useState('');
  const [reason, setReason] = useState('');

  const load = useCallback(async () => {
    try {
      const [list, typeList] = await Promise.all([leaveApi.index(), leaveApi.types()]);
      setLeaves(list.data.data);
      setTypes(typeList.data.types);
      if (!typeId && typeList.data.types[0]) setTypeId(typeList.data.types[0].id);
    } catch (err) {
      Alert.alert('Gagal memuat', apiMessage(err));
    } finally {
      setLoading(false);
    }
  }, [typeId]);

  useEffect(() => {
    load();
  }, [load]);

  const submit = async () => {
    if (!typeId || !startDate || !endDate) {
      Alert.alert('Form belum lengkap', 'Pilih jenis cuti dan isi tanggal mulai/selesai (YYYY-MM-DD).');
      return;
    }
    setSaving(true);
    try {
      await leaveApi.store({
        leave_type_id: typeId,
        start_date: startDate,
        end_date: endDate,
        reason,
      });
      setOpen(false);
      setStartDate('');
      setEndDate('');
      setReason('');
      await load();
    } catch (err) {
      Alert.alert('Gagal mengajukan', apiMessage(err));
    } finally {
      setSaving(false);
    }
  };

  return (
    <Screen>
      <View style={styles.header}>
        <View>
          <Text style={styles.title}>Cuti & Izin</Text>
          <Text style={styles.muted}>Ajukan dan pantau status pengajuan.</Text>
        </View>
        <TouchableOpacity style={styles.addButton} onPress={() => setOpen(true)}>
          <Ionicons name="add" size={20} color="#fff" />
        </TouchableOpacity>
      </View>

      {loading ? <Text style={styles.muted}>Memuat...</Text> : null}
      {!loading && leaves.length === 0 ? <EmptyState message="Belum ada pengajuan cuti." /> : null}

      {leaves.map((leave) => (
        <Card key={leave.id} style={{ gap: 6 }}>
          <View style={styles.rowBetween}>
            <Text style={styles.leaveTitle}>{leave.leave_type?.name ?? 'Cuti'}</Text>
            <Badge
              label={leave.status.toUpperCase()}
              bg={statusColor(leave.status).bg}
              color={statusColor(leave.status).text}
            />
          </View>
          <Text style={styles.muted}>
            {leave.start_date} s/d {leave.end_date} · {leave.days} hari
          </Text>
          {leave.reason ? <Text style={styles.reason}>{leave.reason}</Text> : null}
          {leave.status === 'pending' ? (
            <Button
              label="Batalkan pengajuan"
              variant="danger"
              onPress={async () => {
                try {
                  await leaveApi.cancel(leave.id);
                  await load();
                } catch (err) {
                  Alert.alert('Gagal', apiMessage(err));
                }
              }}
            />
          ) : null}
        </Card>
      ))}

      <Modal visible={open} transparent animationType="slide" onRequestClose={() => setOpen(false)}>
        <View style={styles.modalBackdrop}>
          <View style={styles.modalCard}>
            <View style={styles.rowBetween}>
              <Text style={styles.modalTitle}>Pengajuan Cuti</Text>
              <TouchableOpacity onPress={() => setOpen(false)}>
                <Ionicons name="close" size={22} color={colors.muted} />
              </TouchableOpacity>
            </View>

            <Text style={styles.sectionLabel}>Jenis</Text>
            <View style={styles.chips}>
              {types.map((type) => (
                <TouchableOpacity
                  key={type.id}
                  style={[styles.chip, typeId === type.id && styles.chipActive]}
                  onPress={() => setTypeId(type.id)}
                >
                  <Text style={[styles.chipText, typeId === type.id && styles.chipTextActive]}>{type.name}</Text>
                </TouchableOpacity>
              ))}
            </View>

            <Field label="Tanggal mulai (YYYY-MM-DD)" value={startDate} onChangeText={setStartDate} placeholder="2026-11-20" />
            <Field label="Tanggal selesai (YYYY-MM-DD)" value={endDate} onChangeText={setEndDate} placeholder="2026-11-21" />
            <Field
              label="Alasan"
              value={reason}
              onChangeText={setReason}
              placeholder="Alasan pengajuan"
              multiline
              style={{ minHeight: 80 }}
            />

            <Button label={saving ? 'Mengirim...' : 'Kirim pengajuan'} onPress={submit} disabled={saving} />
          </View>
        </View>
      </Modal>
    </Screen>
  );
};

const styles = StyleSheet.create({
  header: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  title: { fontSize: 22, fontWeight: '800', color: colors.text },
  muted: { fontSize: 12, color: colors.muted },
  addButton: {
    width: 40,
    height: 40,
    borderRadius: 14,
    backgroundColor: colors.primary,
    alignItems: 'center',
    justifyContent: 'center',
  },
  rowBetween: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', gap: 8 },
  leaveTitle: { fontWeight: '700', color: colors.text, flex: 1 },
  reason: { fontSize: 12, color: colors.slate },
  modalBackdrop: { flex: 1, backgroundColor: 'rgba(15,23,42,0.5)', justifyContent: 'flex-end' },
  modalCard: {
    backgroundColor: '#fff',
    borderTopLeftRadius: 24,
    borderTopRightRadius: 24,
    padding: spacing.xl,
    gap: spacing.md,
    maxHeight: '92%',
  },
  modalTitle: { fontSize: 18, fontWeight: '800', color: colors.text },
  sectionLabel: { fontSize: 13, fontWeight: '700', color: colors.text },
  chips: { flexDirection: 'row', flexWrap: 'wrap', gap: spacing.sm },
  chip: { borderWidth: 1, borderColor: colors.border, borderRadius: radius.full, paddingHorizontal: 12, paddingVertical: 6 },
  chipActive: { backgroundColor: colors.primary, borderColor: colors.primary },
  chipText: { fontSize: 12, color: colors.muted },
  chipTextActive: { color: '#fff', fontWeight: '700' },
});
