import React, { useCallback, useEffect, useState } from 'react';
import { ActivityIndicator, Alert, Linking, StyleSheet, Text, TouchableOpacity, View } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { Screen } from '../../components/Screen';
import { Badge, Card, EmptyState } from '../../components/ui';
import { contentApi } from '../../api/endpoints';
import { API_URL, apiMessage } from '../../api/client';
import type { ContentItem } from '../../types';
import { colors, radius, spacing } from '../../theme';

const WEB_BASE = API_URL.replace(/\/api\/mobile\/?$/, '');

type TabKey = 'videos' | 'dokumentasi' | 'pengumuman';

export const ContentsScreen = () => {
  const [tab, setTab] = useState<TabKey>('videos');
  const [items, setItems] = useState<ContentItem[]>([]);
  const [loading, setLoading] = useState(true);

  const load = useCallback(async (key: TabKey) => {
    setLoading(true);
    try {
      const response = key === 'videos' ? await contentApi.videos() : key === 'dokumentasi' ? await contentApi.dokumentasi() : await contentApi.pengumuman();
      setItems(response.data.items);
    } catch (err) {
      Alert.alert('Gagal memuat', apiMessage(err));
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    load(tab);
  }, [load, tab]);

  const open = (url?: string | null) => {
    if (!url) return;
    const normalized = url.startsWith('http')
      ? url
      : `${WEB_BASE}${url.startsWith('/') ? '' : '/'}${url}`;
    Linking.openURL(normalized).catch(() => Alert.alert('Gagal membuka tautan'));
  };

  return (
    <Screen>
      <View>
        <Text style={styles.title}>Konten</Text>
        <Text style={styles.muted}>Video tutorial, dokumentasi, dan pengumuman.</Text>
      </View>

      <View style={styles.tabs}>
        {(
          [
            ['videos', 'Video'],
            ['dokumentasi', 'Dokumentasi'],
            ['pengumuman', 'Pengumuman'],
          ] as [TabKey, string][]
        ).map(([key, label]) => (
          <TouchableOpacity key={key} style={[styles.tab, tab === key && styles.tabActive]} onPress={() => setTab(key)}>
            <Text style={[styles.tabText, tab === key && styles.tabTextActive]}>{label}</Text>
          </TouchableOpacity>
        ))}
      </View>

      {loading ? <ActivityIndicator color={colors.primary} /> : null}
      {!loading && items.length === 0 ? <EmptyState message="Belum ada konten." /> : null}

      {items.map((item) => (
        <Card key={item.id} style={{ gap: 6 }}>
          <View style={styles.rowBetween}>
            <Text style={styles.itemTitle}>{item.title}</Text>
            {item.badge_text ? (
              <Badge label={item.badge_text} bg={colors.infoSoft} color={colors.info} />
            ) : null}
          </View>
          {item.description ? <Text style={styles.muted}>{item.description}</Text> : null}
          {item.content ? (
            <Text style={styles.muted} numberOfLines={3}>
              {item.content.replace(/<[^>]+>/g, '')}
            </Text>
          ) : null}
          <View style={styles.actions}>
            {item.youtube_url ? (
              <TouchableOpacity style={styles.btn} onPress={() => open(item.youtube_url)}>
                <Ionicons name="play" size={14} color={colors.primary} />
                <Text style={styles.btnText}>Tonton</Text>
              </TouchableOpacity>
            ) : null}
            {item.file_url ? (
              <TouchableOpacity style={styles.btn} onPress={() => open(item.file_url)}>
                <Ionicons name="document-text" size={14} color={colors.primary} />
                <Text style={styles.btnText}>Buka file</Text>
              </TouchableOpacity>
            ) : null}
            {item.pdf_path ? (
              <TouchableOpacity style={styles.btn} onPress={() => open(item.pdf_path)}>
                <Ionicons name="document" size={14} color={colors.primary} />
                <Text style={styles.btnText}>Lihat PDF</Text>
              </TouchableOpacity>
            ) : null}
          </View>
        </Card>
      ))}
    </Screen>
  );
};

const styles = StyleSheet.create({
  title: { fontSize: 22, fontWeight: '800', color: colors.text },
  muted: { fontSize: 12, color: colors.muted },
  tabs: { flexDirection: 'row', backgroundColor: '#eef2f7', borderRadius: radius.md, padding: 4, gap: 4 },
  tab: { flex: 1, alignItems: 'center', paddingVertical: 8, borderRadius: radius.sm },
  tabActive: { backgroundColor: '#fff' },
  tabText: { fontSize: 11, color: colors.muted },
  tabTextActive: { color: colors.primary, fontWeight: '700' },
  rowBetween: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', gap: 8 },
  itemTitle: { fontWeight: '700', color: colors.text, flex: 1 },
  actions: { flexDirection: 'row', flexWrap: 'wrap', gap: spacing.sm, marginTop: 4 },
  btn: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
    borderWidth: 1,
    borderColor: colors.border,
    borderRadius: radius.full,
    paddingHorizontal: 12,
    paddingVertical: 6,
  },
  btnText: { fontSize: 11, fontWeight: '600', color: colors.primary },
});
