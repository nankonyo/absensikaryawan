import React, { useCallback, useEffect, useState } from 'react';
import { ActivityIndicator, Alert, StyleSheet, Text, TouchableOpacity, View } from 'react-native';
import { RouteProp, useRoute } from '@react-navigation/native';
import { Ionicons } from '@expo/vector-icons';
import { Screen } from '../../components/Screen';
import { Badge, Card, EmptyState, SectionTitle } from '../../components/ui';
import { projectApi } from '../../api/endpoints';
import { apiMessage } from '../../api/client';
import type { Project, Task, User } from '../../types';
import type { InternalStackParamList } from '../../navigation/types';
import { colors, radius, spacing, statusColor } from '../../theme';

export const ProjectDetailScreen = () => {
  const route = useRoute<RouteProp<InternalStackParamList, 'ProjectDetail'>>();
  const projectId = route.params.id;

  const [project, setProject] = useState<Project | null>(null);
  const [tasks, setTasks] = useState<Task[]>([]);
  const [members, setMembers] = useState<User[]>([]);
  const [loading, setLoading] = useState(true);

  const load = useCallback(async () => {
    try {
      const response = await projectApi.show(projectId);
      setProject(response.data.project);
      setTasks(response.data.tasks);
      setMembers(response.data.members ?? []);
    } catch (err) {
      Alert.alert('Gagal memuat', apiMessage(err));
    } finally {
      setLoading(false);
    }
  }, [projectId]);

  useEffect(() => {
    load();
  }, [load]);

  const nextStatus = (status: string) => {
    const order = ['backlog', 'todo', 'in_progress', 'in_review', 'done'];
    const index = order.indexOf(status);
    return order[Math.min(order.length - 1, index + 1)] ?? 'todo';
  };

  const advanceTask = async (task: Task) => {
    try {
      await projectApi.updateTask(task.id, { status: nextStatus(task.status) });
      await load();
    } catch (err) {
      Alert.alert('Gagal', apiMessage(err));
    }
  };

  if (loading) {
    return (
      <Screen back>
        <ActivityIndicator color={colors.primary} />
      </Screen>
    );
  }

  if (!project) {
    return (
      <Screen back>
        <EmptyState message="Project tidak ditemukan." />
      </Screen>
    );
  }

  return (
    <Screen back>
      <Card style={{ gap: spacing.sm }}>
        <View style={styles.rowBetween}>
          <Text style={styles.title}>{project.name}</Text>
          <Badge
            label={project.status.replace(/_/g, ' ').toUpperCase()}
            bg={statusColor(project.status).bg}
            color={statusColor(project.status).text}
          />
        </View>
        <Text style={styles.muted}>{project.description || 'Tanpa deskripsi'}</Text>
        <Text style={styles.muted}>Owner: {project.owner_name ?? '-'}</Text>
        <Text style={styles.muted}>
          {project.start_date ?? '-'} s/d {project.end_date ?? '-'}
        </Text>
        <View style={styles.progressTrack}>
          <View style={[styles.progressFill, { width: `${Math.min(100, project.progress)}%` }]} />
        </View>
        <Text style={styles.muted}>Progress {project.progress}%</Text>
      </Card>

      <SectionTitle title={`Task (${tasks.length})`} />
      {tasks.length === 0 ? <EmptyState message="Belum ada task." /> : null}
      {tasks.map((task) => (
        <Card key={task.id} style={styles.task}>
          <View style={styles.rowBetween}>
            <Text style={styles.taskTitle}>{task.title}</Text>
            <Badge
              label={task.status.replace(/_/g, ' ').toUpperCase()}
              bg={statusColor(task.status === 'done' ? 'done' : 'pending').bg}
              color={statusColor(task.status === 'done' ? 'done' : 'pending').text}
            />
          </View>
          <Text style={styles.muted}>
            {task.assignee_name ?? 'Unassigned'} · {task.priority}
            {task.due_date ? ` · due ${task.due_date}` : ''}
          </Text>
          <View style={styles.progressTrack}>
            <View style={[styles.progressFill, { width: `${Math.min(100, task.progress)}%` }]} />
          </View>
          {task.status !== 'done' ? (
            <TouchableOpacity style={styles.advance} onPress={() => advanceTask(task)}>
              <Ionicons name="arrow-forward-circle" size={16} color={colors.primary} />
              <Text style={styles.advanceText}>Pindah ke {nextStatus(task.status).replace(/_/g, ' ')}</Text>
            </TouchableOpacity>
          ) : null}
        </Card>
      ))}

      <SectionTitle title={`Anggota (${members.length})`} />
      <Card style={{ gap: 6 }}>
        {members.length === 0 ? <Text style={styles.muted}>Belum ada anggota.</Text> : null}
        {members.map((member) => (
          <View key={member.id} style={styles.memberRow}>
            <View style={styles.avatar}>
              <Text style={styles.avatarText}>{member.name.slice(0, 1).toUpperCase()}</Text>
            </View>
            <View style={{ flex: 1 }}>
              <Text style={styles.memberName}>{member.name}</Text>
              <Text style={styles.muted}>{member.email}</Text>
            </View>
          </View>
        ))}
      </Card>
    </Screen>
  );
};

const styles = StyleSheet.create({
  title: { fontSize: 20, fontWeight: '800', color: colors.text, flex: 1 },
  muted: { fontSize: 12, color: colors.muted },
  rowBetween: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', gap: 8 },
  progressTrack: { height: 6, backgroundColor: '#eef2f7', borderRadius: 999, marginVertical: 4, overflow: 'hidden' },
  progressFill: { height: 6, backgroundColor: colors.primary, borderRadius: 999 },
  task: { gap: 6 },
  taskTitle: { fontWeight: '700', color: colors.text, flex: 1 },
  advance: { flexDirection: 'row', alignItems: 'center', gap: 6, marginTop: 4 },
  advanceText: { fontSize: 12, fontWeight: '600', color: colors.primary },
  memberRow: { flexDirection: 'row', alignItems: 'center', gap: spacing.sm },
  avatar: { width: 32, height: 32, borderRadius: 10, backgroundColor: colors.primarySoft, alignItems: 'center', justifyContent: 'center' },
  avatarText: { color: colors.primary, fontWeight: '800' },
  memberName: { fontWeight: '600', color: colors.text },
});
