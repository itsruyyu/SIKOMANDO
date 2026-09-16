import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { getProposals } from '../api/proposals';

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

function getProposalTitle(proposal) {
  return (
    proposal.title ||
    proposal.name ||
    proposal.proposal_title ||
    proposal.judul ||
    'Proposal tanpa judul'
  );
}

function getProposalStatus(proposal) {
  return (
    proposal.status ||
    proposal.lifecycle_status ||
    proposal.proposal_status ||
    '—'
  );
}

function getOrganizationName(proposal) {
  return (
    proposal.organization?.name ||
    proposal.organization_name ||
    proposal.organization?.organization_name ||
    '—'
  );
}

function formatCurrency(value) {
  if (value === null || value === undefined || value === '') {
    return '—';
  }

  const amount = Number(value);

  if (Number.isNaN(amount)) {
    return String(value);
  }

  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
  }).format(amount);
}

export default function ProposalsPage() {
  const [proposals, setProposals] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  useEffect(() => {
    let active = true;

    async function loadProposals() {
      try {
        const payload = await getProposals();

        if (active) {
          setProposals(extractItems(payload));
        }
      } catch (requestError) {
        if (active) {
          setError(
            requestError.response?.data?.message ||
              'Data proposal gagal dimuat.',
          );
        }
      } finally {
        if (active) {
          setLoading(false);
        }
      }
    }

    loadProposals();

    return () => {
      active = false;
    };
  }, []);

  async function handleReload() {
    setLoading(true);
    setError('');

    try {
      const payload = await getProposals();
      setProposals(extractItems(payload));
    } catch (requestError) {
      setError(
        requestError.response?.data?.message ||
          'Data proposal gagal dimuat.',
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
            Proposal
          </h1>

          <p className="mt-1 text-sm text-slate-600">
            Daftar proposal hibah yang terdaftar dalam SIKOMANDO.
          </p>
        </div>

        <div className="flex flex-wrap gap-3">
          <Link
            to="/proposals/create"
            className="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-blue-700"
          >
            Ajukan Proposal
          </Link>

          <button
            type="button"
            onClick={handleReload}
            disabled={loading}
            className="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100 disabled:cursor-not-allowed disabled:opacity-50"
          >
            {loading ? 'Memuat...' : 'Muat ulang'}
          </button>
        </div>
      </div>

      {loading && (
        <div className="rounded-xl border border-slate-200 bg-white p-6 text-sm text-slate-600">
          Memuat data proposal...
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

      {!loading && !error && proposals.length === 0 && (
        <div className="rounded-xl border border-dashed border-slate-300 bg-white p-10 text-center">
          <h2 className="font-semibold text-slate-800">
            Belum ada proposal
          </h2>

          <p className="mt-2 text-sm text-slate-500">
            Data proposal belum tersedia.
          </p>

          <Link
            to="/proposals/create"
            className="mt-4 inline-block rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700"
          >
            Ajukan Proposal Pertama
          </Link>
        </div>
      )}

      {!loading && !error && proposals.length > 0 && (
        <div className="overflow-hidden rounded-xl border border-slate-200 bg-white">
          <div className="overflow-x-auto">
            <table className="min-w-full divide-y divide-slate-200">
              <thead className="bg-slate-50">
                <tr>
                  <th className="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                    Proposal
                  </th>

                  <th className="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                    Organisasi
                  </th>

                  <th className="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                    Anggaran
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
                {proposals.map((proposal) => (
                  <tr
                    key={proposal.id}
                    className="hover:bg-slate-50"
                  >
                    <td className="px-6 py-4">
                      <div className="font-medium text-slate-900">
                        {getProposalTitle(proposal)}
                      </div>

                      {proposal.proposal_number && (
                        <div className="mt-1 text-xs text-slate-500">
                          Nomor: {proposal.proposal_number}
                        </div>
                      )}
                    </td>

                    <td className="px-6 py-4 text-sm text-slate-600">
                      {getOrganizationName(proposal)}
                    </td>

                    <td className="px-6 py-4 text-sm text-slate-600">
                      {formatCurrency(
                        proposal.requested_amount ||
                          proposal.total_amount ||
                          proposal.amount,
                      )}
                    </td>

                    <td className="px-6 py-4 text-sm text-slate-600">
                      {getProposalStatus(proposal)}
                    </td>

                    <td className="px-6 py-4 text-right">
                      <Link
                        to={`/proposals/${proposal.id}`}
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