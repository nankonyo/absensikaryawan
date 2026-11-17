import React, { useState } from 'react';
import { Alert, StyleSheet, Text, TouchableOpacity, View } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { Screen } from '../components/Screen';
import { Button, Card, SectionTitle } from '../components/ui';
import { Field } from '../components/Field';
import { profileApi } from '../api/endpoints';
import { apiMessage } from '../api/client';
import { useAuth } from '../context/AuthContext';
import { colors, radius, spacing } from '../theme';

export const ProfileScreen = () => {
  const { user, logout, refresh } = useAuth();
  const [name, setName] = useState(user?.name ?? '');
  const [phone, setPhone] = useState(user?.phone ?? '');
  const [saving, setSaving] = useState(false);

  const save = async () => {
    setSaving(true);
    try {
      await profileApi.update({ name, phone });
      await refresh();
      Alert.alert('Berhasil', 'Profil diperbarui.');
    } catch (err) {
      Alert.alert('Gagal', apiMessage(err));
    } finally {
      setSaving(false);
    }
  };

  return (
    <Screen>
      <Card style={styles.hero}>
        <View style={styles.avatar}>
          <Text style={styles.avatarText}>{(user?.name ?? '?').slice(0, 1).toUpperCase()}</Text>
        </View>
        <View style={{ flex: 1 }}>
          <Text style={styles.name}>{user?.name}</Text>
          <Text style={styles.muted}>{user?.email}</Text>
          <Text style={styles.muted}>{user?.role.display_name ?? user?.role.name}</Text>
        </View>
      </Card>

      <SectionTitle title="Data Kepegawaian" />
      <Card style={{ gap: spacing.sm }}>
        <Info label="NIP / Employee ID" value={user?.employee_id ?? '-'} />
        <Info label="Departemen" value={user?.department ?? '-'} />
        <Info label="Unit" value={user?.unit ?? '-'} />
        <Info label="Jabatan" value={user?.position ?? '-'} />
        <Info label="Atasan" value={user?.supervisor ?? '-'} />
      </Card>

      <SectionTitle title="Ubah Profil" />
      <Card style={{ gap: spacing.md }}>
        <Field label="Nama" value={name} onChangeText={setName} />
        <Field label="Telepon" value={phone} onChangeText={setPhone} keyboardType="phone-pad" />
        <Button label={saving ? 'Menyimpan...' : 'Simpan'} onPress={save} disabled={saving} />
      </Card>

      <Button
        label="Keluar"
        variant="danger"
        onPress={async () => {
          await logout();
        }}
      />
    </Screen>
  );
};

const Info = ({ label, value }: { label: string; value: string }) => (
  <View style={styles.row}>
    <Text style={styles.muted}>{label}</Text>
    <Text style={styles.value}>{value}</Text>
  </View>
);

const styles = StyleSheet.create({
  hero: { flexDirection: 'row', alignItems: 'center', gap: spacing.md },
  avatar: {
    width: 60,
    height: 60,
    borderRadius: 20,
    backgroundColor: colors.primarySoft,
    alignItems: 'center',
    justifyContent: 'center',
  },
  avatarText: { fontSize: 24, fontWeight: '800', color: colors.primary },
  name: { fontSize: 20, fontWeight: '800', color: colors.text },
  muted: { fontSize: 12, color: colors.muted },
  row: { flexDirection: 'row', justifyContent: 'space-between', gap: spacing.md },
  value: { fontSize: 12, fontWeight: '600', color: colors.text, flex: 1, textAlign: 'right' },
});
