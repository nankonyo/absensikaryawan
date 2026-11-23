import React from 'react';
import { StyleSheet, Text, TouchableOpacity, View } from 'react-native';
import { useNavigation } from '@react-navigation/native';
import { Ionicons } from '@expo/vector-icons';
import { Screen } from '../components/Screen';
import { Card } from '../components/ui';
import { useAuth } from '../context/AuthContext';
import { colors, radius, spacing } from '../theme';

const adminGroups = [
  {
    title: 'Kepegawaian',
    items: [
      ['Pengguna', 'users'],
      ['Departemen', 'departments'],
      ['Unit', 'units'],
      ['Jabatan', 'positions'],
      ['Cabang', 'branches'],
      ['Atribut Pengguna', 'user-attributes'],
    ],
  },
  {
    title: 'Presensi & Cuti',
    items: [
      ['Presensi', 'attendances'],
      ['Verifikasi Presensi', 'attendance-verifications'],
      ['Cuti', 'leaves'],
      ['Jenis Cuti', 'leave-types'],
      ['Kalender Hari Libur', 'holidays'],
      ['Alasan Kehadiran', 'attendance-reasons'],
      ['Lokasi Kantor', 'office-locations'],
    ],
  },
  {
    title: 'Internal App',
    items: [
      ['Video Tutorial', 'video-tutorials'],
      ['Dokumentasi', 'dokumentasi'],
      ['Pengumuman', 'pengumuman'],
      ['Struktur Organisasi', 'organization'],
      ['Role', 'roles'],
      ['Hak Akses', 'access'],
      ['Server & Domain', 'servers'],
      ['Manage Data', 'manage-data'],
      ['Pengiriman Email', 'email-logs'],
    ],
  },
  {
    title: 'Laporan & Sistem',
    items: [
      ['Laporan', 'reports'],
      ['Audit Log', 'audit-logs'],
      ['Pengaturan', 'settings'],
    ],
  },
];

const staffItems: [string, string][] = [
  ['Profil Saya', 'Profile'],
  ['Presensi Saya', 'Attendance'],
  ['Cuti Saya', 'Leave'],
  ['Kalender Hari Libur', 'Holiday'],
  ['Project & Aktivitas', 'InternalTab'],
];

export const MoreScreen = () => {
  const navigation = useNavigation<any>();
  const { user, logout } = useAuth();
  const isAdmin = Boolean(user?.is_admin);

  const openResource = (label: string, resource: string) => {
    if (resource === 'holidays') {
      navigation.navigate('Holiday');
      return;
    }

    if (resource === 'reports') {
      navigation.navigate('Report');
      return;
    }

    navigation.navigate('AdminResource', { resource, title: label });
  };

  return (
    <Screen>
      <View>
        <Text style={styles.title}>{isAdmin ? 'Administrasi' : 'Lainnya'}</Text>
        <Text style={styles.muted}>
          {isAdmin ? 'Kelola semua data aplikasi langsung dari mobile.' : 'Akses cepat fitur aplikasi.'}
        </Text>
      </View>

      {isAdmin
        ? adminGroups.map((group) => (
            <View key={group.title} style={{ gap: spacing.sm }}>
              <Text style={styles.groupTitle}>{group.title}</Text>
              <Card style={{ padding: 0, overflow: 'hidden' }}>
                {group.items.map(([label, resource], index) => (
                  <TouchableOpacity
                    key={resource}
                    style={[styles.item, index > 0 && styles.itemBorder]}
                    onPress={() => openResource(label, resource)}
                  >
                    <Text style={styles.itemLabel}>{label}</Text>
                    <Ionicons name="chevron-forward" size={16} color={colors.subtle} />
                  </TouchableOpacity>
                ))}
              </Card>
            </View>
          ))
        : (
            <Card style={{ padding: 0, overflow: 'hidden' }}>
              {staffItems.map(([label, tab], index) => (
                <TouchableOpacity
                  key={tab}
                  style={[styles.item, index > 0 && styles.itemBorder]}
                  onPress={() =>
                    tab === 'Holiday'
                      ? navigation.navigate('Holiday')
                      : navigation.getParent()?.navigate(tab)
                  }
                >
                  <Text style={styles.itemLabel}>{label}</Text>
                  <Ionicons name="chevron-forward" size={16} color={colors.subtle} />
                </TouchableOpacity>
              ))}
            </Card>
          )}

      <TouchableOpacity style={styles.logout} onPress={async () => { await logout(); }}>
        <Ionicons name="log-out" size={18} color={colors.danger} />
        <Text style={styles.logoutText}>Keluar</Text>
      </TouchableOpacity>
    </Screen>
  );
};

const styles = StyleSheet.create({
  title: { fontSize: 22, fontWeight: '800', color: colors.text },
  muted: { fontSize: 12, color: colors.muted },
  groupTitle: { fontSize: 12, fontWeight: '800', color: colors.muted, textTransform: 'uppercase', letterSpacing: 0.5 },
  item: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: spacing.md,
    paddingVertical: 12,
  },
  itemBorder: { borderTopWidth: 1, borderTopColor: colors.border },
  itemLabel: { fontSize: 13, color: colors.text, fontWeight: '600' },
  logout: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 8,
    borderWidth: 1,
    borderColor: colors.dangerSoft,
    backgroundColor: colors.dangerSoft,
    borderRadius: radius.md,
    paddingVertical: 12,
    marginTop: spacing.md,
  },
  logoutText: { color: colors.danger, fontWeight: '700' },
});
