import React, { useCallback, useState } from 'react';
import { ActivityIndicator, Alert, StyleSheet, Text, TouchableOpacity, View } from 'react-native';
import { useFocusEffect, useNavigation } from '@react-navigation/native';
import { Ionicons } from '@expo/vector-icons';
import { Screen } from '../../components/Screen';
import { Badge, Card, EmptyState } from '../../components/ui';
import { holidayApi } from '../../api/endpoints';
import { apiMessage } from '../../api/client';
import { useAuth } from '../../context/AuthContext';
import type { Holiday } from '../../types';
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

const WEEKDAYS = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'];

const typeColor = (type: string) => {
  if (type === 'company') return { bg: '#fef3c7', text: '#b45309' };
  if (type === 'collective') return { bg: '#dbeafe', text: '#1d4ed8' };
  return { bg: '#fee2e2', text: '#b91c1c' };
};

const monthKey = (date: Date) => `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`;

const dateKey = (year: number, month: number, day: number) =>
  `${year}-${String(month).padStart(2, '0')}-${String(day).padStart(2, '0')}`;

export const HolidayScreen = () => {
  const navigation = useNavigation<any>();
  const { user } = useAuth();
  const [month, setMonth] = useState(monthKey(new Date()));
  const [holidays, setHolidays] = useState<Holiday[]>([]);
  const [loading, setLoading] = useState(true);

  const [year, monthNumber] = month.split('-').map(Number);

  const load = useCallback(async () => {
    setLoading(true);
    try {
      const response = await holidayApi.index({ month });
      setHolidays(response.data.holidays);
    } catch (error) {
      Alert.alert('Gagal memuat', apiMessage(error));
    } finally {
      setLoading(false);
    }
  }, [month]);

  useFocusEffect(
    useCallback(() => {
      load();
    }, [load]),
  );

  const shiftMonth = (offset: number) => {
    const next = new Date(year, monthNumber - 1 + offset, 1);
    setMonth(monthKey(next));
  };

  const firstDate = new Date(year, monthNumber - 1, 1);
  const startOffset = (firstDate.getDay() + 6) % 7;
  const daysInMonth = new Date(year, monthNumber, 0).getDate();

  const cells: (string | null)[] = Array.from({ length: startOffset }, () => null);
  for (let day = 1; day <= daysInMonth; day++) {
    cells.push(dateKey(year, monthNumber, day));
  }
  while (cells.length % 7 !== 0) {
    cells.push(null);
  }

  const holidayByDate = holidays.reduce<Record<string, Holiday>>((carry, holiday) => {
    carry[holiday.date] = holiday;
    return carry;
  }, {});

  return (
    <Screen back>
      <View style={styles.header}>
        <View style={{ flex: 1 }}>
          <Text style={styles.title}>Kalender Hari Libur</Text>
          <Text style={styles.muted}>Libur nasional, cuti bersama, dan libur perusahaan.</Text>
        </View>
        {user?.is_admin ? (
          <TouchableOpacity
            style={styles.manage}
            onPress={() => navigation.navigate('AdminResource', { resource: 'holidays', title: 'Hari Libur' })}
          >
            <Ionicons name="create-outline" size={16} color={colors.primary} />
            <Text style={styles.manageText}>Kelola</Text>
          </TouchableOpacity>
        ) : null}
      </View>

      <Card style={{ gap: spacing.md }}>
        <View style={styles.monthRow}>
          <TouchableOpacity style={styles.monthButton} onPress={() => shiftMonth(-1)}>
            <Ionicons name="chevron-back" size={18} color={colors.text} />
          </TouchableOpacity>
          <Text style={styles.monthLabel}>
            {MONTHS[monthNumber - 1]} {year}
          </Text>
          <TouchableOpacity style={styles.monthButton} onPress={() => shiftMonth(1)}>
            <Ionicons name="chevron-forward" size={18} color={colors.text} />
          </TouchableOpacity>
        </View>

        <View style={styles.weekRow}>
          {WEEKDAYS.map((weekday) => (
            <Text key={weekday} style={styles.weekday}>
              {weekday}
            </Text>
          ))}
        </View>

        {loading ? <ActivityIndicator color={colors.primary} /> : null}

        <View style={styles.grid}>
          {cells.map((date, index) => {
            if (!date) {
              return <View key={`empty-${index}`} style={styles.cell} />;
            }

            const holiday = holidayByDate[date];
            const palette = holiday ? typeColor(holiday.type) : null;
            const isSunday = index % 7 === 6;

            return (
              <View key={date} style={styles.cell}>
                <View
                  style={[
                    styles.day,
                    holiday && { backgroundColor: palette?.bg, borderColor: palette?.bg },
                  ]}
                >
                  <Text
                    style={[
                      styles.dayNumber,
                      isSunday && styles.sunday,
                      holiday && { color: palette?.text, fontWeight: '800' },
                    ]}
                  >
                    {Number(date.slice(-2))}
                  </Text>
                  {holiday ? <View style={[styles.dot, { backgroundColor: palette?.text }]} /> : null}
                </View>
              </View>
            );
          })}
        </View>

        <View style={styles.legend}>
          {[
            ['national', 'Libur Nasional'],
            ['company', 'Libur Perusahaan'],
            ['collective', 'Cuti Bersama'],
          ].map(([type, label]) => {
            const palette = typeColor(type);
            return (
              <View key={type} style={styles.legendItem}>
                <View style={[styles.legendDot, { backgroundColor: palette.text }]} />
                <Text style={styles.legendText}>{label}</Text>
              </View>
            );
          })}
        </View>
      </Card>

      <Text style={styles.sectionTitle}>Hari Libur Bulan Ini</Text>

      {!loading && holidays.length === 0 ? <EmptyState message="Belum ada hari libur pada bulan ini." /> : null}

      {holidays.map((holiday) => {
        const palette = typeColor(holiday.type);
        return (
          <Card key={holiday.id} style={styles.holidayCard}>
            <View style={[styles.dateBox, { backgroundColor: palette.bg }]}>
              <Text style={[styles.dateDay, { color: palette.text }]}>{Number(holiday.date.slice(-2))}</Text>
              <Text style={[styles.dateMonth, { color: palette.text }]}>
                {MONTHS[Number(holiday.date.slice(5, 7)) - 1]?.slice(0, 3)}
              </Text>
            </View>
            <View style={{ flex: 1 }}>
              <Text style={styles.holidayName}>{holiday.name}</Text>
              <Text style={styles.muted}>{holiday.date}</Text>
            </View>
            <Badge label={holiday.type_label ?? holiday.type} bg={palette.bg} color={palette.text} />
          </Card>
        );
      })}
    </Screen>
  );
};

