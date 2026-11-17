import React, { useCallback, useEffect, useState } from 'react';
import { ActivityIndicator, Alert, Modal, StyleSheet, Text, TouchableOpacity, View } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { Screen } from '../../components/Screen';
import { Badge, Button, Card, EmptyState } from '../../components/ui';
import { Field } from '../../components/Field';
import { activityApi } from '../../api/endpoints';
import { apiMessage } from '../../api/client';
import type { ActivityLog, Project } from '../../types';
import { colors, radius, spacing, statusColor } from '../../theme';

const statuses = ['backlog', 'todo', 'in_progress', 'in_review', 'done'];
const priorities = ['low', 'medium', 'high', 'urgent'];

export const ActivitiesScreen = () => {
  const [activities, setActivities] = useState<ActivityLog[]>([]);
  const [projects, setProjects] = useState<Project[]>([]);
  const [scope, setScope] = useState<'mine' | 'team'>('mine');
  const [loading, setLoading] = useState(true);
  const [open, setOpen] = useState(false);
  const [saving, setSaving] = useState(false);
  const [projectId, setProjectId] = useState<number | null>(null);
  const [title, setTitle] = useState('');
  const [description, setDescription] = useState('');
  const [status, setStatus] = useState('in_progress');
  const [priority, setPriority] = useState('medium');

  const load = useCallback(async (nextScope: 'mine' | 'team') => {
    try {
      const response = await activityApi.index({ scope: nextScope });
      setActivities(response.data.activities.data);
      setProjects(response.data.projects);
      if (!projectId && response.data.projects[0]) setProjectId(response.data.projects[0].id);
    } catch (err) {
      Alert.alert('Gagal memuat', apiMessage(err));
    } finally {
      setLoading(false);
    }
  }, [projectId]);

  useEffect(() => {
    load(scope);
  }, [load, scope]);

  const submit = async () => {
    if (!projectId || !title) {
      Alert.alert('Form belum lengkap', 'Pilih project dan isi judul aktivitas.');
      return;
    }
    setSaving(true);
    try {
      await activityApi.store({ project_id: projectId, title, description, status, priority });
      setOpen(false);
      setTitle('');
      setDescription('');
      await load(scope);
    } catch (err) {
      Alert.alert('Gagal menyimpan', apiMessage(err));
    } finally {
      setSaving(false);
    }
  };

  return (
    <Screen back>
      <View style={styles.rowBetween}>
        <View>
          <Text style={styles.title}>Aktivitas</Text>
          <Text style={styles.muted}>Log aktivitas project.</Text>
        </View>
        <TouchableOpacity style={styles.addButton} onPress={() => setOpen(true)}>
          <Ionicons name="add" size={20} color="#fff" />
        </TouchableOpacity>
      </View>

      <View style={styles.tabs}>
        <TouchableOpacity style={[styles.tab, scope === 'mine' && styles.tabActive]} onPress={() => setScope('mine')}>
          <Text style={[styles.tabText, scope === 'mine' && styles.tabTextActive]}>Saya</Text>
        </TouchableOpacity>
        <TouchableOpacity style={[styles.tab, scope === 'team' && styles.tabActive]} onPress={() => setScope('team')}>
          <Text style={[styles.tabText, scope === 'team' && styles.tabTextActive]}>Tim</Text>
        </TouchableOpacity>
      </View>

      {loading ? <ActivityIndicator color={colors.primary} /> : null}
      {!loading && activities.length === 0 ? <EmptyState message="Belum ada aktivitas." /> : null}

      {activities.map((activity) => (
        <Card key={activity.id} style={{ gap: 4 }}>
          <View style={styles.rowBetween}>
            <Text style={styles.activityTitle}>{activity.description ?? activity.action}</Text>
            <Badge
              label={activity.type.toUpperCase()}
              bg={statusColor('pending').bg}
              color={statusColor('pending').text}
            />
          </View>
          <Text style={styles.muted}>
            {activity.user_name ?? '-'} · {activity.project_name ?? '-'}
          </Text>
          <Text style={styles.muted}>
            {activity.activity_date ?? ''} {activity.action}
          </Text>
        </Card>
      ))}

      <Modal visible={open} transparent animationType="slide" onRequestClose={() => setOpen(false)}>
        <View style={styles.backdrop}>
          <View style={styles.modal}>
            <View style={styles.rowBetween}>
              <Text style={styles.modalTitle}>Input Aktivitas</Text>
              <TouchableOpacity onPress={() => setOpen(false)}>
                <Ionicons name="close" size={22} color={colors.muted} />
              </TouchableOpacity>
            </View>

            <Text style={styles.label}>Project</Text>
            <View style={styles.chips}>
              {projects.map((project) => (
                <TouchableOpacity
                  key={project.id}
                  style={[styles.chip, projectId === project.id && styles.chipActive]}
                  onPress={() => setProjectId(project.id)}
                >
                  <Text style={[styles.chipText, projectId === project.id && styles.chipTextActive]}>{project.name}</Text>
                </TouchableOpacity>
              ))}
            </View>

            <Field label="Judul aktivitas" value={title} onChangeText={setTitle} placeholder="Contoh: Update modul presensi" />
            <Field
              label="Deskripsi"
              value={description}
              onChangeText={setDescription}
              placeholder="Detail aktivitas"
              multiline
              style={{ minHeight: 70 }}
            />

            <Text style={styles.label}>Status</Text>
            <View style={styles.chips}>
              {statuses.map((item) => (
                <TouchableOpacity key={item} style={[styles.chip, status === item && styles.chipActive]} onPress={() => setStatus(item)}>
                  <Text style={[styles.chipText, status === item && styles.chipTextActive]}>{item.replace(/_/g, ' ')}</Text>
                </TouchableOpacity>
              ))}
            </View>

            <Text style={styles.label}>Prioritas</Text>
            <View style={styles.chips}>
              {priorities.map((item) => (
                <TouchableOpacity key={item} style={[styles.chip, priority === item && styles.chipActive]} onPress={() => setPriority(item)}>
                  <Text style={[styles.chipText, priority === item && styles.chipTextActive]}>{item}</Text>
                </TouchableOpacity>
              ))}
            </View>

            <Button label={saving ? 'Menyimpan...' : 'Simpan aktivitas'} onPress={submit} disabled={saving} />
          </View>
        </View>
      </Modal>
    </Screen>
  );
};

