import React from 'react';
import { createNativeStackNavigator } from '@react-navigation/native-stack';
import { MoreScreen } from '../screens/MoreScreen';
import { AdminResourceScreen } from '../screens/admin/AdminResourceScreen';
import type { MoreStackParamList } from './types';
import { colors } from '../theme';

const Stack = createNativeStackNavigator<MoreStackParamList>();

export const MoreNavigator = () => (
  <Stack.Navigator
    screenOptions={{
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
  </Stack.Navigator>
);
