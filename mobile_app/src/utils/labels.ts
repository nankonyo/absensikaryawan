export const formatLabel = (value?: string | null): string => {
  if (!value) return '';

  return String(value)
    .replace(/_/g, ' ')
    .replace(/\b\w/g, (char) => char.toUpperCase());
};
