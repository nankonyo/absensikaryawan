import React from 'react';
import { createBottomTabNavigator } from '@react-navigation/bottom-tabs';
import { Ionicons } from '@expo/vector-icons';
import { useAuth } from '../context/AuthContext';
import type { TabsParamList } from './types';
import { DashboardScreen } from '../screens/DashboardScreen';
import { AttendanceScreen } from '../screens/attendance/AttendanceScreen';
import { LeaveScreen } from '../screens/leave/LeaveScreen';
import { TeamScreen } from '../screens/team/TeamScreen';
import { ApprovalScreen } from '../screens/approval/ApprovalScreen';
import { InternalNavigator } from './InternalNavigator';
import { MoreScreen } from '../screens/MoreScreen';
import { ProfileScreen } from '../screens/ProfileScreen';
import { colors } from '../theme';

const Tab = createBottomTabNavigator<TabsParamList>();

const icon = (name: keyof typeof Ionicons.glyphMap) =>
  ({ color, size }: { color: string; size: number }) => <Ionicons name={name} color={color} size={size} />;

export const BottomTabs = () => {
  const { user } = useAuth();
  const isAdmin = Boolean(user?.is_admin);
  const isAtasan = Boolean(user?.is_atasan) && !isAdmin;
  const isStaff = !isAdmin && !isAtasan;

  return (
    <Tab.Navigator
      screenOptions={{
        headerShown: false,
        tabBarActiveTintColor: colors.primary,
        tabBarInactiveTintColor: colors.subtle,
        tabBarStyle: { borderTopColor: colors.border, height: 62, paddingBottom: 8, paddingTop: 6 },
        tabBarLabelStyle: { fontSize: 10, fontWeight: '600' },
      }}
    >
      <Tab.Screen name="Dashboard" component={DashboardScreen} options={{ title: 'Beranda', tabBarIcon: icon('home') }} />
      <Tab.Screen name="Attendance" component={AttendanceScreen} options={{ title: 'Presensi', tabBarIcon: icon('finger-print') }} />

      {(isAtasan || isAdmin) && (
        <Tab.Screen name="Team" component={TeamScreen} options={{ title: 'Tim', tabBarIcon: icon('people') }} />
      )}

      {(isAtasan || isAdmin) && (
        <Tab.Screen name="Approval" component={ApprovalScreen} options={{ title: 'Approval', tabBarIcon: icon('checkmark-done') }} />
      )}

      {isStaff && (
        <Tab.Screen name="Leave" component={LeaveScreen} options={{ title: 'Cuti', tabBarIcon: icon('calendar') }} />
      )}

      <Tab.Screen
        name="InternalTab"
        component={InternalNavigator}
        options={{ title: 'Internal', tabBarIcon: icon('apps') }}
      />

      {isAdmin && (
        <Tab.Screen name="More" component={MoreScreen} options={{ title: 'Admin', tabBarIcon: icon('settings') }} />
      )}

      <Tab.Screen name="Profile" component={ProfileScreen} options={{ title: 'Profil', tabBarIcon: icon('person') }} />
    </Tab.Navigator>
  );
};
