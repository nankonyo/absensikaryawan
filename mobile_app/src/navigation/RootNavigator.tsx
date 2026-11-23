import React from 'react';
import { ActivityIndicator, StyleSheet, Text, View } from 'react-native';
import { DefaultTheme, NavigationContainer } from '@react-navigation/native';
import { SafeAreaProvider } from 'react-native-safe-area-context';
import { StatusBar } from 'expo-status-bar';
import { AuthProvider, useAuth } from '../context/AuthContext';
import { LoginScreen } from '../screens/auth/LoginScreen';
import { BottomTabs } from './BottomTabs';
import { FaceCaptureProvider } from '../components/FaceCaptureProvider';
import { colors, spacing } from '../theme';

const navTheme = {
  ...DefaultTheme,
  colors: {
    ...DefaultTheme.colors,
    primary: colors.primary,
    background: colors.bg,
    card: colors.card,
    text: colors.text,
    border: colors.border,
  },
};

const Root = () => {
  const { user, loading } = useAuth();

  if (loading) {
    return (
      <View style={styles.loading}>
        <ActivityIndicator size="large" color={colors.primary} />
        <Text style={styles.loadingText}>Memuat aplikasi...</Text>
      </View>
    );
  }

  return user ? <BottomTabs /> : <LoginScreen />;
};

export const RootNavigator = () => (
  <SafeAreaProvider>
    <StatusBar style="light" />
    <AuthProvider>
      <FaceCaptureProvider>
        <NavigationContainer theme={navTheme}>
          <Root />
        </NavigationContainer>
      </FaceCaptureProvider>
    </AuthProvider>
  </SafeAreaProvider>
);

const styles = StyleSheet.create({
  loading: { flex: 1, alignItems: 'center', justifyContent: 'center', gap: spacing.sm, backgroundColor: colors.bg },
  loadingText: { color: colors.muted, fontSize: 12 },
});
