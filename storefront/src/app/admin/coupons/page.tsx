'use client';
import { useEffect, useState } from 'react';
import { getCoupons, createCoupon, updateCoupon, deleteCoupon } from '@/lib/admin-api';

export default function AdminCouponsPage() {
  const [coupons, setCoupons] = useState<any[]>([]);
  const [meta, setMeta] = useState<any>(null);
  const [loading, setLoading] = useState(true);
  const [modalOpen, setModalOpen] = useState(false);
  const [editing, setEditing] = useState<any>(null);
  const [formData, setFormData] = useState({
    code: '', type: 'percentage', value: '', min_order_amount: '', 
    max_discount: '', max_uses: '', start_date: '', expiry_date: '', is_active: true
  });

  async function load(page = 1) {
    setLoading(true);
    const r = await getCoupons({ page: String(page) });
    setCoupons(r.data);
    setMeta(r.meta);
    setLoading(false);
  }

  useEffect(() => { load(); }, []);

  const handleOpenModal = (c?: any) => {
    if (c) {
      setEditing(c.id);
      setFormData({
        code: c.code, type: c.type, value: c.value, min_order_amount: c.min_order_amount || '',
        max_discount: c.max_discount || '', max_uses: c.max_uses || '',
        start_date: c.start_date ? c.start_date.split('T')[0] : '',
        expiry_date: c.expiry_date ? c.expiry_date.split('T')[0] : '',
        is_active: c.is_active
      });
    } else {
      setEditing(null);
      setFormData({ code: '', type: 'percentage', value: '', min_order_amount: '', max_discount: '', max_uses: '', start_date: '', expiry_date: '', is_active: true });
    }
    setModalOpen(true);
  };

  const handleSave = async (e: React.FormEvent) => {
    e.preventDefault();
    try {
      if (editing) await updateCoupon(editing, formData);
      else await createCoupon(formData);
      setModalOpen(false);
      load(meta?.current_page || 1);
    } catch (err: any) {
      alert(err.message);
    }
  };

  const handleDelete = async (id: number) => {
    if (confirm('Delete this coupon?')) {
      await deleteCoupon(id);
      load(meta?.current_page || 1);
    }
  };

  const fmt = (v: number) => new Intl.NumberFormat('en-US', { style: 'currency', currency: 'EGP', minimumFractionDigits: 0 }).format((v || 0) / 100);

  return (
    <div className="space-y-6 max-w-6xl">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="font-display font-black uppercase text-2xl tracking-tight text-slate-900">Coupons & Discounts</h1>
          <p className="text-xs text-slate-500 mt-1">{meta?.total ?? '–'} discount codes available</p>
        </div>
        <button onClick={() => handleOpenModal()} className="bg-[#c2410c] text-amber-50 px-5 py-2.5 font-display uppercase tracking-wider text-xs font-black hover:bg-[#9a3412] active:scale-95 transition-all rounded-xl shadow-md cursor-pointer">
          + Create Coupon
        </button>
      </div>

      <div className="border border-slate-200 bg-white rounded-lg shadow-xs overflow-hidden">
        {loading ? <div className="flex items-center justify-center h-40 text-xs text-slate-400 font-display uppercase tracking-widest animate-pulse font-semibold">Loading coupons...</div> : (
          <table className="w-full text-xs">
            <thead>
              <tr className="border-b border-slate-200 bg-slate-50 text-slate-600 font-semibold">
                <th className="text-left px-4 py-3 font-display uppercase tracking-wider">Coupon Code</th>
                <th className="text-left px-3 py-3 font-display uppercase tracking-wider">Value</th>
                <th className="text-left px-3 py-3 font-display uppercase tracking-wider">Min Order</th>
                <th className="text-right px-3 py-3 font-display uppercase tracking-wider">Usage</th>
                <th className="text-center px-3 py-3 font-display uppercase tracking-wider">Status</th>
                <th className="text-left px-3 py-3 font-display uppercase tracking-wider">Valid Until</th>
                <th className="text-right px-4 py-3 font-display uppercase tracking-wider">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100">
              {coupons.map((c: any) => (
                <tr key={c.id} className="hover:bg-slate-50 transition-colors">
                  <td className="px-4 py-3 font-mono text-slate-900 font-bold">{c.code}</td>
                  <td className="px-3 py-3 font-semibold text-slate-900">
                    {c.type === 'percentage' ? `${c.value}%` : fmt(c.value)}
                  </td>
                  <td className="px-3 py-3 text-slate-600">{c.min_order_amount ? fmt(c.min_order_amount) : 'No Min'}</td>
                  <td className="px-3 py-3 text-right text-slate-600">
                    <span className="font-bold text-slate-900">{c.used_count || 0}</span> / {c.max_uses || 'Unlimited'}
                  </td>
                  <td className="px-3 py-3 text-center">
                    <span className={`inline-block px-2 py-0.5 text-[10px] font-bold rounded ${c.is_active ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200'}`}>
                      {c.is_active ? 'Active' : 'Inactive'}
                    </span>
                  </td>
                  <td className="px-3 py-3 text-slate-500 font-mono text-[11px]">{c.expiry_date ? new Date(c.expiry_date).toLocaleDateString() : 'Never'}</td>
                  <td className="px-4 py-3 text-right">
                    <div className="flex justify-end items-center gap-3">
                      <button onClick={() => handleOpenModal(c)} className="text-xs font-semibold text-slate-700 hover:text-slate-900">Edit</button>
                      <button onClick={() => handleDelete(c.id)} className="text-xs font-semibold text-rose-600 hover:text-rose-800">Delete</button>
                    </div>
                  </td>
                </tr>
              ))}
              {!coupons.length && <tr><td colSpan={7} className="px-4 py-16 text-center text-slate-400 font-display uppercase tracking-widest text-xs font-semibold">No coupons found</td></tr>}
            </tbody>
          </table>
        )}
        {meta && meta.last_page > 1 && (
          <div className="flex items-center justify-between px-4 py-3 border-t border-slate-200 bg-slate-50">
            <p className="text-xs text-slate-500">Page {meta.current_page} of {meta.last_page}</p>
            <div className="flex gap-2">
              {meta.current_page > 1 && <button onClick={() => load(meta.current_page - 1)} className="px-3 py-1 border border-slate-300 bg-white rounded text-xs text-slate-700 hover:bg-slate-100 font-semibold">← Prev</button>}
              {meta.current_page < meta.last_page && <button onClick={() => load(meta.current_page + 1)} className="px-3 py-1 border border-slate-300 bg-white rounded text-xs text-slate-700 hover:bg-slate-100 font-semibold">Next →</button>}
            </div>
          </div>
        )}
      </div>

      {modalOpen && (
        <div className="fixed inset-0 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center z-50 p-4">
          <div className="bg-white border border-slate-200 rounded-xl w-full max-w-lg shadow-2xl overflow-hidden">
            <div className="flex items-center justify-between px-6 py-4 border-b border-slate-100 bg-slate-50">
              <h2 className="font-display font-bold uppercase tracking-wider text-sm text-slate-900">{editing ? 'Edit Coupon' : 'Create Coupon'}</h2>
              <button onClick={() => setModalOpen(false)} className="text-slate-400 hover:text-slate-700 text-lg">✕</button>
            </div>
            <form onSubmit={handleSave} className="p-6 space-y-4">
              <div className="grid grid-cols-2 gap-4">
                <div>
                  <label className="block text-xs font-display font-semibold uppercase tracking-wider text-slate-600 mb-1">Coupon Code</label>
                  <input type="text" required value={formData.code} onChange={e => setFormData({...formData, code: e.target.value.toUpperCase()})}
                    className="w-full bg-slate-50 border border-slate-300 rounded px-3 py-2 text-sm text-slate-900 font-mono outline-none focus:bg-white focus:border-slate-900" />
                </div>
                <div>
                  <label className="block text-xs font-display font-semibold uppercase tracking-wider text-slate-600 mb-1">Discount Type</label>
                  <select value={formData.type} onChange={e => setFormData({...formData, type: e.target.value})}
                    className="w-full bg-slate-50 border border-slate-300 rounded px-3 py-2 text-sm text-slate-900 outline-none focus:bg-white focus:border-slate-900">
                    <option value="percentage">Percentage (%)</option>
                    <option value="fixed">Fixed Amount (EGP)</option>
                  </select>
                </div>
                <div>
                  <label className="block text-xs font-display font-semibold uppercase tracking-wider text-slate-600 mb-1">Value</label>
                  <input type="number" required min="0" step="0.01" value={formData.value} onChange={e => setFormData({...formData, value: e.target.value})}
                    className="w-full bg-slate-50 border border-slate-300 rounded px-3 py-2 text-sm text-slate-900 outline-none focus:bg-white focus:border-slate-900" />
                </div>
                <div>
                  <label className="block text-xs font-display font-semibold uppercase tracking-wider text-slate-600 mb-1">Min Order Amount</label>
                  <input type="number" min="0" step="0.01" value={formData.min_order_amount} onChange={e => setFormData({...formData, min_order_amount: e.target.value})}
                    className="w-full bg-slate-50 border border-slate-300 rounded px-3 py-2 text-sm text-slate-900 outline-none focus:bg-white focus:border-slate-900" />
                </div>
                {formData.type === 'percentage' && (
                  <div>
                    <label className="block text-xs font-display font-semibold uppercase tracking-wider text-slate-600 mb-1">Max Discount</label>
                    <input type="number" min="0" step="0.01" value={formData.max_discount} onChange={e => setFormData({...formData, max_discount: e.target.value})}
                      className="w-full bg-slate-50 border border-slate-300 rounded px-3 py-2 text-sm text-slate-900 outline-none focus:bg-white focus:border-slate-900" />
                  </div>
                )}
                <div>
                  <label className="block text-xs font-display font-semibold uppercase tracking-wider text-slate-600 mb-1">Max Uses</label>
                  <input type="number" min="1" value={formData.max_uses} onChange={e => setFormData({...formData, max_uses: e.target.value})} placeholder="Unlimited"
                    className="w-full bg-slate-50 border border-slate-300 rounded px-3 py-2 text-sm text-slate-900 outline-none focus:bg-white focus:border-slate-900" />
                </div>
                <div>
                  <label className="block text-xs font-display font-semibold uppercase tracking-wider text-slate-600 mb-1">Start Date</label>
                  <input type="date" value={formData.start_date} onChange={e => setFormData({...formData, start_date: e.target.value})}
                    className="w-full bg-slate-50 border border-slate-300 rounded px-3 py-2 text-sm text-slate-900 outline-none focus:bg-white focus:border-slate-900" />
                </div>
                <div>
                  <label className="block text-xs font-display font-semibold uppercase tracking-wider text-slate-600 mb-1">Expiry Date</label>
                  <input type="date" value={formData.expiry_date} onChange={e => setFormData({...formData, expiry_date: e.target.value})}
                    className="w-full bg-slate-50 border border-slate-300 rounded px-3 py-2 text-sm text-slate-900 outline-none focus:bg-white focus:border-slate-900" />
                </div>
              </div>
              <div className="flex items-center gap-2 pt-2">
                <input type="checkbox" id="is_active" checked={formData.is_active} onChange={e => setFormData({...formData, is_active: e.target.checked})} className="accent-slate-900 w-4 h-4" />
                <label htmlFor="is_active" className="text-xs text-slate-800 font-semibold cursor-pointer">Active Coupon</label>
              </div>
              <div className="flex justify-end gap-3 pt-4 border-t border-slate-100">
                <button type="button" onClick={() => setModalOpen(false)} className="px-4 py-2 text-xs font-display uppercase tracking-wider font-semibold text-slate-600 hover:text-slate-900">Cancel</button>
                <button type="submit" className="bg-[#c2410c] text-amber-50 px-6 py-2.5 rounded-xl text-xs font-display uppercase tracking-wider font-black hover:bg-[#9a3412] active:scale-95 transition-all shadow-md cursor-pointer">
                  Save Coupon
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
}
