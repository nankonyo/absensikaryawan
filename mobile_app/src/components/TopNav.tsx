import React, { useEffect, useState } from 'react';
import { Image, StyleSheet, Text, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { useAuth } from '../context/AuthContext';
import { appApi } from '../api/endpoints';
import { colors, spacing } from '../theme';

export const TopNav = () => {
  const { user } = useAuth();
  const [appName, setAppName] = useState('RST Internal App');
  const [logoUrl, setLogoUrl] = useState<string | null>(null);

  useEffect(() => {
    let active = true;

    appApi
      .info()
      .then(({ data }) => {
        if (!active) return;
        if (data.app_name) setAppName(data.app_name);
        if (data.logo_url) setLogoUrl(data.logo_url);
      })
      .catch(() => {});

    return () => {
      active = false;
    };
  }, []);

  const role = user?.role.display_name ?? user?.role.name ?? '-';

  return (
    <SafeAreaView edges={['top']} style={styles.safe}>
      <View style={styles.rowOne}>
        {logoUrl ? (
          <Image source={{ uri: logoUrl }} style={styles.logo} resizeMode="contain" />
        ) : (
          <View style={styles.logoFallback}>
            <Text style={styles.logoText}>{appName.slice(0, 1).toUpperCase()}</Text>
          </View>
        )}
        <Text style={styles.appName} numberOfLines={1}>
          {appName}
        </Text>
      </View>

      <View style={styles.rowTwo}>
        <View style={styles.nameRow}>
          <Text style={styles.meta} numberOfLines={1}>
            <Text style={styles.metaLabel}>NIP </Text>
            {user?.employee_id || '-'}
          </Text>
          <Text style={styles.dot}>•</Text>
          <Text style={styles.metaName} numberOfLines={1}>
            {user?.name || '-'}
          </Text>
        </View>

        <View style={styles.smallRow}>
          <Text style={styles.smallMeta} numberOfLines={1}>
            {user?.email || '-'}
          </Text>
          <Text style={styles.smallDot}>•</Text>
          <Text style={styles.smallMeta} numberOfLines={1}>
            {role}
          </Text>
        </View>
      </View>
    </SafeAreaView>
  );
};

const styles = StyleSheet.create({
  safe: {
    backgroundColor: '#fff',
    borderBottomWidth: 1,
    borderBottomColor: colors.border,
    paddingHorizontal: spacing.lg,
    paddingBottom: spacing.sm,
  },
  rowOne: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    paddingTop: spacing.sm,
    paddingBottom: spacing.sm,
    borderBottomWidth: 1,
    borderBottomColor: colors.border,
  },
  logo: { width: 28, height: 28, borderRadius: 8, backgroundColor: '#fff' },
  logoFallback: {
    width: 28,
    height: 28,
    borderRadius: 8,
    backgroundColor: colors.primary,
    alignItems: 'center',
    justifyContent: 'center',
  },
  logoText: { color: '#fff', fontWeight: '800', fontSize: 14 },
  appName: { flex: 1, fontSize: 17, fontWeight: '800', color: colors.text },
  rowTwo: {
    marginTop: spacing.sm,
    gap: 2,
  },
  nameRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
  },
  smallRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 5,
  },
  meta: { fontSize: 11, color: colors.muted, flexShrink: 1 },
  metaName: { fontSize: 12, color: colors.text, fontWeight: '600', flexShrink: 1 },
  metaLabel: { fontWeight: '700', color: colors.text },
  smallMeta: { fontSize: 10, color: colors.subtle, flexShrink: 1 },
  smallDot: { fontSize: 9, color: colors.subtle },
  dot: { fontSize: 10, color: colors.subtle },
});
