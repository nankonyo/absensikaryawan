import React, { useCallback, useState } from 'react';
import { ActivityIndicator, Alert, ScrollView, StyleSheet, Text, TouchableOpacity, View } from 'react-native';
import { useFocusEffect } from '@react-navigation/native';
import { Ionicons } from '@expo/vector-icons';
import { Screen } from '../../components/Screen';
import { Card, EmptyState, SectionTitle } from '../../components/ui';
import { reportApi } from '../../api/endpoints';
import { apiMessage } from '../../api/client';
import { useAuth } from '../../context/AuthContext';
import type { AttendanceReport, AttendanceReportRow } from '../../types';
import { colors, radius, spacing } from '../../theme';

const MONTHS = [
  'Januari',
  'Februari',
  'Maret',
  'April',
  'Mei',
  'Juni',
  'Juli',
  'Agustus',
  'September',
  'Oktober',
  'November',
  'Desember',
];

const monthKey = (date: Date) => `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`;

const monthRange = (month: string) => {
  const [year, monthNumber] = month.split('-').map(Number);
  const lastDay = new Date(year, monthNumber, 0).getDate();
  return {
    start: `${month}-01`,
    end: `${month}-${String(lastDay).padStart(2, '0')}`,
  };
};

const STAT_FIELDS: { key: keyof AttendanceReportRow; label: string; color: string }[] = [
  { key: 'absent', label: 'Absent', color: colors.danger },
  { key: 'late_hours', label: 'Terlambat (jam)', color: colors.warning },
  { key: 'wfh', label: 'WFH', color: colors.info },
  { key: 'duty_city', label: 'Tugas Dalam Kota', color: colors.info },
  { key: 'duty_out_of_town', label: 'Tugas Luar Kota', color: colors.info },
  { key: 'training', label: 'Pelatihan', color: colors.info },
  { key: 'permit', label: 'Izin', color: colors.warning },
  { key: 'leave', label: 'Cuti', color: colors.success },
  { key: 'sick_leave', label: 'Izin Sakit', color: colors.warning },
  { key: 'collective_leave', label: 'Cuti Bersama', color: '#7c3aed' },
  { key: 'company_holiday', label: 'Libur Perusahaan', color: '#b45309' },
  { key: 'national_holiday', label: 'Libur Nasional', color: colors.danger },
];

