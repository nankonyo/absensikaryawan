import React from 'react';
import { Alert, Linking, StyleSheet, Text, TouchableOpacity, View } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { Screen } from '../components/Screen';
import { Card } from '../components/ui';
import { API_URL } from '../api/client';
import { useAuth } from '../context/AuthContext';
import { colors, radius, spacing } from '../theme';

const WEB_URL = API_URL.replace(/\/api\/mobile\/?$/, '');

const groups = [
  {
    title: 'Kepegawaian',
    items: [
      ['Pengguna', '/admin/users'],
      ['Departemen', '/admin/departments'],
      ['Unit', '/admin/units'],
      ['Jabatan', '/admin/positions'],
      ['Cabang', '/admin/branches'],
      ['Atribut Pengguna', '/admin/user-attributes'],
    ],
  },
  {
    title: 'Presensi & Cuti',
    items: [
      ['Presensi', '/admin/attendances'],
      ['Verifikasi Presensi', '/admin/attendance-verifications'],
      ['Cuti', '/admin/leaves'],
      ['Jenis Cuti', '/admin/leave-types'],
      ['Alasan Kehadiran', '/admin/attendance-reasons'],
      ['Lokasi Kantor', '/admin/office-locations'],
    ],
  },
  {
    title: 'Internal App',
    items: [
      ['Video Tutorial', '/internal/video-tutorials'],
      ['Dokumentasi', '/internal/dokumentasi'],
      ['Pengumuman', '/internal/pengumuman'],
      ['Struktur Organisasi', '/internal/organization'],
      ['Role', '/internal/roles'],
      ['Hak Akses', '/internal/access'],
      ['Server & Domain', '/internal/servers'],
      ['Manage Data', '/internal/manage-data'],
      ['Pengiriman Email', '/internal/email-logs'],
    ],
  },
  {
    title: 'Laporan & Sistem',
    items: [
      ['Laporan', '/admin/reports'],
      ['Audit Log', '/admin/audit-logs'],
      ['Pengaturan', '/admin/settings'],
    ],
  },
];

export const MoreScreen = () => {
  const { user, logout } = useAuth();

  const open = (path: string) => {
    Linking.openURL(`${WEB_URL}${path}`).catch(() => Alert.alert('Gagal membuka halaman web'));
  };

  if (!user?.is_admin) {
    return (
      <Screen>
        <Card>
          <Text style={styles.title}>Menu Lainnya</Text>
          <Text style={styles.muted}>Menu administrasi hanya tersedia untuk admin/direksi.</Text>
        </Card>
        <TouchableOpacity style={styles.logout} onPress={logout}>
          <Ionicons name="log-out" size={18} color={colors.danger} />
          <Text style={styles.logoutText}>Keluar</Text>
        </TouchableOpacity>
      </Screen>
    );
  }

  return (
    <Screen>
      <View>
        <Text style={styles.title}>Administrasi</Text>
        <Text style={styles.muted}>Menu lengkap membuka versi web agar fitur tetap lengkap.</Text>
      </View>

      {groups.map((group) => (
        <View key={group.title} style={{ gap: spacing.sm }}>
          <Text style={styles.groupTitle}>{group.title}</Text>
          <Card style={{ padding: 0, overflow: 'hidden' }}>
            {group.items.map(([label, path], index) => (
              <TouchableOpacity key={path} style={[styles.item, index > 0 && styles.itemBorder]} onPress={() => open(path)}>
                <Text style={styles.itemLabel}>{label}</Text>
                <Ionicons name="open-outline" size={16} color={colors.subtle} />
              </TouchableOpacity>
            ))}
          </Card>
        </View>
      ))}

      <TouchableOpacity style={styles.logout} onPress={logout}>
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
  item: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', paddingHorizontal: spacing.md, paddingVertical: 12 },
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
