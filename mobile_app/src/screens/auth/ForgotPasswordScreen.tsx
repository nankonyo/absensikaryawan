import React, { useRef, useState } from 'react';
import { Alert, StyleSheet, Text, TextInput, TouchableOpacity, View } from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { Ionicons } from '@expo/vector-icons';
import { Button } from '../../components/ui';
import { Field } from '../../components/Field';
import { authApi } from '../../api/endpoints';
import { apiMessage } from '../../api/client';
import { colors, radius, spacing } from '../../theme';

type Props = {
  onBack: () => void;
};

type Step = 'email' | 'otp' | 'done';

export const ForgotPasswordScreen = ({ onBack }: Props) => {
  const [step, setStep] = useState<Step>('email');
  const [email, setEmail] = useState('');
  const [otp, setOtp] = useState('');
  const [password, setPassword] = useState('');
  const [passwordConfirmation, setPasswordConfirmation] = useState('');
  const [loading, setLoading] = useState(false);
  const [message, setMessage] = useState('');
  const otpRef = useRef<React.ComponentRef<typeof TextInput>>(null);

  const focusOtp = () => {
    setTimeout(() => otpRef.current?.focus(), 120);
  };

  const sendOtp = async () => {
    if (!email.trim()) {
      Alert.alert('Email wajib diisi', 'Masukkan email akun kepegawaian Anda.');
      return;
    }

    setLoading(true);
    try {
      const response = await authApi.forgotPassword(email.trim());
      setMessage(response.data.message);
      setOtp('');
      setStep('otp');
      focusOtp();
    } catch (error) {
      Alert.alert('Gagal', apiMessage(error, 'Gagal mengirim kode OTP.'));
    } finally {
      setLoading(false);
    }
  };

  const resetPassword = async () => {
    if (!otp.trim() || !password || !passwordConfirmation) {
      Alert.alert('Lengkapi data', 'Kode OTP dan password baru wajib diisi.');
      return;
    }

    if (otp.trim().length !== 6) {
      Alert.alert('Kode OTP tidak valid', 'Kode OTP terdiri dari 6 digit.');
      return;
    }

    if (password.length < 8) {
      Alert.alert('Password terlalu pendek', 'Password baru minimal 8 karakter.');
      return;
    }

    if (password !== passwordConfirmation) {
      Alert.alert('Password tidak sama', 'Konfirmasi password baru tidak cocok.');
      return;
    }

    setLoading(true);
    try {
      const response = await authApi.resetPasswordOtp({
        email: email.trim(),
        otp: otp.trim(),
        password,
        password_confirmation: passwordConfirmation,
      });
      setMessage(response.data.message);
      setStep('done');
    } catch (error) {
      Alert.alert('Gagal', apiMessage(error, 'Kode OTP tidak valid atau sudah kedaluwarsa.'));
    } finally {
      setLoading(false);
    }
  };

  return (
    <LinearGradient colors={[colors.primaryDark, colors.primary, '#EDA2A7']} style={styles.root}>
      <View style={styles.hero}>
        <View style={styles.logo}>
          <Ionicons name="lock-open" size={32} color="#fff" />
        </View>
        <Text style={styles.title}>Lupa Password</Text>
        <Text style={styles.subtitle}>
          {step === 'email'
            ? 'Masukkan email untuk menerima kode OTP.'
            : step === 'otp'
              ? 'Masukkan kode OTP dan password baru.'
              : 'Password berhasil direset.'}
        </Text>
      </View>

      <View style={styles.card}>
        {step === 'email' ? (
          <View style={{ gap: spacing.md }}>
            <Text style={styles.cardTitle}>Kirim Kode OTP</Text>
            <Text style={styles.cardSubtitle}>Kode OTP 6 digit akan dikirim ke email terdaftar.</Text>

            <Field
              label="Email"
              value={email}
              onChangeText={setEmail}
              placeholder="nama@perusahaan.com"
              keyboardType="email-address"
              autoCapitalize="none"
              autoCorrect={false}
              autoFocus
            />

            <Button label={loading ? 'Mengirim...' : 'Kirim Kode OTP'} onPress={sendOtp} disabled={loading} />

            <TouchableOpacity style={styles.backLink} onPress={onBack}>
              <Ionicons name="arrow-back" size={16} color={colors.primary} />
              <Text style={styles.backText}>Kembali ke Login</Text>
            </TouchableOpacity>
          </View>
        ) : null}

        {step === 'otp' ? (
          <View style={{ gap: spacing.md }}>
            <Text style={styles.cardTitle}>Verifikasi OTP</Text>
            <Text style={styles.cardSubtitle}>{message || 'Kode OTP sudah dikirim ke email Anda. Kode berlaku 10 menit.'}</Text>

            <Field
              label="Kode OTP"
              value={otp}
              onChangeText={setOtp}
              placeholder="000000"
              keyboardType="number-pad"
              maxLength={6}
              autoCapitalize="none"
              autoFocus
              inputRef={otpRef}
              style={styles.otpInput}
            />

            <Field
              label="Password baru"
              value={password}
              onChangeText={setPassword}
              placeholder="Minimal 8 karakter"
              secureTextEntry
              autoCapitalize="none"
            />

            <Field
              label="Konfirmasi password baru"
              value={passwordConfirmation}
              onChangeText={setPasswordConfirmation}
              placeholder="Ulangi password baru"
              secureTextEntry
              autoCapitalize="none"
            />

            <Button
              label={loading ? 'Menyimpan...' : 'Simpan Password Baru'}
              onPress={resetPassword}
              disabled={loading}
            />

            <TouchableOpacity style={styles.backLink} onPress={sendOtp} disabled={loading}>
              <Ionicons name="refresh" size={16} color={colors.primary} />
              <Text style={styles.backText}>Kirim ulang kode OTP</Text>
            </TouchableOpacity>

            <TouchableOpacity style={styles.backLink} onPress={onBack}>
              <Ionicons name="arrow-back" size={16} color={colors.primary} />
              <Text style={styles.backText}>Kembali ke Login</Text>
            </TouchableOpacity>
          </View>
        ) : null}

        {step === 'done' ? (
          <View style={{ gap: spacing.md }}>
            <View style={styles.successIcon}>
              <Ionicons name="checkmark-circle" size={42} color={colors.success} />
            </View>
            <Text style={styles.cardTitle}>Berhasil</Text>
            <Text style={styles.cardSubtitle}>{message}</Text>
            <Button label="Kembali ke Login" onPress={onBack} />
          </View>
        ) : null}
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
  cardSubtitle: { fontSize: 13, color: colors.muted, lineHeight: 18 },
  otpInput: { textAlign: 'center', fontSize: 22, fontWeight: '800', letterSpacing: 8 },
  successIcon: { alignItems: 'center' },
  backLink: { flexDirection: 'row', alignItems: 'center', justifyContent: 'center', gap: 6, paddingVertical: 8 },
  backText: { fontSize: 12, fontWeight: '700', color: colors.primary },
  footer: { color: 'rgba(255,255,255,0.7)', textAlign: 'center', fontSize: 11 },
});
