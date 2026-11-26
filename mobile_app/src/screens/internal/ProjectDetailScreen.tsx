import React, { useCallback, useEffect, useState } from 'react';
import { ActivityIndicator, Alert, Modal, StyleSheet, Text, TouchableOpacity, View } from 'react-native';
import { RouteProp, useNavigation, useRoute } from '@react-navigation/native';
import { Ionicons } from '@expo/vector-icons';
import { Screen } from '../../components/Screen';
import { Badge, Button, Card, EmptyState, SectionTitle } from '../../components/ui';
import { Field } from '../../components/Field';
import { useAuth } from '../../context/AuthContext';
import { can } from '../../utils/permissions';
import { formatLabel } from '../../utils/labels';
import { projectApi } from '../../api/endpoints';
import { apiMessage } from '../../api/client';
import type { Project, Task, User } from '../../types';
import type { InternalStackParamList } from '../../navigation/types';
import { colors, radius, spacing, statusColor } from '../../theme';

export const ProjectDetailScreen = () => {
  const route = useRoute<RouteProp<InternalStackParamList, 'ProjectDetail'>>();
  const navigation = useNavigation<any>();
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
  const [projectFormOpen, setProjectFormOpen] = useState(false);
  const [projectForm, setProjectForm] = useState({ name: '', code: '', description: '', start_date: '', end_date: '', status: 'planning' });
  const [savingProject, setSavingProject] = useState(false);
  const [taskFormOpen, setTaskFormOpen] = useState(false);
  const [editingTask, setEditingTask] = useState<Task | null>(null);
  const [taskForm, setTaskForm] = useState({
    title: '', description: '', status: 'todo', priority: 'medium',
    start_date: '', due_date: '', estimated_hours: '', is_milestone: false,
  });
  const [savingTaskForm, setSavingTaskForm] = useState(false);

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

  const openProjectEdit = () => {
    if (!project) return;
    setProjectForm({
      name: project.name ?? '',
      code: project.code ?? '',
      description: project.description ?? '',
      start_date: project.start_date ?? '',
      end_date: project.end_date ?? '',
      status: project.status ?? 'planning',
    });
    setProjectFormOpen(true);
  };

  const saveProjectEdit = async () => {
    if (!projectForm.name.trim()) {
      Alert.alert('Form belum lengkap', 'Nama project wajib diisi.');
      return;
    }
    setSavingProject(true);
    try {
      await projectApi.update(projectId, {
        name: projectForm.name.trim(),
        code: projectForm.code.trim() || undefined,
        description: projectForm.description.trim() || undefined,
        start_date: projectForm.start_date.trim() || undefined,
        end_date: projectForm.end_date.trim() || undefined,
        status: projectForm.status,
      });
      setProjectFormOpen(false);
      await load();
      Alert.alert('Berhasil', 'Project diperbarui.');
    } catch (err) {
      Alert.alert('Gagal', apiMessage(err));
    } finally {
      setSavingProject(false);
    }
  };

  const deleteProject = () => {
    Alert.alert('Hapus project?', 'Semua task dan data project akan dihapus.', [
      { text: 'Batal', style: 'cancel' },
      {
        text: 'Hapus',
        style: 'destructive',
        onPress: async () => {
          try {
            await projectApi.remove(projectId);
            navigation.goBack();
          } catch (err) {
            Alert.alert('Gagal', apiMessage(err));
          }
        },
      },
    ]);
  };

  const openTaskForm = (task?: Task) => {
    setEditingTask(task ?? null);
    setTaskForm({
      title: task?.title ?? '',
      description: task?.description ?? '',
      status: task?.status ?? 'todo',
      priority: task?.priority ?? 'medium',
      start_date: task?.start_date ?? '',
      due_date: task?.due_date ?? '',
      estimated_hours: task?.estimated_hours ? String(task.estimated_hours) : '',
      is_milestone: Boolean(task?.is_milestone),
    });
    setTaskAssigneeIds(task?.assignee_ids ?? (task?.assignee_id ? [task.assignee_id] : []));
    setTaskFormOpen(true);
  };

  const saveTaskForm = async () => {
    if (!taskForm.title.trim()) {
      Alert.alert('Form belum lengkap', 'Judul task wajib diisi.');
      return;
    }
    setSavingTaskForm(true);
    try {
      const payload = {
        title: taskForm.title.trim(),
        description: taskForm.description.trim() || undefined,
        status: taskForm.status,
        priority: taskForm.priority,
        start_date: taskForm.start_date.trim() || undefined,
        due_date: taskForm.due_date.trim() || undefined,
        estimated_hours: taskForm.estimated_hours ? Number(taskForm.estimated_hours) : undefined,
        is_milestone: taskForm.is_milestone,
        assignee_ids: taskAssigneeIds,
      };
      if (editingTask) {
        await projectApi.updateTask(editingTask.id, payload);
      } else {
        await projectApi.createTask({ project_id: projectId, ...payload });
      }
      setTaskFormOpen(false);
      setEditingTask(null);
      await load();
      Alert.alert('Berhasil', editingTask ? 'Task diperbarui.' : 'Task dibuat.');
    } catch (err) {
      Alert.alert('Gagal', apiMessage(err));
    } finally {
      setSavingTaskForm(false);
    }
  };

  const deleteTask = (task: Task) => {
    Alert.alert('Hapus task?', task.title, [
      { text: 'Batal', style: 'cancel' },
      {
        text: 'Hapus',
        style: 'destructive',
        onPress: async () => {
          try {
            await projectApi.removeTask(task.id);
            await load();
          } catch (err) {
            Alert.alert('Gagal', apiMessage(err));
          }
        },
      },
    ]);
  };

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
            label={formatLabel(project.status)}
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
        <View style={styles.projectActions}>
          <TouchableOpacity style={styles.outlineButton} onPress={openProjectEdit}>
            <Ionicons name="create-outline" size={16} color={colors.primary} />
            <Text style={styles.outlineText}>Edit Project</Text>
          </TouchableOpacity>
          <TouchableOpacity style={styles.deleteButton} onPress={deleteProject}>
            <Ionicons name="trash-outline" size={16} color={colors.danger} />
            <Text style={styles.deleteText}>Hapus</Text>
          </TouchableOpacity>
        </View>
      </Card>

      <SectionTitle
        title={`Task (${tasks.length})`}
        action={
          canUpdateTask ? (
            <TouchableOpacity onPress={() => openTaskForm()}>
              <Text style={styles.link}>Tambah Task</Text>
            </TouchableOpacity>
          ) : null
        }
      />
      {tasks.length === 0 ? <EmptyState message="Belum ada task." /> : null}
      {tasks.map((task) => (
        <Card key={task.id} style={styles.task}>
          <View style={styles.rowBetween}>
            <Text style={styles.taskTitle}>{task.title}</Text>
            <Badge
              label={formatLabel(task.status)}
              bg={statusColor(task.status === 'done' ? 'done' : 'pending').bg}
              color={statusColor(task.status === 'done' ? 'done' : 'pending').text}
            />
          </View>
          <Text style={styles.muted}>
            {(task.assignee_names?.length ? task.assignee_names.join(', ') : task.assignee_name) ?? 'Unassigned'} · {formatLabel(task.priority)}
            {task.due_date ? ` · due ${task.due_date}` : ''}
          </Text>
          <View style={styles.progressTrack}>
            <View style={[styles.progressFill, { width: `${Math.min(100, task.progress)}%` }]} />
          </View>
          {canUpdateTask ? (
            <View style={styles.taskActions}>
              <TouchableOpacity style={styles.advance} onPress={() => openTaskForm(task)}>
                <Ionicons name="create-outline" size={16} color={colors.primary} />
                <Text style={styles.advanceText}>Edit Task</Text>
              </TouchableOpacity>
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
                  <Text style={styles.advanceText}>Pindah ke {formatLabel(nextStatus(task.status))}</Text>
                </TouchableOpacity>
              ) : null}
              <TouchableOpacity style={styles.advance} onPress={() => deleteTask(task)}>
                <Ionicons name="trash-outline" size={16} color={colors.danger} />
                <Text style={[styles.advanceText, { color: colors.danger }]}>Hapus Task</Text>
              </TouchableOpacity>
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

      <Modal visible={projectFormOpen} transparent animationType="slide" onRequestClose={() => setProjectFormOpen(false)}>
        <View style={styles.backdrop}>
          <View style={styles.modal}>
            <View style={styles.rowBetween}>
              <Text style={styles.modalTitle}>Edit Project</Text>
              <TouchableOpacity onPress={() => setProjectFormOpen(false)}>
                <Ionicons name="close" size={22} color={colors.muted} />
              </TouchableOpacity>
            </View>
            <Field label="Nama project *" value={projectForm.name} onChangeText={(name) => setProjectForm({ ...projectForm, name })} />
            <Field label="Kode" value={projectForm.code} onChangeText={(code) => setProjectForm({ ...projectForm, code })} />
            <Field label="Deskripsi" value={projectForm.description} onChangeText={(description) => setProjectForm({ ...projectForm, description })} multiline style={{ minHeight: 70 }} />
            <View style={styles.dateRow}>
              <View style={{ flex: 1 }}>
                <Field label="Tanggal mulai" value={projectForm.start_date} onChangeText={(start_date) => setProjectForm({ ...projectForm, start_date })} placeholder="YYYY-MM-DD" />
              </View>
              <View style={{ flex: 1 }}>
                <Field label="Tanggal selesai" value={projectForm.end_date} onChangeText={(end_date) => setProjectForm({ ...projectForm, end_date })} placeholder="YYYY-MM-DD" />
              </View>
            </View>
            <Text style={styles.label}>Status</Text>
            <View style={styles.chips}>
              {['planning', 'in_progress', 'on_hold', 'completed'].map((item) => (
                <TouchableOpacity key={item} style={[styles.chip, projectForm.status === item && styles.chipActive]} onPress={() => setProjectForm({ ...projectForm, status: item })}>
                  <Text style={[styles.chipText, projectForm.status === item && styles.chipTextActive]}>{formatLabel(item)}</Text>
                </TouchableOpacity>
              ))}
            </View>
            <Button label={savingProject ? 'Menyimpan...' : 'Simpan Project'} disabled={savingProject} onPress={saveProjectEdit} />
          </View>
        </View>
      </Modal>

      <Modal visible={taskFormOpen} transparent animationType="slide" onRequestClose={() => setTaskFormOpen(false)}>
        <View style={styles.backdrop}>
          <View style={[styles.modal, { maxHeight: '92%' }]}>
            <View style={styles.rowBetween}>
              <Text style={styles.modalTitle}>{editingTask ? 'Edit Task' : 'Tambah Task'}</Text>
              <TouchableOpacity onPress={() => setTaskFormOpen(false)}>
                <Ionicons name="close" size={22} color={colors.muted} />
              </TouchableOpacity>
            </View>
            <Field label="Judul *" value={taskForm.title} onChangeText={(title) => setTaskForm({ ...taskForm, title })} />
            <Field label="Deskripsi" value={taskForm.description} onChangeText={(description) => setTaskForm({ ...taskForm, description })} multiline style={{ minHeight: 70 }} />

            <Text style={styles.label}>Status</Text>
            <View style={styles.chips}>
              {['backlog', 'todo', 'in_progress', 'in_review', 'done'].map((item) => (
                <TouchableOpacity key={item} style={[styles.chip, taskForm.status === item && styles.chipActive]} onPress={() => setTaskForm({ ...taskForm, status: item })}>
                  <Text style={[styles.chipText, taskForm.status === item && styles.chipTextActive]}>{formatLabel(item)}</Text>
                </TouchableOpacity>
              ))}
            </View>

            <Text style={styles.label}>Prioritas</Text>
            <View style={styles.chips}>
              {['low', 'medium', 'high', 'urgent'].map((item) => (
                <TouchableOpacity key={item} style={[styles.chip, taskForm.priority === item && styles.chipActive]} onPress={() => setTaskForm({ ...taskForm, priority: item })}>
                  <Text style={[styles.chipText, taskForm.priority === item && styles.chipTextActive]}>{formatLabel(item)}</Text>
                </TouchableOpacity>
              ))}
            </View>

            <View style={styles.dateRow}>
              <View style={{ flex: 1 }}>
                <Field label="Tanggal mulai" value={taskForm.start_date} onChangeText={(start_date) => setTaskForm({ ...taskForm, start_date })} placeholder="YYYY-MM-DD" />
              </View>
              <View style={{ flex: 1 }}>
                <Field label="Deadline" value={taskForm.due_date} onChangeText={(due_date) => setTaskForm({ ...taskForm, due_date })} placeholder="YYYY-MM-DD" />
              </View>
            </View>
            <Field label="Estimasi jam" value={taskForm.estimated_hours} onChangeText={(estimated_hours) => setTaskForm({ ...taskForm, estimated_hours })} keyboardType="numeric" />

            <Text style={styles.label}>Assignments</Text>
            <View style={styles.chips}>
              {(members.length ? members : availableUsers).map((member) => {
                const active = taskAssigneeIds.includes(member.id);
                return (
                  <TouchableOpacity
                    key={member.id}
                    style={[styles.chip, active && styles.chipActive]}
                    onPress={() => setTaskAssigneeIds((ids) => (ids.includes(member.id) ? ids.filter((id) => id !== member.id) : [...ids, member.id]))}
                  >
                    <Text style={[styles.chipText, active && styles.chipTextActive]}>{member.name}</Text>
                  </TouchableOpacity>
                );
              })}
            </View>

            <TouchableOpacity style={styles.switchRow} onPress={() => setTaskForm({ ...taskForm, is_milestone: !taskForm.is_milestone })}>
              <Text style={styles.switchLabel}>Milestone</Text>
              <View style={[styles.switchTrack, taskForm.is_milestone && styles.switchTrackOn]}>
                <View style={[styles.switchThumb, taskForm.is_milestone && styles.switchThumbOn]} />
              </View>
            </TouchableOpacity>

            <Button label={savingTaskForm ? 'Menyimpan...' : 'Simpan Task'} disabled={savingTaskForm} onPress={saveTaskForm} />
          </View>
        </View>
      </Modal>

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
  taskActions: { flexDirection: 'row', flexWrap: 'wrap', gap: spacing.md },
  projectActions: { flexDirection: 'row', gap: spacing.sm, marginTop: spacing.xs },
  outlineButton: { flexDirection: 'row', alignItems: 'center', gap: 6, borderWidth: 1, borderColor: colors.primarySoft, backgroundColor: colors.primarySoft, borderRadius: radius.md, paddingHorizontal: 12, paddingVertical: 8 },
  outlineText: { fontSize: 11, fontWeight: '700', color: colors.primary },
  deleteButton: { flexDirection: 'row', alignItems: 'center', gap: 6, borderWidth: 1, borderColor: colors.dangerSoft, backgroundColor: colors.dangerSoft, borderRadius: radius.md, paddingHorizontal: 12, paddingVertical: 8 },
  deleteText: { fontSize: 11, fontWeight: '700', color: colors.danger },
  dateRow: { flexDirection: 'row', gap: spacing.sm },
  label: { fontSize: 12, fontWeight: '700', color: colors.muted, marginTop: 4 },
  switchRow: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', borderWidth: 1, borderColor: colors.border, borderRadius: radius.md, paddingHorizontal: spacing.md, paddingVertical: 10 },
  switchLabel: { fontSize: 12, fontWeight: '700', color: colors.text },
  switchTrack: { width: 44, height: 24, borderRadius: 999, backgroundColor: '#e2e8f0', padding: 2 },
  switchTrackOn: { backgroundColor: colors.primary },
  switchThumb: { width: 20, height: 20, borderRadius: 999, backgroundColor: '#fff' },
  switchThumbOn: { transform: [{ translateX: 20 }] },
  backdrop: { flex: 1, backgroundColor: 'rgba(15,23,42,0.5)', justifyContent: 'flex-end' },
  modal: { backgroundColor: '#fff', borderTopLeftRadius: 24, borderTopRightRadius: 24, padding: spacing.xl, gap: spacing.md, maxHeight: '92%' },
  modalTitle: { fontSize: 18, fontWeight: '800', color: colors.text, flex: 1 },
  chips: { flexDirection: 'row', flexWrap: 'wrap', gap: spacing.sm },
  chip: { borderWidth: 1, borderColor: colors.border, borderRadius: radius.full, paddingHorizontal: 12, paddingVertical: 6 },
  chipActive: { backgroundColor: colors.primary, borderColor: colors.primary },
  chipText: { fontSize: 11, color: colors.muted },
  chipTextActive: { color: '#fff', fontWeight: '700' },
});