export const ReportScreen = () => {
  const { user } = useAuth();
  const isAdmin = Boolean(user?.is_admin);
  const [month, setMonth] = useState(monthKey(new Date()));
  const [report, setReport] = useState<AttendanceReport | null>(null);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);

  const [year, monthNumber] = month.split('-').map(Number);

  const load = useCallback(async () => {
    if (!isAdmin) {
      setLoading(false);
      return;
    }

    const range = monthRange(month);
    try {
      const response = await reportApi.attendance({ start_date: range.start, end_date: range.end });
      setReport(response.data);
    } catch (error) {
      Alert.alert('Gagal memuat laporan', apiMessage(error));
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  }, [isAdmin, month]);

  useFocusEffect(
    useCallback(() => {
      load();
    }, [load]),
  );

  const shiftMonth = (offset: number) => {
    const next = new Date(year, monthNumber - 1 + offset, 1);
    setMonth(monthKey(next));
  };

  const onRefresh = () => {
    setRefreshing(true);
    load();
  };

  if (!isAdmin) {
    return (
      <Screen back>
        <Text style={styles.title}>Laporan</Text>
        <EmptyState message="Laporan absensi hanya dapat diakses oleh admin." />
      </Screen>
    );
  }

  return (
    <Screen back>
      <Text style={styles.title}>Laporan Absensi</Text>
      <Text style={styles.muted}>Rekap kehadiran karyawan per bulan.</Text>

      <Card style={{ gap: spacing.md }}>
        <View style={styles.monthRow}>
          <TouchableOpacity style={styles.monthButton} onPress={() => shiftMonth(-1)}>
            <Ionicons name="chevron-back" size={18} color={colors.text} />
          </TouchableOpacity>
          <View style={{ alignItems: 'center' }}>
            <Text style={styles.monthLabel}>
              {MONTHS[monthNumber - 1]} {year}
            </Text>
            <Text style={styles.muted}>
              {report?.start_date ?? '-'} s.d. {report?.end_date ?? '-'}
            </Text>
          </View>
          <TouchableOpacity style={styles.monthButton} onPress={() => shiftMonth(1)}>
            <Ionicons name="chevron-forward" size={18} color={colors.text} />
          </TouchableOpacity>
        </View>

        {loading ? <ActivityIndicator color={colors.primary} /> : null}

        {report ? (
          <View style={styles.summaryGrid}>
            <Summary label="Karyawan" value={report.employee_count} icon="people" color={colors.primary} />
            <Summary label="Hari Kerja" value={report.normal_work_days} icon="calendar" color={colors.info} />
            <Summary label="Absent" value={report.totals.absent} icon="close-circle" color={colors.danger} />
            <Summary label="Terlambat" value={report.totals.late_hours} icon="time" color={colors.warning} />
          </View>
        ) : null}
      </Card>

      {!loading && report?.rows?.length === 0 ? <EmptyState message="Belum ada data karyawan." /> : null}

      {report?.rows?.length ? <SectionTitle title="Rekap Karyawan" /> : null}

      {report?.rows?.map((row) => (
        <Card key={String(row.employee_id)} style={{ gap: spacing.sm }}>
          <View style={styles.rowHeader}>
            <View style={{ flex: 1 }}>
              <Text style={styles.employeeName}>{row.name}</Text>
              <Text style={styles.muted}>{row.employee_id}</Text>
            </View>
            <View style={styles.actualBox}>
              <Text style={styles.actualValue}>{row.actual_work_days}</Text>
              <Text style={styles.actualLabel}>Riil / {row.normal_work_days}</Text>
            </View>
          </View>

          <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.statsRow}>
            {STAT_FIELDS.map((field) => {
              const value = Number(row[field.key] ?? 0);
              return (
                <View key={field.key} style={[styles.statChip, { borderColor: `${field.color}33` }]}>
                  <Text style={[styles.statValue, { color: field.color }]}>{value}</Text>
                  <Text style={styles.statLabel}>{field.label}</Text>
                </View>
              );
            })}
          </ScrollView>
        </Card>
      ))}
    </Screen>
  );
};

const Summary = ({
  label,
  value,
  icon,
  color,
}: {
  label: string;
  value: number;
  icon: string;
  color: string;
}) => (
  <View style={styles.summaryCard}>
    <View style={[styles.summaryIcon, { backgroundColor: `${color}1a` }]}>
      <Ionicons name={icon as any} size={16} color={color} />
    </View>
    <Text style={styles.summaryValue}>{value}</Text>
    <Text style={styles.summaryLabel}>{label}</Text>
  </View>
);

const styles = StyleSheet.create({
  title: { fontSize: 22, fontWeight: '800', color: colors.text },
  muted: { fontSize: 12, color: colors.muted },
  monthRow: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between' },
  monthButton: {
    width: 34,
    height: 34,
    borderRadius: radius.md,
    borderWidth: 1,
    borderColor: colors.border,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: '#fff',
  },
  monthLabel: { fontSize: 16, fontWeight: '800', color: colors.text },
  summaryGrid: { flexDirection: 'row', flexWrap: 'wrap', gap: spacing.sm },
  summaryCard: {
    width: '48%',
    borderRadius: radius.md,
    borderWidth: 1,
    borderColor: colors.border,
    padding: spacing.md,
    gap: 2,
  },
  summaryIcon: { width: 28, height: 28, borderRadius: 9, alignItems: 'center', justifyContent: 'center' },
  summaryValue: { fontSize: 20, fontWeight: '800', color: colors.text },
  summaryLabel: { fontSize: 10, color: colors.muted },
  rowHeader: { flexDirection: 'row', alignItems: 'center', gap: spacing.sm },
  employeeName: { fontSize: 14, fontWeight: '800', color: colors.text },
  actualBox: { alignItems: 'flex-end' },
  actualValue: { fontSize: 20, fontWeight: '800', color: colors.success },
  actualLabel: { fontSize: 9, color: colors.muted },
  statsRow: { gap: spacing.sm, paddingVertical: 2 },
  statChip: {
    minWidth: 78,
    borderRadius: radius.md,
    borderWidth: 1,
    paddingHorizontal: spacing.sm,
    paddingVertical: 6,
    backgroundColor: '#fff',
  },
  statValue: { fontSize: 15, fontWeight: '800' },
  statLabel: { fontSize: 9, color: colors.muted },
});
