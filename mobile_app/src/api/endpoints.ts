import { api } from './client';
import type {
  ActivityLog,
  Attendance,
  ContentItem,
  Leave,
  LeaveType,
  Project,
  Task,
  User,
} from '../types';

export const authApi = {
  login: (email: string, password: string) =>
    api.post<{ token: string; user: User }>('/login', { email, password, device_name: 'mobile' }),
  me: () => api.get<{ user: User }>('/me'),
  logout: () => api.post('/logout'),
};

export const dashboardApi = {
  index: () =>
    api.get<{
      user: User;
      stats: {
        today_attendance: Attendance | null;
        my_pending_leaves: number;
        team_members: number;
        pending_approvals: number;
        projects: number;
        my_activities_this_month: number;
      };
      announcements: ContentItem[];
      projects: Project[];
    }>('/dashboard'),
};

export const attendanceApi = {
  today: () =>
    api.get<{
      attendance: Attendance | null;
      office: { id: number; name: string; latitude: number; longitude: number; radius: number; address?: string } | null;
      work_start_time: string;
      work_end_time: string;
      reasons: { id: number; name: string }[];
    }>('/attendance/today'),
  history: (params?: { month?: string; status?: string; per_page?: number }) =>
    api.get<{ data: Attendance[]; current_page: number; last_page: number }>('/attendance/history', { params }),
  checkIn: (payload: Record<string, unknown>) => api.post('/attendance/check-in', payload),
  checkOut: (payload: Record<string, unknown>) => api.post('/attendance/check-out', payload),
};

export const leaveApi = {
  index: (params?: { status?: string; per_page?: number }) =>
    api.get<{ data: Leave[]; current_page: number; last_page: number }>('/leaves', { params }),
  types: () => api.get<{ types: LeaveType[] }>('/leaves/types'),
  store: (payload: FormData | Record<string, unknown>) => api.post('/leaves', payload),
  show: (id: number) => api.get<{ leave: Leave }>(`/leaves/${id}`),
  cancel: (id: number) => api.delete(`/leaves/${id}`),
};

export const approvalApi = {
  index: (params?: { per_page?: number }) =>
    api.get<{ data: Leave[]; current_page: number; last_page: number }>('/approvals', { params }),
  approve: (id: number, notes?: string) => api.post(`/approvals/${id}/approve`, { notes }),
  reject: (id: number, notes?: string) => api.post(`/approvals/${id}/reject`, { notes }),
  verify: (id: number, notes?: string) => api.post(`/approvals/${id}/verify`, { notes }),
};

export const teamApi = {
  index: () =>
    api.get<{
      members: {
        id: number;
        name: string;
        role?: string | null;
        unit?: string | null;
        department?: string | null;
        attendance?: Attendance | null;
      }[];
    }>('/team'),
  show: (id: number, params?: { per_page?: number }) => api.get(`/team/${id}`, { params }),
};

export const projectApi = {
  index: (params?: { q?: string; page?: number }) =>
    api.get<{ data: Project[]; current_page: number; last_page: number }>('/projects', { params }),
  show: (id: number) => api.get<{ project: Project; tasks: Task[]; members: User[] }>(`/projects/${id}`),
  tasks: (id: number) => api.get<{ tasks: Task[] }>(`/projects/${id}/tasks`),
  kanban: (projectId?: number) =>
    api.get<{ statuses: string[]; board: Record<string, Task[]>; projects: Project[] }>('/projects/kanban', {
      params: projectId ? { project_id: projectId } : {},
    }),
  gantt: (projectId?: number) =>
    api.get<{ tasks: Task[]; projects: Project[] }>('/projects/gantt', {
      params: projectId ? { project_id: projectId } : {},
    }),
  updateTask: (taskId: number, payload: { status?: string; progress?: number; assignee_id?: number; due_date?: string }) =>
    api.post(`/projects/tasks/${taskId}`, payload),
};

export const activityApi = {
  index: (params?: { scope?: 'mine' | 'team'; project_id?: number; from?: string; to?: string; page?: number }) =>
    api.get<{ activities: { data: ActivityLog[] }; projects: Project[] }>('/activities', { params }),
  store: (payload: {
    project_id: number;
    title: string;
    description?: string;
    status: string;
    priority: string;
    activity_date?: string;
    due_date?: string;
  }) => api.post('/activities', payload),
};

export const contentApi = {
  videos: () => api.get<{ items: ContentItem[] }>('/contents/videos'),
  dokumentasi: () => api.get<{ items: ContentItem[] }>('/contents/dokumentasi'),
  pengumuman: () => api.get<{ items: ContentItem[] }>('/contents/pengumuman'),
};

export const profileApi = {
  show: () => api.get<{ user: User }>('/profile'),
  update: (payload: { name: string; phone?: string }) => api.put<{ user: User }>('/profile', payload),
};
