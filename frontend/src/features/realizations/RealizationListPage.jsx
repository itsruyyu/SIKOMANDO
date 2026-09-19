import React, { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import api from '../../services/api';
import { formatCurrency, formatDate } from '../../utils/formatters';
import PageHeader from '../../components/layout/PageHeader';
import Card, { CardBody } from '../../components/ui/Card';
import Table, { TableHead, TableBody, TableRow, TableHeaderCell, TableCell } from '../../components/ui/Table';
import Button from '../../components/ui/Button';
import Spinner from '../../components/feedback/Spinner';
import EmptyState from '../../components/feedback/EmptyState';
import {
  ShoppingBagIcon,
  MagnifyingGlassIcon,
  ArrowRightIcon,
  CheckBadgeIcon,
  QrCodeIcon,
} from '@heroicons/react/24/outline';

export function RealizationListPage() {
  const [proposals, setProposals] = useState([]);
  const [isLoading, setIsLoading] = useState(true);
  const [search, setSearch] = useState('');

  useEffect(() => {
    let isMounted = true;
    async function loadRealizationProposals() {
      setIsLoading(true);
      try {
        const res = await api.get('/proposals');
        const list = Array.isArray(res?.data)
          ? res.data
          : Array.isArray(res?.data?.data)
          ? res.data.data
          : Array.isArray(res)
          ? res
          : [];
        if (isMounted) {
          // Filter proposals in implementation / disbursed / lpj stages
          const active = list.filter((p) =>
            ['disbursed', 'implementation', 'lpj_submitted', 'completed'].includes(p.status)
          );
          setProposals(active.length > 0 ? active : list);
        }
      } catch (err) {
        console.error('Failed to load realization proposals:', err);
        if (isMounted) setProposals([]);
      } finally {
        if (isMounted) setIsLoading(false);
      }
    }

    loadRealizationProposals();
    return () => { isMounted = false; };
  }, []);

  const filtered = (Array.isArray(proposals) ? proposals : []).filter((p) =>
    p?.title?.toLowerCase().includes(search.toLowerCase()) ||
    p?.proposal_number?.toLowerCase().includes(search.toLowerCase()) ||
    p?.organization?.name?.toLowerCase().includes(search.toLowerCase())
  );

  return (
    <div className="space-y-6 pb-20">
      <PageHeader
        title="Paket Realisasi Pengadaan, Kuitansi & BAST"
        subtitle="Pemantauan fisik pelaksanaan belanja barang/jasa, pencatatan kuitansi ber-QR, dan Berita Acara Serah Terima"
        breadcrumbs={[{ label: 'Realisasi Belanja' }]}
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
              placeholder="Cari nomor usulan atau nama ormas..."
              className="w-full pl-9 pr-3 py-2 text-xs sm:text-sm bg-slate-50 border border-slate-200 rounded-lg focus:outline-hidden focus:bg-white focus:border-blue-500"
            />
          </div>
        </CardBody>
      </Card>

      {/* Proposals in Realization Table */}
      <Card className="border-slate-200 shadow-xs">
        {isLoading ? (
          <Spinner label="Memuat data realisasi belanja..." />
        ) : filtered.length === 0 ? (
          <div className="p-12">
            <EmptyState
              icon={ShoppingBagIcon}
              title="Belum Ada Usulan Dalam Realisasi"
              description="Usulan yang dananya telah dicairkan akan muncul di sini untuk pencatatan kuitansi dan BAST."
            />
          </div>
        ) : (
          <Table>
            <TableHead>
              <tr>
                <TableHeaderCell>Nomor Usulan</TableHeaderCell>
                <TableHeaderCell>Nama Organisasi & Kegiatan</TableHeaderCell>
                <TableHeaderCell>Pagu Disetujui</TableHeaderCell>
                <TableHeaderCell>Status Tahapan</TableHeaderCell>
                <TableHeaderCell className="text-right">Aksi Realisasi</TableHeaderCell>
              </tr>
            </TableHead>
            <TableBody>
              {filtered.map((prop) => (
                <TableRow key={prop.id}>
                  <TableCell mono className="font-bold text-blue-900">
                    {prop.proposal_number}
                  </TableCell>
                  <TableCell>
                    <div className="font-bold text-slate-900 max-w-xs truncate">{prop.title}</div>
                    <div className="text-[11px] text-slate-400">{prop.organization?.name}</div>
                  </TableCell>
                  <TableCell mono className="font-bold text-emerald-800">
                    {formatCurrency(prop.approved_amount || prop.requested_amount)}
                  </TableCell>
                  <TableCell>
                    <span className="text-xs font-semibold px-2 py-0.5 rounded-full bg-blue-100 text-blue-800 capitalize">
                      {prop.status}
                    </span>
                  </TableCell>
                  <TableCell className="text-right">
                    <Link to={`/realizations/${prop.id}`}>
                      <Button variant="primary" size="xs" icon={ArrowRightIcon} iconPosition="right">
                        Kelola Belanja & BAST
                      </Button>
                    </Link>
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

export default RealizationListPage;

