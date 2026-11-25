import React, { useCallback, useEffect, useState } from 'react';
import { ActivityIndicator, Alert, Modal, StyleSheet, Text, TouchableOpacity, View } from 'react-native';
import { RouteProp, useRoute } from '@react-navigation/native';
import { Ionicons } from '@expo/vector-icons';
import { Screen } from '../../components/Screen';
import { Badge, Button, Card, EmptyState, SectionTitle } from '../../components/ui';
import { useAuth } from '../../context/AuthContext';
import { can } from '../../utils/permissions';
import { projectApi } from '../../api/endpoints';
import { apiMessage } from '../../api/client';
import type { Project, Task, User } from '../../types';
import type { InternalStackParamList } from '../../navigation/types';
import { colors, radius, spacing, statusColor } from '../../theme';

export const ProjectDetailScreen = () => {
  const route = useRoute<RouteProp<InternalStackParamList, 'ProjectDetail'>>();
  const { user } = useAuth();
  const projectId = route.params.id;
  const canUpdateTask = can(user, 'internalapp.project.kanban', 'update');

  const [project, setProject] = useState<Project | null>(null);
  const [tasks, setTasks] = useState<Task[]>([]);
  const [members, setMembers] = useState<User[]>([]);
  const [availableUsers, setAvailableUsers] = useState<{ id: number; name: string }[]>([]);
  const [loading, setLoading] = useState(true);
  const [teamOpen, setTeamOpen] = useState(false);
  const [teamIds, setTeamIds] = useState<number[]>([]);
  const [savingTeam, setSavingTeam] = useState(false);
  const [taskOpen, setTaskOpen] = useState<Task | null>(null);
  const [taskAssigneeIds, setTaskAssigneeIds] = useState<number[]>([]);
  const [savingTask, setSavingTask] = useState(false);

  const load = useCallback(async () => {
    try {
      const response = await projectApi.show(projectId);
      setProject(response.data.project);
      setTasks(response.data.tasks);
      setMembers(response.data.members ?? []);
      setAvailableUsers(response.data.available_users ?? []);
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
            label={project.status === 'in_progress' ? 'In Progress' : project.status.replace(/_/g, ' ').toUpperCase()}
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
              label={task.status === 'in_progress' ? 'In Progress' : task.status.replace(/_/g, ' ').replace(/\b\w/g, (char) => char.toUpperCase())}
              bg={statusColor(task.status === 'done' ? 'done' : 'pending').bg}
              color={statusColor(task.status === 'done' ? 'done' : 'pending').text}
            />
          </View>
          <Text style={styles.muted}>
            {(task.assignee_names?.length ? task.assignee_names.join(', ') : task.assignee_name) ?? 'Unassigned'} · {task.priority}
            {task.due_date ? ` · due ${task.due_date}` : ''}
          </Text>
          <View style={styles.progressTrack}>
            <View style={[styles.progressFill, { width: `${Math.min(100, task.progress)}%` }]} />
          </View>
          {canUpdateTask ? (
            <View style={styles.taskActions}>
              <TouchableOpacity
                style={styles.advance}
                onPress={() => {
                  setTaskOpen(task);
                  setTaskAssigneeIds(task.assignee_ids ?? (task.assignee_id ? [task.assignee_id] : []));
                }}
              >
                <Ionicons name="people-outline" size={16} color={colors.primary} />
                <Text style={styles.advanceText}>Atur Assignment</Text>
              </TouchableOpacity>
              {task.status !== 'done' ? (
                <TouchableOpacity style={styles.advance} onPress={() => advanceTask(task)}>
                  <Ionicons name="arrow-forward-circle" size={16} color={colors.primary} />
                  <Text style={styles.advanceText}>Pindah ke {nextStatus(task.status) === 'in_progress' ? 'In Progress' : nextStatus(task.status).replace(/_/g, ' ')}</Text>
                </TouchableOpacity>
              ) : null}
            </View>
          ) : null}
        </Card>
      ))}

      <SectionTitle
        title={`Anggota (${members.length})`}
        action={
          canUpdateTask ? (
            <TouchableOpacity onPress={() => { setTeamIds(members.map((member) => member.id)); setTeamOpen(true); }}>
              <Text style={styles.link}>Update Tim</Text>
            </TouchableOpacity>
          ) : null
        }
      />
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

      <Modal visible={teamOpen} transparent animationType="slide" onRequestClose={() => setTeamOpen(false)}>
        <View style={styles.backdrop}>
          <View style={styles.modal}>
            <View style={styles.rowBetween}>
              <Text style={styles.modalTitle}>Update Tim Project</Text>
              <TouchableOpacity onPress={() => setTeamOpen(false)}>
                <Ionicons name="close" size={22} color={colors.muted} />
              </TouchableOpacity>
            </View>
            <Text style={styles.muted}>Pilih anggota tim. Owner project selalu dipertahankan.</Text>
            <View style={styles.chips}>
              {availableUsers.map((member) => {
                const active = teamIds.includes(member.id);
                return (
                  <TouchableOpacity
                    key={member.id}
                    style={[styles.chip, active && styles.chipActive]}
                    onPress={() =>
                      setTeamIds((ids) => (ids.includes(member.id) ? ids.filter((id) => id !== member.id) : [...ids, member.id]))
                    }
                  >
                    <Text style={[styles.chipText, active && styles.chipTextActive]}>{member.name}</Text>
                  </TouchableOpacity>
                );
              })}
            </View>
            <Button
              label={savingTeam ? 'Menyimpan...' : 'Simpan Tim'}
              disabled={savingTeam}
              onPress={async () => {
                setSavingTeam(true);
                try {
                  await projectApi.updateMembers(projectId, teamIds);
                  setTeamOpen(false);
                  await load();
                  Alert.alert('Berhasil', 'Tim project diperbarui.');
                } catch (err) {
                  Alert.alert('Gagal', apiMessage(err));
                } finally {
                  setSavingTeam(false);
                }
              }}
            />
          </View>
        </View>
      </Modal>

      <Modal visible={Boolean(taskOpen)} transparent animationType="slide" onRequestClose={() => setTaskOpen(null)}>
        <View style={styles.backdrop}>
          <View style={styles.modal}>
            <View style={styles.rowBetween}>
              <Text style={styles.modalTitle}>Assignment Task</Text>
              <TouchableOpacity onPress={() => setTaskOpen(null)}>
                <Ionicons name="close" size={22} color={colors.muted} />
              </TouchableOpacity>
            </View>
            <Text style={styles.muted}>{taskOpen?.title}</Text>
            <View style={styles.chips}>
              {(members.length ? members : availableUsers).map((member) => {
                const active = taskAssigneeIds.includes(member.id);
                return (
                  <TouchableOpacity
                    key={member.id}
                    style={[styles.chip, active && styles.chipActive]}
                    onPress={() =>
                      setTaskAssigneeIds((ids) => (ids.includes(member.id) ? ids.filter((id) => id !== member.id) : [...ids, member.id]))
                    }
                  >
                    <Text style={[styles.chipText, active && styles.chipTextActive]}>{member.name}</Text>
                  </TouchableOpacity>
                );
              })}
            </View>
            <Button
              label={savingTask ? 'Menyimpan...' : 'Simpan Assignment'}
              disabled={savingTask}
              onPress={async () => {
                if (!taskOpen) return;
                setSavingTask(true);
                try {
                  await projectApi.updateTask(taskOpen.id, { assignee_ids: taskAssigneeIds });
                  setTaskOpen(null);
                  await load();
                  Alert.alert('Berhasil', 'Assignment task diperbarui.');
                } catch (err) {
                  Alert.alert('Gagal', apiMessage(err));
                } finally {
                  setSavingTask(false);
                }
              }}
            />
          </View>
        </View>
      </Modal>
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
  link: { fontSize: 12, fontWeight: '700', color: colors.primary },
  taskActions: { gap: 4 },
  backdrop: { flex: 1, backgroundColor: 'rgba(15,23,42,0.5)', justifyContent: 'flex-end' },
  modal: { backgroundColor: '#fff', borderTopLeftRadius: 24, borderTopRightRadius: 24, padding: spacing.xl, gap: spacing.md, maxHeight: '92%' },
  modalTitle: { fontSize: 18, fontWeight: '800', color: colors.text, flex: 1 },
  chips: { flexDirection: 'row', flexWrap: 'wrap', gap: spacing.sm },
  chip: { borderWidth: 1, borderColor: colors.border, borderRadius: radius.full, paddingHorizontal: 12, paddingVertical: 6 },
  chipActive: { backgroundColor: colors.primary, borderColor: colors.primary },
  chipText: { fontSize: 11, color: colors.muted },
  chipTextActive: { color: '#fff', fontWeight: '700' },
});
