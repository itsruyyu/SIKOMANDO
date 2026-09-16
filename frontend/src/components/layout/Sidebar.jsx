import {
  BuildingOffice2Icon,
  ChartBarIcon,
  ClipboardDocumentCheckIcon,
  Cog6ToothIcon,
  DocumentTextIcon,
  HomeIcon,
} from '@heroicons/react/24/outline';

import { NavLink } from 'react-router-dom';

const navigation = [
  {
    name: 'Dashboard',
    href: '/dashboard',
    icon: HomeIcon,
  },
  {
    name: 'Organisasi',
    href: '/organizations',
    icon: BuildingOffice2Icon,
  },
  {
    name: 'Proposal',
    href: '/proposals',
    icon: DocumentTextIcon,
  },
  {
    name: 'Verifikasi',
    href: '/verification',
    icon: ClipboardDocumentCheckIcon,
  },
  {
    name: 'Evaluasi',
    href: '/evaluation',
    icon: ChartBarIcon,
  },
  {
    name: 'Konfigurasi',
    href: '/settings',
    icon: Cog6ToothIcon,
  },
];

export default function Sidebar({ open, onClose }) {
  return (
    <>
      {open && (
        <button
          type="button"
          aria-label="Tutup menu"
          className="fixed inset-0 z-30 bg-black/40 lg:hidden"
          onClick={onClose}
        />
      )}

      <aside
        className={[
          'fixed inset-y-0 left-0 z-40 flex w-72 flex-col border-r border-slate-200 bg-white transition-transform',
          open ? 'translate-x-0' : '-translate-x-full',
          'lg:static lg:translate-x-0',
        ].join(' ')}
      >
        <div className="flex h-16 items-center border-b border-slate-200 px-6">
          <div>
            <h1 className="text-lg font-bold text-slate-900">
              SIKOMANDO
            </h1>

            <p className="text-xs text-slate-500">
              Manajemen Hibah Digital
            </p>
          </div>
        </div>

        <nav className="flex-1 space-y-1 overflow-y-auto p-4">
          {navigation.map((item) => {
            const Icon = item.icon;

            return (
              <NavLink
                key={item.href}
                to={item.href}
                onClick={onClose}
                className={({ isActive }) =>
                  [
                    'flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition',
                    isActive
                      ? 'bg-blue-50 text-blue-700'
                      : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900',
                  ].join(' ')
                }
              >
                <Icon className="h-5 w-5" />
                <span>{item.name}</span>
              </NavLink>
            );
          })}
        </nav>

        <div className="border-t border-slate-200 p-4 text-xs text-slate-500">
          SIKOMANDO v1.0
        </div>
      </aside>
    </>
  );
}