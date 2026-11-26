import React, { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { ActivityIndicator, Alert, Animated, PanResponder, ScrollView, StyleSheet, Text, TouchableOpacity, View } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { Screen } from '../../components/Screen';
import { Badge, EmptyState } from '../../components/ui';
import { projectApi } from '../../api/endpoints';
import { apiMessage } from '../../api/client';
import { useAuth } from '../../context/AuthContext';
import { can } from '../../utils/permissions';
import { formatLabel } from '../../utils/labels';
import type { Project, Task } from '../../types';
import { colors, radius, spacing, statusColor } from '../../theme';

const COLUMN_STEP = 252;

const statusLabel = (status: string) =>
  status === 'in_progress' ? 'In Progress' : status.replace(/_/g, ' ').replace(/\b\w/g, (char) => char.toUpperCase());

const DraggableTaskCard = ({
  task,
  statusIndex,
  totalStatuses,
  canMove,
  onMove,
}: {
  task: Task;
  statusIndex: number;
  totalStatuses: number;
  canMove: boolean;
  onMove: (task: Task, targetIndex: number) => void;
}) => {
  const pan = useRef(new Animated.ValueXY()).current;
  const [dragging, setDragging] = useState(false);

  const reset = () => {
    setDragging(false);
    Animated.spring(pan, { toValue: { x: 0, y: 0 }, useNativeDriver: false, friction: 6 }).start();
  };

  const responder = useMemo(
    () =>
      PanResponder.create({
        onStartShouldSetPanResponder: () => false,
        onMoveShouldSetPanResponder: (_, gesture) =>
          canMove && (Math.abs(gesture.dx) > 8 || Math.abs(gesture.dy) > 8),
        onPanResponderGrant: () => setDragging(true),
        onPanResponderMove: Animated.event([null, { dx: pan.x, dy: pan.y }], { useNativeDriver: false }),
        onPanResponderTerminationRequest: () => false,
        onPanResponderRelease: (_, gesture) => {
          const step = Math.round(gesture.dx / COLUMN_STEP);
          const targetIndex = Math.max(0, Math.min(totalStatuses - 1, statusIndex + step));
          const shouldMove = Math.abs(gesture.dx) > 45 && targetIndex !== statusIndex;
          reset();

          if (shouldMove) {
            onMove(task, targetIndex);
          }
        },
        onPanResponderTerminate: reset,
      }),
    [canMove, onMove, pan, statusIndex, task, totalStatuses],
  );

  return (
    <Animated.View
      {...responder.panHandlers}
      style={[styles.card, dragging && styles.cardDragging, { transform: pan.getTranslateTransform() }]}
    >
      <View style={styles.cardTop}>
        <Badge
          label={formatLabel(task.priority ?? 'medium')}
          bg={statusColor('pending').bg}
          color={statusColor('pending').text}
        />
        {canMove ? <Ionicons name="move-outline" size={15} color={colors.subtle} /> : null}
      </View>
      <Text style={styles.cardTitle}>{task.title}</Text>
      <Text style={styles.muted}>{task.project_name}</Text>
      <Text style={styles.muted}>
        {(task.assignee_names?.length ? task.assignee_names.join(', ') : task.assignee_name) ?? 'Unassigned'}
      </Text>
    </Animated.View>
  );
};

export const KanbanScreen = () => {
  const { user } = useAuth();
  const canMove = can(user, 'internalapp.project.kanban', 'update');
  const [board, setBoard] = useState<Record<string, Task[]>>({});
  const [statuses, setStatuses] = useState<string[]>([]);
  const [projects, setProjects] = useState<Project[]>([]);
  const [projectId, setProjectId] = useState<number | null>(null);
  const [loading, setLoading] = useState(true);
  const [dragging, setDragging] = useState(false);

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

  const moveTask = useCallback(
    async (task: Task, targetIndex: number) => {
      const targetStatus = statuses[targetIndex];
      if (!targetStatus) return;

      setDragging(false);
      try {
        await projectApi.updateTask(task.id, { status: targetStatus });
        await load(projectId);
      } catch (err) {
        Alert.alert('Gagal memindahkan task', apiMessage(err));
      }
    },
    [load, projectId, statuses],
  );

  return (
    <Screen scroll={false} back>
      <View style={{ padding: spacing.lg, gap: spacing.md }}>
        <View style={styles.rowBetween}>
          <View>
            <Text style={styles.title}>Kanban Board</Text>
            <Text style={styles.muted}>{canMove ? 'Geser kartu ke kolom lain untuk mengubah status.' : 'Hanya lihat.'}</Text>
          </View>
        </View>
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

      <ScrollView
        horizontal
        scrollEnabled={!dragging}
        showsHorizontalScrollIndicator={false}
        contentContainerStyle={styles.board}
      >
        {statuses.map((status, statusIndex) => (
          <View key={status} style={styles.column}>
            <View style={styles.columnHeader}>
              <Text style={styles.columnTitle}>{statusLabel(status)}</Text>
              <Text style={styles.count}>{(board[status] ?? []).length}</Text>
            </View>
            <ScrollView showsVerticalScrollIndicator={false} contentContainerStyle={{ gap: spacing.sm, paddingBottom: spacing.xl }}>
              {(board[status] ?? []).length === 0 ? <EmptyState message="Kosong" /> : null}
              {(board[status] ?? []).map((task) => (
                <View key={task.id} onTouchStart={() => canMove && setDragging(true)} onTouchEnd={() => setDragging(false)}>
                  <DraggableTaskCard
                    task={task}
                    statusIndex={statusIndex}
                    totalStatuses={statuses.length}
                    canMove={canMove}
                    onMove={moveTask}
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
  muted: { fontSize: 11, color: colors.muted },
  rowBetween: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', gap: 8 },
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
  card: { backgroundColor: '#fff', borderRadius: radius.md, padding: spacing.md, gap: 4, borderWidth: 1, borderColor: colors.border },
  cardDragging: { zIndex: 999, elevation: 12, borderColor: colors.primary, opacity: 0.95 },
  cardTop: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between' },
  cardTitle: { fontWeight: '700', color: colors.text },
});
