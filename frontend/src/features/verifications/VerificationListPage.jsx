import React, { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import api from '../../services/api';
import { formatCurrency, formatDate } from '../../utils/formatters';
import PageHeader from '../../components/layout/PageHeader';
import Card, { CardBody } from '../../components/ui/Card';
import Table, { TableHead, TableBody, TableRow, TableHeaderCell, TableCell } from '../../components/ui/Table';
import Badge from '../../components/ui/Badge';
import Button from '../../components/ui/Button';
import Spinner from '../../components/feedback/Spinner';
import EmptyState from '../../components/feedback/EmptyState';
import {
  ClipboardDocumentCheckIcon,
  MagnifyingGlassIcon,
  ArrowRightIcon,
  ClockIcon,
} from '@heroicons/react/24/outline';

export function VerificationListPage() {
  const [proposals, setProposals] = useState([]);
  const [isLoading, setIsLoading] = useState(true);
  const [search, setSearch] = useState('');

  useEffect(() => {
    let isMounted = true;
    async function loadVerificationQueue() {
      setIsLoading(true);
      try {
        const res = await api.get('/proposals?status=submitted');
        if (isMounted) {
          const list = Array.isArray(res?.data) ? res.data : (Array.isArray(res?.data?.data) ? res.data.data : []);
          setProposals(list);
        }
      } catch (err) {
        console.error('Failed to load verification queue:', err);
      } finally {
        if (isMounted) setIsLoading(false);
      }
    }

    loadVerificationQueue();
    return () => { isMounted = false; };
  }, []);

  const filtered = proposals.filter((p) =>
    p.title.toLowerCase().includes(search.toLowerCase()) ||
    p.proposal_number?.toLowerCase().includes(search.toLowerCase()) ||
    p.organization?.name?.toLowerCase().includes(search.toLowerCase())
  );

  return (
    <div className="space-y-6">
      <PageHeader
        title="Antrean Verifikasi Administrasi Dokumen"
        subtitle="Pemeriksaan berkas persyaratan hukum, legalitas organisasi, dan kelengkapan proposal pemohon"
        breadcrumbs={[{ label: 'Verifikasi Administrasi' }]}
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
              placeholder="Cari nomor usulan, ormas, atau judul proposal..."
              className="w-full pl-9 pr-3 py-2 text-xs sm:text-sm bg-slate-50 border border-slate-200 rounded-lg focus:outline-hidden focus:bg-white focus:border-blue-500"
            />
          </div>
        </CardBody>
      </Card>

      {/* Queue Table */}
      <Card className="border-slate-200 shadow-xs">
        {isLoading ? (
          <Spinner label="Memuat antrean verifikasi..." />
        ) : filtered.length === 0 ? (
          <div className="p-12">
            <EmptyState
              icon={ClipboardDocumentCheckIcon}
              title="Antrean Verifikasi Bersih"
              description="Tidak ada berkas usulan berstatus diajukan yang menunggu verifikasi administrasi saat ini."
            />
          </div>
        ) : (
          <Table>
            <TableHead>
              <tr>
                <TableHeaderCell>Nomor Usulan</TableHeaderCell>
                <TableHeaderCell>Judul Proposal Kegiatan</TableHeaderCell>
                <TableHeaderCell>Organisasi Pemohon</TableHeaderCell>
                <TableHeaderCell>Pagu Dimohon</TableHeaderCell>
                <TableHeaderCell>Waktu Masuk</TableHeaderCell>
                <TableHeaderCell className="text-right">Tindakan</TableHeaderCell>
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
                    <div className="text-[11px] text-slate-400">{prop.grant_program?.name}</div>
                  </TableCell>
                  <TableCell>
                    <span className="text-xs font-semibold text-slate-700">{prop.organization?.name}</span>
                  </TableCell>
                  <TableCell mono className="font-bold text-slate-900">
                    {formatCurrency(prop.requested_amount)}
                  </TableCell>
                  <TableCell>
                    <span className="text-xs text-slate-500 flex items-center gap-1">
                      <ClockIcon className="w-3.5 h-3.5 text-slate-400" />
                      {formatDate(prop.submitted_at || prop.created_at)}
                    </span>
                  </TableCell>
                  <TableCell className="text-right">
                    <Link to={`/verifications/${prop.id}`}>
                      <Button variant="primary" size="xs" icon={ArrowRightIcon} iconPosition="right">
                        Mulai Verifikasi
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

export default VerificationListPage;

