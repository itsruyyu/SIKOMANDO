import React, { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { getAssignedSurveys } from '../api/fieldSurveys';
import DataTable from '../components/common/DataTable';
import StatusBadge from '../components/common/StatusBadge';
import { MapPinIcon, CalendarIcon } from '@heroicons/react/24/outline';

export default function FieldSurveyListPage() {
  const navigate = useNavigate();
  const [surveys, setSurveys] = useState([]);
  const [loading, setLoading] = useState(true);

  async function loadSurveys() {
    setLoading(true);
    try {
      const res = await getAssignedSurveys();
      const list = res?.data || res || [];
      setSurveys(Array.isArray(list) ? list : list.data || []);
    } catch (err) {
      console.error('Failed to load field surveys', err);
    } finally {
      setLoading(false);
    }
  }

  useEffect(() => {
    loadSurveys();
  }, []);

  const columns = [
    {
      title: 'Proposal & Organisasi',
      key: 'proposal',
      render: (val, row) => (
        <div>
          <span className="font-mono text-xs text-blue-600 font-semibold">
            #{row.proposal?.proposal_number || row.proposal_id?.slice(0, 8)}
          </span>
          <p className="font-bold text-slate-900">{row.proposal?.title || 'Usulan Kegiatan'}</p>
          <p className="text-xs text-slate-500">{row.proposal?.organization?.name || 'Organisasi Pemohon'}</p>
        </div>
      ),
    },
    {
      title: 'Jadwal Rencana Survei',
      key: 'scheduled_at',
      render: (val, row) => (
        <div className="flex items-center gap-1.5 text-xs text-slate-700">
          <CalendarIcon className="h-4 w-4 text-purple-600" />
          <span>{val || row.schedule_date || 'Belum Dijadwalkan'}</span>
        </div>
      ),
    },
    {
      title: 'Lokasi / Alamat',
      key: 'location',
      render: (val, row) => (
        <div className="flex items-center gap-1.5 text-xs text-slate-600 max-w-xs">
          <MapPinIcon className="h-4 w-4 text-slate-400 shrink-0" />
          <span className="truncate">{row.location_address || row.proposal?.organization?.address || 'Lokasi Sekretariat'}</span>
        </div>
      ),
    },
    {
      title: 'Status Survei',
      key: 'status',
      render: (val) => <StatusBadge status={val || 'survey'} />,
    },
    {
      title: 'Aksi',
      key: 'action',
      render: (_, row) => (
        <button
          type="button"
          onClick={() => navigate(`/field-surveys/${row.proposal_id || row.proposal?.id}/${row.id}`)}
          className="inline-flex items-center gap-1 rounded-lg bg-purple-600 px-3 py-1.5 text-xs font-semibold text-white shadow-2xs hover:bg-purple-700 transition"
        >
          <MapPinIcon className="h-4 w-4" />
          <span>Lembar Survei</span>
        </button>
      ),
    },
  ];

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold text-slate-900">Penugasan Survei Lapangan</h1>
        <p className="text-xs text-slate-500 mt-1">
          Pemeriksaan fisik langsung ke sekretariat atau lokasi kegiatan organisasi untuk validasi faktual.
        </p>
      </div>

      <DataTable
        columns={columns}
        data={surveys}
        loading={loading}
        searchPlaceholder="Cari jadwal survei, nomor usulan, atau nama lembaga..."
        emptyTitle="Tidak Ada Tugas Survei"
        emptyDescription="Saat ini Anda tidak memiliki agenda survei lapangan yang aktif."
      />
    </div>
  );
}

