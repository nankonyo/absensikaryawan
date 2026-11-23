import React, { useCallback, useState } from 'react';
import { ActivityIndicator, Image, RefreshControl, StyleSheet, Text, TouchableOpacity, View } from 'react-native';
import { useFocusEffect, useNavigation } from '@react-navigation/native';
import { Ionicons } from '@expo/vector-icons';
import { Screen } from '../components/Screen';
import { Badge, Card, EmptyState, SectionTitle } from '../components/ui';
import { dashboardApi } from '../api/endpoints';
import { apiMessage } from '../api/client';
import { useAuth } from '../context/AuthContext';
import type { ContentItem, Project } from '../types';
import { colors, radius, spacing, statusColor } from '../theme';

type DashboardData = Awaited<ReturnType<typeof dashboardApi.index>>['data'];

export const DashboardScreen = () => {
  const navigation = useNavigation<any>();
  const { user } = useAuth();
  const [data, setData] = useState<DashboardData | null>(null);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [failedAvatarUri, setFailedAvatarUri] = useState<string | null>(null);

  const load = useCallback(async () => {
    try {
      const response = await dashboardApi.index();
      setData(response.data);
      setError(null);
    } catch (err) {
      setError(apiMessage(err));
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  }, []);

  useFocusEffect(
    useCallback(() => {
      load();
    }, [load]),
  );

  const onRefresh = () => {
    setRefreshing(true);
    load();
  };

  const quickMenu = [
    { key: 'Attendance', label: 'Presensi', icon: 'finger-print', color: colors.primary },
    { key: 'Leave', label: 'Cuti & Izin', icon: 'calendar', color: '#059669' },
    { key: 'InternalTab', label: 'Internal App', icon: 'apps', color: '#7c3aed' },
    { key: 'Profile', label: 'Profil', icon: 'person', color: '#f59e0b' },
  ];

  if (user?.is_atasan || user?.is_admin) {
    quickMenu.push({ key: 'Team', label: 'Tim Saya', icon: 'people', color: '#0ea5e9' });
    quickMenu.push({ key: 'Approval', label: 'Approval', icon: 'checkmark-done', color: '#dc2626' });
  }

  if (loading) {
    return (
      <Screen>
        <View style={styles.loading}>
          <ActivityIndicator color={colors.primary} />
          <Text style={styles.muted}>Memuat dashboard...</Text>
        </View>
      </Screen>
    );
  }

  const attendance = data?.stats.today_attendance;
  const attendanceStatus = attendance?.check_in
    ? attendance?.check_out
      ? 'Selesai'
      : 'Sudah Check-in'
    : 'Belum Check-in';

  return (
    <Screen>
      <View style={styles.header}>
        <View>
          <Text style={styles.hello}>Halo,</Text>
          <Text style={styles.name}>{user?.name}</Text>
          <Text style={styles.role}>
            {user?.role.display_name ?? user?.role.name}
            {user?.unit ? ` · ${user.unit}` : ''}
          </Text>
        </View>
        {user?.avatar_url && failedAvatarUri !== user.avatar_url ? (
          <Image
            source={{ uri: user.avatar_url }}
            style={styles.avatarImage}
            onError={() => setFailedAvatarUri(user.avatar_url ?? null)}
          />
        ) : (
          <View style={styles.avatar}>
            <Text style={styles.avatarText}>{(user?.name ?? '?').slice(0, 1).toUpperCase()}</Text>
          </View>
        )}
      </View>

      {error ? (
        <Card style={{ borderColor: colors.dangerSoft }}>
          <Text style={{ color: colors.danger }}>{error}</Text>
        </Card>
      ) : null}

      <Card style={styles.attendanceCard}>
        <View style={styles.rowBetween}>
          <View>
            <Text style={styles.muted}>Presensi hari ini</Text>
            <Text style={styles.bigStatus}>{attendanceStatus}</Text>
            <Text style={styles.muted}>
              {attendance?.check_in ? `Masuk ${String(attendance.check_in).slice(0, 5)}` : 'Silakan check-in'}
              {attendance?.check_out ? ` · Pulang ${String(attendance.check_out).slice(0, 5)}` : ''}
            </Text>
          </View>
          <Badge
            label={attendance?.status ? attendance.status.toUpperCase() : 'BELUM'}
            bg={statusColor(attendance?.status).bg}
            color={statusColor(attendance?.status).text}
          />
        </View>
        <TouchableOpacity style={styles.cta} onPress={() => navigation.navigate('Attendance')}>
          <Ionicons name="finger-print" size={18} color="#fff" />
          <Text style={styles.ctaText}>Buka Presensi</Text>
        </TouchableOpacity>
      </Card>

      <View style={styles.statGrid}>
        <Stat label="Cuti Pending" value={data?.stats.my_pending_leaves ?? 0} icon="time" color="#f59e0b" />
        <Stat label="Project" value={data?.stats.projects ?? 0} icon="briefcase" color={colors.primary} />
        <Stat label="Aktivitas Bulan Ini" value={data?.stats.my_activities_this_month ?? 0} icon="pulse" color="#7c3aed" />
        {(user?.is_atasan || user?.is_admin) && (
          <Stat label="Menunggu Approval" value={data?.stats.pending_approvals ?? 0} icon="checkmark-done" color="#dc2626" />
        )}
      </View>

      <SectionTitle title="Menu Cepat" />
      <View style={styles.menuGrid}>
        {quickMenu.map((item) => (
          <TouchableOpacity key={item.key} style={styles.menuItem} onPress={() => navigation.navigate(item.key)}>
            <View style={[styles.menuIcon, { backgroundColor: `${item.color}1a` }]}>
              <Ionicons name={item.icon as any} size={20} color={item.color} />
            </View>
            <Text style={styles.menuLabel}>{item.label}</Text>
          </TouchableOpacity>
        ))}
      </View>

      <SectionTitle title="Pengumuman" />
      {data?.announcements?.length ? (
        data.announcements.map((announcement: ContentItem) => (
          <Card key={announcement.id} style={styles.announcement}>
            <Text style={styles.announcementTitle}>{announcement.title}</Text>
            {announcement.content ? (
              <Text style={styles.announcementBody} numberOfLines={3}>
                {announcement.content.replace(/<[^>]+>/g, '')}
              </Text>
            ) : null}
          </Card>
        ))
      ) : (
        <EmptyState message="Belum ada pengumuman." />
      )}

      <SectionTitle title="Project Terbaru" />
      {data?.projects?.length ? (
        data.projects.map((project: Project) => (
          <TouchableOpacity
            key={project.id}
            style={styles.projectItem}
            onPress={() => navigation.navigate('InternalTab', { screen: 'ProjectDetail', params: { id: project.id } })}
          >
            <View style={{ flex: 1 }}>
              <Text style={styles.projectName}>{project.name}</Text>
              <Text style={styles.muted}>{project.status.replace(/_/g, ' ')}</Text>
              <View style={styles.progressTrack}>
                <View style={[styles.progressFill, { width: `${Math.min(100, project.progress)}%` }]} />
              </View>
            </View>
            <Text style={styles.progressText}>{project.progress}%</Text>
          </TouchableOpacity>
        ))
      ) : (
        <EmptyState message="Belum ada project." />
      )}
    </Screen>
  );
};

const Stat = ({ label, value, icon, color }: { label: string; value: number; icon: string; color: string }) => (
  <View style={styles.statCard}>
    <View style={[styles.statIcon, { backgroundColor: `${color}1a` }]}>
      <Ionicons name={icon as any} size={18} color={color} />
    </View>
    <Text style={styles.statValue}>{value}</Text>
    <Text style={styles.statLabel}>{label}</Text>
  </View>
);

const styles = StyleSheet.create({
  loading: { flex: 1, alignItems: 'center', justifyContent: 'center', gap: 8, paddingVertical: 80 },
  muted: { fontSize: 12, color: colors.muted },
  header: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  hello: { fontSize: 13, color: colors.muted },
  name: { fontSize: 22, fontWeight: '800', color: colors.text },
  role: { fontSize: 12, color: colors.muted },
  avatar: {
    width: 48,
    height: 48,
    borderRadius: 16,
    backgroundColor: colors.primarySoft,
    alignItems: 'center',
    justifyContent: 'center',
  },
  avatarImage: {
    width: 48,
    height: 48,
    borderRadius: 16,
    backgroundColor: colors.primarySoft,
  },
  avatarText: { fontSize: 18, fontWeight: '800', color: colors.primary },
  attendanceCard: { gap: spacing.md },
  rowBetween: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'flex-start', gap: spacing.md },
  bigStatus: { fontSize: 20, fontWeight: '800', color: colors.text, marginVertical: 2 },
  cta: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 8,
    backgroundColor: colors.primary,
    borderRadius: radius.md,
    paddingVertical: 12,
  },
  ctaText: { color: '#fff', fontWeight: '700' },
  statGrid: { flexDirection: 'row', flexWrap: 'wrap', gap: spacing.sm },
  statCard: {
    width: '48%',
    backgroundColor: '#fff',
    borderWidth: 1,
    borderColor: colors.border,
    borderRadius: radius.lg,
    padding: spacing.md,
    gap: 4,
  },
  statIcon: { width: 32, height: 32, borderRadius: 10, alignItems: 'center', justifyContent: 'center' },
  statValue: { fontSize: 22, fontWeight: '800', color: colors.text },
  statLabel: { fontSize: 11, color: colors.muted },
  menuGrid: { flexDirection: 'row', flexWrap: 'wrap', gap: spacing.sm },
  menuItem: {
    width: '31%',
    backgroundColor: '#fff',
    borderWidth: 1,
    borderColor: colors.border,
    borderRadius: radius.lg,
    padding: spacing.md,
    alignItems: 'center',
    gap: 6,
  },
  menuIcon: { width: 36, height: 36, borderRadius: 12, alignItems: 'center', justifyContent: 'center' },
  menuLabel: { fontSize: 11, fontWeight: '600', color: colors.text, textAlign: 'center' },
  announcement: { gap: 4 },
  announcementTitle: { fontWeight: '700', color: colors.text },
  announcementBody: { fontSize: 12, color: colors.muted },
  projectItem: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
    backgroundColor: '#fff',
    borderWidth: 1,
    borderColor: colors.border,
    borderRadius: radius.lg,
    padding: spacing.md,
  },
  projectName: { fontWeight: '700', color: colors.text },
  progressTrack: { height: 6, backgroundColor: '#eef2f7', borderRadius: 999, marginTop: 6, overflow: 'hidden' },
  progressFill: { height: 6, backgroundColor: colors.primary, borderRadius: 999 },
  progressText: { fontWeight: '800', color: colors.primary },
});
