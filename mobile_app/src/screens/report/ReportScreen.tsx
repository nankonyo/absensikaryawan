import React, { useCallback, useState } from 'react';
import {
  ActivityIndicator,
  Alert,
  Linking,
  ScrollView,
  StyleSheet,
  Text,
  TextInput,
  TouchableOpacity,
  View,
} from 'react-native';
import { useFocusEffect } from '@react-navigation/native';
import { Ionicons } from '@expo/vector-icons';
import { Screen } from '../../components/Screen';
import { Card, EmptyState } from '../../components/ui';
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

type FilterType = 'month' | 'range' | 'semester' | 'year';

const pad2 = (value: number) => String(value).padStart(2, '0');

const monthKey = (date: Date) => `${date.getFullYear()}-${pad2(date.getMonth() + 1)}`;

const monthRange = (month: string) => {
  const [year, monthNumber] = month.split('-').map(Number);
  const lastDay = new Date(year, monthNumber, 0).getDate();
  return {
    start_date: `${month}-01`,
    end_date: `${month}-${pad2(lastDay)}`,
  };
};

const semesterRange = (year: number, semester: number) => {
  const startMonth = semester === 1 ? 1 : 7;
  const endMonth = semester === 1 ? 6 : 12;
  const lastDay = new Date(year, endMonth, 0).getDate();
  return {
    start_date: `${year}-${pad2(startMonth)}-01`,
    end_date: `${year}-${pad2(endMonth)}-${pad2(lastDay)}`,
  };
};

const yearRange = (year: number) => ({
  start_date: `${year}-01-01`,
  end_date: `${year}-12-31`,
});

const isDate = (value: string) => /^\d{4}-\d{2}-\d{2}$/.test(value);

const gradeFor = (percentage: number) => {
  if (percentage > 91) return 'Sangat Baik';
  if (percentage >= 71) return 'Baik';
  if (percentage >= 51) return 'Kurang';
  return 'Sangat Kurang';
};

type TableColumn = {
  key: keyof AttendanceReportRow | 'keterangan';
  label: string;
  width: number;
  color?: string;
};

const TABLE_COLUMNS: TableColumn[] = [
  { key: 'employee_id', label: 'No. ID', width: 84 },
  { key: 'name', label: 'Nama', width: 160 },
  { key: 'normal_work_days', label: 'Normal Hari Kerja', width: 104, color: '#b45309' },
  { key: 'actual_work_days', label: 'Riil', width: 64, color: '#a16207' },
  { key: 'absent', label: 'Absent', width: 70, color: colors.danger },
  { key: 'late_hours', label: 'Terlambat / Jam', width: 100, color: colors.warning },
  { key: 'duty_city', label: 'Tugas Dalam Kota', width: 110 },
  { key: 'wfh', label: 'WFH', width: 60, color: colors.info },
  { key: 'permit', label: 'Izin', width: 60 },
  { key: 'leave', label: 'CUTI', width: 60, color: colors.success },
  { key: 'sick_leave', label: 'Izin Sakit', width: 80 },
  { key: 'compensation_hours', label: 'Jam Kompensasi', width: 100 },
  { key: 'collective_leave', label: 'Cuti Bersama', width: 90 },
  { key: 'company_holiday', label: 'Libur Perusahaan', width: 110 },
  { key: 'national_holiday', label: 'Libur Tanggal Merah', width: 120 },
  { key: 'training', label: 'Tugas Pelatihan', width: 100 },
  { key: 'duty_out_of_town', label: 'Tugas Keluar Kota', width: 110 },
  { key: 'present_days', label: 'Total Hari Hadir', width: 100, color: colors.success },
  { key: 'attendance_percentage', label: '% Kehadiran', width: 90, color: colors.primary },
  { key: 'attendance_grade', label: 'Penilaian', width: 108 },
  { key: 'keterangan', label: 'Keterangan', width: 110 },
];

const NUMERIC_KEYS: (keyof AttendanceReportRow)[] = [
  'normal_work_days',
  'actual_work_days',
  'absent',
  'late_hours',
  'duty_city',
  'wfh',
  'permit',
  'leave',
  'sick_leave',
  'compensation_hours',
  'collective_leave',
  'company_holiday',
  'national_holiday',
  'training',
  'duty_out_of_town',
  'present_days',
];

