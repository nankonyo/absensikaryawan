import { api } from './client';
import type {
  ActivityLog,
  Attendance,
  AttendanceReport,
  ContentItem,
  Holiday,
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
      is_day_off: boolean;
      day_off_name: string | null;
      office: { id: number; name: string; latitude: number; longitude: number; radius: number; address?: string } | null;
      work_start_time: string;
      work_end_time: string;
      face_verification_required?: boolean;
      reasons: { id: number; name: string }[];
      work_mode: 'WFO' | 'WFH' | 'WFA';
    }>('/attendance/today'),
  history: (params?: { month?: string; status?: string; per_page?: number }) =>
    api.get<{ data: Attendance[]; current_page: number; last_page: number }>('/attendance/history', { params }),
  faceCaptureUrl: (mode: 'in' | 'out', warmup = false) =>
    api.get<{ url: string | null; threshold?: number; reason?: string; enforced?: boolean }>('/attendance/face-capture-url', {
      params: { mode, warmup: warmup ? 1 : 0 },
    }),
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

export const holidayApi = {
  index: (params?: { month?: string }) =>
    api.get<{ month: string; month_label: string; holidays: Holiday[] }>('/holidays', { params }),
};

export const reportApi = {
  attendance: (params: { start_date: string; end_date: string }) =>
    api.get<AttendanceReport>('/reports/attendance', { params }),
};

export const profileApi = {
  show: () => api.get<{ user: User }>('/profile'),
  update: (payload: { name: string; phone?: string } | FormData) =>
    payload instanceof FormData
      ? api.post<{ user: User }>('/profile', payload)
      : api.put<{ user: User }>('/profile', payload),
  updatePassword: (payload: { current_password: string; password: string; password_confirmation: string }) =>
    api.post<{ message: string }>('/profile/password', payload),
};

export const adminApi = {
  schema: (resource: string) => api.get<{ title: string; read_only: boolean; fields: any[] }>(`/admin/resources/${resource}/schema`),
  list: (resource: string, params?: { q?: string; page?: number; per_page?: number }) =>
    api.get<{ data: { data: any[]; current_page: number; last_page: number } }>(`/admin/resources/${resource}`, { params }),
  create: (resource: string, payload: Record<string, unknown> | FormData) =>
    api.post(`/admin/resources/${resource}`, payload),
  update: (resource: string, id: number, payload: Record<string, unknown> | FormData) =>
    payload instanceof FormData
      ? api.post(`/admin/resources/${resource}/${id}`, payload)
      : api.put(`/admin/resources/${resource}/${id}`, payload),
  remove: (resource: string, id: number) => api.delete(`/admin/resources/${resource}/${id}`),
  verifyAttendance: (id: number, status: 'valid' | 'invalid' | 'pending', notes?: string) =>
    api.post(`/admin/resources/attendances/${id}/verify`, { status, notes }),
};

export const appApi = {
  info: () =>
    api.get<{
      app_name: string;
      company_name: string;
      logo_url?: string | null;
      play_store_url?: string | null;
      app_store_url?: string | null;
    }>('/app-info'),
};
