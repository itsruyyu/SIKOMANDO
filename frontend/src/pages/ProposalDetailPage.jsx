import { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { getProposal } from '../api/proposals';

function getProposalTitle(proposal) {
  return (
    proposal.title ||
    proposal.name ||
    proposal.proposal_title ||
    proposal.judul ||
    'Detail Proposal'
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

function getValue(payload) {
  return payload?.data || payload;
}

export default function ProposalDetailPage() {
  const { id } = useParams();

  const [proposal, setProposal] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  useEffect(() => {
    let active = true;

    async function loadProposal() {
      try {
        const payload = await getProposal(id);

        if (active) {
          setProposal(getValue(payload));
        }
      } catch (requestError) {
        if (active) {
          setError(
            requestError.response?.data?.message ||
              'Detail proposal gagal dimuat.',
          );
        }
      } finally {
        if (active) {
          setLoading(false);
        }
      }
    }

    loadProposal();

    return () => {
      active = false;
    };
  }, [id]);

  if (loading) {
    return (
      <p className="text-sm text-slate-600">
        Memuat detail proposal...
      </p>
    );
  }

  if (error) {
    return (
      <div className="rounded-xl border border-red-200 bg-red-50 p-6 text-sm text-red-700">
        {error}
      </div>
    );
  }

  if (!proposal) {
    return (
      <div className="rounded-xl border border-slate-200 bg-white p-6">
        Proposal tidak ditemukan.
      </div>
    );
  }

  const organizationName =
    proposal.organization?.name ||
    proposal.organization_name ||
    proposal.organization?.organization_name ||
    '—';

  const status =
    proposal.status ||
    proposal.lifecycle_status ||
    proposal.proposal_status ||
    '—';

  const requestedAmount =
    proposal.requested_amount ||
    proposal.total_amount ||
    proposal.amount;

  return (
    <section className="space-y-6">
      <div>
        <Link
          to="/proposals"
          className="text-sm font-medium text-blue-600 hover:text-blue-800"
        >
          ← Kembali ke proposal
        </Link>

        <h1 className="mt-4 text-2xl font-bold text-slate-900">
          {getProposalTitle(proposal)}
        </h1>

        {proposal.proposal_number && (
          <p className="mt-1 text-sm text-slate-500">
            Nomor proposal: {proposal.proposal_number}
          </p>
        )}
      </div>

      <div className="grid gap-6 md:grid-cols-2">
        <div className="rounded-xl border border-slate-200 bg-white p-6">
          <h2 className="font-semibold text-slate-900">
            Informasi Proposal
          </h2>

          <dl className="mt-4 space-y-4 text-sm">
            <div>
              <dt className="text-slate-500">Organisasi</dt>
              <dd className="mt-1 font-medium text-slate-800">
                {organizationName}
              </dd>
            </div>

            <div>
              <dt className="text-slate-500">Status</dt>
              <dd className="mt-1 font-medium text-slate-800">
                {status}
              </dd>
            </div>

            <div>
              <dt className="text-slate-500">Jumlah Pengajuan</dt>
              <dd className="mt-1 font-medium text-slate-800">
                {formatCurrency(requestedAmount)}
              </dd>
            </div>

            <div>
              <dt className="text-slate-500">Tanggal Pengajuan</dt>
              <dd className="mt-1 font-medium text-slate-800">
                {proposal.submitted_at ||
                  proposal.submission_date ||
                  proposal.created_at ||
                  '—'}
              </dd>
            </div>
          </dl>
        </div>

        <div className="rounded-xl border border-slate-200 bg-white p-6">
          <h2 className="font-semibold text-slate-900">
            Deskripsi
          </h2>

          <p className="mt-4 whitespace-pre-line text-sm leading-6 text-slate-600">
            {proposal.description ||
              proposal.summary ||
              proposal.ringkasan ||
              'Deskripsi proposal belum tersedia.'}
          </p>
        </div>
      </div>

      {Array.isArray(proposal.budget_items) &&
        proposal.budget_items.length > 0 && (
          <div className="overflow-hidden rounded-xl border border-slate-200 bg-white">
            <div className="border-b border-slate-200 px-6 py-4">
              <h2 className="font-semibold text-slate-900">
                Rencana Anggaran Biaya
              </h2>
            </div>

            <div className="overflow-x-auto">
              <table className="min-w-full divide-y divide-slate-200">
                <thead className="bg-slate-50">
                  <tr>
                    <th className="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                      Uraian
                    </th>

                    <th className="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                      Volume
                    </th>

                    <th className="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                      Harga Satuan
                    </th>

                    <th className="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                      Total
                    </th>
                  </tr>
                </thead>

                <tbody className="divide-y divide-slate-200">
                  {proposal.budget_items.map((item) => (
                    <tr key={item.id}>
                      <td className="px-6 py-4 text-sm text-slate-700">
                        {item.description ||
                          item.name ||
                          item.item_name ||
                          '—'}
                      </td>

                      <td className="px-6 py-4 text-sm text-slate-600">
                        {item.quantity || item.volume || '—'}
                      </td>

                      <td className="px-6 py-4 text-sm text-slate-600">
                        {formatCurrency(
                          item.unit_price ||
                            item.price ||
                            item.harga_satuan,
                        )}
                      </td>

                      <td className="px-6 py-4 text-sm text-slate-600">
                        {formatCurrency(
                          item.total_amount ||
                            item.total ||
                            item.subtotal,
                        )}
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