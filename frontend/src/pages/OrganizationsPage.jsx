import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { getOrganizations } from '../api/organizations';

function extractItems(payload) {
  if (Array.isArray(payload)) {
    return payload;
  }

  if (Array.isArray(payload?.data)) {
    return payload.data;
  }

  if (Array.isArray(payload?.data?.data)) {
    return payload.data.data;
  }

  return [];
}

function getOrganizationName(organization) {
  return (
    organization.name ||
    organization.organization_name ||
    organization.nama ||
    'Organisasi tanpa nama'
  );
}

function getOrganizationStatus(organization) {
  return organization.status || organization.lifecycle_status || '—';
}

export default function OrganizationsPage() {
  const [organizations, setOrganizations] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  useEffect(() => {
    let active = true;

    async function loadOrganizations() {
      try {
        const payload = await getOrganizations();

        if (active) {
          setOrganizations(extractItems(payload));
        }
      } catch (requestError) {
        if (active) {
          setError(
            requestError.response?.data?.message ||
              'Data organisasi gagal dimuat.',
          );
        }
      } finally {
        if (active) {
          setLoading(false);
        }
      }
    }

    loadOrganizations();

    return () => {
      active = false;
    };
  }, []);

  async function handleReload() {
    setLoading(true);
    setError('');

    try {
      const payload = await getOrganizations();
      setOrganizations(extractItems(payload));
    } catch (requestError) {
      setError(
        requestError.response?.data?.message ||
          'Data organisasi gagal dimuat.',
      );
    } finally {
      setLoading(false);
    }
  }

  return (
    <section className="space-y-6">
      <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
        <div>
          <h1 className="text-2xl font-bold text-slate-900">
            Organisasi
          </h1>

          <p className="mt-1 text-sm text-slate-600">
            Daftar organisasi pemohon hibah SIKOMANDO.
          </p>
        </div>

        <button
          type="button"
          onClick={handleReload}
          disabled={loading}
          className="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100 disabled:cursor-not-allowed disabled:opacity-50"
        >
          {loading ? 'Memuat...' : 'Muat ulang'}
        </button>
      </div>

      {loading && (
        <div className="rounded-xl border border-slate-200 bg-white p-6 text-sm text-slate-600">
          Memuat data organisasi...
        </div>
      )}

      {!loading && error && (
        <div className="rounded-xl border border-red-200 bg-red-50 p-6">
          <p className="text-sm text-red-700">{error}</p>

          <button
            type="button"
            onClick={handleReload}
            className="mt-3 rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700"
          >
            Coba lagi
          </button>
        </div>
      )}

      {!loading && !error && organizations.length === 0 && (
        <div className="rounded-xl border border-dashed border-slate-300 bg-white p-10 text-center">
          <h2 className="font-semibold text-slate-800">
            Belum ada organisasi
          </h2>

          <p className="mt-2 text-sm text-slate-500">
            Data organisasi belum tersedia.
          </p>
        </div>
      )}

      {!loading && !error && organizations.length > 0 && (
        <div className="overflow-hidden rounded-xl border border-slate-200 bg-white">
          <div className="overflow-x-auto">
            <table className="min-w-full divide-y divide-slate-200">
              <thead className="bg-slate-50">
                <tr>
                  <th className="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                    Organisasi
                  </th>

                  <th className="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                    Status
                  </th>

                  <th className="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                    Aksi
                  </th>
                </tr>
              </thead>

              <tbody className="divide-y divide-slate-200">
                {organizations.map((organization) => (
                  <tr
                    key={organization.id}
                    className="hover:bg-slate-50"
                  >
                    <td className="px-6 py-4">
                      <div className="font-medium text-slate-900">
                        {getOrganizationName(organization)}
                      </div>

                      {organization.registration_number && (
                        <div className="mt-1 text-xs text-slate-500">
                          Nomor registrasi:{' '}
                          {organization.registration_number}
                        </div>
                      )}
                    </td>

                    <td className="px-6 py-4 text-sm text-slate-600">
                      {getOrganizationStatus(organization)}
                    </td>

                    <td className="px-6 py-4 text-right">
                      <Link
                        to={`/organizations/${organization.id}`}
                        className="text-sm font-medium text-blue-600 hover:text-blue-800"
                      >
                        Detail
                      </Link>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      )}
    </section>
  );
}