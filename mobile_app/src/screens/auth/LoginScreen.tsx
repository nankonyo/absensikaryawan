import React, { useState } from 'react';
import { Alert, StyleSheet, Text, View } from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { Ionicons } from '@expo/vector-icons';
import { useAuth } from '../../context/AuthContext';
import { Button } from '../../components/ui';
import { Field } from '../../components/Field';
import { apiMessage } from '../../api/client';
import { colors, radius, spacing } from '../../theme';

export const LoginScreen = () => {
  const { login } = useAuth();
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);

  const submit = async () => {
    if (!email || !password) {
      setError('Email dan password wajib diisi.');
      return;
    }
    setLoading(true);
    setError(null);
    try {
      await login(email.trim(), password);
    } catch (err) {
      setError(apiMessage(err, 'Gagal masuk. Periksa email dan password.'));
    } finally {
      setLoading(false);
    }
  };

  return (
    <LinearGradient colors={[colors.primaryDark, colors.primary, '#EDA2A7']} style={styles.root}>
      <View style={styles.hero}>
        <View style={styles.logo}>
          <Ionicons name="business" size={34} color="#fff" />
        </View>
        <Text style={styles.title}>RST Internal App</Text>
        <Text style={styles.subtitle}>Presensi, cuti, project & aktivitas internal</Text>
      </View>

      <View style={styles.card}>
        <Text style={styles.cardTitle}>Masuk</Text>
        <Text style={styles.cardSubtitle}>Gunakan akun kepegawaian Anda.</Text>

        <View style={styles.form}>
          <Field
            label="Email"
            value={email}
            onChangeText={setEmail}
            placeholder="nama@perusahaan.com"
            keyboardType="email-address"
            autoCapitalize="none"
            autoCorrect={false}
          />
          <Field
            label="Password"
            value={password}
            onChangeText={setPassword}
            placeholder="Password"
            secureTextEntry
          />
          {error ? <Text style={styles.error}>{error}</Text> : null}
          <Button label={loading ? 'Memproses...' : 'Masuk'} onPress={submit} disabled={loading} />
        </View>
      </View>

      <Text style={styles.footer}>© {new Date().getFullYear()} Raja Sakti Telematika</Text>
    </LinearGradient>
  );
};

const styles = StyleSheet.create({
  root: { flex: 1, padding: spacing.xl, justifyContent: 'center', gap: spacing.xl },
  hero: { alignItems: 'center', gap: spacing.sm },
  logo: {
    width: 72,
    height: 72,
    borderRadius: 24,
    backgroundColor: 'rgba(255,255,255,0.2)',
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 1,
    borderColor: 'rgba(255,255,255,0.35)',
  },
  title: { color: '#fff', fontSize: 26, fontWeight: '800' },
  subtitle: { color: 'rgba(255,255,255,0.85)', fontSize: 13, textAlign: 'center' },
  card: { backgroundColor: '#fff', borderRadius: radius.xl, padding: spacing.xl, gap: spacing.xs },
  cardTitle: { fontSize: 22, fontWeight: '800', color: colors.text },
  cardSubtitle: { fontSize: 13, color: colors.muted, marginBottom: spacing.md },
  form: { gap: spacing.md },
  error: { color: colors.danger, fontSize: 12 },
  footer: { color: 'rgba(255,255,255,0.7)', textAlign: 'center', fontSize: 11 },
});
