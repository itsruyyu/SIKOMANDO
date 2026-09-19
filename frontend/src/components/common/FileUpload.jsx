import React, { useRef, useState } from 'react';
import { ArrowUpTrayIcon, DocumentTextIcon, XMarkIcon } from '@heroicons/react/24/outline';

export default function FileUpload({
  label = 'Unggah Dokumen',
  accept = '.pdf,.doc,.docx,.jpg,.jpeg,.png',
  maxSizeMB = 10,
  onFileSelect,
  selectedFile,
  onFileRemove,
  helperText = 'Format yang didukung: PDF, DOC, DOCX, JPG, PNG (Maks. 10MB)',
}) {
  const fileInputRef = useRef(null);
  const [dragActive, setDragActive] = useState(false);
  const [error, setError] = useState('');

  function handleFile(file) {
    setError('');
    if (!file) return;

    if (file.size > maxSizeMB * 1024 * 1024) {
      setError(`Ukuran berkas melebihi batas maksimal (${maxSizeMB}MB).`);
      return;
    }

    if (onFileSelect) {
      onFileSelect(file);
    }
  }

  function handleDrop(e) {
    e.preventDefault();
    e.stopPropagation();
    setDragActive(false);
    if (e.dataTransfer.files && e.dataTransfer.files[0]) {
      handleFile(e.dataTransfer.files[0]);
    }
  }

  return (
    <div>
      {label && <label className="block text-sm font-medium text-slate-700">{label}</label>}

      {selectedFile ? (
        <div className="mt-2 flex items-center justify-between rounded-lg border border-slate-200 bg-slate-50 p-3">
          <div className="flex items-center gap-3">
            <DocumentTextIcon className="h-6 w-6 text-blue-600" />
            <div>
              <p className="text-sm font-medium text-slate-800">{selectedFile.name}</p>
              <p className="text-xs text-slate-500">
                {(selectedFile.size / 1024 / 1024).toFixed(2)} MB
              </p>
            </div>
          </div>
          {onFileRemove && (
            <button
              type="button"
              onClick={onFileRemove}
              className="rounded-md p-1 text-slate-400 hover:bg-slate-200 hover:text-slate-600"
            >
              <XMarkIcon className="h-5 w-5" />
            </button>
          )}
        </div>
      ) : (
        <div
          onDragEnter={() => setDragActive(true)}
          onDragLeave={() => setDragActive(false)}
          onDragOver={(e) => e.preventDefault()}
          onDrop={handleDrop}
          onClick={() => fileInputRef.current?.click()}
          className={`mt-2 flex cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed p-6 text-center transition ${
            dragActive
              ? 'border-blue-500 bg-blue-50/50'
              : 'border-slate-300 bg-slate-50/50 hover:bg-slate-100/50'
          }`}
        >
          <ArrowUpTrayIcon className="h-8 w-8 text-slate-400" />
          <p className="mt-2 text-sm font-medium text-slate-700">
            Klik atau seret berkas ke area ini
          </p>
          <p className="mt-1 text-xs text-slate-500">{helperText}</p>
          <input
            ref={fileInputRef}
            type="file"
            accept={accept}
            onChange={(e) => handleFile(e.target.files?.[0])}
            className="hidden"
          />
        </div>
      )}

      {error && <p className="mt-1 text-xs text-rose-600">{error}</p>}
    </div>
  );
}

