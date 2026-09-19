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
  DocumentArrowDownIcon,
  MagnifyingGlassIcon,
  QrCodeIcon,
  PrinterIcon,
  ShieldCheckIcon,
} from '@heroicons/react/24/outline';

export function DecisionListPage() {
  const [decisions, setDecisions] = useState([]);
  const [isLoading, setIsLoading] = useState(true);
  const [search, setSearch] = useState('');

  useEffect(() => {
    let isMounted = true;
    async function loadDecisions() {
      setIsLoading(true);
      try {
        const res = await api.get('/decisions');
        if (isMounted) {
          const list = Array.isArray(res?.data) ? res.data : (Array.isArray(res?.data?.data) ? res.data.data : []);
          setDecisions(list);
        }
      } catch (err) {
        console.error('Failed to load decisions:', err);
      } finally {
        if (isMounted) setIsLoading(false);
      }
    }

    loadDecisions();
    return () => { isMounted = false; };
  }, []);

  const filtered = decisions.filter((d) => {
    const num = d.decision_number || '';
    const title = d.proposal?.title || '';
    const org = d.proposal?.organization?.name || '';
    return num.toLowerCase().includes(search.toLowerCase()) ||
           title.toLowerCase().includes(search.toLowerCase()) ||
           org.toLowerCase().includes(search.toLowerCase());
  });

  return (
    <div className="space-y-6">
      <PageHeader
        title="Surat Keputusan (SK) Penetapan Penerima Hibah"
        subtitle="Dokumen hukum resmi penetapan penerima dan alokasi dana hibah Pemerintah Provinsi Sulawesi Utara"
        breadcrumbs={[{ label: 'Surat Keputusan (SK)' }]}
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
              placeholder="Cari nomor SK, nama ormas, atau judul usulan..."
              className="w-full pl-9 pr-3 py-2 text-xs sm:text-sm bg-slate-50 border border-slate-200 rounded-lg focus:outline-hidden focus:bg-white focus:border-blue-500"
            />
          </div>
        </CardBody>
      </Card>

      {/* Table */}
      <Card className="border-slate-200 shadow-xs">
        {isLoading ? (
          <Spinner label="Memuat arsip Surat Keputusan resmi..." />
        ) : filtered.length === 0 ? (
          <div className="p-12">
            <EmptyState
              icon={DocumentArrowDownIcon}
              title="Belum Ada Surat Keputusan Terbit"
              description="Belum ada SK Penetapan yang disahkan atau diterbitkan oleh pimpinan."
            />
          </div>
        ) : (
          <Table>
            <TableHead>
              <tr>
                <TableHeaderCell>Nomor SK Resmi</TableHeaderCell>
                <TableHeaderCell>Penerima Hibah (Lembaga)</TableHeaderCell>
                <TableHeaderCell>Judul Kegiatan Terpilih</TableHeaderCell>
                <TableHeaderCell>Pagu Ditetapkan</TableHeaderCell>
                <TableHeaderCell>Tanggal Ditetapkan</TableHeaderCell>
                <TableHeaderCell className="text-right">Aksi Dokumen</TableHeaderCell>
              </tr>
            </TableHead>
            <TableBody>
              {filtered.map((dec) => (
                <TableRow key={dec.id}>
                  <TableCell mono className="font-bold text-blue-900">
                    {dec.decision_number || 'SK-GUB-SULUT'}
                  </TableCell>
                  <TableCell>
                    <div className="font-bold text-slate-900">{dec.proposal?.organization?.name || '-'}</div>
                    <div className="text-[11px] text-slate-400">Ketua: {dec.proposal?.applicant?.name || '-'}</div>
                  </TableCell>
                  <TableCell>
                    <div className="text-xs text-slate-800 max-w-xs truncate">{dec.proposal?.title}</div>
                  </TableCell>
                  <TableCell mono className="font-black text-emerald-800">
                    {formatCurrency(dec.amount || dec.proposal?.approved_amount || 0)}
                  </TableCell>
                  <TableCell>
                    <span className="text-xs text-slate-600">
                      {formatDate(dec.decision_date || dec.created_at)}
                    </span>
                  </TableCell>
                  <TableCell className="text-right">
                    <div className="flex items-center justify-end gap-1.5">
                      <Link to={`/decisions/${dec.id}`}>
                        <Button variant="outline" size="xs">
                          Rincian SK
                        </Button>
                      </Link>
                      <Button
                        variant="primary"
                        size="xs"
                        icon={PrinterIcon}
                        onClick={() => openPdf(`/pdf/decisions/${dec.id}`)}
                      >
                        Cetak PDF
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

export default DecisionListPage;

