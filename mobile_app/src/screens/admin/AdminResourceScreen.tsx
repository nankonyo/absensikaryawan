import React, { useCallback, useState } from 'react';
import {
  ActivityIndicator,
  Alert,
  Image,
  Modal,
  ScrollView,
  StyleSheet,
  Text,
  TextInput,
  TouchableOpacity,
  View,
} from 'react-native';
import { RouteProp, useFocusEffect, useRoute } from '@react-navigation/native';
import { Ionicons } from '@expo/vector-icons';
import * as ImagePicker from 'expo-image-picker';
import * as DocumentPicker from 'expo-document-picker';
import { Screen } from '../../components/Screen';
import { Badge, Card, EmptyState } from '../../components/ui';
import { Field } from '../../components/Field';
import { adminApi, approvalApi } from '../../api/endpoints';
import { apiMessage } from '../../api/client';
import type { MoreStackParamList } from '../../navigation/types';
import { colors, radius, spacing, statusColor } from '../../theme';

type AdminField = {
  name: string;
  label: string;
  type: string;
  required?: boolean;
  create_only?: boolean;
  accept?: string;
  mimes?: string;
  max?: number;
  path?: string;
  options?: any[];
};

type AdminItem = Record<string, any> & {
  id: number;
  title?: string;
  subtitle?: string;
  meta?: string;
};

