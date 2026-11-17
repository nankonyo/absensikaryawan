import React from 'react';
import { StyleSheet, Text, TouchableOpacity, View, ViewStyle } from 'react-native';
import { colors, radius, shadow, spacing } from '../theme';

export const Card = ({ children, style }: { children: React.ReactNode; style?: ViewStyle }) => (
  <View style={[styles.card, style]}>{children}</View>
);

export const SectionTitle = ({ title, action }: { title: string; action?: React.ReactNode }) => (
  <View style={styles.sectionRow}>
    <Text style={styles.sectionTitle}>{title}</Text>
    {action}
  </View>
);

export const Button = ({
  label,
  onPress,
  variant = 'primary',
  disabled,
  icon,
  style,
}: {
  label: string;
  onPress: () => void;
  variant?: 'primary' | 'secondary' | 'danger' | 'ghost';
  disabled?: boolean;
  icon?: React.ReactNode;
  style?: ViewStyle;
}) => {
  const palette =
    variant === 'primary'
      ? styles.primary
      : variant === 'danger'
        ? styles.danger
        : variant === 'ghost'
          ? styles.ghost
          : styles.secondary;

  return (
    <TouchableOpacity
      onPress={onPress}
      disabled={disabled}
      activeOpacity={0.8}
      style={[styles.button, palette, disabled && styles.disabled, style]}
    >
      {icon}
      <Text style={[styles.buttonText, variant === 'secondary' && styles.secondaryText, variant === 'ghost' && styles.ghostText]}>
        {label}
      </Text>
    </TouchableOpacity>
  );
};

export const Badge = ({ label, bg, color }: { label: string; bg?: string; color?: string }) => (
  <View style={[styles.badge, { backgroundColor: bg ?? colors.primarySoft }]}>
    <Text style={[styles.badgeText, { color: color ?? colors.primary }]}>{label}</Text>
  </View>
);

export const EmptyState = ({ message }: { message: string }) => (
  <View style={styles.empty}>
    <Text style={styles.emptyText}>{message}</Text>
  </View>
);

const styles = StyleSheet.create({
  card: {
    backgroundColor: colors.card,
    borderRadius: radius.lg,
    borderWidth: 1,
    borderColor: colors.border,
    padding: spacing.lg,
    ...shadow.card,
  },
  sectionRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    marginTop: spacing.sm,
  },
  sectionTitle: { fontSize: 16, fontWeight: '700', color: colors.text },
  button: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 8,
    borderRadius: radius.md,
    paddingVertical: 12,
    paddingHorizontal: spacing.lg,
  },
  primary: { backgroundColor: colors.primary },
  secondary: { backgroundColor: '#eef2f7' },
  danger: { backgroundColor: colors.danger },
  ghost: { backgroundColor: 'transparent' },
  disabled: { opacity: 0.5 },
  buttonText: { color: '#fff', fontWeight: '700', fontSize: 14 },
  secondaryText: { color: colors.text },
  ghostText: { color: colors.primary },
  badge: { alignSelf: 'flex-start', borderRadius: radius.full, paddingHorizontal: 10, paddingVertical: 4 },
  badgeText: { fontSize: 11, fontWeight: '700' },
  empty: { paddingVertical: spacing.xl, alignItems: 'center' },
  emptyText: { color: colors.subtle, fontSize: 13 },
});
