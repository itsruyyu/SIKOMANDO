import React, { useState, useEffect } from 'react';
import api from '../../services/api';
import { formatCurrency } from '../../utils/formatters';
import Card, { CardBody, CardHeader } from '../../components/ui/Card';
import Table, { TableHead, TableBody, TableRow, TableHeaderCell, TableCell } from '../../components/ui/Table';
import Spinner from '../../components/feedback/Spinner';
import {
  ShieldCheckIcon,
  BanknotesIcon,
  BuildingLibraryIcon,
  DocumentCheckIcon,
  CheckBadgeIcon,
} from '@heroicons/react/24/outline';

export function TransparencyPage() {
  const [transparency, setTransparency] = useState(null);
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    let isMounted = true;
    async function loadTransparency() {
      setIsLoading(true);
      try {
        const res = await api.get('/public/transparency');
        if (isMounted && res?.data) {
          setTransparency(res.data);
        }
      } catch (err) {
        console.error('Failed to load transparency summary:', err);
      } finally {
        if (isMounted) setIsLoading(false);
      }
    }
    loadTransparency();
    return () => { isMounted = false; };
  }, []);

  const overview = transparency?.overview || {};
  const programs = transparency?.programs || [];

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 space-y-10">
      {/* Header Banner */}
      <div className="bg-gradient-to-r from-slate-900 to-blue-950 text-white rounded-3xl p-8 sm:p-10 shadow-lg border border-slate-800">
        <div className="max-w-3xl space-y-3">
          <div className="text-xs font-bold uppercase tracking-wider text-emerald-400 flex items-center gap-1.5">
            <ShieldCheckIcon className="w-4 h-4" />
            <span>Keterbukaan Informasi Publik (UU No. 14 Tahun 2008)</span>
          </div>
          <h1 className="text-2xl sm:text-4xl font-black tracking-tight">
            Portal Transparansi Penyaluran Hibah Daerah
          </h1>
          <p className="text-xs sm:text-sm text-slate-300 leading-relaxed">
            Data akuntabilitas publik penyaluran dana hibah APBD Pemerintah Provinsi Sulawesi Utara. Publik dapat memantau usulan, organisasi penerima, jumlah pagu disetujui, dan serapan anggaran.
          </p>
        </div>
      </div>

      {isLoading ? (
        <Spinner label="Memuat data transparansi publik..." />
      ) : (
        <>
          {/* Summary Cards */}
          <div className="grid grid-cols-1 sm:grid-cols-3 gap-6">
            <Card className="border-blue-200 bg-blue-50/40">
              <CardBody className="p-6">
                <span className="text-xs font-bold uppercase tracking-wider text-blue-800">
                  Total Anggaran Disetujui
                </span>
                <div className="text-2xl font-black font-mono text-blue-950 mt-2">
                  {formatCurrency(overview.total_approved_amount || 0)}
                </div>
                <div className="text-xs text-blue-700 mt-1">
                  Untuk {overview.proposals_approved || 0} Usulan Disetujui
                </div>
              </CardBody>
            </Card>

            <Card className="border-emerald-200 bg-emerald-50/40">
              <CardBody className="p-6">
                <span className="text-xs font-bold uppercase tracking-wider text-emerald-800">
                  Total Dana Dicairkan (SP2D)
                </span>
                <div className="text-2xl font-black font-mono text-emerald-950 mt-2">
                  {formatCurrency(overview.total_disbursed_amount || 0)}
                </div>
                <div className="text-xs text-emerald-700 mt-1">
                  Penyaluran via rekening bank resmi
                </div>
              </CardBody>
            </Card>

            <Card className="border-amber-200 bg-amber-50/40">
              <CardBody className="p-6">
                <span className="text-xs font-bold uppercase tracking-wider text-amber-800">
                  Organisasi Penerima Terverifikasi
                </span>
                <div className="text-2xl font-black font-mono text-amber-950 mt-2">
                  {overview.recipient_organizations_count || 0} Ormas
                </div>
                <div className="text-xs text-amber-700 mt-1">
                  Di wilayah Provinsi Sulawesi Utara
                </div>
              </CardBody>
            </Card>
          </div>

          {/* Program Transparency Table */}
          <Card className="border-slate-200 shadow-md">
            <CardHeader
              title="Rekapitulasi Penyaluran Per Program Hibah"
              subtitle="Data keterbukaan anggaran per program hibah aktif dan pagu yang dialokasikan"
            />
            <Table>
              <TableHead>
                <tr>
                  <TableHeaderCell>Kode & Program Hibah</TableHeaderCell>
                  <TableHeaderCell>T.A.</TableHeaderCell>
                  <TableHeaderCell>Usulan Masuk</TableHeaderCell>
                  <TableHeaderCell>Lolos Verifikasi</TableHeaderCell>
                  <TableHeaderCell>Penerima (SK)</TableHeaderCell>
                  <TableHeaderCell className="text-right">Total Disetujui</TableHeaderCell>
                  <TableHeaderCell className="text-right">Total Dicairkan</TableHeaderCell>
                </tr>
              </TableHead>
              <TableBody>
                {programs.length === 0 ? (
                  <TableRow>
                    <TableCell colSpan={7} className="text-center py-6 text-slate-400">
                      Belum ada data program transparansi yang tersedia.
                    </TableCell>
                  </TableRow>
                ) : (
                  programs.map((prog, idx) => (
                    <TableRow key={idx}>
                      <TableCell>
                        <div className="font-bold text-slate-900">{prog.grant_program?.name}</div>
                        <div className="font-mono text-[11px] text-blue-600">{prog.grant_program?.code}</div>
                      </TableCell>
                      <TableCell className="font-mono font-semibold">
                        {prog.grant_program?.fiscal_year}
                      </TableCell>
                      <TableCell>{prog.proposals_received} Usulan</TableCell>
                      <TableCell>{prog.proposals_verified} Usulan</TableCell>
                      <TableCell>
                        <span className="font-bold text-emerald-700">
                          {prog.proposals_approved} Usulan ({prog.recipient_organizations_count} Ormas)
                        </span>
                      </TableCell>
                      <TableCell className="text-right font-mono font-bold text-slate-900">
                        {formatCurrency(prog.total_approved_amount)}
                      </TableCell>
                      <TableCell className="text-right font-mono font-bold text-emerald-700">
                        {formatCurrency(prog.total_disbursed_amount)}
                      </TableCell>
                    </TableRow>
                  ))
                )}
              </TableBody>
            </Table>
          </Card>
        </>
      )}
    </div>
  );
}

export default TransparencyPage;

