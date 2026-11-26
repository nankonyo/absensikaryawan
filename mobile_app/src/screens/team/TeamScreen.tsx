import React, { useCallback, useEffect, useState } from 'react';
import { Alert, RefreshControl, StyleSheet, Text, View } from 'react-native';
import { Screen } from '../../components/Screen';
import { Badge, Card, EmptyState } from '../../components/ui';
import { teamApi } from '../../api/endpoints';
import { formatLabel } from '../../utils/labels';
import { apiMessage } from '../../api/client';
import { colors, spacing, statusColor } from '../../theme';

type Member = Awaited<ReturnType<typeof teamApi.index>>['data']['members'][number];

export const TeamScreen = () => {
  const [members, setMembers] = useState<Member[]>([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);

  const load = useCallback(async () => {
    try {
      const response = await teamApi.index();
      setMembers(response.data.members);
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

  return (
    <Screen
      scroll
    >
      {refreshing ? null : null}
      <View>
        <Text style={styles.title}>Tim Saya</Text>
        <Text style={styles.muted}>Pantau kehadiran anggota tim hari ini.</Text>
      </View>

      {loading ? <Text style={styles.muted}>Memuat...</Text> : null}
      {!loading && members.length === 0 ? <EmptyState message="Belum ada anggota tim." /> : null}

      {members.map((member) => {
        const attendance = member.attendance;
        const status = attendance?.check_in ? (attendance?.check_out ? 'Selesai' : 'Check-in') : 'Belum';
        return (
          <Card key={member.id} style={styles.card}>
            <View style={styles.rowBetween}>
              <View style={{ flex: 1 }}>
                <Text style={styles.name}>{member.name}</Text>
                <Text style={styles.muted}>
                  {member.role ?? '-'} {member.unit ? `· ${member.unit}` : ''}
                </Text>
              </View>
              <Badge
                label={formatLabel(status)}
                bg={statusColor(attendance?.status ?? (attendance?.check_in ? 'valid' : 'pending')).bg}
                color={statusColor(attendance?.status ?? (attendance?.check_in ? 'valid' : 'pending')).text}
              />
            </View>
            <Text style={styles.attendanceText}>
              Masuk {attendance?.check_in ? String(attendance.check_in).slice(0, 5) : '—'} · Pulang{' '}
              {attendance?.check_out ? String(attendance.check_out).slice(0, 5) : '—'}
            </Text>
          </Card>
        );
      })}
    </Screen>
  );
};

const styles = StyleSheet.create({
  title: { fontSize: 22, fontWeight: '800', color: colors.text },
  muted: { fontSize: 12, color: colors.muted },
  card: { gap: 8 },
  rowBetween: { flexDirection: 'row', alignItems: 'center', gap: spacing.sm },
  name: { fontWeight: '700', color: colors.text },
  attendanceText: { fontSize: 12, color: colors.slate },
});
