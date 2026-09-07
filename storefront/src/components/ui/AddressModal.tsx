'use client';

import React, { useState, useEffect } from 'react';
import { useStore, SavedAddress } from '@/components/providers/StoreProvider';

const EGYPT_GOVERNORATES = [
  'Cairo', 'Giza', 'Alexandria', 'Qalyubia', 'Sharqia', 'Dakahlia', 'Gharbia',
  'Monufia', 'Beheira', 'Kafr El Sheikh', 'Damietta', 'Port Said', 'Ismailia',
  'Suez', 'Fayoum', 'Beni Suef', 'Minya', 'Assiut', 'Sohag', 'Qena', 'Luxor',
  'Aswan', 'Red Sea (Hurghada)', 'South Sinai (Sharm El Sheikh)', 'Matrouh'
];

export function AddressModal() {
  const { savedAddress, saveAddress, isAddressModalOpen, closeAddressModal, customer } = useStore();

  const [form, setForm] = useState<SavedAddress>({
    name: '',
    phone: '',
    email: '',
    city: 'Cairo',
    state: '',
    line1: '',
    postal: '',
    country: 'EG',
  });

  useEffect(() => {
    if (savedAddress) {
      setForm(savedAddress);
    } else if (customer) {
      setForm(prev => ({
        ...prev,
        name: `${customer.first_name} ${customer.last_name}`.trim(),
        email: customer.email,
        phone: customer.phone || '',
      }));
    }
  }, [savedAddress, customer, isAddressModalOpen]);

  if (!isAddressModalOpen) return null;

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!form.name.trim() || !form.phone.trim() || !form.line1.trim()) {
      alert('Please fill in your Name, Phone Number, and Street Address.');
      return;
    }
    saveAddress(form);
    closeAddressModal();
  };

  return (
    <div className="fixed inset-0 z-[70] bg-black/80 backdrop-blur-md flex items-center justify-center p-4 animate-fade-in">
      <div className="bg-[#111111] border border-white/20 w-full max-w-lg shadow-2xl p-6 md:p-8 relative">
        {/* Header */}
        <div className="flex items-center justify-between border-b border-white/10 pb-4 mb-6">
          <div>
            <h2 className="font-display font-black text-lg md:text-xl uppercase tracking-wider text-white">
              Delivery Address
            </h2>
            <p className="text-xs text-white/50 mt-0.5">
              {customer ? 'Saved to your account' : 'Saved for quick 1-click checkout (No account required)'}
            </p>
          </div>
          <button 
            onClick={closeAddressModal}
            className="w-8 h-8 flex items-center justify-center text-white/50 hover:text-white border border-white/10 hover:border-white/30 transition-colors"
          >
            ✕
          </button>
        </div>

        {/* Form */}
        <form onSubmit={handleSubmit} className="space-y-4">
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label className="block font-display font-bold text-xs uppercase tracking-wider text-white/70 mb-1.5">
                Full Name *
              </label>
              <input
                type="text"
                required
                placeholder="e.g. Ahmed Hassan"
                value={form.name}
                onChange={e => setForm({ ...form, name: e.target.value })}
                className="w-full bg-[#181818] border border-white/15 px-4 py-3 text-sm text-white font-medium outline-none focus:border-[#e8ff00] transition-colors"
              />
            </div>
            <div>
              <label className="block font-display font-bold text-xs uppercase tracking-wider text-white/70 mb-1.5">
                Phone Number *
              </label>
              <input
                type="tel"
                required
                placeholder="01012345678"
                value={form.phone}
                onChange={e => setForm({ ...form, phone: e.target.value })}
                className="w-full bg-[#181818] border border-white/15 px-4 py-3 text-sm text-white font-medium outline-none focus:border-[#e8ff00] transition-colors"
              />
            </div>
          </div>

          <div>
            <label className="block font-display font-bold text-xs uppercase tracking-wider text-white/70 mb-1.5">
              Email (Optional)
            </label>
            <input
              type="email"
              placeholder="ahmed@example.com"
              value={form.email || ''}
              onChange={e => setForm({ ...form, email: e.target.value })}
              className="w-full bg-[#181818] border border-white/15 px-4 py-3 text-sm text-white font-medium outline-none focus:border-[#e8ff00] transition-colors"
            />
          </div>

          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label className="block font-display font-bold text-xs uppercase tracking-wider text-white/70 mb-1.5">
                Governorate / City *
              </label>
              <select
                value={form.city}
                onChange={e => setForm({ ...form, city: e.target.value })}
                className="w-full bg-[#181818] border border-white/15 px-4 py-3 text-sm text-white font-medium outline-none focus:border-[#e8ff00] transition-colors cursor-pointer"
              >
                {EGYPT_GOVERNORATES.map(gov => (
                  <option key={gov} value={gov}>{gov}</option>
                ))}
              </select>
            </div>
            <div>
              <label className="block font-display font-bold text-xs uppercase tracking-wider text-white/70 mb-1.5">
                Area / District
              </label>
              <input
                type="text"
                placeholder="e.g. Maadi / Nasr City / Dokki"
                value={form.state || ''}
                onChange={e => setForm({ ...form, state: e.target.value })}
                className="w-full bg-[#181818] border border-white/15 px-4 py-3 text-sm text-white font-medium outline-none focus:border-[#e8ff00] transition-colors"
              />
            </div>
          </div>

          <div>
            <label className="block font-display font-bold text-xs uppercase tracking-wider text-white/70 mb-1.5">
              Street Address, Building & Apt *
            </label>
            <input
              type="text"
              required
              placeholder="e.g. 15 Tahrir St, Building 4, Apt 12"
              value={form.line1}
              onChange={e => setForm({ ...form, line1: e.target.value })}
              className="w-full bg-[#181818] border border-white/15 px-4 py-3 text-sm text-white font-medium outline-none focus:border-[#e8ff00] transition-colors"
            />
          </div>

          <div className="pt-3 flex items-center justify-end gap-3">
            <button
              type="button"
              onClick={closeAddressModal}
              className="px-5 py-3 text-xs font-display font-bold uppercase tracking-wider text-white/60 hover:text-white border border-white/10 hover:border-white/30 transition-colors"
            >
              Cancel
            </button>
            <button
              type="submit"
              className="px-6 py-3 text-xs font-display font-black uppercase tracking-widest bg-[#e8ff00] text-black hover:bg-white transition-colors shadow-[0_0_15px_rgba(232,255,0,0.3)]"
            >
              Save Address
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}
