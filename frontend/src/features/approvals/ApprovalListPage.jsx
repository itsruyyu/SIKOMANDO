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
  CheckBadgeIcon,
  MagnifyingGlassIcon,
  ArrowRightIcon,
  DocumentCheckIcon,
} from '@heroicons/react/24/outline';

export function ApprovalListPage() {
  const [approvals, setApprovals] = useState([]);
  const [isLoading, setIsLoading] = useState(true);
  const [search, setSearch] = useState('');

  useEffect(() => {
    let isMounted = true;
    async function loadApprovals() {
      setIsLoading(true);
      try {
        const res = await api.get('/approvals');
        if (isMounted) {
          const list = Array.isArray(res?.data) ? res.data : (Array.isArray(res?.data?.data) ? res.data.data : []);
          setApprovals(list);
        }
      } catch (err) {
        console.error('Failed to load approvals queue:', err);
      } finally {
        if (isMounted) setIsLoading(false);
      }
    }

    loadApprovals();
    return () => { isMounted = false; };
  }, []);

  const filtered = approvals.filter((a) => {
    const title = a.proposal?.title || '';
    const number = a.proposal?.proposal_number || '';
    const org = a.proposal?.organization?.name || '';
    return title.toLowerCase().includes(search.toLowerCase()) ||
           number.toLowerCase().includes(search.toLowerCase()) ||
           org.toLowerCase().includes(search.toLowerCase());
  });

  return (
    <div className="space-y-6">
      <PageHeader
        title="Antrean Persetujuan Pimpinan (Executive Approval)"
        subtitle="Dossier komprehensif hasil pengkajian TAPD untuk pengesahan formal penetapan bantuan hibah"
        breadcrumbs={[{ label: 'Persetujuan Pimpinan' }]}
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
              placeholder="Cari nomor usulan, ormas, atau judul..."
              className="w-full pl-9 pr-3 py-2 text-xs sm:text-sm bg-slate-50 border border-slate-200 rounded-lg focus:outline-hidden focus:bg-white focus:border-blue-500"
            />
          </div>
        </CardBody>
      </Card>

      {/* Approvals Table */}
      <Card className="border-slate-200 shadow-xs">
        {isLoading ? (
          <Spinner label="Memuat antrean persetujuan..." />
        ) : filtered.length === 0 ? (
          <div className="p-12">
            <EmptyState
              icon={CheckBadgeIcon}
              title="Tidak Ada Usulan Menunggu Persetujuan"
              description="Seluruh usulan yang masuk telah diputuskan atau belum ada dossier yang diajukan oleh TAPD."
            />
          </div>
        ) : (
          <Table>
            <TableHead>
              <tr>
                <TableHeaderCell>Nomor Usulan</TableHeaderCell>
                <TableHeaderCell>Judul Proposal Kegiatan</TableHeaderCell>
                <TableHeaderCell>Organisasi Pemohon</TableHeaderCell>
                <TableHeaderCell>Pagu Rekomendasi TAPD</TableHeaderCell>
                <TableHeaderCell>Status</TableHeaderCell>
                <TableHeaderCell className="text-right">Tindakan</TableHeaderCell>
              </tr>
            </TableHead>
            <TableBody>
              {filtered.map((app) => (
                <TableRow key={app.id}>
                  <TableCell mono className="font-bold text-blue-900">
                    {app.proposal?.proposal_number || 'USULAN'}
                  </TableCell>
                  <TableCell>
                    <div className="font-bold text-slate-900 max-w-xs truncate">{app.proposal?.title || '-'}</div>
                    <div className="text-[11px] text-slate-400">{app.proposal?.grant_program?.name}</div>
                  </TableCell>
                  <TableCell>
                    <span className="text-xs font-semibold text-slate-700">{app.proposal?.organization?.name}</span>
                  </TableCell>
                  <TableCell mono className="font-bold text-emerald-800">
                    {formatCurrency(app.recommended_amount || app.proposal?.approved_amount || 0)}
                  </TableCell>
                  <TableCell>
                    <span className={`text-xs font-bold px-2 py-0.5 rounded-full ${app.status === 'approved' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'}`}>
                      {app.status === 'approved' ? 'Disetujui' : 'Menunggu Review'}
                    </span>
                  </TableCell>
                  <TableCell className="text-right">
                    <Link to={`/approvals/${app.id}`}>
                      <Button variant="primary" size="xs" icon={ArrowRightIcon} iconPosition="right">
                        Tinjau Dossier
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

export default ApprovalListPage;