const styles = StyleSheet.create({
  header: { flexDirection: 'row', alignItems: 'center', gap: spacing.sm },
  title: { fontSize: 22, fontWeight: '800', color: colors.text },
  muted: { fontSize: 12, color: colors.muted },
  manage: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
    borderWidth: 1,
    borderColor: colors.primarySoft,
    backgroundColor: colors.primarySoft,
    borderRadius: radius.md,
    paddingHorizontal: 10,
    paddingVertical: 8,
  },
  manageText: { fontSize: 11, fontWeight: '700', color: colors.primary },
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
  weekRow: { flexDirection: 'row' },
  weekday: {
    width: '14.2857%',
    textAlign: 'center',
    fontSize: 10,
    fontWeight: '800',
    color: colors.muted,
    textTransform: 'uppercase',
  },
  grid: { flexDirection: 'row', flexWrap: 'wrap' },
  cell: { width: '14.2857%', padding: 2 },
  day: {
    minHeight: 42,
    borderRadius: radius.md,
    borderWidth: 1,
    borderColor: colors.border,
    backgroundColor: '#fff',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 3,
  },
  dayNumber: { fontSize: 12, fontWeight: '600', color: colors.text },
  sunday: { color: colors.danger },
  dot: { width: 5, height: 5, borderRadius: 999 },
  legend: { flexDirection: 'row', flexWrap: 'wrap', gap: spacing.md },
  legendItem: { flexDirection: 'row', alignItems: 'center', gap: 5 },
  legendDot: { width: 8, height: 8, borderRadius: 3 },
  legendText: { fontSize: 10, color: colors.muted },
  sectionTitle: { fontSize: 16, fontWeight: '700', color: colors.text },
  holidayCard: { flexDirection: 'row', alignItems: 'center', gap: spacing.md, padding: spacing.md },
  dateBox: { width: 46, height: 46, borderRadius: radius.md, alignItems: 'center', justifyContent: 'center' },
  dateDay: { fontSize: 16, fontWeight: '800' },
  dateMonth: { fontSize: 9, fontWeight: '700', textTransform: 'uppercase' },
  holidayName: { fontSize: 13, fontWeight: '700', color: colors.text },
});
