import React from 'react';
import { getStatusBadge } from '../../utils/formatters';

/**
 * Standard Status Badge with automatic color mapping from status key or custom styling
 */
export function Badge({
  children,
  status,
  variant = 'default',
  size = 'md',
  dot = false,
  className = '',
}) {
  let badgeStyle;
  let label = children;

  if (status) {
    const config = getStatusBadge(status);
    badgeStyle = config.color;
    if (!children) {
      label = config.label;
    }
  } else {
    const variants = {
      default: 'bg-slate-100 text-slate-700 border-slate-200',
      primary: 'bg-blue-100 text-blue-800 border-blue-200',
      success: 'bg-emerald-100 text-emerald-800 border-emerald-200',
      warning: 'bg-amber-100 text-amber-800 border-amber-200',
      danger: 'bg-rose-100 text-rose-800 border-rose-200',
      info: 'bg-cyan-100 text-cyan-800 border-cyan-200',
    };
    badgeStyle = variants[variant] || variants.default;
  }

  const sizes = {
    sm: 'text-[10px] px-2 py-0.5 font-medium',
    md: 'text-xs px-2.5 py-1 font-semibold',
    lg: 'text-sm px-3 py-1.5 font-bold',
  };

  return (
    <span
      className={`inline-flex items-center gap-1.5 rounded-full border leading-none tracking-wide ${badgeStyle} ${sizes[size] || sizes.md} ${className}`}
    >
      {dot && (
        <span className="w-1.5 h-1.5 rounded-full bg-current opacity-75 shrink-0" />
      )}
      <span>{label}</span>
    </span>
  );
}

export default Badge;
