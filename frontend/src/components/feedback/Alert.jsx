import React from 'react';
import {
  CheckCircleIcon,
  ExclamationTriangleIcon,
  InformationCircleIcon,
  XCircleIcon,
} from '@heroicons/react/24/outline';

/**
 * Standard Alert box with variants (info, success, warning, danger) and optional title
 */
export function Alert({
  children,
  type = 'info',
  title,
  className = '',
}) {
  const styles = {
    info: 'bg-blue-50 border-blue-200 text-blue-800',
    success: 'bg-emerald-50 border-emerald-200 text-emerald-800',
    warning: 'bg-amber-50 border-amber-200 text-amber-800',
    danger: 'bg-rose-50 border-rose-200 text-rose-800',
  };

  const icons = {
    info: <InformationCircleIcon className="w-5 h-5 text-blue-600 shrink-0" />,
    success: <CheckCircleIcon className="w-5 h-5 text-emerald-600 shrink-0" />,
    warning: <ExclamationTriangleIcon className="w-5 h-5 text-amber-600 shrink-0" />,
    danger: <XCircleIcon className="w-5 h-5 text-rose-600 shrink-0" />,
  };

  return (
    <div className={`p-4 rounded-xl border flex items-start gap-3 text-sm leading-relaxed ${styles[type] || styles.info} ${className}`}>
      <div className="mt-0.5">{icons[type] || icons.info}</div>
      <div className="flex-1">
        {title && <h4 className="font-bold mb-0.5">{title}</h4>}
        <div>{children}</div>
      </div>
    </div>
  );
}

export default Alert;

