import React from 'react';
import { createNativeStackNavigator } from '@react-navigation/native-stack';
import { colors } from '../theme';
import type { InternalStackParamList } from './types';
import { InternalHomeScreen } from '../screens/internal/InternalHomeScreen';
import { ProjectsScreen } from '../screens/internal/ProjectsScreen';
import { ProjectDetailScreen } from '../screens/internal/ProjectDetailScreen';
import { KanbanScreen } from '../screens/internal/KanbanScreen';
import { GanttScreen } from '../screens/internal/GanttScreen';
import { ActivitiesScreen } from '../screens/internal/ActivitiesScreen';
import { ContentsScreen } from '../screens/internal/ContentsScreen';

const Stack = createNativeStackNavigator<InternalStackParamList>();

export const InternalNavigator = () => (
  <Stack.Navigator
    screenOptions={{
      headerShown: false,
      headerStyle: { backgroundColor: colors.primary },
      headerTintColor: '#fff',
      headerTitleStyle: { fontWeight: '700' },
      contentStyle: { backgroundColor: colors.bg },
    }}
  >
    <Stack.Screen name="InternalHome" component={InternalHomeScreen} options={{ title: 'Internal App' }} />
    <Stack.Screen name="Projects" component={ProjectsScreen} options={{ title: 'Project' }} />
    <Stack.Screen name="ProjectDetail" component={ProjectDetailScreen} options={{ title: 'Detail Project' }} />
    <Stack.Screen name="Kanban" component={KanbanScreen} options={{ title: 'Kanban' }} />
    <Stack.Screen name="Gantt" component={GanttScreen} options={{ title: 'Gantt' }} />
    <Stack.Screen name="Activities" component={ActivitiesScreen} options={{ title: 'Aktivitas' }} />
    <Stack.Screen name="Contents" component={ContentsScreen} options={{ title: 'Konten' }} />
  </Stack.Navigator>
);
