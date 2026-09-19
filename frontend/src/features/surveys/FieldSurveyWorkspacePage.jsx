import React, { useState, useEffect } from 'react';
import { useParams, useNavigate, Link } from 'react-router-dom';
import api from '../../services/api';
import { useToast } from '../../context/ToastContext';
import { formatCoordinates } from '../../utils/formatters';
import PageHeader from '../../components/layout/PageHeader';
import Card, { CardBody, CardHeader } from '../../components/ui/Card';
import Input from '../../components/ui/Input';
import Textarea from '../../components/ui/Textarea';
import Button from '../../components/ui/Button';
import Spinner from '../../components/feedback/Spinner';
import Alert from '../../components/feedback/Alert';
import {
  MapPinIcon,
  CameraIcon,
  CheckCircleIcon,
  XCircleIcon,
  ArrowLeftIcon,
  ShieldCheckIcon,
  SparklesIcon,
} from '@heroicons/react/24/outline';

export function FieldSurveyWorkspacePage() {
  const { proposalId } = useParams();
  const navigate = useNavigate();
  const toast = useToast();

  const [proposal, setProposal] = useState(null);
  const [survey, setSurvey] = useState(null);
  const [latitude, setLatitude] = useState('1.47483');
  const [longitude, setLongitude] = useState('124.84281');
  const [accuracy, setAccuracy] = useState(null);
  const [isLocating, setIsLocating] = useState(false);

  // Photos state
  const [photos, setPhotos] = useState([]);
  const [photoPreviews, setPhotoPreviews] = useState([]);

  // Checklist items
  const [checklist, setChecklist] = useState([
    { id: '1', label: 'Plang nama ormas/lembaga terpasang jelas di lokasi', status: 'valid' },
    { id: '2', label: 'Kesesuaian alamat sekretariat fisik dengan surat domisili', status: 'valid' },
    { id: '3', label: 'Keberadaan sarana dan fasilitas penunjang kegiatan', status: 'valid' },
    { id: '4', label: 'Wawancara langsung dengan ketua / pengurus resmi', status: 'valid' },
  ]);

  const [findingsNotes, setFindingsNotes] = useState('');
  const [conclusion, setConclusion] = useState('recommended'); // 'recommended', 'rejected'
  const [isLoading, setIsLoading] = useState(true);
  const [isSubmitting, setIsSubmitting] = useState(false);

  useEffect(() => {
    let isMounted = true;
    async function loadSurvey() {
      setIsLoading(true);
      try {
        const [propRes, surListRes] = await Promise.all([
          api.get(`/proposals/${proposalId}`),
          api.get(`/proposals/${proposalId}/field-surveys`),
        ]);

        if (!isMounted) return;
        setProposal(propRes.data);

        let activeSurvey = null;
        if (surListRes.data && surListRes.data.length > 0) {
          activeSurvey = surListRes.data[0];
        } else {
          try {
            const createRes = await api.post(`/proposals/${proposalId}/field-surveys`, {
              notes: 'Survei peninjauan fisik lapangan.',
            });
            activeSurvey = createRes.data;
          } catch {
            // Handled
          }
        }

        if (activeSurvey) {
          setSurvey(activeSurvey);
          if (activeSurvey.latitude) setLatitude(activeSurvey.latitude.toString());
          if (activeSurvey.longitude) setLongitude(activeSurvey.longitude.toString());
          if (activeSurvey.findings) setFindingsNotes(activeSurvey.findings);
        }
      } catch (err) {
        console.error('Failed to load survey details:', err);
      } finally {
        if (isMounted) setIsLoading(false);
      }
    }

    loadSurvey();
    return () => { isMounted = false; };
  }, [proposalId]);

  // Geolocation Capture
  const handleCaptureGps = () => {
    if (!navigator.geolocation) {
      toast.error('Perangkat Anda tidak mendukung geolokasi GPS.');
      return;
    }

    setIsLocating(true);
    navigator.geolocation.getCurrentPosition(
      (pos) => {
        setIsLocating(false);
        setLatitude(pos.coords.latitude.toFixed(6));
        setLongitude(pos.coords.longitude.toFixed(6));
        setAccuracy(Math.round(pos.coords.accuracy));
        toast.success(`Koordinat GPS berhasil dikunci (Akurasi: ±${Math.round(pos.coords.accuracy)}m)`);
      },
      (err) => {
        setIsLocating(false);
        toast.warning('Gagal mengunci GPS otomatis. Anda dapat mengetik koordinat secara manual.');
      },
      { enableHighAccuracy: true, timeout: 10000 }
    );
  };

  const handlePhotoSelect = (e) => {
    const files = Array.from(e.target.files || []);
    if (files.length === 0) return;

    setPhotos((prev) => [...prev, ...files]);

    const newPreviews = files.map((file) => URL.createObjectURL(file));
    setPhotoPreviews((prev) => [...prev, ...newPreviews]);
  };

  const handleChecklistToggle = (id, status) => {
    setChecklist((prev) =>
      prev.map((it) => (it.id === id ? { ...it, status } : it))
    );
  };

  const handleCompleteSurvey = async () => {
    if (!survey) return;
    setIsSubmitting(true);

    try {
      // 1. Submit results with coordinates
      await api.patch(`/proposals/${proposalId}/field-surveys/${survey.id}/result`, {
        latitude: parseFloat(latitude),
        longitude: parseFloat(longitude),
        findings: findingsNotes || 'Survei faktual lapangan selesai dilaksanakan dengan hasil memuaskan.',
        recommendation: conclusion,
      });

      // 2. Submit photos if any
      for (const file of photos) {
        const formData = new FormData();
        formData.append('document', file);
        formData.append('document_type', 'SURVEY_EVIDENCE_PHOTO');
        try {
          await api.post(`/proposals/${proposalId}/field-surveys/${survey.id}/documents`, formData);
        } catch {
          // Non-blocking
        }
      }

      // 3. Mark complete
      const surveyResult = conclusion === 'rejected' ? 'not_recommended' : 'recommended';
      await api.post(`/proposals/${proposalId}/field-surveys/${survey.id}/complete`, {
        result: surveyResult,
        recommendation: conclusion,
        summary: findingsNotes || 'Survei faktual lapangan selesai dilaksanakan dengan hasil memuaskan.',
        notes: findingsNotes || '',
      });

      toast.success('Berita Acara Survei Lapangan & Tagging GPS berhasil disimpan!');
      navigate('/field-surveys');
    } catch (err) {
      toast.error(err.message || 'Gagal menyelesaikan survei lapangan.');
    } finally {
      setIsSubmitting(false);
    }
  };

  if (isLoading) {
    return (
      <div className="py-20">
        <Spinner size="lg" label="Menyiapkan formulir survei lapangan..." />
      </div>
    );
  }

  return (
    <div className="max-w-4xl mx-auto space-y-6 pb-20">
      <PageHeader
        title={`Survei Lapangan: ${proposal?.proposal_number || 'USULAN'}`}
        subtitle={`Lokasi: ${proposal?.organization?.name} • ${proposal?.organization?.address || 'Sulawesi Utara'}`}
        breadcrumbs={[
          { label: 'Survei Lapangan', to: '/field-surveys' },
          { label: 'Formulir Faktual' },
        ]}
        action={
          <Link to="/field-surveys">
            <Button variant="secondary" size="sm" icon={ArrowLeftIcon}>
              Kembali
            </Button>
          </Link>
        }
      />

      {/* Geolocation Tagging Card */}
      <Card className="border-blue-200 shadow-sm overflow-hidden">
        <CardHeader
          title="Titik Koordinat Geospasial Lapangan (GPS)"
          subtitle="Kunci koordinat presisi lokasi sekretariat atau tempat pelaksanaan kegiatan"
        />
        <CardBody className="space-y-4">
          <div className="p-4 rounded-xl bg-blue-50/70 border border-blue-200 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div className="flex items-center gap-3">
              <div className="p-3 rounded-xl bg-blue-600 text-white">
                <MapPinIcon className="w-6 h-6" />
              </div>
              <div>
                <span className="text-xs text-blue-900 font-bold block">Koordinat Terpilih:</span>
                <span className="font-mono text-sm sm:text-base font-black text-blue-950">
                  {formatCoordinates(latitude, longitude)}
                </span>
                {accuracy && (
                  <span className="text-[11px] text-blue-700 block">
                    Radius Akurasi Perangkat: ±{accuracy} meter
                  </span>
                )}
              </div>
            </div>

            <Button
              variant="primary"
              size="sm"
              icon={SparklesIcon}
              onClick={handleCaptureGps}
              isLoading={isLocating}
              className="shrink-0"
            >
              Kunci GPS Otomatis
            </Button>
          </div>

          <div className="grid grid-cols-2 gap-4">
            <Input
              label="Latitude (Lintang)"
              value={latitude}
              onChange={(e) => setLatitude(e.target.value)}
              placeholder="Contoh: 1.47483"
              mono
              required
            />
            <Input
              label="Longitude (Bujur)"
              value={longitude}
              onChange={(e) => setLongitude(e.target.value)}
              placeholder="Contoh: 124.84281"
              mono
              required
            />
          </div>
        </CardBody>
      </Card>

      {/* Physical Checklist Card */}
      <Card className="border-slate-200 shadow-sm">
        <CardHeader
          title="Pemeriksaan Faktual Lapangan"
          subtitle="Centang kesesuaian kondisi nyata di lapangan"
        />
        <CardBody className="space-y-3">
          {checklist.map((it) => (
            <div
              key={it.id}
              className="p-3.5 rounded-xl border border-slate-200 flex items-center justify-between gap-3 bg-white"
            >
              <span className="text-xs font-semibold text-slate-800">{it.label}</span>
              <div className="flex items-center gap-1.5 shrink-0">
                <button
                  type="button"
                  onClick={() => handleChecklistToggle(it.id, 'valid')}
                  className={`px-2.5 py-1 rounded-md text-xs font-bold transition cursor-pointer ${
                    it.status === 'valid'
                      ? 'bg-emerald-600 text-white'
                      : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
                  }`}
                >
                  Sesuai
                </button>
                <button
                  type="button"
                  onClick={() => handleChecklistToggle(it.id, 'invalid')}
                  className={`px-2.5 py-1 rounded-md text-xs font-bold transition cursor-pointer ${
                    it.status === 'invalid'
                      ? 'bg-rose-600 text-white'
                      : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
                  }`}
                >
                  Tidak Sesuai
                </button>
              </div>
            </div>
          ))}
        </CardBody>
      </Card>

      {/* Photo Uploads Card */}
      <Card className="border-slate-200 shadow-sm">
        <CardHeader
          title="Dokumentasi Foto Bukti Fisik Lapangan"
          subtitle="Unggah foto plang nama, ruangan kantor, atau wawancara dengan pengurus"
        />
        <CardBody className="space-y-4">
          <div className="flex items-center gap-3">
            <label className="cursor-pointer inline-flex items-center gap-2 px-4 py-2 rounded-xl border border-dashed border-blue-400 bg-blue-50/50 hover:bg-blue-100/50 text-xs font-bold text-blue-700 transition">
              <CameraIcon className="w-5 h-5" />
              <span>Ambil Foto / Pilih Gambar</span>
              <input
                type="file"
                accept="image/*"
                multiple
                className="hidden"
                onChange={handlePhotoSelect}
              />
            </label>
            <span className="text-xs text-slate-400">Format JPG, PNG (maksimal 5MB)</span>
          </div>

          {photoPreviews.length > 0 && (
            <div className="grid grid-cols-2 sm:grid-cols-4 gap-3 pt-2">
              {photoPreviews.map((src, idx) => (
                <div key={idx} className="relative rounded-xl overflow-hidden border border-slate-200 aspect-video bg-slate-100">
                  <img src={src} alt="Bukti Lapangan" className="w-full h-full object-cover" />
                  <span className="absolute bottom-1 right-1 text-[9px] font-bold px-1.5 py-0.5 rounded bg-black/60 text-white">
                    Foto #{idx + 1}
                  </span>
                </div>
              ))}
            </div>
          )}
        </CardBody>
      </Card>

      {/* Findings & Conclusion */}
      <Card className="border-slate-200 shadow-sm">
        <CardHeader
          title="Temuan & Kesimpulan Survei Faktual"
          subtitle="Penetapan kelayakan usulan berdasarkan kondisi nyata di lapangan"
        />
        <CardBody className="space-y-4">
          <Textarea
            label="Catatan Temuan Lapangan"
            value={findingsNotes}
            onChange={(e) => setFindingsNotes(e.target.value)}
            placeholder="Deskripsikan kondisi fisik, kesiapan pengurus, dan kelayakan sarana yang ditinjau..."
            rows={3}
            required
          />

          <div className="space-y-2">
            <label className="text-xs font-bold text-slate-800 block">
              Keputusan Kelayakan Faktual:
            </label>
            <div className="grid grid-cols-2 gap-3">
              <button
                type="button"
                onClick={() => setConclusion('recommended')}
                className={`p-3 rounded-xl border text-xs font-bold text-center transition cursor-pointer ${
                  conclusion === 'recommended'
                    ? 'border-emerald-500 bg-emerald-50 text-emerald-900 ring-2 ring-emerald-500'
                    : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50'
                }`}
              >
                Memenuhi Syarat (Rekomendasi Lolos)
              </button>
              <button
                type="button"
                onClick={() => setConclusion('rejected')}
                className={`p-3 rounded-xl border text-xs font-bold text-center transition cursor-pointer ${
                  conclusion === 'rejected'
                    ? 'border-rose-500 bg-rose-50 text-rose-900 ring-2 ring-rose-500'
                    : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50'
                }`}
              >
                Tidak Memenuhi Syarat (Gugur Lapangan)
              </button>
            </div>
          </div>

          <div className="pt-2">
            <Button
              variant="primary"
              size="lg"
              className="w-full font-bold shadow-md"
              onClick={handleCompleteSurvey}
              isLoading={isSubmitting}
              icon={ShieldCheckIcon}
            >
              Selesaikan & Terbitkan Berita Acara Survei Lapangan
            </Button>
          </div>
        </CardBody>
      </Card>
    </div>
  );
}

export default FieldSurveyWorkspacePage;

