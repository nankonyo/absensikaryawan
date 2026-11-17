import React, { useCallback, useEffect, useState } from 'react';
import { ActivityIndicator, Alert, StyleSheet, Text, TouchableOpacity, View } from 'react-native';
import { useNavigation } from '@react-navigation/native';
import { Ionicons } from '@expo/vector-icons';
import { Screen } from '../../components/Screen';
import { Card, EmptyState } from '../../components/ui';
import { Field } from '../../components/Field';
import { projectApi } from '../../api/endpoints';
import { apiMessage } from '../../api/client';
import type { Project } from '../../types';
import { colors, radius, spacing } from '../../theme';

export const ProjectsScreen = () => {
  const navigation = useNavigation<any>();
  const [projects, setProjects] = useState<Project[]>([]);
  const [search, setSearch] = useState('');
  const [loading, setLoading] = useState(true);

  const load = useCallback(async (q?: string) => {
    try {
      const response = await projectApi.index({ q: q || undefined });
      setProjects(response.data.data);
    } catch (err) {
      Alert.alert('Gagal memuat', apiMessage(err));
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    load();
  }, [load]);

  return (
    <Screen back>
      <View>
        <Text style={styles.title}>Project</Text>
        <Text style={styles.muted}>Daftar project dan progres terbaru.</Text>
      </View>

      <View style={styles.searchRow}>
        <View style={styles.searchBox}>
          <Ionicons name="search" size={16} color={colors.subtle} />
          <Field
            value={search}
            onChangeText={setSearch}
            placeholder="Cari project..."
            style={styles.searchInput}
            onSubmitEditing={() => load(search)}
            returnKeyType="search"
          />
        </View>
        <TouchableOpacity style={styles.searchButton} onPress={() => load(search)}>
          <Text style={styles.searchButtonText}>Cari</Text>
        </TouchableOpacity>
      </View>

      {loading ? <ActivityIndicator color={colors.primary} /> : null}
      {!loading && projects.length === 0 ? <EmptyState message="Belum ada project." /> : null}

      {projects.map((project) => (
        <TouchableOpacity
          key={project.id}
          style={styles.project}
          onPress={() => navigation.navigate('ProjectDetail', { id: project.id })}
        >
          <View style={{ flex: 1, gap: 4 }}>
            <View style={styles.rowBetween}>
              <Text style={styles.projectName}>{project.name}</Text>
              <Text style={styles.code}>{project.code ?? '-'}</Text>
            </View>
            <Text style={styles.muted}>{project.owner_name ?? 'Tanpa owner'}</Text>
            <View style={styles.progressTrack}>
              <View style={[styles.progressFill, { width: `${Math.min(100, project.progress)}%` }]} />
            </View>
            <Text style={styles.muted}>
              {project.progress}% · {project.tasks_done ?? 0}/{project.tasks_count ?? 0} task selesai
            </Text>
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
  searchRow: { flexDirection: 'row', gap: spacing.sm, alignItems: 'center' },
  searchBox: {
    flex: 1,
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    backgroundColor: '#fff',
    borderWidth: 1,
    borderColor: colors.border,
    borderRadius: radius.md,
    paddingHorizontal: spacing.md,
  },
  searchInput: { flex: 1, borderWidth: 0, backgroundColor: 'transparent', paddingVertical: 10 },
  searchButton: { backgroundColor: colors.primary, borderRadius: radius.md, paddingHorizontal: 16, paddingVertical: 12 },
  searchButtonText: { color: '#fff', fontWeight: '700' },
  project: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
    backgroundColor: '#fff',
    borderWidth: 1,
    borderColor: colors.border,
    borderRadius: radius.lg,
    padding: spacing.md,
  },
  rowBetween: { flexDirection: 'row', justifyContent: 'space-between', gap: 8 },
  projectName: { fontWeight: '700', color: colors.text, flex: 1 },
  code: { fontSize: 11, color: colors.subtle },
  progressTrack: { height: 6, backgroundColor: '#eef2f7', borderRadius: 999, marginVertical: 4, overflow: 'hidden' },
  progressFill: { height: 6, backgroundColor: colors.primary, borderRadius: 999 },
});
