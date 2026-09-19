import React, { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import api from '../../services/api';
import { formatCurrency, formatDate } from '../../utils/formatters';
import { openPdf } from '../../utils/pdf';
import PageHeader from '../../components/layout/PageHeader';
import Card, { CardBody } from '../../components/ui/Card';
import Table, { TableHead, TableBody, TableRow, TableHeaderCell, TableCell } from '../../components/ui/Table';
import Button from '../../components/ui/Button';
import Spinner from '../../components/feedback/Spinner';
import EmptyState from '../../components/feedback/EmptyState';
import {
  ClipboardDocumentListIcon,
  MagnifyingGlassIcon,
  ArrowRightIcon,
  PrinterIcon,
} from '@heroicons/react/24/outline';

export function LpjListPage() {
  const [lpjList, setLpjList] = useState([]);
  const [isLoading, setIsLoading] = useState(true);
  const [search, setSearch] = useState('');

  useEffect(() => {
    let isMounted = true;
    async function loadLpj() {
      setIsLoading(true);
      try {
        const res = await api.get('/lpj');
        if (isMounted) {
          const list = Array.isArray(res?.data) ? res.data : (Array.isArray(res?.data?.data) ? res.data.data : []);
          setLpjList(list);
        }
      } catch (err) {
        console.error('Failed to load LPJ list:', err);
      } finally {
        if (isMounted) setIsLoading(false);
      }
    }

    loadLpj();
    return () => { isMounted = false; };
  }, []);

  const filtered = lpjList.filter((l) => {
    const num = l.lpj_number || '';
    const org = l.proposal?.organization?.name || '';
    const title = l.proposal?.title || '';
    return num.toLowerCase().includes(search.toLowerCase()) ||
           org.toLowerCase().includes(search.toLowerCase()) ||
           title.toLowerCase().includes(search.toLowerCase());
  });

  return (
    <div className="space-y-6 pb-20">
      <PageHeader
        title="Laporan Pertanggungjawaban (LPJ) Hibah"
        subtitle="Verifikasi pertanggungjawaban penggunaan dana hibah daerah, kesesuaian realisasi belanja vs RAB, dan penutupan berkas"
        breadcrumbs={[{ label: 'Laporan LPJ' }]}
      />

      {/* Search Bar */}
      <Card className="border-slate-200">
        <CardBody className="p-4">
          <div className="relative max-w-md">
            <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
              <MagnifyingGlassIcon className="w-4 h-4" />
            </div>
            <input
              type="text"
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              placeholder="Cari nomor LPJ, nama ormas, atau proposal..."
              className="w-full pl-9 pr-3 py-2 text-xs sm:text-sm bg-slate-50 border border-slate-200 rounded-lg focus:outline-hidden focus:bg-white focus:border-blue-500"
            />
          </div>
        </CardBody>
      </Card>

      {/* Table */}
      <Card className="border-slate-200 shadow-xs">
        {isLoading ? (
          <Spinner label="Memuat berkas LPJ..." />
        ) : filtered.length === 0 ? (
          <div className="p-12">
            <EmptyState
              icon={ClipboardDocumentListIcon}
              title="Belum Ada Laporan LPJ Masuk"
              description="Laporan pertanggungjawaban yang diajukan oleh organisasi penerima hibah akan tampil di sini."
            />
          </div>
        ) : (
          <Table>
            <TableHead>
              <tr>
                <TableHeaderCell>Nomor LPJ</TableHeaderCell>
                <TableHeaderCell>Lembaga Pelapor</TableHeaderCell>
                <TableHeaderCell>Judul Kegiatan</TableHeaderCell>
                <TableHeaderCell>Total Realisasi (Rp)</TableHeaderCell>
                <TableHeaderCell>Status LPJ</TableHeaderCell>
                <TableHeaderCell className="text-right">Aksi</TableHeaderCell>
              </tr>
            </TableHead>
            <TableBody>
              {filtered.map((lpj) => (
                <TableRow key={lpj.id}>
                  <TableCell mono className="font-bold text-blue-900">
                    {lpj.lpj_number || 'LPJ/2026/01'}
                  </TableCell>
                  <TableCell>
                    <div className="font-bold text-slate-900">{lpj.proposal?.organization?.name || '-'}</div>
                    <div className="text-[11px] text-slate-400">Ketua: {lpj.proposal?.applicant?.name || '-'}</div>
                  </TableCell>
                  <TableCell>
                    <div className="text-xs text-slate-800 max-w-xs truncate">{lpj.proposal?.title}</div>
                  </TableCell>
                  <TableCell mono className="font-black text-emerald-800">
                    {formatCurrency(lpj.total_spent || lpj.proposal?.approved_amount || 0)}
                  </TableCell>
                  <TableCell>
                    <span className={`text-xs font-bold px-2.5 py-0.5 rounded-full ${
                      lpj.status === 'approved' || lpj.status === 'finalized'
                        ? 'bg-emerald-100 text-emerald-800'
                        : 'bg-blue-100 text-blue-800'
                    }`}>
                      {lpj.status === 'approved' || lpj.status === 'finalized' ? 'Diterima Sah' : 'Menunggu Verifikasi'}
                    </span>
                  </TableCell>
                  <TableCell className="text-right">
                    <div className="flex items-center justify-end gap-1.5">
                      <Link to={`/lpj/${lpj.id}`}>
                        <Button variant="outline" size="xs">
                          Tinjau LPJ
                        </Button>
                      </Link>
                      <Button
                        variant="primary"
                        size="xs"
                        icon={PrinterIcon}
                        onClick={() => openPdf(`/pdf/lpj/${lpj.id}`)}
                      >
                        Cetak
                      </Button>
                    </div>
                  </TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
        )}
      </Card>
    </div>
  );
}

export default LpjListPage;

