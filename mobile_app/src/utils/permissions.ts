import type { User } from '../types';

export const can = (user: User | null | undefined, code: string, action = 'read'): boolean => {
  if (!user) return false;
  if (user.is_admin) return true;
  return Boolean(user.permissions?.[code]?.[action]);
};

export const canAny = (user: User | null | undefined, codes: string[], action = 'read'): boolean =>
  codes.some((code) => can(user, code, action));
