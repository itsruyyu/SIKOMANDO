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
  BanknotesIcon,
  MagnifyingGlassIcon,
  ArrowRightIcon,
  CheckCircleIcon,
  ClockIcon,
} from '@heroicons/react/24/outline';

export function DisbursementListPage() {
  const [disbursements, setDisbursements] = useState([]);
  const [isLoading, setIsLoading] = useState(true);
  const [search, setSearch] = useState('');

  useEffect(() => {
    let isMounted = true;
    async function loadDisbursements() {
      setIsLoading(true);
      try {
        const res = await api.get('/disbursements');
        if (isMounted) {
          const list = Array.isArray(res?.data) ? res.data : (Array.isArray(res?.data?.data) ? res.data.data : []);
          setDisbursements(list);
        }
      } catch (err) {
        console.error('Failed to load disbursements:', err);
      } finally {
        if (isMounted) setIsLoading(false);
      }
    }

    loadDisbursements();
    return () => { isMounted = false; };
  }, []);

  const filtered = disbursements.filter((d) => {
    const num = d.disbursement_number || d.sp2d_number || '';
    const org = d.proposal?.organization?.name || '';
    const title = d.proposal?.title || '';
    return num.toLowerCase().includes(search.toLowerCase()) ||
           org.toLowerCase().includes(search.toLowerCase()) ||
           title.toLowerCase().includes(search.toLowerCase());
  });

  return (
    <div className="space-y-6 pb-20">
      <PageHeader
        title="Pencairan Dana Hibah (SP2D Bank Transfer)"
        subtitle="Pengelolaan penyaluran dana per termin dan pencatatan transaksi SP2D perbankan ke rekening ormas"
        breadcrumbs={[{ label: 'Pencairan Dana (SP2D)' }]}
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
              placeholder="Cari nomor SP2D, ormas, atau proposal..."
              className="w-full pl-9 pr-3 py-2 text-xs sm:text-sm bg-slate-50 border border-slate-200 rounded-lg focus:outline-hidden focus:bg-white focus:border-blue-500"
            />
          </div>
        </CardBody>
      </Card>

      {/* Table */}
      <Card className="border-slate-200 shadow-xs">
        {isLoading ? (
          <Spinner label="Memuat riwayat pencairan dana..." />
        ) : filtered.length === 0 ? (
          <div className="p-12">
            <EmptyState
              icon={BanknotesIcon}
              title="Belum Ada Rekod Pencairan"
              description="Belum ada transaksi pencairan dana atau SP2D yang diproses."
            />
          </div>
        ) : (
          <Table>
            <TableHead>
              <tr>
                <TableHeaderCell>Nomor Pencairan / SP2D</TableHeaderCell>
                <TableHeaderCell>Lembaga Penerima</TableHeaderCell>
                <TableHeaderCell>Termin / Tahap</TableHeaderCell>
                <TableHeaderCell>Nominal Penyaluran</TableHeaderCell>
                <TableHeaderCell>Status</TableHeaderCell>
                <TableHeaderCell className="text-right">Aksi</TableHeaderCell>
              </tr>
            </TableHead>
            <TableBody>
              {filtered.map((d) => (
                <TableRow key={d.id}>
                  <TableCell mono className="font-bold text-blue-900">
                    {d.disbursement_number || d.sp2d_number || 'SP2D-SULUT'}
                  </TableCell>
                  <TableCell>
                    <div className="font-bold text-slate-900">{d.proposal?.organization?.name || '-'}</div>
                    <div className="text-[11px] text-slate-400">{d.proposal?.title}</div>
                  </TableCell>
                  <TableCell>
                    <span className="text-xs font-semibold px-2 py-0.5 rounded bg-slate-100 text-slate-700">
                      Tahap {d.stage || d.installment_number || 1}
                    </span>
                  </TableCell>
                  <TableCell mono className="font-black text-emerald-800">
                    {formatCurrency(d.amount || d.paid_amount || 0)}
                  </TableCell>
                  <TableCell>
                    <span className={`text-xs font-bold px-2.5 py-0.5 rounded-full ${d.status === 'paid' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'}`}>
                      {d.status === 'paid' ? 'Dana Dicairkan' : 'Dalam Proses'}
                    </span>
                  </TableCell>
                  <TableCell className="text-right">
                    <Link to={`/disbursements/${d.id}`}>
                      <Button variant="outline" size="xs" icon={ArrowRightIcon} iconPosition="right">
                        Rincian SP2D
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

export default DisbursementListPage;

