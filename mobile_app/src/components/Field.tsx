import React, { useState } from 'react';
import { StyleSheet, Text, TextInput, TextInputProps, TouchableOpacity, View } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { colors, radius, spacing } from '../theme';

type Props = TextInputProps & {
  label?: string;
  error?: string | null;
  inputRef?: React.Ref<React.ComponentRef<typeof TextInput>>;
};

export const Field = ({ label, error, style, secureTextEntry, inputRef, ...props }: Props) => {
  const [visible, setVisible] = useState(false);
  const isPassword = Boolean(secureTextEntry);

  return (
    <View style={styles.wrap}>
      {label ? <Text style={styles.label}>{label}</Text> : null}
      <View style={styles.inputWrap}>
        <TextInput
          ref={inputRef}
          placeholderTextColor={colors.subtle}
          style={[styles.input, isPassword && styles.inputSecure, error ? styles.inputError : null, style]}
          secureTextEntry={isPassword && !visible}
          {...props}
        />
        {isPassword ? (
          <TouchableOpacity
            style={styles.eye}
            onPress={() => setVisible((value) => !value)}
            hitSlop={{ top: 10, bottom: 10, left: 10, right: 10 }}
            accessibilityRole="button"
            accessibilityLabel={visible ? 'Sembunyikan password' : 'Tampilkan password'}
          >
            <Ionicons name={visible ? 'eye-off-outline' : 'eye-outline'} size={20} color={colors.muted} />
          </TouchableOpacity>
        ) : null}
      </View>
      {error ? <Text style={styles.error}>{error}</Text> : null}
    </View>
  );
};

const styles = StyleSheet.create({
  wrap: { gap: 6 },
  label: { fontSize: 13, fontWeight: '600', color: colors.muted },
  inputWrap: { position: 'relative', justifyContent: 'center' },
  input: {
    backgroundColor: '#fff',
    borderWidth: 1,
    borderColor: colors.border,
    borderRadius: radius.md,
    paddingHorizontal: spacing.md,
    paddingVertical: 12,
    fontSize: 14,
    color: colors.text,
  },
  inputSecure: { paddingRight: 44 },
  inputError: { borderColor: colors.danger },
  eye: { position: 'absolute', right: 12, height: '100%', justifyContent: 'center' },
  error: { fontSize: 12, color: colors.danger },
});
