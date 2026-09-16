import { useNavigate } from 'react-router-dom'
import { useAuth } from '../context/useAuth'

function DashboardPage() {
  const { user, logout } = useAuth()
  const navigate = useNavigate()

  async function handleLogout() {
    await logout()
    navigate('/login', { replace: true })
  }

  return (
    <main className="min-h-screen bg-slate-100">
      <header className="border-b border-slate-200 bg-white">
        <div className="mx-auto flex max-w-7xl items-center justify-between px-6 py-4">
          <div>
            <h1 className="text-xl font-bold text-slate-900">SIKOMANDO</h1>
            <p className="text-sm text-slate-500">Dashboard</p>
          </div>

          <button
            type="button"
            onClick={handleLogout}
            className="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
          >
            Keluar
          </button>
        </div>
      </header>

      <section className="mx-auto max-w-7xl px-6 py-10">
        <div className="rounded-2xl bg-white p-8 shadow-sm">
          <h2 className="text-2xl font-semibold text-slate-900">
            Selamat datang{user?.name ? `, ${user.name}` : ''}.
          </h2>
          <p className="mt-2 text-slate-600">
            Fondasi dashboard SIKOMANDO berhasil dimuat.
          </p>
        </div>
      </section>
    </main>
  )
}

export default DashboardPage