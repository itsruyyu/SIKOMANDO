import React, { useState, useEffect } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import { useAuth } from '../../context/AuthContext';
import api from '../../services/api';
import { formatCurrency, formatDate } from '../../utils/formatters';
import { PROPOSAL_STATUSES } from '../../utils/constants';
import PageHeader from '../../components/layout/PageHeader';
import Card, { CardBody } from '../../components/ui/Card';
import Table, { TableHead, TableBody, TableRow, TableHeaderCell, TableCell } from '../../components/ui/Table';
import Badge from '../../components/ui/Badge';
import Button from '../../components/ui/Button';
import Pagination from '../../components/ui/Pagination';
import Spinner from '../../components/feedback/Spinner';
import EmptyState from '../../components/feedback/EmptyState';
import {
  DocumentPlusIcon,
  MagnifyingGlassIcon,
  FunnelIcon,
  ArrowRightIcon,
  DocumentTextIcon,
} from '@heroicons/react/24/outline';

export function ProposalListPage() {
  const { isPemohon } = useAuth();
  const [searchParams, setSearchParams] = useSearchParams();

  const [proposals, setProposals] = useState([]);
  const [paginationMeta, setPaginationMeta] = useState(null);
  const [isLoading, setIsLoading] = useState(true);

  const [searchTerm, setSearchTerm] = useState(searchParams.get('search') || '');
  const [selectedStatus, setSelectedStatus] = useState(searchParams.get('status') || 'all');
  const [currentPage, setCurrentPage] = useState(1);

  const fetchProposals = async (page = 1, status = selectedStatus, search = searchTerm) => {
    setIsLoading(true);
    try {
      const params = new URLSearchParams();
      params.append('page', page);
      if (status !== 'all') {
        params.append('status', status);
      }
      if (search.trim()) {
        params.append('search', search.trim());
      }

      const res = await api.get(`/proposals?${params.toString()}`);
      if (res !== undefined && res !== null) {
        // Pattern A: res.data is the array directly (ResourceCollection)
        // Pattern B: res.data.data is the array (ApiResponse wrapping)
        const list = Array.isArray(res?.data) ? res.data
          : Array.isArray(res?.data?.data) ? res.data.data
          : [];
        const meta = res?.meta || res?.data?.meta || null;
        setProposals(list);
        setPaginationMeta(meta);
      }
    } catch (err) {
      console.error('Failed to load proposals:', err);
    } finally {
      setIsLoading(false);
    }
  };

  useEffect(() => {
    fetchProposals(currentPage, selectedStatus, searchTerm);
  }, [currentPage, selectedStatus]);

  const handleSearchSubmit = (e) => {
    e.preventDefault();
    setCurrentPage(1);
    fetchProposals(1, selectedStatus, searchTerm);
  };

  const handleStatusChange = (status) => {
    setSelectedStatus(status);
    setCurrentPage(1);
  };

  return (
    <div className="space-y-6">
      <PageHeader
        title={isPemohon ? 'Daftar Usulan Hibah Organisasi' : 'Daftar Semua Usulan Hibah Daerah'}
        subtitle="Manajemen berkas usulan permohonan dana hibah Pemerintah Provinsi Sulawesi Utara"
        breadcrumbs={[{ label: 'Daftar Usulan' }]}
        action={
          isPemohon && (
            <Link to="/proposals/create">
              <Button variant="primary" size="md" icon={DocumentPlusIcon}>
                Buat Usulan Baru
              </Button>
            </Link>
          )
        }
      />

      {/* Filter and Search Bar */}
      <Card className="border-slate-200">
        <CardBody className="p-4 flex flex-col sm:flex-row items-center justify-between gap-4">
          <form onSubmit={handleSearchSubmit} className="relative w-full sm:max-w-md">
            <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
              <MagnifyingGlassIcon className="w-4 h-4" />
            </div>
            <input
              type="text"
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
              placeholder="Cari nomor registrasi, judul, atau ormas..."
              className="w-full pl-9 pr-3 py-2 text-xs sm:text-sm bg-slate-50 border border-slate-200 rounded-lg focus:outline-hidden focus:bg-white focus:border-blue-500"
            />
          </form>

          <div className="flex items-center gap-2 w-full sm:w-auto justify-end">
            <FunnelIcon className="w-4 h-4 text-slate-400" />
            <select
              value={selectedStatus}
              onChange={(e) => handleStatusChange(e.target.value)}
              className="text-xs font-semibold px-3 py-2 rounded-lg border border-slate-200 bg-white cursor-pointer"
            >
              <option value="all">Semua Status Usulan</option>
              {Object.entries(PROPOSAL_STATUSES).map(([key, val]) => (
                <option key={key} value={key}>
                  {val.label}
                </option>
              ))}
            </select>
          </div>
        </CardBody>
      </Card>

      {/* Table of Proposals */}
      <Card className="border-slate-200 shadow-xs overflow-hidden">
        {isLoading ? (
          <Spinner label="Memuat daftar usulan..." />
        ) : proposals.length === 0 ? (
          <div className="p-12">
            <EmptyState
              title="Tidak Ada Usulan Ditemukan"
              description="Tidak ada usulan hibah yang cocok dengan filter atau kata kunci yang dicari."
              actionLabel={isPemohon ? 'Ajukan Usulan Baru' : undefined}
              onAction={isPemohon ? () => window.location.href = '/proposals/create' : undefined}
            />
          </div>
        ) : (
          <>
            <Table>
              <TableHead>
                <tr>
                  <TableHeaderCell>Nomor Usulan</TableHeaderCell>
                  <TableHeaderCell>Judul Usulan Kegiatan</TableHeaderCell>
                  <TableHeaderCell>Organisasi Pemohon</TableHeaderCell>
                  <TableHeaderCell>Anggaran Dimohon</TableHeaderCell>
                  <TableHeaderCell>Status</TableHeaderCell>
                  <TableHeaderCell className="text-right">Aksi</TableHeaderCell>
                </tr>
              </TableHead>
              <TableBody>
                {proposals.map((prop) => (
                  <TableRow key={prop.id}>
                    <TableCell mono className="font-bold text-blue-900">
                      {prop.proposal_number || 'DRAFT'}
                    </TableCell>
                    <TableCell>
                      <div className="font-bold text-slate-900 max-w-xs truncate">{prop.title}</div>
                      <div className="text-[11px] text-slate-400">
                        {prop.grant_program?.name || 'Program Hibah'} • {formatDate(prop.submitted_at || prop.created_at)}
                      </div>
                    </TableCell>
                    <TableCell>
                      <span className="text-xs font-semibold text-slate-700">
                        {prop.organization?.name || '-'}
                      </span>
                    </TableCell>
                    <TableCell mono className="font-bold text-slate-900">
                      {formatCurrency(prop.requested_amount)}
                    </TableCell>
                    <TableCell>
                      <Badge status={prop.status} />
                    </TableCell>
                    <TableCell className="text-right">
                      <Link to={`/proposals/${prop.id}`}>
                        <Button variant="outline" size="xs">
                          Buka Detail
                        </Button>
                      </Link>
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>

            {/* Pagination Controls */}
            {paginationMeta && (
              <Pagination
                meta={paginationMeta}
                onPageChange={(page) => setCurrentPage(page)}
              />
            )}
          </>
        )}
      </Card>
    </div>
  );
}

export default ProposalListPage;

