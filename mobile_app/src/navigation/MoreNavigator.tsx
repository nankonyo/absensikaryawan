import React from 'react';
import { createNativeStackNavigator } from '@react-navigation/native-stack';
import { MoreScreen } from '../screens/MoreScreen';
import { AdminResourceScreen } from '../screens/admin/AdminResourceScreen';
import { HolidayScreen } from '../screens/holiday/HolidayScreen';
import { ReportScreen } from '../screens/report/ReportScreen';
import type { MoreStackParamList } from './types';
import { colors } from '../theme';

const Stack = createNativeStackNavigator<MoreStackParamList>();

export const MoreNavigator = () => (
  <Stack.Navigator
    screenOptions={{
      headerShown: false,
      headerStyle: { backgroundColor: colors.primary },
      headerTintColor: '#fff',
      headerTitleStyle: { fontWeight: '700' },
      contentStyle: { backgroundColor: colors.bg },
    }}
  >
    <Stack.Screen name="MoreHome" component={MoreScreen} options={{ headerShown: false }} />
    <Stack.Screen
      name="AdminResource"
      component={AdminResourceScreen}
      options={({ route }) => ({ title: route.params.title })}
    />
    <Stack.Screen name="Holiday" component={HolidayScreen} options={{ title: 'Kalender Hari Libur' }} />
    <Stack.Screen name="Report" component={ReportScreen} options={{ title: 'Laporan Absensi' }} />
  </Stack.Navigator>
);
