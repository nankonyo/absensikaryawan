export type Role = {
  name?: string | null;
  display_name?: string | null;
  level?: number | null;
};

export type User = {
  id: number;
  name: string;
  email: string;
  phone?: string | null;
  employee_id?: string | null;
  avatar_url?: string | null;
  role: Role;
  department?: string | null;
  unit?: string | null;
  position?: string | null;
  supervisor?: string | null;
  is_admin: boolean;
  is_atasan: boolean;
  is_karyawan: boolean;
  can_manage_users: boolean;
  can_verify_leaves: boolean;
  can_view_all_team_attendance: boolean;
  can_verify_all_attendance: boolean;
};

export type Attendance = {
  id: number;
  user_id: number;
  date: string;
  check_in?: string | null;
  check_out?: string | null;
  status: string;
  check_in_address?: string | null;
  check_out_address?: string | null;
  late_duration?: number | null;
  early_leave_duration?: number | null;
  work_duration?: number | null;
  attendance_reason?: { id: number; name: string } | null;
  user?: { id: number; name: string } | null;
};

export type LeaveType = {
  id: number;
  name: string;
  category?: string | null;
  requires_attachment?: boolean;
  requires_reason?: boolean;
  deducts_quota?: boolean;
};

export type Leave = {
  id: number;
  user_id: number;
  start_date: string;
  end_date: string;
  days: number;
  leave_type_id: number;
  reason?: string | null;
  attachment?: string | null;
  status: string;
  notes?: string | null;
  leave_type?: LeaveType | null;
  user?: User | null;
  approver?: User | null;
  verifier?: User | null;
};

export type Project = {
  id: number;
  name: string;
  code?: string | null;
  description?: string | null;
  status: string;
  progress: number;
  priority?: string | null;
  start_date?: string | null;
  end_date?: string | null;
  owner_id?: number | null;
  owner_name?: string | null;
  tasks_count?: number;
  tasks_done?: number;
};

export type Task = {
  id: number;
  project_id: number;
  title: string;
  description?: string | null;
  status: string;
  priority: string;
  progress: number;
  start_date?: string | null;
  due_date?: string | null;
  assignee_id?: number | null;
  assignee_name?: string | null;
  project_name?: string | null;
};

export type ActivityLog = {
  id: number;
  user_id?: number | null;
  project_id?: number | null;
  task_id?: number | null;
  type: string;
  action: string;
  description?: string | null;
  activity_date?: string | null;
  user_name?: string | null;
  project_name?: string | null;
  task_title?: string | null;
};

export type ContentItem = {
  id: number;
  title: string;
  youtube_url?: string | null;
  badge_text?: string | null;
  badge_color?: string | null;
  description?: string | null;
  file_url?: string | null;
  content?: string | null;
  pdf_path?: string | null;
  created_at?: string | null;
};
