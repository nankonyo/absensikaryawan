import React, { useCallback, useEffect, useState } from 'react';
import { ActivityIndicator, Alert, Modal, StyleSheet, Text, TouchableOpacity, View } from 'react-native';
import { useNavigation } from '@react-navigation/native';
import { Ionicons } from '@expo/vector-icons';
import { Screen } from '../../components/Screen';
import { Button, Card, EmptyState } from '../../components/ui';
import { Field } from '../../components/Field';
import { useAuth } from '../../context/AuthContext';
import { can } from '../../utils/permissions';
import { projectApi } from '../../api/endpoints';
import { apiMessage } from '../../api/client';
import type { Project } from '../../types';
import { colors, radius, spacing } from '../../theme';

const projectStatuses = ['planning', 'in_progress', 'on_hold', 'completed'];

export const ProjectsScreen = () => {
  const navigation = useNavigation<any>();
  const { user } = useAuth();
  const [projects, setProjects] = useState<Project[]>([]);
  const [search, setSearch] = useState('');
  const [users, setUsers] = useState<{ id: number; name: string }[]>([]);
  const [memberIds, setMemberIds] = useState<number[]>([]);
  const [loading, setLoading] = useState(true);
  const [open, setOpen] = useState(false);
  const [saving, setSaving] = useState(false);
  const [name, setName] = useState('');
  const [code, setCode] = useState('');
  const [description, setDescription] = useState('');
  const [startDate, setStartDate] = useState('');
  const [endDate, setEndDate] = useState('');
  const [status, setStatus] = useState('planning');

  const canCreate = can(user, 'internalapp.project.project', 'create');

  const load = useCallback(async (q?: string) => {
    try {
      const response = await projectApi.index({ q: q || undefined });
      setProjects(response.data.data);
      setUsers(response.data.users ?? []);
    } catch (err) {
      Alert.alert('Gagal memuat', apiMessage(err));
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    load();
  }, [load]);

  const submit = async () => {
    if (!name.trim()) {
      Alert.alert('Form belum lengkap', 'Nama project wajib diisi.');
      return;
    }

    setSaving(true);
    try {
      await projectApi.create({
        name: name.trim(),
        code: code.trim() || undefined,
        description: description.trim() || undefined,
        start_date: startDate.trim() || undefined,
        end_date: endDate.trim() || undefined,
        status,
        members: memberIds,
      });
      setOpen(false);
      setName('');
      setCode('');
      setDescription('');
      setStartDate('');
      setEndDate('');
      setStatus('planning');
      setMemberIds([]);
      await load();
      Alert.alert('Berhasil', 'Project berhasil dibuat.');
    } catch (err) {
      Alert.alert('Gagal menyimpan', apiMessage(err));
    } finally {
      setSaving(false);
    }
  };

  return (
    <Screen back>
      <View style={styles.header}>
        <View style={{ flex: 1 }}>
          <Text style={styles.title}>Project</Text>
          <Text style={styles.muted}>Daftar project dan progres terbaru.</Text>
        </View>
        {canCreate ? (
          <TouchableOpacity style={styles.addButton} onPress={() => setOpen(true)}>
            <Ionicons name="add" size={20} color="#fff" />
          </TouchableOpacity>
        ) : null}
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

      <Modal visible={open} transparent animationType="slide" onRequestClose={() => setOpen(false)}>
        <View style={styles.backdrop}>
          <View style={styles.modal}>
            <View style={styles.rowBetween}>
              <Text style={styles.modalTitle}>Project Baru</Text>
              <TouchableOpacity onPress={() => setOpen(false)}>
                <Ionicons name="close" size={22} color={colors.muted} />
              </TouchableOpacity>
            </View>

            <Field label="Nama project *" value={name} onChangeText={setName} placeholder="Contoh: Portal Internal" />
            <Field label="Kode" value={code} onChangeText={setCode} placeholder="Opsional" />
            <Field
              label="Deskripsi"
              value={description}
              onChangeText={setDescription}
              placeholder="Deskripsi project"
              multiline
              style={{ minHeight: 70 }}
            />
            <View style={styles.dateRow}>
              <View style={{ flex: 1 }}>
                <Field label="Tanggal mulai" value={startDate} onChangeText={setStartDate} placeholder="YYYY-MM-DD" />
              </View>
              <View style={{ flex: 1 }}>
                <Field label="Tanggal selesai" value={endDate} onChangeText={setEndDate} placeholder="YYYY-MM-DD" />
              </View>
            </View>

            <Text style={styles.label}>Status</Text>
            <View style={styles.chips}>
              {projectStatuses.map((item) => (
                <TouchableOpacity
                  key={item}
                  style={[styles.chip, status === item && styles.chipActive]}
                  onPress={() => setStatus(item)}
                >
                  <Text style={[styles.chipText, status === item && styles.chipTextActive]}>
                    {item === 'in_progress' ? 'In Progress' : item.replace(/_/g, ' ')}
                  </Text>
                </TouchableOpacity>
              ))}
            </View>

            <Text style={styles.label}>Tim Project (multiple)</Text>
            <View style={styles.chips}>
              {users.map((member) => {
                const active = memberIds.includes(member.id);
                return (
                  <TouchableOpacity
                    key={member.id}
                    style={[styles.chip, active && styles.chipActive]}
                    onPress={() =>
                      setMemberIds((ids) =>
                        ids.includes(member.id) ? ids.filter((id) => id !== member.id) : [...ids, member.id]
                      )
                    }
                  >
                    <Text style={[styles.chipText, active && styles.chipTextActive]}>{member.name}</Text>
                  </TouchableOpacity>
                );
              })}
            </View>

            <Button label={saving ? 'Menyimpan...' : 'Simpan project'} onPress={submit} disabled={saving} />
          </View>
        </View>
      </Modal>
    </Screen>
  );
};

const styles = StyleSheet.create({
  header: { flexDirection: 'row', alignItems: 'center', gap: spacing.sm },
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
  addButton: { width: 40, height: 40, borderRadius: 14, backgroundColor: colors.primary, alignItems: 'center', justifyContent: 'center' },
  backdrop: { flex: 1, backgroundColor: 'rgba(15,23,42,0.5)', justifyContent: 'flex-end' },
  modal: { backgroundColor: '#fff', borderTopLeftRadius: 24, borderTopRightRadius: 24, padding: spacing.xl, gap: spacing.sm, maxHeight: '92%' },
  modalTitle: { fontSize: 18, fontWeight: '800', color: colors.text, flex: 1 },
  label: { fontSize: 12, fontWeight: '700', color: colors.muted, marginTop: 4 },
  dateRow: { flexDirection: 'row', gap: spacing.sm },
  chips: { flexDirection: 'row', flexWrap: 'wrap', gap: spacing.sm },
  chip: { borderWidth: 1, borderColor: colors.border, borderRadius: radius.full, paddingHorizontal: 12, paddingVertical: 6 },
  chipActive: { backgroundColor: colors.primary, borderColor: colors.primary },
  chipText: { fontSize: 11, color: colors.muted },
  chipTextActive: { color: '#fff', fontWeight: '700' },
});
