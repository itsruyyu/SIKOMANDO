import { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import api from '../../services/api';
import Card from '../../components/ui/Card';
import Badge from '../../components/ui/Badge';
import Button from '../../components/ui/Button';
import Spinner from '../../components/feedback/Spinner';
import EmptyState from '../../components/feedback/EmptyState';
import { useToast } from '../../context/ToastContext';
import { formatDateIndo } from '../../utils/formatters';
import {
  BellIcon,
  CheckIcon,
  InboxIcon,
  EnvelopeOpenIcon,
  ArrowRightIcon,
  DocumentTextIcon,
  ClipboardDocumentCheckIcon,
  CurrencyDollarIcon,
  MapPinIcon,
  ShieldCheckIcon
} from '@heroicons/react/24/outline';

export default function NotificationInboxPage() {
  const navigate = useNavigate();
  const { toast } = useToast();

  const [notifications, setNotifications] = useState([]);
  const [loading, setLoading] = useState(true);
  const [unreadOnly, setUnreadOnly] = useState(false);
  const [unreadCount, setUnreadCount] = useState(0);
  const [markingAll, setMarkingAll] = useState(false);

  const fetchNotifications = async () => {
    setLoading(true);
    try {
      const [listRes, countRes] = await Promise.all([
        api.get('/notifications', { params: { unread_only: unreadOnly ? 1 : 0 } }),
        api.get('/notifications/unread-count')
      ]);

      const items = listRes.data.data || listRes.data || [];
      setNotifications(items);
      setUnreadCount(countRes.data.data?.unread_count || 0);
    } catch (err) {
      toast.error('Gagal memuat notifikasi: ' + (err.response?.data?.message || err.message));
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchNotifications();
  }, [unreadOnly]);

  const handleMarkAsRead = async (notif) => {
    if (notif.read_at) return;
    try {
      await api.post(`/notifications/${notif.id}/read`);
      setNotifications(prev =>
        prev.map(n => n.id === notif.id ? { ...n, read_at: new Date().toISOString() } : n)
      );
      setUnreadCount(prev => Math.max(0, prev - 1));
    } catch (err) {
      console.error('Failed to mark read', err);
    }
  };

  const handleMarkAllAsRead = async () => {
    setMarkingAll(true);
    try {
      await api.post('/notifications/mark-all-as-read');
      setNotifications(prev =>
        prev.map(n => ({ ...n, read_at: n.read_at || new Date().toISOString() }))
      );
      setUnreadCount(0);
      toast.success('Semua notifikasi telah ditandai sebagai dibaca.');
    } catch (err) {
      toast.error('Gagal menandai notifikasi: ' + (err.response?.data?.message || err.message));
    } finally {
      setMarkingAll(false);
    }
  };

  const handleNavigateToEntity = (notif) => {
    handleMarkAsRead(notif);

    const type = (notif.entity_type || '').toLowerCase();
    const id = notif.entity_id;

    if (!id) return;

    if (type.includes('proposal')) {
      navigate(`/proposals/${id}`);
    } else if (type.includes('verification')) {
      navigate(`/verifications/${id}`);
    } else if (type.includes('evaluation')) {
      navigate(`/evaluations/${id}`);
    } else if (type.includes('survey')) {
      navigate(`/field-surveys/${id}`);
    } else if (type.includes('approval')) {
      navigate(`/approvals/${id}`);
    } else if (type.includes('decision')) {
      navigate(`/decisions/${id}`);
    } else if (type.includes('disbursement')) {
      navigate(`/disbursements/${id}`);
    } else if (type.includes('realization')) {
      navigate(`/realizations/${id}`);
    } else if (type.includes('lpj')) {
      navigate(`/lpj/${id}`);
    } else {
      navigate('/dashboard');
    }
  };

  const getNotificationIcon = (notif) => {
    const type = (notif.entity_type || notif.type || '').toLowerCase();
    if (type.includes('proposal')) return <DocumentTextIcon className="w-5 h-5 text-blue-600" />;
    if (type.includes('verification') || type.includes('evaluation')) return <ClipboardDocumentCheckIcon className="w-5 h-5 text-emerald-600" />;
    if (type.includes('survey')) return <MapPinIcon className="w-5 h-5 text-amber-600" />;
    if (type.includes('disbursement') || type.includes('lpj')) return <CurrencyDollarIcon className="w-5 h-5 text-purple-600" />;
    return <ShieldCheckIcon className="w-5 h-5 text-gov-navy" />;
  };

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold text-slate-900 tracking-tight flex items-center gap-2">
            <BellIcon className="w-7 h-7 text-gov-navy" />
            Kotak Masuk Notifikasi
          </h1>
          <p className="text-sm text-slate-500 mt-1">
            Pemberitahuan perubahan status proposal, penugasan teknis, dan verifikasi berkas secara real-time.
          </p>
        </div>
        {unreadCount > 0 && (
          <Button
            variant="outline"
            onClick={handleMarkAllAsRead}
            loading={markingAll}
            leftIcon={<CheckIcon className="w-4 h-4" />}
          >
            Tandai Semua Dibaca
          </Button>
        )}
      </div>

      {/* Filter Tabs */}
      <div className="flex items-center gap-2 border-b border-slate-200">
        <button
          onClick={() => setUnreadOnly(false)}
          className={`pb-3 px-4 text-xs font-semibold border-b-2 transition-all ${
            !unreadOnly
              ? 'border-gov-navy text-gov-navy font-bold'
              : 'border-transparent text-slate-500 hover:text-slate-800'
          }`}
        >
          Semua Notifikasi
        </button>
        <button
          onClick={() => setUnreadOnly(true)}
          className={`pb-3 px-4 text-xs font-semibold border-b-2 transition-all flex items-center gap-2 ${
            unreadOnly
              ? 'border-gov-navy text-gov-navy font-bold'
              : 'border-transparent text-slate-500 hover:text-slate-800'
          }`}
        >
          <span>Belum Dibaca</span>
          {unreadCount > 0 && (
            <span className="px-1.5 py-0.5 rounded-full text-[10px] font-bold bg-gov-navy text-white">
              {unreadCount}
            </span>
          )}
        </button>
      </div>

      {/* Notifications List */}
      {loading ? (
        <div className="flex justify-center p-12">
          <Spinner size="lg" />
        </div>
      ) : notifications.length === 0 ? (
        <Card>
          <EmptyState
            title="Tidak Ada Notifikasi"
            description={
              unreadOnly
                ? 'Semua notifikasi telah dibaca. Anda sudah mengikuti perkembangan terbaru.'
                : 'Belum ada notifikasi yang masuk ke akun Anda.'
            }
            icon={InboxIcon}
          />
        </Card>
      ) : (
        <div className="space-y-3">
          {notifications.map((notif) => {
            const isUnread = !notif.read_at;
            return (
              <div
                key={notif.id}
                onClick={() => handleMarkAsRead(notif)}
                className={`p-4 rounded-xl border transition-all cursor-pointer ${
                  isUnread
                    ? 'border-gov-navy/40 bg-gov-navy/[0.02] shadow-sm hover:border-gov-navy'
                    : 'border-slate-200 bg-white hover:border-slate-300'
                }`}
              >
                <div className="flex items-start gap-3.5">
                  <div className={`p-2.5 rounded-xl shrink-0 ${isUnread ? 'bg-gov-navy/10' : 'bg-slate-100'}`}>
                    {getNotificationIcon(notif)}
                  </div>

                  <div className="flex-1 min-w-0">
                    <div className="flex items-start justify-between gap-2">
                      <div className="flex items-center gap-2">
                        <h3 className={`text-sm ${isUnread ? 'font-bold text-slate-900' : 'font-semibold text-slate-700'}`}>
                          {notif.title || 'Notifikasi SIKOMANDO'}
                        </h3>
                        {isUnread && (
                          <span className="w-2 h-2 rounded-full bg-gov-navy shrink-0" title="Belum dibaca" />
                        )}
                      </div>
                      <span className="text-[11px] text-slate-400 shrink-0">
                        {formatDateIndo(notif.created_at)}
                      </span>
                    </div>

                    <p className="text-xs text-slate-600 mt-1 leading-relaxed">
                      {notif.message}
                    </p>

                    {/* Metadata & Direct Link */}
                    <div className="mt-3 flex items-center justify-between">
                      <div className="flex items-center gap-2">
                        {notif.entity_type && (
                          <span className="text-[10px] font-mono px-2 py-0.5 rounded bg-slate-100 text-slate-600 uppercase">
                            {notif.entity_type.replace(/_/g, ' ')}
                          </span>
                        )}
                        {notif.read_at ? (
                          <span className="text-[10px] text-slate-400 flex items-center gap-1">
                            <EnvelopeOpenIcon className="w-3 h-3" /> Dibaca
                          </span>
                        ) : (
                          <Badge variant="info" size="sm">Baru</Badge>
                        )}
                      </div>

                      {notif.entity_id && (
                        <Button
                          size="xs"
                          variant="ghost"
                          onClick={(e) => {
                            e.stopPropagation();
                            handleNavigateToEntity(notif);
                          }}
                          rightIcon={<ArrowRightIcon className="w-3 h-3" />}
                        >
                          Buka Berkas
                        </Button>
                      )}
                    </div>
                  </div>
                </div>
              </div>
            );
          })}
        </div>
      )}
    </div>
  );
}

