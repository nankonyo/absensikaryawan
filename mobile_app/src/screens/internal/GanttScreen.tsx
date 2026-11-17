import React, { useCallback, useEffect, useState } from 'react';
import { ActivityIndicator, Alert, StyleSheet, Text, View } from 'react-native';
import { Screen } from '../../components/Screen';
import { Card, EmptyState } from '../../components/ui';
import { projectApi } from '../../api/endpoints';
import { apiMessage } from '../../api/client';
import type { Project, Task } from '../../types';
import { colors, spacing } from '../../theme';

export const GanttScreen = () => {
  const [tasks, setTasks] = useState<Task[]>([]);
  const [projects, setProjects] = useState<Project[]>([]);
  const [loading, setLoading] = useState(true);

  const load = useCallback(async () => {
    try {
      const response = await projectApi.gantt();
      setTasks(response.data.tasks);
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

  const dates = tasks.flatMap((task) => [task.start_date, task.due_date]).filter(Boolean) as string[];
  const min = dates.length ? new Date(dates.reduce((a, b) => (a < b ? a : b))) : null;
  const max = dates.length ? new Date(dates.reduce((a, b) => (a > b ? a : b))) : null;
  const totalDays = min && max ? Math.max(1, Math.ceil((max.getTime() - min.getTime()) / 86400000) + 1) : 1;

  return (
    <Screen back>
      <View>
        <Text style={styles.title}>Gantt Chart</Text>
        <Text style={styles.muted}>Timeline task project berdasarkan tanggal.</Text>
      </View>

      {loading ? <ActivityIndicator color={colors.primary} /> : null}
      {!loading && tasks.length === 0 ? <EmptyState message="Belum ada task dengan tanggal." /> : null}

      {min && max ? (
        <Text style={styles.muted}>
          {min.toLocaleDateString('id-ID')} — {max.toLocaleDateString('id-ID')}
        </Text>
      ) : null}

      {tasks.map((task) => {
        const start = task.start_date ? new Date(task.start_date) : null;
        const end = task.due_date ? new Date(task.due_date) : null;
        const offset = start && min ? Math.max(0, Math.ceil((start.getTime() - min.getTime()) / 86400000)) : 0;
        const duration = start && end ? Math.max(1, Math.ceil((end.getTime() - start.getTime()) / 86400000) + 1) : 1;
        const left = (offset / totalDays) * 100;
        const width = Math.max(2, (duration / totalDays) * 100);

        return (
          <Card key={task.id} style={{ gap: 6 }}>
            <View style={styles.rowBetween}>
              <Text style={styles.taskTitle}>{task.title}</Text>
              <Text style={styles.muted}>{task.project_name}</Text>
            </View>
            <View style={styles.track}>
              <View style={[styles.fill, { left: `${left}%`, width: `${width}%` }]} />
            </View>
            <View style={styles.rowBetween}>
              <Text style={styles.muted}>{task.start_date ?? '-'}</Text>
              <Text style={styles.muted}>{task.due_date ?? '-'}</Text>
            </View>
          </Card>
        );
      })}
    </Screen>
  );
};

const styles = StyleSheet.create({
  title: { fontSize: 22, fontWeight: '800', color: colors.text },
  muted: { fontSize: 11, color: colors.muted },
  rowBetween: { flexDirection: 'row', justifyContent: 'space-between', gap: 8 },
  taskTitle: { fontWeight: '700', color: colors.text, flex: 1 },
  track: { height: 20, backgroundColor: '#eef2f7', borderRadius: 999, overflow: 'hidden', position: 'relative' },
  fill: { position: 'absolute', top: 0, bottom: 0, backgroundColor: colors.primary, borderRadius: 999 },
});
