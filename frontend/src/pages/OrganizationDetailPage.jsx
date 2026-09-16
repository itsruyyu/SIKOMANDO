import { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { getOrganization } from '../api/organizations';

export default function OrganizationDetailPage() {
  const { id } = useParams();

  const [organization, setOrganization] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  useEffect(() => {
    async function loadOrganization() {
      setLoading(true);
      setError('');

      try {
        const payload = await getOrganization(id);
        setOrganization(payload?.data || payload);
      } catch (requestError) {
        setError(
          requestError.response?.data?.message ||
            'Detail organisasi gagal dimuat.',
        );
      } finally {
        setLoading(false);
      }
    }

    loadOrganization();
  }, [id]);

  if (loading) {
    return <p className="text-sm text-slate-600">Memuat detail organisasi...</p>;
  }

  if (error) {
    return (
      <div className="rounded-xl border border-red-200 bg-red-50 p-6 text-sm text-red-700">
        {error}
      </div>
    );
  }

  if (!organization) {
    return (
      <div className="rounded-xl border border-slate-200 bg-white p-6">
        Organisasi tidak ditemukan.
      </div>
    );
  }

  return (
    <section className="space-y-6">
      <div>
        <Link
          to="/organizations"
          className="text-sm font-medium text-blue-600 hover:text-blue-800"
        >
          ← Kembali ke organisasi
        </Link>

        <h1 className="mt-4 text-2xl font-bold text-slate-900">
          {organization.name ||
            organization.organization_name ||
            organization.nama ||
            'Detail Organisasi'}
        </h1>
      </div>

      <div className="grid gap-6 md:grid-cols-2">
        <div className="rounded-xl border border-slate-200 bg-white p-6">
          <h2 className="font-semibold text-slate-900">Informasi Umum</h2>

          <dl className="mt-4 space-y-4 text-sm">
            <div>
              <dt className="text-slate-500">Status</dt>
              <dd className="mt-1 font-medium text-slate-800">
                {organization.status ||
                  organization.lifecycle_status ||
                  '—'}
              </dd>
            </div>

            <div>
              <dt className="text-slate-500">Nomor Registrasi</dt>
              <dd className="mt-1 font-medium text-slate-800">
                {organization.registration_number || '—'}
              </dd>
            </div>

            <div>
              <dt className="text-slate-500">Email</dt>
              <dd className="mt-1 font-medium text-slate-800">
                {organization.email || '—'}
              </dd>
            </div>

            <div>
              <dt className="text-slate-500">Telepon</dt>
              <dd className="mt-1 font-medium text-slate-800">
                {organization.phone || '—'}
              </dd>
            </div>
          </dl>
        </div>

        <div className="rounded-xl border border-slate-200 bg-white p-6">
          <h2 className="font-semibold text-slate-900">Alamat</h2>

          <p className="mt-4 whitespace-pre-line text-sm leading-6 text-slate-600">
            {organization.address || 'Alamat belum tersedia.'}
          </p>
        </div>
      </div>
    </section>
  );
}