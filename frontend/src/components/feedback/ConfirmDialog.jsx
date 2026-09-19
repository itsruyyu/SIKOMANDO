import React from 'react';
import Modal from '../ui/Modal';
import Button from '../ui/Button';
import { ExclamationTriangleIcon } from '@heroicons/react/24/outline';

/**
 * Standard confirmation dialog for critical or destructive actions
 */
export function ConfirmDialog({
  isOpen,
  onClose,
  onConfirm,
  title = 'Konfirmasi Tindakan',
  message = 'Apakah Anda yakin ingin melanjutkan tindakan ini?',
  confirmLabel = 'Ya, Lanjutkan',
  cancelLabel = 'Batal',
  variant = 'danger',
  isLoading = false,
}) {
  return (
    <Modal
      isOpen={isOpen}
      onClose={onClose}
      title={title}
      size="sm"
      footer={
        <>
          <Button variant="secondary" size="sm" onClick={onClose} disabled={isLoading}>
            {cancelLabel}
          </Button>
          <Button
            variant={variant}
            size="sm"
            onClick={onConfirm}
            isLoading={isLoading}
          >
            {confirmLabel}
          </Button>
        </>
      }
    >
      <div className="flex items-start gap-4 py-2">
        <div className={`p-2.5 rounded-xl shrink-0 ${variant === 'danger' ? 'bg-rose-100 text-rose-600' : 'bg-amber-100 text-amber-600'}`}>
          <ExclamationTriangleIcon className="w-6 h-6" />
        </div>
        <div className="text-sm text-slate-600 leading-relaxed pt-0.5">
          {message}
        </div>
      </div>
    </Modal>
  );
}

export default ConfirmDialog;