export const ReportScreen = () => {
  const { user } = useAuth();
  const isAdmin = Boolean(user?.is_admin);

  const initialMonth = monthKey(new Date());
  const initialRange = monthRange(initialMonth);

  const [filterType, setFilterType] = useState<FilterType>('month');
  const [month, setMonth] = useState(initialMonth);
  const [startDate, setStartDate] = useState(initialRange.start_date);
  const [endDate, setEndDate] = useState(initialRange.end_date);
  const [semester, setSemester] = useState(new Date().getMonth() + 1 <= 6 ? 1 : 2);
  const [year, setYear] = useState(new Date().getFullYear());
  const [params, setParams] = useState(initialRange);
  const [report, setReport] = useState<AttendanceReport | null>(null);
  const [loading, setLoading] = useState(true);
  const [downloading, setDownloading] = useState<'pdf' | 'excel' | null>(null);

  const [monthYear, monthNumber] = month.split('-').map(Number);

  const load = useCallback(async () => {
    if (!isAdmin) {
      setLoading(false);
      return;
    }

    setLoading(true);
    try {
      const response = await reportApi.attendance(params);
      setReport(response.data);
    } catch (error) {
      Alert.alert('Gagal memuat laporan', apiMessage(error));
    } finally {
      setLoading(false);
    }
  }, [isAdmin, params]);

  useFocusEffect(
    useCallback(() => {
      load();
    }, [load]),
  );

  const applyMonth = (offset: number) => {
    const next = new Date(monthYear, monthNumber - 1 + offset, 1);
    const nextMonth = monthKey(next);
    setMonth(nextMonth);
    const range = monthRange(nextMonth);
    setStartDate(range.start_date);
    setEndDate(range.end_date);
    setParams(range);
  };

  const changeFilterType = (next: FilterType) => {
    setFilterType(next);

    if (next === 'month') {
      const range = monthRange(month);
      setStartDate(range.start_date);
      setEndDate(range.end_date);
      setParams(range);
    } else if (next === 'range') {
      setParams({ start_date: startDate, end_date: endDate });
    } else if (next === 'semester') {
      const range = semesterRange(year, semester);
      setStartDate(range.start_date);
      setEndDate(range.end_date);
      setParams(range);
    } else {
      const range = yearRange(year);
      setStartDate(range.start_date);
      setEndDate(range.end_date);
      setParams(range);
    }
  };

  const applyRange = () => {
    if (!isDate(startDate) || !isDate(endDate)) {
      Alert.alert('Tanggal tidak valid', 'Gunakan format tanggal YYYY-MM-DD.');
      return;
    }

    if (endDate < startDate) {
      Alert.alert('Tanggal tidak valid', 'Tanggal selesai tidak boleh lebih awal dari tanggal mulai.');
      return;
    }

    setParams({ start_date: startDate, end_date: endDate });
  };

  const applySemester = () => {
    if (year < 2000 || year > 2100) {
      Alert.alert('Tahun tidak valid', 'Masukkan tahun antara 2000 sampai 2100.');
      return;
    }

    const range = semesterRange(year, semester);
    setStartDate(range.start_date);
    setEndDate(range.end_date);
    setParams(range);
  };

  const applyYear = () => {
    if (year < 2000 || year > 2100) {
      Alert.alert('Tahun tidak valid', 'Masukkan tahun antara 2000 sampai 2100.');
      return;
    }

    const range = yearRange(year);
    setStartDate(range.start_date);
    setEndDate(range.end_date);
    setParams(range);
  };

  const download = async (format: 'pdf' | 'excel') => {
    setDownloading(format);
    try {
      const response = await reportApi.downloadUrl({ ...params, format });
      await Linking.openURL(response.data.url);
    } catch (error) {
      Alert.alert('Gagal mengunduh', apiMessage(error));
    } finally {
      setDownloading(null);
    }
  };

  const totals = report
    ? NUMERIC_KEYS.reduce<Record<string, number>>((carry, key) => {
        carry[key] = report.rows.reduce((sum, row) => sum + Number(row[key] ?? 0), 0);
        return carry;
      }, {})
    : {};

  const totalPresent = Number(totals.present_days ?? 0);
  const totalNormal = Number(totals.normal_work_days ?? 0);
  const percentage = totalNormal > 0 ? Math.round((totalPresent / totalNormal) * 1000) / 10 : 0;
  const grade = gradeFor(percentage);

  const totalValue = (column: TableColumn) => {
    if (column.key === 'employee_id') return 'TOTAL';
    if (column.key === 'name' || column.key === 'keterangan' || column.key === 'attendance_grade') return '';
    if (column.key === 'attendance_percentage') return `${percentage}%`;
    return String(totals[column.key] ?? '');
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
      <Text style={styles.title}>Laporan Kehadiran</Text>
      <Text style={styles.muted}>Pilih filter bulan, rentang tanggal, semester, atau tahun. Tabel tampil langsung di bawah filter.</Text>

      <Card style={{ gap: spacing.md }}>
        <View style={styles.filterRow}>
          {([
            ['month', 'Per Bulan'],
            ['range', 'Antar Tanggal'],
            ['semester', 'Per Semester'],
            ['year', 'Per Tahun'],
          ] as [FilterType, string][]).map(([value, label]) => (
            <TouchableOpacity
              key={value}
              style={[styles.filterButton, filterType === value && styles.filterButtonActive]}
              onPress={() => changeFilterType(value)}
            >
              <Text style={[styles.filterText, filterType === value && styles.filterTextActive]}>{label}</Text>
            </TouchableOpacity>
          ))}
        </View>

        {filterType === 'month' ? (
          <View style={styles.monthRow}>
            <TouchableOpacity style={styles.monthButton} onPress={() => applyMonth(-1)}>
              <Ionicons name="chevron-back" size={18} color={colors.text} />
            </TouchableOpacity>
            <View style={{ alignItems: 'center' }}>
              <Text style={styles.monthLabel}>
                {MONTHS[monthNumber - 1]} {monthYear}
              </Text>
              <Text style={styles.muted}>
                {params.start_date} s.d. {params.end_date}
              </Text>
            </View>
            <TouchableOpacity style={styles.monthButton} onPress={() => applyMonth(1)}>
              <Ionicons name="chevron-forward" size={18} color={colors.text} />
            </TouchableOpacity>
          </View>
        ) : null}

        {filterType === 'range' ? (
          <View style={{ gap: spacing.sm }}>
            <View style={styles.dateRow}>
              <View style={{ flex: 1, gap: 4 }}>
                <Text style={styles.label}>Tanggal Mulai</Text>
                <TextInput
                  value={startDate}
                  onChangeText={setStartDate}
                  placeholder="YYYY-MM-DD"
                  placeholderTextColor={colors.subtle}
                  autoCapitalize="none"
                  style={styles.input}
                />
              </View>
              <View style={{ flex: 1, gap: 4 }}>
                <Text style={styles.label}>Tanggal Selesai</Text>
                <TextInput
                  value={endDate}
                  onChangeText={setEndDate}
                  placeholder="YYYY-MM-DD"
                  placeholderTextColor={colors.subtle}
                  autoCapitalize="none"
                  style={styles.input}
                />
              </View>
            </View>

            <TouchableOpacity style={styles.applyButton} onPress={applyRange}>
              <Ionicons name="search" size={16} color="#fff" />
              <Text style={styles.applyText}>Tampilkan Laporan</Text>
            </TouchableOpacity>
          </View>
        ) : null}

        {filterType === 'semester' ? (
          <View style={{ gap: spacing.sm }}>
            <View style={styles.dateRow}>
              <View style={{ flex: 1, gap: 4 }}>
                <Text style={styles.label}>Tahun</Text>
                <TextInput
                  value={String(year)}
                  onChangeText={(value) => setYear(Number(value.replace(/[^0-9]/g, '')) || 0)}
                  keyboardType="numeric"
                  style={styles.input}
                />
              </View>
              <View style={{ flex: 1, gap: 4 }}>
                <Text style={styles.label}>Semester</Text>
                <View style={styles.semesterRow}>
                  {[1, 2].map((value) => (
                    <TouchableOpacity
                      key={value}
                      style={[styles.semesterChip, semester === value && styles.semesterChipActive]}
                      onPress={() => setSemester(value)}
                    >
                      <Text style={[styles.semesterText, semester === value && styles.semesterTextActive]}>
                        {value === 1 ? '1 (Jan-Jun)' : '2 (Jul-Des)'}
                      </Text>
                    </TouchableOpacity>
                  ))}
                </View>
              </View>
            </View>

            <TouchableOpacity style={styles.applyButton} onPress={applySemester}>
              <Ionicons name="search" size={16} color="#fff" />
              <Text style={styles.applyText}>Tampilkan Laporan</Text>
            </TouchableOpacity>
          </View>
        ) : null}

        {filterType === 'year' ? (
          <View style={{ gap: spacing.sm }}>
            <View style={{ gap: 4 }}>
              <Text style={styles.label}>Tahun</Text>
              <TextInput
                value={String(year)}
                onChangeText={(value) => setYear(Number(value.replace(/[^0-9]/g, '')) || 0)}
                keyboardType="numeric"
                style={styles.input}
              />
            </View>

            <TouchableOpacity style={styles.applyButton} onPress={applyYear}>
              <Ionicons name="search" size={16} color="#fff" />
              <Text style={styles.applyText}>Tampilkan Laporan</Text>
            </TouchableOpacity>
          </View>
        ) : null}

        <View style={styles.downloadRow}>
          <TouchableOpacity
            style={styles.downloadButton}
            onPress={() => download('pdf')}
            disabled={downloading !== null}
          >
            {downloading === 'pdf' ? (
              <ActivityIndicator size="small" color={colors.primary} />
            ) : (
              <Ionicons name="document-text-outline" size={16} color={colors.primary} />
            )}
            <Text style={styles.downloadText}>Unduh PDF</Text>
          </TouchableOpacity>
          <TouchableOpacity
            style={styles.downloadButton}
            onPress={() => download('excel')}
            disabled={downloading !== null}
          >
            {downloading === 'excel' ? (
              <ActivityIndicator size="small" color={colors.success} />
            ) : (
              <Ionicons name="grid-outline" size={16} color={colors.success} />
            )}
            <Text style={[styles.downloadText, { color: colors.success }]}>Unduh Excel</Text>
          </TouchableOpacity>
        </View>
      </Card>

      {loading ? (
        <View style={styles.loading}>
          <ActivityIndicator color={colors.primary} />
          <Text style={styles.muted}>Memuat laporan...</Text>
        </View>
      ) : null}

      {!loading && report ? (
        <Card style={{ padding: 0, overflow: 'hidden' }}>
          <View style={styles.tableHeader}>
            <View>
              <Text style={styles.tableTitle}>Tabel Laporan</Text>
              <Text style={styles.muted}>
                {report.start_date} s.d. {report.end_date} · {report.employee_count} karyawan · {report.normal_work_days} hari kerja
              </Text>
            </View>
          </View>

          {report.rows.length === 0 ? (
            <EmptyState message="Belum ada data karyawan." />
          ) : (
            <ScrollView horizontal showsHorizontalScrollIndicator>
              <View>
                <View style={styles.tableHeadRow}>
                  {TABLE_COLUMNS.map((column) => (
                    <View key={column.key} style={[styles.headCell, { width: column.width }]}>
                      <Text style={styles.headText}>{column.label}</Text>
                    </View>
                  ))}
                </View>

                {report.rows.map((row, index) => (
                  <View
                    key={`${row.employee_id}-${index}`}
                    style={[styles.tableRow, index % 2 === 1 && styles.tableRowAlt]}
                  >
                    {TABLE_COLUMNS.map((column) => {
                      let value: React.ReactNode = column.key === 'keterangan' ? '' : row[column.key];

                      if (column.key === 'attendance_percentage') {
                        value = `${row.attendance_percentage}%`;
                      }

                      if (column.key === 'attendance_grade') {
                        value = row.attendance_grade;
                      }

                      return (
                        <View key={column.key} style={[styles.cell, { width: column.width }]}>
                          <Text
                            style={[
                              styles.cellText,
                              (column.key === 'name' || column.key === 'employee_id') && styles.cellTextLeft,
                              column.color ? { color: column.color, fontWeight: '700' } : null,
                            ]}
                            numberOfLines={2}
                          >
                            {String(value ?? '')}
                          </Text>
                        </View>
                      );
                    })}
                  </View>
                ))}

                <View style={styles.totalRow}>
                  {TABLE_COLUMNS.map((column) => (
                    <View key={column.key} style={[styles.cell, styles.totalCell, { width: column.width }]}>
                      <Text style={[styles.cellText, styles.totalText]} numberOfLines={2}>
                        {totalValue(column)}
                      </Text>
                    </View>
                  ))}
                </View>
              </View>
            </ScrollView>
          )}

          <View style={styles.summaryBox}>
            <Text style={styles.summaryTitle}>Rekap Kehadiran</Text>
            <Text style={styles.summaryText}>
              Total Hari Hadir {totalPresent} / Hari Kerja {totalNormal} = {percentage}%
            </Text>
            <Text style={styles.summaryText}>
              Penilaian: <Text style={styles.summaryGrade}>{grade}</Text>
            </Text>
          </View>
        </Card>
      ) : null}
    </Screen>
  );
};

