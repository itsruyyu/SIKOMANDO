import React from 'react';
import Breadcrumbs from './Breadcrumbs';

/**
 * Standard PageHeader with title, subtitle, breadcrumbs, and action button slots
 */
export function PageHeader({
  title,
  subtitle,
  breadcrumbs = [],
  action = null,
  badge = null,
  className = '',
}) {
  return (
    <div className={`mb-6 flex flex-col gap-2 ${className}`}>
      {breadcrumbs.length > 0 && <Breadcrumbs items={breadcrumbs} />}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <div className="flex items-center gap-3">
            <h1 className="text-xl sm:text-2xl font-black tracking-tight text-slate-900 leading-tight">
              {title}
            </h1>
            {badge}
          </div>
          {subtitle && <p className="text-xs sm:text-sm text-slate-500 mt-1">{subtitle}</p>}
        </div>
        {action && <div className="flex items-center gap-2.5 shrink-0">{action}</div>}
      </div>
    </div>
  );
}

export default PageHeader;

