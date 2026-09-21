import { PROPOSAL_STATUSES, ROLE_LABELS } from './constants';

/**
 * Standard Indonesian label helper for Proposal status.
 */
export function getProposalStatusLabel(status) {
  if (!status) return 'Belum Ditentukan';
  const key = String(status).toLowerCase();
  if (PROPOSAL_STATUSES[key]) {
    return PROPOSAL_STATUSES[key].label;
  }
  return status.charAt(0).toUpperCase() + status.slice(1).replace(/_/g, ' ');
}

/**
 * Badge variant helper for Proposal status.
 */
export function getProposalStatusVariant(status) {
  if (!status) return 'default';
  const key = String(status).toLowerCase();
  switch (key) {
    case 'draft':
      return 'default';
    case 'submitted':
    case 'verification':
    case 'evaluation':
    case 'survey':
      return 'info';
    case 'revision':
      return 'warning';
    case 'verified':
    case 'recommended':
      return 'primary';
    case 'approval':
      return 'warning';
    case 'approved':
    case 'disbursed':
    case 'completed':
      return 'success';
    case 'rejected':
      return 'danger';
    default:
      return 'default';
  }
}

/**
 * Assignment status labels in Indonesian.
 */
export function getAssignmentStatusLabel(status) {
  switch (String(status).toUpperCase()) {
    case 'ASSIGNED':
      return 'Ditugaskan';
    case 'IN_PROGRESS':
      return 'Sedang Dikerjakan';
    case 'COMPLETED':
      return 'Selesai';
    case 'REVOKED':
      return 'Dicabut';
    default:
      return status || '-';
  }
}

/**
 * Disbursement status labels in Indonesian.
 */
export function getDisbursementStatusLabel(status) {
  switch (String(status).toLowerCase()) {
    case 'draft':
      return 'Konsep SP2D';
    case 'ready':
      return 'Siap Disalurkan';
    case 'partially_disbursed':
      return 'Disalurkan Sebagian';
    case 'disbursed':
      return 'Tuntas Disalurkan';
    case 'cancelled':
      return 'Dibatalkan';
    default:
      return status || '-';
  }
}

/**
 * LPJ status labels in Indonesian.
 */
export function getLpjStatusLabel(status) {
  switch (String(status).toLowerCase()) {
    case 'draft':
      return 'Konsep LPJ';
    case 'submitted':
      return 'Menunggu Verifikasi LPJ';
    case 'verified':
      return 'LPJ Sah & Diverifikasi';
    case 'revision':
      return 'Perlu Perbaikan LPJ';
    case 'approved':
      return 'LPJ Diterima Tuntas';
    case 'rejected':
      return 'LPJ Ditolak';
    default:
      return status || '-';
  }
}