const styles = StyleSheet.create({
  title: { fontSize: 22, fontWeight: '800', color: colors.text },
  muted: { fontSize: 11, color: colors.muted },
  rowBetween: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', gap: 8 },
  addButton: { width: 40, height: 40, borderRadius: 14, backgroundColor: colors.primary, alignItems: 'center', justifyContent: 'center' },
  tabs: { flexDirection: 'row', backgroundColor: '#eef2f7', borderRadius: radius.md, padding: 4, gap: 4 },
  tab: { flex: 1, alignItems: 'center', paddingVertical: 8, borderRadius: radius.sm },
  tabActive: { backgroundColor: '#fff' },
  tabText: { fontSize: 12, color: colors.muted },
  tabTextActive: { color: colors.primary, fontWeight: '700' },
  activityTitle: { fontWeight: '700', color: colors.text, flex: 1 },
  backdrop: { flex: 1, backgroundColor: 'rgba(15,23,42,0.5)', justifyContent: 'flex-end' },
  modal: { backgroundColor: '#fff', borderTopLeftRadius: 24, borderTopRightRadius: 24, padding: spacing.xl, gap: spacing.sm, maxHeight: '92%' },
  modalTitle: { fontSize: 18, fontWeight: '800', color: colors.text },
  label: { fontSize: 12, fontWeight: '700', color: colors.muted, marginTop: 4 },
  chips: { flexDirection: 'row', flexWrap: 'wrap', gap: spacing.sm },
  chip: { borderWidth: 1, borderColor: colors.border, borderRadius: radius.full, paddingHorizontal: 12, paddingVertical: 6 },
  chipActive: { backgroundColor: colors.primary, borderColor: colors.primary },
  chipText: { fontSize: 11, color: colors.muted },
  chipTextActive: { color: '#fff', fontWeight: '700' },
});
