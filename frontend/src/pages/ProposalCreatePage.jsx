import { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { createProposal } from '../api/proposals';

const initialForm = {
  title: '',
  description: '',
  requested_amount: '',
  grant_program_id: '',
};

function extractCreatedProposal(payload) {
  return payload?.data?.data || payload?.data || payload;
}

export default function ProposalCreatePage() {
  const navigate = useNavigate();

  const [form, setForm] = useState(initialForm);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [fieldErrors, setFieldErrors] = useState({});

  function handleChange(event) {
    const { name, value } = event.target;

    setForm((currentForm) => ({
      ...currentForm,
      [name]: value,
    }));

    setFieldErrors((currentErrors) => ({
      ...currentErrors,
      [name]: undefined,
    }));
  }

  async function handleSubmit(event) {
    event.preventDefault();

    setLoading(true);
    setError('');
    setFieldErrors({});

    const payload = {
      title: form.title.trim(),
      description: form.description.trim(),
      requested_amount: Number(form.requested_amount),
      grant_program_id: form.grant_program_id.trim() || null,
    };

    try {
      const response = await createProposal(payload);
      const createdProposal = extractCreatedProposal(response);

      if (createdProposal?.id) {
        navigate(`/proposals/${createdProposal.id}`);
        return;
      }

      navigate('/proposals');
    } catch (requestError) {
      const responseData = requestError.response?.data;

      setError(
        responseData?.message ||
          'Proposal gagal disimpan. Periksa kembali data yang diisi.',
      );

      if (responseData?.errors) {
        setFieldErrors(responseData.errors);
      }
    } finally {
      setLoading(false);
    }
  }

  return (
    <section className="mx-auto max-w-4xl space-y-6">
      <div>
        <Link
          to="/proposals"
          className="text-sm font-medium text-blue-600 hover:text-blue-800"
        >
          ← Kembali ke proposal
        </Link>

        <h1 className="mt-4 text-2xl font-bold text-slate-900">
          Ajukan Proposal
        </h1>

        <p className="mt-1 text-sm text-slate-600">
          Isi data awal proposal hibah dengan lengkap dan benar.
        </p>
      </div>

      {error && (
        <div
          role="alert"
          className="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700"
        >
          {error}
        </div>
      )}

      <form
        onSubmit={handleSubmit}
        className="space-y-6 rounded-xl border border-slate-200 bg-white p-6 shadow-sm"
      >
        <div>
          <label
            htmlFor="title"
            className="block text-sm font-medium text-slate-700"
          >
            Judul Proposal
          </label>

          <input
            id="title"
            name="title"
            type="text"
            value={form.title}
            onChange={handleChange}
            required
            placeholder="Masukkan judul proposal"
            className="mt-2 block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
          />

          {fieldErrors.title && (
            <p className="mt-1 text-xs text-red-600">
              {fieldErrors.title[0] || fieldErrors.title}
            </p>
          )}
        </div>

        <div>
          <label
            htmlFor="grant_program_id"
            className="block text-sm font-medium text-slate-700"
          >
            ID Program Hibah
          </label>

          <input
            id="grant_program_id"
            name="grant_program_id"
            type="text"
            value={form.grant_program_id}
            onChange={handleChange}
            placeholder="Masukkan UUID program hibah jika diperlukan"
            className="mt-2 block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
          />

          <p className="mt-1 text-xs text-slate-500">
            Kosongkan jika backend tidak mewajibkan program hibah pada tahap ini.
          </p>

          {fieldErrors.grant_program_id && (
            <p className="mt-1 text-xs text-red-600">
              {fieldErrors.grant_program_id[0] ||
                fieldErrors.grant_program_id}
            </p>
          )}
        </div>

        <div>
          <label
            htmlFor="requested_amount"
            className="block text-sm font-medium text-slate-700"
          >
            Jumlah Dana yang Diajukan
          </label>

          <input
            id="requested_amount"
            name="requested_amount"
            type="number"
            min="0"
            step="1"
            value={form.requested_amount}
            onChange={handleChange}
            required
            placeholder="Contoh: 50000000"
            className="mt-2 block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
          />

          {fieldErrors.requested_amount && (
            <p className="mt-1 text-xs text-red-600">
              {fieldErrors.requested_amount[0] ||
                fieldErrors.requested_amount}
            </p>
          )}
        </div>

        <div>
          <label
            htmlFor="description"
            className="block text-sm font-medium text-slate-700"
          >
            Deskripsi Proposal
          </label>

          <textarea
            id="description"
            name="description"
            rows="7"
            value={form.description}
            onChange={handleChange}
            required
            placeholder="Jelaskan latar belakang, tujuan, kegiatan, dan hasil yang diharapkan."
            className="mt-2 block w-full resize-y rounded-lg border border-slate-300 px-3 py-2.5 text-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
          />

          {fieldErrors.description && (
            <p className="mt-1 text-xs text-red-600">
              {fieldErrors.description[0] || fieldErrors.description}
            </p>
          )}
        </div>

        <div className="flex flex-col-reverse justify-end gap-3 border-t border-slate-200 pt-6 sm:flex-row">
          <Link
            to="/proposals"
            className="rounded-lg border border-slate-300 px-5 py-2.5 text-center text-sm font-medium text-slate-700 transition hover:bg-slate-100"
          >
            Batal
          </Link>

          <button
            type="submit"
            disabled={loading}
            className="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-medium text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
          >
            {loading ? 'Menyimpan...' : 'Simpan Proposal'}
          </button>
        </div>
      </form>
    </section>
  );
}