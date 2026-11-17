export const colors = {
  primary: '#2563eb',
  primaryDark: '#1d4ed8',
  primarySoft: '#dbeafe',
  bg: '#f8fafc',
  card: '#ffffff',
  border: '#e2e8f0',
  text: '#0f172a',
  muted: '#64748b',
  subtle: '#94a3b8',
  success: '#059669',
  successSoft: '#d1fae5',
  warning: '#d97706',
  warningSoft: '#fef3c7',
  danger: '#dc2626',
  dangerSoft: '#fee2e2',
  info: '#0284c7',
  infoSoft: '#e0f2fe',
  slate: '#475569',
};

export const spacing = {
  xs: 4,
  sm: 8,
  md: 12,
  lg: 16,
  xl: 24,
  xxl: 32,
};

export const radius = {
  sm: 8,
  md: 12,
  lg: 16,
  xl: 24,
  full: 999,
};

export const shadow = {
  card: {
    shadowColor: '#0f172a',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.06,
    shadowRadius: 8,
    elevation: 2,
  },
};

export const statusColor = (status?: string) => {
  switch (status) {
    case 'valid':
    case 'approved':
    case 'done':
    case 'completed':
    case 'active':
      return { bg: colors.successSoft, text: colors.success };
    case 'pending':
    case 'in_progress':
    case 'planned':
      return { bg: colors.warningSoft, text: colors.warning };
    case 'invalid':
    case 'rejected':
    case 'cancelled':
      return { bg: colors.dangerSoft, text: colors.danger };
    default:
      return { bg: colors.infoSoft, text: colors.info };
  }
};
