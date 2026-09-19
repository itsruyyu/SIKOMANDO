import React from 'react';
import { RAB_CATEGORIES } from '../../../utils/constants';
import { formatCurrency } from '../../../utils/formatters';
import Input from '../../../components/ui/Input';
import Select from '../../../components/ui/Select';
import Button from '../../../components/ui/Button';
import { PlusIcon, TrashIcon, BanknotesIcon } from '@heroicons/react/24/outline';

/**
 * Dynamic RAB Item Builder with category grouping and real-time total calculation
 */
export function RabBuilder({
  items = [],
  onChange,
  maxBudget = null,
  disabled = false,
}) {
  const handleAddItem = () => {
    const newItem = {
      id: Date.now().toString(),
      category: RAB_CATEGORIES[0],
      item_name: '',
      specification: '',
      quantity: 1,
      unit: 'Unit',
      unit_price: 0,
      total_price: 0,
    };
    onChange([...items, newItem]);
  };

  const handleRemoveItem = (index) => {
    const updated = items.filter((_, idx) => idx !== index);
    onChange(updated);
  };

  const handleFieldChange = (index, field, value) => {
    const updated = [...items];
    const target = { ...updated[index] };

    if (field === 'quantity') {
      const qty = Math.max(1, parseInt(value) || 0);
      target.quantity = qty;
      target.total_price = qty * (parseFloat(target.unit_price) || 0);
    } else if (field === 'unit_price') {
      const price = Math.max(0, parseFloat(value) || 0);
      target.unit_price = price;
      target.total_price = (parseInt(target.quantity) || 1) * price;
    } else {
      target[field] = value;
    }

    updated[index] = target;
    onChange(updated);
  };

  const totalCalculated = items.reduce((sum, item) => sum + (parseFloat(item.total_price) || 0), 0);
  const isOverBudget = maxBudget && totalCalculated > maxBudget;

  return (
    <div className="space-y-4">
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-2 border-b border-slate-100">
        <div>
          <h4 className="text-sm font-bold text-slate-800 flex items-center gap-2">
            <BanknotesIcon className="w-4 h-4 text-emerald-600" />
            <span>Rincian Anggaran Biaya (RAB) Usulan</span>
          </h4>
          <p className="text-xs text-slate-500">
            Cantumkan item kebutuhan kegiatan secara transparan, logis, dan wajar.
          </p>
        </div>

        {!disabled && (
          <Button
            variant="subtle"
            size="sm"
            icon={PlusIcon}
            onClick={handleAddItem}
          >
            Tambah Baris Anggaran
          </Button>
        )}
      </div>

      {/* Items List */}
      {items.length === 0 ? (
        <div className="p-8 text-center bg-slate-50 rounded-xl border border-dashed border-slate-300 text-slate-500 text-xs">
          Belum ada item anggaran. Klik tombol &quot;Tambah Baris Anggaran&quot; di atas untuk menyusun RAB.
        </div>
      ) : (
        <div className="space-y-3">
          {items.map((item, idx) => (
            <div
              key={item.id || idx}
              className="p-4 rounded-xl border border-slate-200 bg-white shadow-2xs hover:border-blue-200 transition space-y-3"
            >
              <div className="flex items-center justify-between gap-2">
                <span className="text-xs font-bold font-mono text-slate-500">#{idx + 1}</span>
                <div className="flex-1 max-w-xs">
                  <Select
                    value={item.category}
                    onChange={(e) => handleFieldChange(idx, 'category', e.target.value)}
                    options={RAB_CATEGORIES}
                    disabled={disabled}
                    placeholder=""
                  />
                </div>
                {!disabled && (
                  <button
                    type="button"
                    onClick={() => handleRemoveItem(idx)}
                    className="text-slate-400 hover:text-rose-600 transition p-1 rounded-lg"
                    title="Hapus baris"
                  >
                    <TrashIcon className="w-4 h-4" />
                  </button>
                )}
              </div>

              <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <Input
                  label="Nama Barang / Kegiatan"
                  value={item.item_name}
                  onChange={(e) => handleFieldChange(idx, 'item_name', e.target.value)}
                  placeholder="Misal: Spanduk Kegiatan 3x1"
                  disabled={disabled}
                  required
                />
                <Input
                  label="Spesifikasi / Rincian Teknis"
                  value={item.specification}
                  onChange={(e) => handleFieldChange(idx, 'specification', e.target.value)}
                  placeholder="Bahan flexi 340gr"
                  disabled={disabled}
                />
                <div className="grid grid-cols-2 gap-2">
                  <Input
                    label="Volume / Qty"
                    type="number"
                    min="1"
                    value={item.quantity}
                    onChange={(e) => handleFieldChange(idx, 'quantity', e.target.value)}
                    disabled={disabled}
                    required
                  />
                  <Input
                    label="Satuan"
                    value={item.unit}
                    onChange={(e) => handleFieldChange(idx, 'unit', e.target.value)}
                    placeholder="Unit/Hari"
                    disabled={disabled}
                    required
                  />
                </div>
                <Input
                  label="Harga Satuan (Rp)"
                  type="number"
                  min="0"
                  value={item.unit_price}
                  onChange={(e) => handleFieldChange(idx, 'unit_price', e.target.value)}
                  disabled={disabled}
                  required
                  mono
                />
              </div>

              <div className="pt-2 border-t border-slate-100 flex items-center justify-between text-xs">
                <span className="text-slate-400">Subtotal Item:</span>
                <span className="font-mono font-bold text-slate-800 text-sm">
                  {formatCurrency(item.total_price)}
                </span>
              </div>
            </div>
          ))}
        </div>
      )}

      {/* Summary Banner */}
      <div className={`p-4 rounded-xl border flex flex-col sm:flex-row items-center justify-between gap-3 ${
        isOverBudget ? 'bg-rose-50 border-rose-200 text-rose-900' : 'bg-slate-900 text-white border-slate-800'
      }`}>
        <div>
          <span className="text-xs font-semibold block opacity-80">Total Akumulasi RAB Usulan:</span>
          {maxBudget && (
            <span className="text-[11px] opacity-75">
              Pagu Maksimum Program: <strong className="font-mono">{formatCurrency(maxBudget)}</strong>
            </span>
          )}
        </div>
        <div className="text-right">
          <span className={`text-xl sm:text-2xl font-black font-mono tracking-tight block ${
            isOverBudget ? 'text-rose-600' : 'text-emerald-400'
          }`}>
            {formatCurrency(totalCalculated)}
          </span>
          {isOverBudget && (
            <span className="text-[11px] font-bold text-rose-600">
              Peringatan: Total RAB melebihi pagu maksimum yang ditetapkan program!
            </span>
          )}
        </div>
      </div>
    </div>
  );
}

export default RabBuilder;