export const AdminResourceScreen = () => {
  const route = useRoute<RouteProp<MoreStackParamList, 'AdminResource'>>();
  const { resource, title } = route.params;

  const [schema, setSchema] = useState<{ read_only: boolean; fields: AdminField[] } | null>(null);
  const [items, setItems] = useState<AdminItem[]>([]);
  const [page, setPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);
  const [search, setSearch] = useState('');
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [modalOpen, setModalOpen] = useState(false);
  const [editing, setEditing] = useState<AdminItem | null>(null);
  const [form, setForm] = useState<Record<string, any>>({});

  const load = useCallback(
    async (nextPage = page, query = search) => {
      setLoading(true);
      try {
        const [schemaResponse, listResponse] = await Promise.all([
          adminApi.schema(resource),
          adminApi.list(resource, { q: query || undefined, page: nextPage, per_page: 20 }),
        ]);
        setSchema({ read_only: schemaResponse.data.read_only, fields: schemaResponse.data.fields });
        setItems(listResponse.data.data.data || []);
        setPage(listResponse.data.data.current_page || 1);
        setLastPage(listResponse.data.data.last_page || 1);
      } catch (error) {
        Alert.alert('Gagal memuat', apiMessage(error));
      } finally {
        setLoading(false);
      }
    },
    [page, resource, search],
  );

  useFocusEffect(
    useCallback(() => {
      load(1, search);
      // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [resource]),
  );

  const openCreate = () => {
    const initial: Record<string, any> = {};
    (schema?.fields || []).forEach((field) => {
      initial[field.name] = field.type === 'switch' ? true : field.type === 'attributes' ? {} : field.type === 'select' ? field.options?.[0]?.value ?? '' : '';
    });
    setEditing(null);
    setForm(initial);
    setModalOpen(true);
  };

  const openEdit = (item: AdminItem) => {
    if (schema?.read_only) return;
    const initial: Record<string, any> = {};
    (schema?.fields || []).forEach((field) => {
      initial[field.name] = field.type === 'password'
        ? ''
        : field.type === 'attributes'
          ? item.attribute_values ?? {}
          : item[field.name] ?? (field.type === 'switch' ? false : '');
    });
    setEditing(item);
    setForm(initial);
    setModalOpen(true);
  };

  const setPickedFile = (field: AdminField, asset: { uri: string; fileName?: string | null; name?: string | null; mimeType?: string | null }, fallbackType: string) => {
    setForm({
      ...form,
      [field.name]: {
        uri: asset.uri,
        name: asset.fileName || asset.name || `${field.name}.jpg`,
        type: asset.mimeType || fallbackType,
      },
    });
  };

  const pickImage = async (field: AdminField, source: 'camera' | 'gallery') => {
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
      setPickedFile(field, result.assets[0], 'image/jpeg');
    }
  };

  const pickFile = async (field: AdminField) => {
    try {
      if (field.accept === 'image') {
        Alert.alert('Foto Wajah', 'Pilih sumber foto', [
          { text: 'Kamera', onPress: () => pickImage(field, 'camera') },
          { text: 'Galeri', onPress: () => pickImage(field, 'gallery') },
          { text: 'Batal', style: 'cancel' },
        ]);
        return;
      }

      const result = await DocumentPicker.getDocumentAsync({
        type: ['application/pdf', 'image/*'],
        copyToCacheDirectory: true,
        multiple: false,
      });

      if (!result.canceled && result.assets?.[0]) {
        setPickedFile(field, result.assets[0], 'application/pdf');
      }
    } catch (error) {
      Alert.alert('Gagal', 'Tidak dapat memilih file.');
    }
  };

  const isFileValue = (value: any) => value && typeof value === 'object' && typeof value.uri === 'string';

  const submit = async () => {
    setSaving(true);
    try {
      const fileFields = (schema?.fields || []).filter(
        (field) => field.type === 'file' && isFileValue(form[field.name]),
      );
      const useFormData = fileFields.length > 0;

      let payload: Record<string, any> | FormData = {};
      if (useFormData) {
        const formData = new FormData();
        (schema?.fields || []).forEach((field) => {
          if (field.create_only && editing) return;
          const value = form[field.name];

          if (field.type === 'file') {
            if (isFileValue(value)) {
              formData.append(field.name, {
                uri: value.uri,
                name: value.name,
                type: value.type,
              } as any);
            }
            return;
          }

          if (field.type === 'switch') {
            formData.append(field.name, value ? '1' : '0');
          } else if (value !== undefined && value !== null && value !== '') {
            formData.append(field.name, String(value));
          }
        });

        if (editing) {
          formData.append('_method', 'PUT');
        }

        payload = formData;
      } else {
        const data: Record<string, any> = {};
        (schema?.fields || []).forEach((field) => {
          if (field.create_only && editing) return;
          const value = form[field.name];
          if (field.type === 'file') return;
          if (field.type === 'attributes') {
            data[field.name] = value || {};
            return;
          }
          if (field.type === 'switch') {
            data[field.name] = value ? 1 : 0;
          } else if (value !== undefined && value !== null && value !== '') {
            data[field.name] = value;
          }
        });
        payload = data;
      }

      if (editing) {
        await adminApi.update(resource, editing.id, payload);
      } else {
        await adminApi.create(resource, payload);
      }

      setModalOpen(false);
      await load(editing ? page : 1, search);
      Alert.alert('Berhasil', editing ? 'Data berhasil diperbarui.' : 'Data berhasil ditambahkan.');
    } catch (error) {
      Alert.alert('Gagal', apiMessage(error));
    } finally {
      setSaving(false);
    }
  };

  const verifyAttendance = async (item: AdminItem, status: 'valid' | 'invalid') => {
    try {
      await adminApi.verifyAttendance(item.id, status);
      await load(page, search);
      Alert.alert('Berhasil', status === 'valid' ? 'Presensi disetujui.' : 'Presensi ditolak.');
    } catch (error) {
      Alert.alert('Gagal', apiMessage(error));
    }
  };

  const actLeave = async (item: AdminItem, action: 'approve' | 'reject') => {
    try {
      if (action === 'approve') await approvalApi.approve(item.id);
      else await approvalApi.reject(item.id);
      await load(page, search);
      Alert.alert('Berhasil', action === 'approve' ? 'Cuti disetujui.' : 'Cuti ditolak.');
    } catch (error) {
      Alert.alert('Gagal', apiMessage(error));
    }
  };

  const remove = (item: AdminItem) => {
    Alert.alert('Hapus data?', 'Data yang dihapus tidak bisa dikembalikan.', [
      { text: 'Batal', style: 'cancel' },
      {
        text: 'Hapus',
        style: 'destructive',
        onPress: async () => {
          try {
            await adminApi.remove(resource, item.id);
            await load(page, search);
          } catch (error) {
            Alert.alert('Gagal', apiMessage(error));
          }
        },
      },
    ]);
  };

  return (
    <Screen back>
      <View style={styles.header}>
        <View style={{ flex: 1 }}>
          <Text style={styles.title}>{title}</Text>
          <Text style={styles.muted}>{items.length} data ditampilkan</Text>
        </View>
        {!schema?.read_only && (
          <TouchableOpacity style={styles.addButton} onPress={openCreate}>
            <Ionicons name="add" size={20} color="#fff" />
          </TouchableOpacity>
        )}
      </View>

      <View style={styles.searchRow}>
        <TextInput
          value={search}
          onChangeText={setSearch}
          onSubmitEditing={() => load(1, search)}
          placeholder="Cari data..."
          placeholderTextColor={colors.subtle}
          style={styles.search}
        />
        <TouchableOpacity style={styles.searchButton} onPress={() => load(1, search)}>
          <Text style={styles.searchButtonText}>Cari</Text>
        </TouchableOpacity>
      </View>

      {loading ? <ActivityIndicator color={colors.primary} /> : null}
      {!loading && items.length === 0 ? <EmptyState message="Belum ada data." /> : null}

      {items.map((item) => (
        <TouchableOpacity
          key={`${item.id}-${item.title}`}
          activeOpacity={schema?.read_only ? 1 : 0.8}
          onPress={() => openEdit(item)}
        >
          <Card style={{ gap: 4 }}>
            <View style={styles.rowBetween}>
              <Text style={styles.itemTitle}>{item.title || '-'}</Text>
              {item.meta ? (
                <Badge
                  label={String(item.meta).toUpperCase()}
                  bg={statusColor(String(item.meta)).bg}
                  color={statusColor(String(item.meta)).text}
                />
              ) : null}
            </View>
            {item.subtitle ? <Text style={styles.muted}>{item.subtitle}</Text> : null}

            {resource === 'attendance-verifications' && String(item.raw?.status ?? item.status ?? '') === 'pending' ? (
              <View style={styles.actions}>
                <TouchableOpacity onPress={() => verifyAttendance(item, 'valid')}>
                  <Text style={styles.edit}>Setujui</Text>
                </TouchableOpacity>
                <TouchableOpacity onPress={() => verifyAttendance(item, 'invalid')}>
                  <Text style={styles.delete}>Tolak</Text>
                </TouchableOpacity>
              </View>
            ) : null}

            {resource === 'leaves' && String(item.raw?.status ?? item.status ?? '') === 'pending' ? (
              <View style={styles.actions}>
                <TouchableOpacity onPress={() => actLeave(item, 'approve')}>
                  <Text style={styles.edit}>Setujui</Text>
                </TouchableOpacity>
                <TouchableOpacity onPress={() => actLeave(item, 'reject')}>
                  <Text style={styles.delete}>Tolak</Text>
                </TouchableOpacity>
              </View>
            ) : null}

            {!schema?.read_only ? (
              <View style={styles.actions}>
                <TouchableOpacity onPress={() => openEdit(item)}>
                  <Text style={styles.edit}>Edit</Text>
                </TouchableOpacity>
                <TouchableOpacity onPress={() => remove(item)}>
                  <Text style={styles.delete}>Hapus</Text>
                </TouchableOpacity>
              </View>
            ) : null}
          </Card>
        </TouchableOpacity>
      ))}

      {lastPage > 1 ? (
        <View style={styles.pagination}>
          <TouchableOpacity
            disabled={page <= 1}
            onPress={() => load(page - 1, search)}
            style={[styles.pageButton, page <= 1 && styles.disabled]}
          >
            <Text style={styles.pageText}>Sebelumnya</Text>
          </TouchableOpacity>
          <Text style={styles.muted}>
            {page} / {lastPage}
          </Text>
          <TouchableOpacity
            disabled={page >= lastPage}
            onPress={() => load(page + 1, search)}
            style={[styles.pageButton, page >= lastPage && styles.disabled]}
          >
            <Text style={styles.pageText}>Berikutnya</Text>
          </TouchableOpacity>
        </View>
      ) : null}

      <Modal visible={modalOpen} transparent animationType="slide" onRequestClose={() => setModalOpen(false)}>
        <View style={styles.backdrop}>
          <View style={styles.modal}>
            <View style={styles.rowBetween}>
              <Text style={styles.modalTitle}>{editing ? `Edit ${title}` : `Tambah ${title}`}</Text>
              <TouchableOpacity onPress={() => setModalOpen(false)}>
                <Ionicons name="close" size={22} color={colors.muted} />
              </TouchableOpacity>
            </View>

            <ScrollView contentContainerStyle={{ gap: spacing.md }} showsVerticalScrollIndicator={false}>
              {(schema?.fields || []).map((field) => {
                if (field.create_only && editing) return null;

                if (field.type === 'file') {
                  const selected = form[field.name];
                  const existingUrl = editing?.avatar_url && field.name === 'avatar' ? editing.avatar_url : null;

                  return (
                    <View key={field.name} style={{ gap: 6 }}>
                      <Text style={styles.fieldLabel}>{field.label}</Text>

                      {isFileValue(selected) && field.accept === 'image' ? (
                        <Image source={{ uri: selected.uri }} style={styles.avatarPreview} />
                      ) : isFileValue(selected) ? (
                        <Text style={styles.muted}>Dipilih: {selected.name}</Text>
                      ) : existingUrl ? (
                        <Image source={{ uri: existingUrl }} style={styles.avatarPreview} />
                      ) : (
                        <Text style={styles.muted}>
                          {field.accept === 'image' ? 'Belum ada foto dipilih.' : 'Belum ada file dipilih.'}
                        </Text>
                      )}

                      <TouchableOpacity style={styles.fileButton} onPress={() => pickFile(field)}>
                        <Ionicons
                          name={field.accept === 'image' ? 'camera-outline' : 'cloud-upload-outline'}
                          size={18}
                          color={colors.primary}
                        />
                        <Text style={styles.fileButtonText}>
                          {field.accept === 'image' ? 'Kamera / Galeri' : 'Pilih File'}
                        </Text>
                      </TouchableOpacity>
                    </View>
                  );
                }

                if (field.type === 'attributes') {
                  const attributes = (field.options || []) as {
                    id: number;
                    label: string;
                    type: string;
                    options: string[];
                  }[];

                  return (
                    <View key={field.name} style={{ gap: spacing.md }}>
                      <Text style={styles.fieldLabel}>{field.label}</Text>

                      {attributes.length === 0 ? (
                        <Text style={styles.muted}>Belum ada atribut aktif.</Text>
                      ) : null}

                      {attributes.map((attribute) => {
                        const value = form.attributes?.[attribute.id] ?? '';
                        const update = (nextValue: string) =>
                          setForm({
                            ...form,
                            attributes: { ...(form.attributes || {}), [attribute.id]: nextValue },
                          });

                        if (attribute.type === 'select' && Array.isArray(attribute.options)) {
                          return (
                            <View key={attribute.id} style={{ gap: 6 }}>
                              <Text style={styles.fieldLabel}>{attribute.label}</Text>
                              <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={{ gap: 8 }}>
                                <TouchableOpacity
                                  onPress={() => update('')}
                                  style={[styles.chip, value === '' && styles.chipActive]}
                                >
                                  <Text style={[styles.chipText, value === '' && styles.chipTextActive]}>-</Text>
                                </TouchableOpacity>
                                {attribute.options.map((option) => {
                                  const active = String(value) === String(option);
                                  return (
                                    <TouchableOpacity
                                      key={`${attribute.id}-${option}`}
                                      onPress={() => update(option)}
                                      style={[styles.chip, active && styles.chipActive]}
                                    >
                                      <Text style={[styles.chipText, active && styles.chipTextActive]}>{option}</Text>
                                    </TouchableOpacity>
                                  );
                                })}
                              </ScrollView>
                            </View>
                          );
                        }

                        return (
                          <Field
                            key={attribute.id}
                            label={attribute.label}
                            value={String(value)}
                            onChangeText={update}
                            placeholder={attribute.type === 'date' ? 'YYYY-MM-DD' : undefined}
                            multiline={attribute.type === 'textarea'}
                            style={attribute.type === 'textarea' ? { minHeight: 80, textAlignVertical: 'top' } : undefined}
                          />
                        );
                      })}
                    </View>
                  );
                }

                if (field.type === 'switch') {
                  return (
                    <TouchableOpacity
                      key={field.name}
                      style={styles.switchRow}
                      onPress={() => setForm({ ...form, [field.name]: !form[field.name] })}
                    >
                      <Text style={styles.switchLabel}>{field.label}</Text>
                      <View style={[styles.switchTrack, form[field.name] && styles.switchTrackOn]}>
                        <View style={[styles.switchThumb, form[field.name] && styles.switchThumbOn]} />
                      </View>
                    </TouchableOpacity>
                  );
                }

                if (field.type === 'select') {
                  return (
                    <View key={field.name} style={{ gap: 6 }}>
                      <Text style={styles.fieldLabel}>{field.label}</Text>
                      <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={{ gap: 8 }}>
                        {(field.options || []).map((option) => {
                          const active = String(form[field.name] ?? '') === String(option.value);
                          return (
                            <TouchableOpacity
                              key={`${field.name}-${option.value}`}
                              onPress={() => setForm({ ...form, [field.name]: option.value })}
                              style={[styles.chip, active && styles.chipActive]}
                            >
                              <Text style={[styles.chipText, active && styles.chipTextActive]}>{option.label}</Text>
                            </TouchableOpacity>
                          );
                        })}
                      </ScrollView>
                    </View>
                  );
                }

                return (
                  <Field
                    key={field.name}
                    label={field.label + (field.required ? ' *' : '')}
                    value={form[field.name] === undefined || form[field.name] === null ? '' : String(form[field.name])}
                    onChangeText={(value) => setForm({ ...form, [field.name]: value })}
                    keyboardType={field.type === 'number' ? 'numeric' : field.type === 'email' ? 'email-address' : 'default'}
                    placeholder={field.type === 'date' ? 'YYYY-MM-DD' : undefined}
                    secureTextEntry={field.type === 'password'}
                    autoCapitalize={field.type === 'email' ? 'none' : 'sentences'}
                    multiline={field.type === 'textarea'}
                    style={field.type === 'textarea' ? { minHeight: 90, textAlignVertical: 'top' } : undefined}
                  />
                );
              })}
            </ScrollView>

            <TouchableOpacity style={styles.saveButton} onPress={submit} disabled={saving}>
              <Text style={styles.saveText}>{saving ? 'Menyimpan...' : 'Simpan'}</Text>
            </TouchableOpacity>
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
  addButton: {
    width: 40,
    height: 40,
    borderRadius: 14,
    backgroundColor: colors.primary,
    alignItems: 'center',
    justifyContent: 'center',
  },
  searchRow: { flexDirection: 'row', gap: spacing.sm },
  search: {
    flex: 1,
    borderRadius: radius.md,
    borderWidth: 1,
    borderColor: colors.border,
    backgroundColor: '#fff',
    paddingHorizontal: spacing.md,
    paddingVertical: 10,
    fontSize: 13,
    color: colors.text,
  },
  searchButton: { borderRadius: radius.md, backgroundColor: colors.primary, paddingHorizontal: 16, justifyContent: 'center' },
  searchButtonText: { color: '#fff', fontWeight: '700', fontSize: 12 },
  rowBetween: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', gap: 8 },
  itemTitle: { fontWeight: '700', color: colors.text, flex: 1 },
  actions: { flexDirection: 'row', gap: 16, marginTop: 6 },
  edit: { fontSize: 12, fontWeight: '700', color: colors.primary },
  delete: { fontSize: 12, fontWeight: '700', color: colors.danger },
  pagination: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', marginTop: spacing.sm },
  pageButton: {
    borderRadius: radius.md,
    borderWidth: 1,
    borderColor: colors.border,
    backgroundColor: '#fff',
    paddingHorizontal: 12,
    paddingVertical: 8,
  },
  pageText: { fontSize: 12, fontWeight: '600', color: colors.text },
  disabled: { opacity: 0.4 },
  backdrop: { flex: 1, backgroundColor: 'rgba(15,23,42,0.5)', justifyContent: 'flex-end' },
  modal: {
    maxHeight: '92%',
    backgroundColor: '#fff',
    borderTopLeftRadius: 24,
    borderTopRightRadius: 24,
    padding: spacing.xl,
    gap: spacing.md,
  },
  modalTitle: { fontSize: 18, fontWeight: '800', color: colors.text, flex: 1 },
  fieldLabel: { fontSize: 13, fontWeight: '600', color: colors.muted },
  switchRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    borderWidth: 1,
    borderColor: colors.border,
    borderRadius: radius.md,
    paddingHorizontal: spacing.md,
    paddingVertical: 12,
  },
  switchLabel: { fontSize: 13, fontWeight: '600', color: colors.text },
  switchTrack: { width: 44, height: 24, borderRadius: 999, backgroundColor: '#e2e8f0', padding: 2 },
  switchTrackOn: { backgroundColor: colors.primary },
  switchThumb: { width: 20, height: 20, borderRadius: 999, backgroundColor: '#fff' },
  switchThumbOn: { transform: [{ translateX: 20 }] },
  chip: { borderWidth: 1, borderColor: colors.border, borderRadius: radius.full, paddingHorizontal: 12, paddingVertical: 6 },
  chipActive: { backgroundColor: colors.primary, borderColor: colors.primary },
  chipText: { fontSize: 11, color: colors.muted },
  chipTextActive: { color: '#fff', fontWeight: '700' },
  avatarPreview: { width: 72, height: 72, borderRadius: 36, backgroundColor: '#f1f5f9' },
  fileButton: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 8,
    borderWidth: 1,
    borderColor: colors.border,
    borderRadius: radius.md,
    paddingVertical: 10,
    backgroundColor: '#fff',
  },
  fileButtonText: { fontSize: 12, fontWeight: '700', color: colors.primary },
  saveButton: {
    borderRadius: radius.md,
    backgroundColor: colors.primary,
    paddingVertical: 13,
    alignItems: 'center',
  },
  saveText: { color: '#fff', fontWeight: '700', fontSize: 14 },
});
