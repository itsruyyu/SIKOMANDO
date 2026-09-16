import { Bars3Icon } from '@heroicons/react/24/outline'
import { useAuth } from '../../context/useAuth'

export default function Topbar({ onMenuClick }) {
  const { user, logout } = useAuth()

  return (
    <header className="flex h-16 items-center justify-between border-b border-slate-200 bg-white px-4 sm:px-6">
      <button
        type="button"
        aria-label="Buka menu"
        className="rounded-lg p-2 text-slate-600 hover:bg-slate-100 lg:hidden"
        onClick={onMenuClick}
      >
        <Bars3Icon className="h-6 w-6" />
      </button>

      <div className="ml-auto flex items-center gap-4">
        <div className="hidden text-right sm:block">
          <p className="text-sm font-semibold text-slate-900">
            {user?.name ?? 'Pengguna'}
          </p>
          <p className="text-xs text-slate-500">
            {user?.email ?? ''}
          </p>
        </div>

        <button
          type="button"
          onClick={logout}
          className="rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
          Logout
        </button>
      </div>
    </header>
  )
}