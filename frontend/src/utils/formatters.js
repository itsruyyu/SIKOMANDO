import { PROPOSAL_STATUSES } from './constants';

/**
 * Format numeric value into Indonesian Rupiah currency string.
 * @param {number|string} value 
 * @returns {string} e.g. "Rp 150.000.000"
 */
export function formatCurrency(value) {
  if (value === null || value === undefined || value === '') return 'Rp 0';
  const numeric = typeof value === 'string' ? parseFloat(value) : value;
  if (isNaN(numeric)) return 'Rp 0';
  
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
  }).format(numeric);
}

/**
 * Format ISO date string into Indonesian readable date.
 * @param {string} isoString 
 * @returns {string} e.g. "19 September 2026"
 */
export function formatDate(isoString) {
  if (!isoString) return '-';
  try {
    const date = new Date(isoString);
    if (isNaN(date.getTime())) return '-';
    return new Intl.DateTimeFormat('id-ID', {
      day: 'numeric',
      month: 'long',
      year: 'numeric',
    }).format(date);
  } catch {
    return '-';
  }
}

export const formatDateIndo = formatDate;

/**
 * Format ISO date string into Indonesian readable date and time with WITA timezone.
 * @param {string} isoString 
 * @returns {string} e.g. "19 Sep 2026, 14:30 WITA"
 */
export function formatDateTime(isoString) {
  if (!isoString) return '-';
  try {
    const date = new Date(isoString);
    if (isNaN(date.getTime())) return '-';
    const formatted = new Intl.DateTimeFormat('id-ID', {
      day: 'numeric',
      month: 'short',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
      hour12: false,
    }).format(date);
    return `${formatted} WITA`;
  } catch {
    return '-';
  }
}

/**
 * Format byte size into readable string.
 * @param {number} bytes 
 * @returns {string} e.g. "2.4 MB"
 */
export function formatFileSize(bytes) {
  if (!bytes || bytes === 0) return '0 B';
  const k = 1024;
  const sizes = ['B', 'KB', 'MB', 'GB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return `${parseFloat((bytes / Math.pow(k, i)).toFixed(1))} ${sizes[i]}`;
}

/**
 * Format geographic coordinates.
 * @param {number|string} lat 
 * @param {number|string} lng 
 * @returns {string} e.g. "1.4748° N, 124.8428° E"
 */
export function formatCoordinates(lat, lng) {
  if (!lat || !lng) return 'Koordinat belum disetel';
  const latNum = parseFloat(lat);
  const lngNum = parseFloat(lng);
  const latDir = latNum >= 0 ? 'LU' : 'LS';
  const lngDir = lngNum >= 0 ? 'BT' : 'BB';
  return `${Math.abs(latNum).toFixed(5)}° ${latDir}, ${Math.abs(lngNum).toFixed(5)}° ${lngDir}`;
}

/**
 * Get visual badge configuration for a proposal status.
 * @param {string} status 
 * @returns {{ label: string, color: string }}
 */
export function getStatusBadge(status) {
  if (!status) return { label: 'Tidak Diketahui', color: 'bg-gray-100 text-gray-700 border-gray-200' };
  return PROPOSAL_STATUSES[status.toLowerCase()] || {
    label: status.toUpperCase(),
    color: 'bg-gray-100 text-gray-700 border-gray-200',
  };
}
