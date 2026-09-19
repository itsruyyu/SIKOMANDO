import React, { useEffect, useState } from 'react';
import { getPublicTransparency } from '../../api/public';
import DataTable from '../../components/common/DataTable';
import StatusBadge from '../../components/common/StatusBadge';
import { BuildingOffice2Icon, BanknotesIcon, ShieldCheckIcon } from '@heroicons/react/24/outline';

export default function PublicTransparencyPage() {
  const [data, setData] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    async function loadTransparency() {
      try {
        const res = await getPublicTransparency();
        setData(res?.data || res || []);
      } catch (err) {
        console.error('Failed to load transparency data', err);
      } finally {
        setLoading(false);
      }
    }

    loadTransparency();
  }, []);

  const columns = [
    {
      title: 'Nama Lembaga / Organisasi',
      key: 'organization_name',
      render: (val, row) => (
        <div>
          <p className="font-semibold text-slate-900">{val || row.organization?.name || 'Organisasi'}</p>
          <p className="text-xs text-slate-500">{row.proposal_title || row.title || 'Usulan Kegiatan'}</p>
        </div>
      ),
    },
    {
      title: 'Program Hibah',
      key: 'program_name',
      render: (val, row) => (
        <span className="text-xs text-slate-700">{val || row.grant_program?.name || 'Program Daerah'}</span>
      ),
    },
    {
      title: 'Tahun Anggaran',
      key: 'fiscal_year',
      render: (val, row) => (
        <span className="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-700">
          {val || row.grant_program?.fiscal_year || '2026'}
        </span>
      ),
    },
    {
      title: 'Alokasi Disetujui',
      key: 'approved_amount',
      render: (val, row) => {
        const amount = val ?? row.approved_amount ?? row.requested_amount;
        return (
          <span className="font-semibold text-slate-900">
            {amount ? `Rp ${Number(amount).toLocaleString('id-ID')}` : '—'}
          </span>
        );
      },
    },
    {
      title: 'Status Realisasi',
      key: 'status',
      render: (val) => <StatusBadge status={val || 'approved'} />,
    },
  ];

  return (
    <div className="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
      <div className="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
        <div>
          <div className="flex items-center gap-2">
            <span className="rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700 border border-emerald-200">
              Keterbukaan Informasi Publik
            </span>
          </div>
          <h1 className="mt-2 text-3xl font-extrabold text-slate-900">Portal Transparansi Hibah</h1>
          <p className="mt-1 text-sm text-slate-600 max-w-2xl">
            Informasi daftar lembaga penerima dana hibah, nilai bantuan yang disetujui, dan progres
            pertanggungjawaban demi keterbukaan tata kelola anggaran.
          </p>
        </div>
      </div>

      <div className="mt-8">
        <DataTable
          columns={columns}
          data={data}
          loading={loading}
          searchPlaceholder="Cari nama lembaga atau judul usulan..."
          searchKey="organization_name"
          emptyTitle="Belum Ada Data Penerima"
          emptyDescription="Data penerima hibah belum tersedia atau dalam proses penetapan keputusan."
        />
      </div>
    </div>
  );
}

