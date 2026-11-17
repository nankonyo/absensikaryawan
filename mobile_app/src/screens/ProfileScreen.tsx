import React, { useState } from 'react';
import { Alert, Image, StyleSheet, Text, TouchableOpacity, View } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import * as ImagePicker from 'expo-image-picker';
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
  const [avatarFile, setAvatarFile] = useState<{ uri: string; name: string; type: string } | null>(null);
  const [avatarPreview, setAvatarPreview] = useState<string | null>(user?.avatar_url ?? null);
  const [currentPassword, setCurrentPassword] = useState('');
  const [newPassword, setNewPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');
  const [changingPassword, setChangingPassword] = useState(false);

  const pickAvatar = async (source: 'camera' | 'gallery') => {
    const permission = source === 'camera'
      ? await ImagePicker.requestCameraPermissionsAsync()
      : await ImagePicker.requestMediaLibraryPermissionsAsync();

    if (permission.status !== 'granted') {
      Alert.alert('Izin diperlukan', source === 'camera' ? 'Izinkan akses kamera.' : 'Izinkan akses galeri.');
      return;
    }

    const result = source === 'camera'
      ? await ImagePicker.launchCameraAsync({
          mediaTypes: ['images'],
          quality: 0.7,
          allowsEditing: true,
          aspect: [1, 1],
          cameraType: ImagePicker.CameraType.front,
        })
      : await ImagePicker.launchImageLibraryAsync({
          mediaTypes: ['images'],
          quality: 0.7,
          allowsEditing: true,
          aspect: [1, 1],
        });

    if (!result.canceled && result.assets?.[0]) {
      const asset = result.assets[0];
      setAvatarFile({
        uri: asset.uri,
        name: asset.fileName || 'avatar.jpg',
        type: asset.mimeType || 'image/jpeg',
      });
      setAvatarPreview(asset.uri);
    }
  };

  const chooseAvatar = () => {
    Alert.alert('Foto Profil', 'Pilih sumber foto', [
      { text: 'Kamera', onPress: () => pickAvatar('camera') },
      { text: 'Galeri', onPress: () => pickAvatar('gallery') },
      { text: 'Batal', style: 'cancel' },
    ]);
  };

  const save = async () => {
    setSaving(true);
    try {
      if (avatarFile) {
        const formData = new FormData();
        formData.append('name', name);
        if (phone) formData.append('phone', phone);
        formData.append('avatar', avatarFile as any);
        await profileApi.update(formData);
      } else {
        await profileApi.update({ name, phone });
      }
      await refresh();
      Alert.alert('Berhasil', 'Profil diperbarui.');
    } catch (err) {
      Alert.alert('Gagal', apiMessage(err));
    } finally {
      setSaving(false);
    }
  };

  const changePassword = async () => {
    if (!currentPassword || !newPassword || !confirmPassword) {
      Alert.alert('Lengkapi data', 'Semua kolom password wajib diisi.');
      return;
    }
    if (newPassword.length < 8) {
      Alert.alert('Password terlalu pendek', 'Password baru minimal 8 karakter.');
      return;
    }
    if (newPassword !== confirmPassword) {
      Alert.alert('Password tidak sama', 'Konfirmasi password baru tidak cocok.');
      return;
    }

    setChangingPassword(true);
    try {
      await profileApi.updatePassword({
        current_password: currentPassword,
        password: newPassword,
        password_confirmation: confirmPassword,
      });
      setCurrentPassword('');
      setNewPassword('');
      setConfirmPassword('');
      Alert.alert('Berhasil', 'Password berhasil diubah.');
    } catch (err) {
      Alert.alert('Gagal mengubah password', apiMessage(err));
    } finally {
      setChangingPassword(false);
    }
  };

  return (
    <Screen>
      <Card style={styles.hero}>
        <TouchableOpacity style={styles.avatarWrap} onPress={chooseAvatar}>
          {avatarPreview ? (
            <Image source={{ uri: avatarPreview }} style={styles.avatar} />
          ) : (
            <View style={styles.avatar}>
              <Text style={styles.avatarText}>{(user?.name ?? '?').slice(0, 1).toUpperCase()}</Text>
            </View>
          )}
          <View style={styles.cameraBadge}>
            <Ionicons name="camera" size={13} color="#fff" />
          </View>
        </TouchableOpacity>
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
        <TouchableOpacity style={styles.choosePhoto} onPress={chooseAvatar}>
          <Ionicons name="camera-outline" size={18} color={colors.primary} />
          <Text style={styles.choosePhotoText}>Kamera / Galeri</Text>
        </TouchableOpacity>
        <Field label="Nama" value={name} onChangeText={setName} />
        <Field label="Telepon" value={phone} onChangeText={setPhone} keyboardType="phone-pad" />
        <Button label={saving ? 'Menyimpan...' : 'Simpan'} onPress={save} disabled={saving} />
      </Card>

      <SectionTitle title="Ubah Password" />
      <Card style={{ gap: spacing.md }}>
        <Field
          label="Password saat ini"
          value={currentPassword}
          onChangeText={setCurrentPassword}
          placeholder="Password saat ini"
          secureTextEntry
          autoCapitalize="none"
        />
        <Field
          label="Password baru"
          value={newPassword}
          onChangeText={setNewPassword}
          placeholder="Minimal 8 karakter"
          secureTextEntry
          autoCapitalize="none"
        />
        <Field
          label="Konfirmasi password baru"
          value={confirmPassword}
          onChangeText={setConfirmPassword}
          placeholder="Ulangi password baru"
          secureTextEntry
          autoCapitalize="none"
        />
        <Button
          label={changingPassword ? 'Menyimpan...' : 'Ubah Password'}
          onPress={changePassword}
          disabled={changingPassword}
        />
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
  avatarWrap: { position: 'relative' },
  avatar: {
    width: 60,
    height: 60,
    borderRadius: 20,
    backgroundColor: colors.primarySoft,
    alignItems: 'center',
    justifyContent: 'center',
  },
  cameraBadge: {
    position: 'absolute',
    right: -4,
    bottom: -4,
    width: 22,
    height: 22,
    borderRadius: 11,
    backgroundColor: colors.primary,
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 2,
    borderColor: '#fff',
  },
  choosePhoto: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 8,
    borderWidth: 1,
    borderColor: colors.border,
    borderRadius: radius.md,
    paddingVertical: 10,
  },
  choosePhotoText: { fontSize: 12, fontWeight: '700', color: colors.primary },
  avatarText: { fontSize: 24, fontWeight: '800', color: colors.primary },
  name: { fontSize: 20, fontWeight: '800', color: colors.text },
  muted: { fontSize: 12, color: colors.muted },
  row: { flexDirection: 'row', justifyContent: 'space-between', gap: spacing.md },
  value: { fontSize: 12, fontWeight: '600', color: colors.text, flex: 1, textAlign: 'right' },
});
