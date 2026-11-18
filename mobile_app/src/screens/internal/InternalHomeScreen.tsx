import React from 'react';
import { StyleSheet, Text, TouchableOpacity, View } from 'react-native';
import { useNavigation } from '@react-navigation/native';
import { Ionicons } from '@expo/vector-icons';
import { Screen } from '../../components/Screen';
import { Card } from '../../components/ui';
import { useAuth } from '../../context/AuthContext';
import { colors, radius, spacing } from '../../theme';

const menus = [
  { key: 'Projects', label: 'Project Management', desc: 'Daftar project dan progres', icon: 'briefcase', color: colors.primary },
  { key: 'Kanban', label: 'Kanban Board', desc: 'Task per status', icon: 'grid', color: '#7c3aed' },
  { key: 'Gantt', label: 'Gantt Chart', desc: 'Timeline task project', icon: 'bar-chart', color: '#0ea5e9' },
  { key: 'Activities', label: 'Aktivitas', desc: 'Log aktivitas project', icon: 'pulse', color: '#059669' },
  { key: 'Contents', label: 'Konten', desc: 'Video, dokumentasi, pengumuman', icon: 'play-circle', color: '#f59e0b' },
];

export const InternalHomeScreen = () => {
  const navigation = useNavigation<any>();
  const { user } = useAuth();

  return (
    <Screen>
      <View>
        <Text style={styles.title}>Internal App</Text>
        <Text style={styles.muted}>Project, aktivitas, dan konten internal.</Text>
      </View>

      <Card style={styles.hero}>
        <View style={{ flex: 1 }}>
          <Text style={styles.heroTitle}>{user?.role.display_name ?? user?.role.name}</Text>
          <Text style={styles.muted}>
            {user?.department ?? 'Tanpa departemen'} {user?.unit ? `· ${user.unit}` : ''}
          </Text>
        </View>
        <Ionicons name="apps" size={28} color={colors.primary} />
      </Card>

      {menus.map((menu) => (
        <TouchableOpacity key={menu.key} style={styles.menu} onPress={() => navigation.navigate(menu.key)}>
          <View style={[styles.icon, { backgroundColor: `${menu.color}1a` }]}>
            <Ionicons name={menu.icon as any} size={22} color={menu.color} />
          </View>
          <View style={{ flex: 1 }}>
            <Text style={styles.menuLabel}>{menu.label}</Text>
            <Text style={styles.muted}>{menu.desc}</Text>
          </View>
          <Ionicons name="chevron-forward" size={18} color={colors.subtle} />
        </TouchableOpacity>
      ))}
    </Screen>
  );
};

const styles = StyleSheet.create({
  title: { fontSize: 22, fontWeight: '800', color: colors.text },
  muted: { fontSize: 12, color: colors.muted },
  hero: { flexDirection: 'row', alignItems: 'center', gap: spacing.md },
  heroTitle: { fontSize: 16, fontWeight: '800', color: colors.text },
  menu: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
    backgroundColor: '#fff',
    borderWidth: 1,
    borderColor: colors.border,
    borderRadius: radius.lg,
    padding: spacing.md,
  },
  icon: { width: 44, height: 44, borderRadius: 14, alignItems: 'center', justifyContent: 'center' },
  menuLabel: { fontWeight: '700', color: colors.text, marginBottom: 2 },
});
