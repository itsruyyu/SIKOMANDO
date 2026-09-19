import React from 'react';
import { CheckIcon } from '@heroicons/react/24/solid';

const STAGES = [
  { id: 1, key: 'submission', label: 'Pengajuan', statuses: ['draft', 'submitted'] },
  { id: 2, key: 'verification', label: 'Verifikasi Administrasi', statuses: ['verification', 'revision', 'verified'] },
  { id: 3, key: 'evaluation', label: 'Evaluasi Teknis', statuses: ['evaluation'] },
  { id: 4, key: 'survey', label: 'Survei Lapangan', statuses: ['survey', 'recommended'] },
  { id: 5, key: 'approval', label: 'Persetujuan & SK', statuses: ['approval', 'approved'] },
  { id: 6, key: 'disbursement', label: 'Pencairan Dana', statuses: ['disbursed'] },
  { id: 7, key: 'implementation', label: 'Pelaksanaan & Realisasi', statuses: ['implementation'] },
  { id: 8, key: 'closing', label: 'LPJ & Penutupan', statuses: ['lpj_submitted', 'lpj_verified', 'completed'] },
];

function getStageIndex(status) {
  const norm = String(status || '').toLowerCase();
  if (norm === 'rejected' || norm === 'cancelled') return -1;
  const idx = STAGES.findIndex((st) => st.statuses.includes(norm));
  return idx !== -1 ? idx : 0;
}

export default function WorkflowStepper({ currentStatus }) {
  const currentIndex = getStageIndex(currentStatus);
  const isTerminated = ['rejected', 'cancelled'].includes(String(currentStatus).toLowerCase());

  return (
    <div className="w-full overflow-x-auto py-4">
      <div className="min-w-[700px] flex items-center justify-between">
        {STAGES.map((stage, idx) => {
          const isPassed = !isTerminated && idx < currentIndex;
          const isCurrent = !isTerminated && idx === currentIndex;

          return (
            <React.Fragment key={stage.key}>
              <div className="flex flex-col items-center">
                <div
                  className={`flex h-9 w-9 items-center justify-center rounded-full border-2 text-xs font-bold transition-all ${
                    isPassed
                      ? 'border-blue-600 bg-blue-600 text-white'
                      : isCurrent
                      ? 'border-blue-600 bg-white text-blue-600 ring-4 ring-blue-50'
                      : 'border-slate-300 bg-white text-slate-400'
                  }`}
                >
                  {isPassed ? <CheckIcon className="h-4 w-4 stroke-2" /> : stage.id}
                </div>
                <span
                  className={`mt-2 text-center text-xs max-w-[90px] font-medium leading-tight ${
                    isCurrent
                      ? 'text-blue-700 font-semibold'
                      : isPassed
                      ? 'text-slate-700'
                      : 'text-slate-400'
                  }`}
                >
                  {stage.label}
                </span>
              </div>

              {idx < STAGES.length - 1 && (
                <div
                  className={`h-0.5 flex-1 mb-5 transition-colors ${
                    idx < currentIndex && !isTerminated ? 'bg-blue-600' : 'bg-slate-200'
                  }`}
                />
              )}
            </React.Fragment>
          );
        })}
      </div>
      {isTerminated && (
        <div className="mt-2 rounded-lg bg-rose-50 p-2 text-center text-xs font-medium text-rose-700">
          Status Usulan Saat Ini: {currentStatus === 'rejected' ? 'Ditolak' : 'Dibatalkan'}
        </div>
      )}
    </div>
  );
}

