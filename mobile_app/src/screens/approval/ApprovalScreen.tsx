import React, { useCallback, useEffect, useState } from 'react';
import { Alert, StyleSheet, Text, View } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { Screen } from '../../components/Screen';
import { Badge, Button, Card, EmptyState } from '../../components/ui';
import { approvalApi } from '../../api/endpoints';
import { apiMessage } from '../../api/client';
import type { Leave } from '../../types';
import { colors, spacing, statusColor } from '../../theme';

export const ApprovalScreen = () => {
  const [leaves, setLeaves] = useState<Leave[]>([]);
  const [loading, setLoading] = useState(true);
  const [processing, setProcessing] = useState<number | null>(null);

  const load = useCallback(async () => {
    try {
      const response = await approvalApi.index();
      setLeaves(response.data.data);
    } catch (err) {
      Alert.alert('Gagal memuat', apiMessage(err));
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    load();
  }, [load]);

  const act = async (leave: Leave, action: 'approve' | 'reject') => {
    setProcessing(leave.id);
    try {
      if (action === 'approve') await approvalApi.approve(leave.id);
      else await approvalApi.reject(leave.id);
      await load();
    } catch (err) {
      Alert.alert('Gagal', apiMessage(err));
    } finally {
      setProcessing(null);
    }
  };

  return (
    <Screen>
      <View>
        <Text style={styles.title}>Approval</Text>
        <Text style={styles.muted}>Pengajuan cuti tim yang menunggu keputusan.</Text>
      </View>

      {loading ? <Text style={styles.muted}>Memuat...</Text> : null}
      {!loading && leaves.length === 0 ? <EmptyState message="Tidak ada pengajuan pending." /> : null}

      {leaves.map((leave) => (
        <Card key={leave.id} style={styles.card}>
          <View style={styles.rowBetween}>
            <View style={{ flex: 1 }}>
              <Text style={styles.name}>{leave.user?.name ?? 'Karyawan'}</Text>
              <Text style={styles.muted}>{leave.leave_type?.name ?? 'Cuti'}</Text>
            </View>
            <Badge
              label="PENDING"
              bg={statusColor('pending').bg}
              color={statusColor('pending').text}
            />
          </View>
          <Text style={styles.detail}>
            {leave.start_date} s/d {leave.end_date} · {leave.days} hari
          </Text>
          {leave.reason ? <Text style={styles.reason}>{leave.reason}</Text> : null}
          <View style={styles.actions}>
            <Button
              label={processing === leave.id ? 'Memproses...' : 'Setujui'}
              onPress={() => act(leave, 'approve')}
              disabled={processing === leave.id}
              style={{ flex: 1 }}
            />
            <Button
              label="Tolak"
              variant="danger"
              onPress={() => act(leave, 'reject')}
              disabled={processing === leave.id}
              style={{ flex: 1 }}
            />
          </View>
        </Card>
      ))}
    </Screen>
  );
};

const styles = StyleSheet.create({
  title: { fontSize: 22, fontWeight: '800', color: colors.text },
  muted: { fontSize: 12, color: colors.muted },
  card: { gap: spacing.sm },
  rowBetween: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', gap: 8 },
  name: { fontWeight: '700', color: colors.text },
  detail: { fontSize: 12, color: colors.slate },
  reason: { fontSize: 12, color: colors.muted },
  actions: { flexDirection: 'row', gap: spacing.sm },
});
