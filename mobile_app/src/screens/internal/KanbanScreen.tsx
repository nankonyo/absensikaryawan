import React, { useCallback, useEffect, useState } from 'react';
import { ActivityIndicator, Alert, ScrollView, StyleSheet, Text, TouchableOpacity, View } from 'react-native';
import { Screen } from '../../components/Screen';
import { Badge, EmptyState } from '../../components/ui';
import { projectApi } from '../../api/endpoints';
import { apiMessage } from '../../api/client';
import type { Project, Task } from '../../types';
import { colors, radius, spacing, statusColor } from '../../theme';

export const KanbanScreen = () => {
  const [board, setBoard] = useState<Record<string, Task[]>>({});
  const [statuses, setStatuses] = useState<string[]>([]);
  const [projects, setProjects] = useState<Project[]>([]);
  const [projectId, setProjectId] = useState<number | null>(null);
  const [loading, setLoading] = useState(true);

  const load = useCallback(async (id?: number | null) => {
    try {
      const response = await projectApi.kanban(id ?? undefined);
      setBoard(response.data.board);
      setStatuses(response.data.statuses);
      setProjects(response.data.projects);
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
    <Screen scroll={false} back>
      <View style={{ padding: spacing.lg, gap: spacing.md }}>
        <Text style={styles.title}>Kanban Board</Text>
        <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={{ gap: spacing.sm }}>
          <TouchableOpacity
            style={[styles.filter, projectId === null && styles.filterActive]}
            onPress={() => {
              setProjectId(null);
              load(null);
            }}
          >
            <Text style={[styles.filterText, projectId === null && styles.filterTextActive]}>Semua Project</Text>
          </TouchableOpacity>
          {projects.map((project) => (
            <TouchableOpacity
              key={project.id}
              style={[styles.filter, projectId === project.id && styles.filterActive]}
              onPress={() => {
                setProjectId(project.id);
                load(project.id);
              }}
            >
              <Text style={[styles.filterText, projectId === project.id && styles.filterTextActive]}>{project.name}</Text>
            </TouchableOpacity>
          ))}
        </ScrollView>
      </View>

      {loading ? <ActivityIndicator color={colors.primary} /> : null}

      <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.board}>
        {statuses.map((status) => (
          <View key={status} style={styles.column}>
            <View style={styles.columnHeader}>
              <Text style={styles.columnTitle}>{status.replace(/_/g, ' ').toUpperCase()}</Text>
              <Text style={styles.count}>{(board[status] ?? []).length}</Text>
            </View>
            <ScrollView showsVerticalScrollIndicator={false} contentContainerStyle={{ gap: spacing.sm, paddingBottom: spacing.xl }}>
              {(board[status] ?? []).length === 0 ? <EmptyState message="Kosong" /> : null}
              {(board[status] ?? []).map((task) => (
                <View key={task.id} style={styles.card}>
                  <Text style={styles.cardTitle}>{task.title}</Text>
                  <Text style={styles.muted}>{task.project_name}</Text>
                  <Text style={styles.muted}>{task.assignee_name ?? 'Unassigned'}</Text>
                  <Badge
                    label={task.priority.toUpperCase()}
                    bg={statusColor('pending').bg}
                    color={statusColor('pending').text}
                  />
                </View>
              ))}
            </ScrollView>
          </View>
        ))}
      </ScrollView>
    </Screen>
  );
};

const styles = StyleSheet.create({
  title: { fontSize: 22, fontWeight: '800', color: colors.text },
  filter: {
    borderWidth: 1,
    borderColor: colors.border,
    backgroundColor: '#fff',
    borderRadius: radius.full,
    paddingHorizontal: 12,
    paddingVertical: 6,
  },
  filterActive: { backgroundColor: colors.primary, borderColor: colors.primary },
  filterText: { fontSize: 12, color: colors.muted },
  filterTextActive: { color: '#fff', fontWeight: '700' },
  board: { paddingHorizontal: spacing.lg, paddingBottom: spacing.xl, gap: spacing.md },
  column: { width: 240, backgroundColor: '#eef2f7', borderRadius: radius.lg, padding: spacing.md, maxHeight: '100%' },
  columnHeader: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: spacing.sm },
  columnTitle: { fontSize: 11, fontWeight: '800', color: colors.muted },
  count: { fontSize: 11, fontWeight: '700', color: colors.primary, backgroundColor: '#fff', paddingHorizontal: 8, paddingVertical: 2, borderRadius: 999 },
  card: { backgroundColor: '#fff', borderRadius: radius.md, padding: spacing.md, gap: 4 },
  cardTitle: { fontWeight: '700', color: colors.text },
  muted: { fontSize: 11, color: colors.muted },
});