const styles = StyleSheet.create({
  title: { fontSize: 22, fontWeight: '800', color: colors.text },
  muted: { fontSize: 12, color: colors.muted },
  label: { fontSize: 11, fontWeight: '700', color: colors.muted },
  filterRow: { flexDirection: 'row', flexWrap: 'wrap', gap: spacing.sm },
  filterButton: {
    width: '48%',
    flexGrow: 1,
    borderWidth: 1,
    borderColor: colors.border,
    borderRadius: radius.md,
    paddingVertical: 9,
    alignItems: 'center',
    backgroundColor: '#fff',
  },
  filterButtonActive: { backgroundColor: colors.primary, borderColor: colors.primary },
  filterText: { fontSize: 12, fontWeight: '700', color: colors.muted },
  filterTextActive: { color: '#fff' },
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
  dateRow: { flexDirection: 'row', gap: spacing.sm },
  input: {
    borderRadius: radius.md,
    borderWidth: 1,
    borderColor: colors.border,
    backgroundColor: '#fff',
    paddingHorizontal: spacing.md,
    paddingVertical: 9,
    fontSize: 13,
    color: colors.text,
  },
  semesterRow: { flexDirection: 'row', gap: spacing.sm },
  semesterChip: {
    flex: 1,
    borderWidth: 1,
    borderColor: colors.border,
    borderRadius: radius.md,
    paddingVertical: 9,
    alignItems: 'center',
    backgroundColor: '#fff',
  },
  semesterChipActive: { backgroundColor: colors.primary, borderColor: colors.primary },
  semesterText: { fontSize: 11, fontWeight: '700', color: colors.muted },
  semesterTextActive: { color: '#fff' },
  applyButton: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 8,
    backgroundColor: colors.primary,
    borderRadius: radius.md,
    paddingVertical: 10,
  },
  applyText: { color: '#fff', fontWeight: '700', fontSize: 13 },
  downloadRow: { flexDirection: 'row', gap: spacing.sm },
  downloadButton: {
    flex: 1,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 6,
    borderWidth: 1,
    borderColor: colors.border,
    borderRadius: radius.md,
    paddingVertical: 10,
    backgroundColor: '#fff',
  },
  downloadText: { fontSize: 12, fontWeight: '700', color: colors.primary },
  loading: { flexDirection: 'row', alignItems: 'center', justifyContent: 'center', gap: spacing.sm, paddingVertical: spacing.md },
  tableHeader: { padding: spacing.md, borderBottomWidth: 1, borderBottomColor: colors.border },
  tableTitle: { fontSize: 15, fontWeight: '800', color: colors.text },
  tableHeadRow: { flexDirection: 'row', backgroundColor: '#44546A' },
  headCell: {
    paddingHorizontal: 6,
    paddingVertical: 8,
    borderRightWidth: 1,
    borderRightColor: '#94a3b8',
    justifyContent: 'center',
  },
  headText: { color: '#fff', fontSize: 10, fontWeight: '800', textAlign: 'center' },
  tableRow: { flexDirection: 'row', backgroundColor: '#fff' },
  tableRowAlt: { backgroundColor: '#f8fafc' },
  totalRow: { flexDirection: 'row', backgroundColor: '#e2e8f0' },
  totalCell: { borderTopWidth: 1, borderTopColor: '#94a3b8' },
  totalText: { fontWeight: '800', color: colors.text },
  cell: {
    paddingHorizontal: 6,
    paddingVertical: 6,
    borderRightWidth: 1,
    borderBottomWidth: 1,
    borderColor: colors.border,
    justifyContent: 'center',
  },
  cellText: { fontSize: 10, color: colors.text, textAlign: 'center' },
  cellTextLeft: { textAlign: 'left' },
  summaryBox: {
    margin: spacing.md,
    padding: spacing.md,
    borderRadius: radius.md,
    backgroundColor: colors.infoSoft,
    gap: 3,
  },
  summaryTitle: { fontSize: 12, fontWeight: '800', color: colors.info },
  summaryText: { fontSize: 12, color: colors.text },
  summaryGrade: { fontWeight: '800', color: colors.info },
});
