import React, { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import api from '../../services/api';
import { formatDate } from '../../utils/formatters';
import PageHeader from '../../components/layout/PageHeader';
import Card, { CardBody } from '../../components/ui/Card';
import Table, { TableHead, TableBody, TableRow, TableHeaderCell, TableCell } from '../../components/ui/Table';
import Badge from '../../components/ui/Badge';
import Button from '../../components/ui/Button';
import Spinner from '../../components/feedback/Spinner';
import EmptyState from '../../components/feedback/EmptyState';
import {
  MapPinIcon,
  MagnifyingGlassIcon,
  ArrowRightIcon,
  CalendarDaysIcon,
} from '@heroicons/react/24/outline';

export function FieldSurveyListPage() {
  const [surveys, setSurveys] = useState([]);
  const [isLoading, setIsLoading] = useState(true);
  const [search, setSearch] = useState('');

  useEffect(() => {
    let isMounted = true;
    async function loadSurveys() {
      setIsLoading(true);
      try {
        const [assignedRes, propRes] = await Promise.allSettled([
          api.get('/field-surveys/assigned'),
          api.get('/proposals?status=survey'),
        ]);

        if (!isMounted) return;

        let list = [];
        const assignedData = assignedRes.status === 'fulfilled' ? (Array.isArray(assignedRes.value?.data) ? assignedRes.value.data : Array.isArray(assignedRes.value?.data?.data) ? assignedRes.value.data.data : []) : [];
        const propData = propRes.status === 'fulfilled' ? (Array.isArray(propRes.value?.data) ? propRes.value.data : Array.isArray(propRes.value?.data?.data) ? propRes.value.data.data : []) : [];

        if (assignedData.length > 0) {
          list = assignedData;
        } else if (propData.length > 0) {
          list = propData.map((p) => ({
            id: p.id,
            proposal_id: p.id,
            proposal: p,
            scheduled_at: p.updated_at,
            status: 'scheduled',
          }));
        }
        setSurveys(list);
      } catch (err) {
        console.error('Failed to load field surveys:', err);
        if (isMounted) setSurveys([]);
      } finally {
        if (isMounted) setIsLoading(false);
      }
    }

    loadSurveys();
    return () => { isMounted = false; };
  }, []);

  const filtered = (Array.isArray(surveys) ? surveys : []).filter((s) => {
    const title = s.proposal?.title || '';
    const number = s.proposal?.proposal_number || '';
    const org = s.proposal?.organization?.name || '';
    return title.toLowerCase().includes(search.toLowerCase()) ||
           number.toLowerCase().includes(search.toLowerCase()) ||
           org.toLowerCase().includes(search.toLowerCase());
  });

  return (
    <div className="space-y-6">
      <PageHeader
        title="Jadwal Survei Faktual Lapangan & Geospasial"
        subtitle="Verifikasi keberadaan fisik sekretariat, sarana prasarana, dan dokumentasi foto ber-GPS di Sulawesi Utara"
        breadcrumbs={[{ label: 'Survei Lapangan' }]}
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
              placeholder="Cari nomor usulan, ormas, atau lokasi..."
              className="w-full pl-9 pr-3 py-2 text-xs sm:text-sm bg-slate-50 border border-slate-200 rounded-lg focus:outline-hidden focus:bg-white focus:border-blue-500"
            />
          </div>
        </CardBody>
      </Card>

      {/* Surveys Table */}
      <Card className="border-slate-200 shadow-xs">
        {isLoading ? (
          <Spinner label="Memuat jadwal survei lapangan..." />
        ) : filtered.length === 0 ? (
          <div className="p-12">
            <EmptyState
              icon={MapPinIcon}
              title="Tidak Ada Jadwal Survei Aktif"
              description="Belum ada usulan yang dijadwalkan untuk peninjauan faktual lapangan saat ini."
            />
          </div>
        ) : (
          <Table>
            <TableHead>
              <tr>
                <TableHeaderCell>Nomor Usulan</TableHeaderCell>
                <TableHeaderCell>Kegiatan / Ormas</TableHeaderCell>
                <TableHeaderCell>Wilayah / Lokasi</TableHeaderCell>
                <TableHeaderCell>Jadwal Peninjauan</TableHeaderCell>
                <TableHeaderCell>Status</TableHeaderCell>
                <TableHeaderCell className="text-right">Tindakan</TableHeaderCell>
              </tr>
            </TableHead>
            <TableBody>
              {filtered.map((survey) => (
                <TableRow key={survey.id}>
                  <TableCell mono className="font-bold text-blue-900">
                    {survey.proposal?.proposal_number || 'USULAN'}
                  </TableCell>
                  <TableCell>
                    <div className="font-bold text-slate-900 max-w-xs truncate">{survey.proposal?.title || '-'}</div>
                    <div className="text-[11px] text-slate-400">{survey.proposal?.organization?.name}</div>
                  </TableCell>
                  <TableCell>
                    <span className="text-xs text-slate-700 flex items-center gap-1">
                      <MapPinIcon className="w-3.5 h-3.5 text-rose-500" />
                      {survey.proposal?.organization?.regency || 'Sulawesi Utara'}
                    </span>
                  </TableCell>
                  <TableCell>
                    <span className="text-xs text-slate-600 flex items-center gap-1">
                      <CalendarDaysIcon className="w-3.5 h-3.5 text-slate-400" />
                      {formatDate(survey.scheduled_at || survey.created_at)}
                    </span>
                  </TableCell>
                  <TableCell>
                    <span className="text-xs font-bold px-2 py-0.5 rounded-full bg-blue-100 text-blue-800">
                      {survey.status || 'Terjadwal'}
                    </span>
                  </TableCell>
                  <TableCell className="text-right">
                    <Link to={`/field-surveys/${survey.proposal_id || survey.id}`}>
                      <Button variant="primary" size="xs" icon={ArrowRightIcon} iconPosition="right">
                        Buka Formulir Lapangan
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

export default FieldSurveyListPage;

