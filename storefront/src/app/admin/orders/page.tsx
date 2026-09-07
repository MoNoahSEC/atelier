'use client';
import { useEffect, useState } from 'react';
import Link from 'next/link';
import { getOrders } from '@/lib/admin-api';

const STATUS_COLORS: Record<string, string> = {
  paid: 'text-emerald-700 bg-emerald-50 border-emerald-200',
  pending: 'text-amber-700 bg-amber-50 border-amber-200',
  failed: 'text-rose-700 bg-rose-50 border-rose-200',
  refunded: 'text-purple-700 bg-purple-50 border-purple-200',
  fulfilled: 'text-emerald-700 bg-emerald-50 border-emerald-200',
  unfulfilled: 'text-orange-700 bg-orange-50 border-orange-200',
  fulfilling: 'text-sky-700 bg-sky-50 border-sky-200',
  cancelled: 'text-rose-700 bg-rose-50 border-rose-200',
};
const Badge = ({ s }: { s: string }) => <span className={`inline-block px-2.5 py-0.5 text-[10px] font-display uppercase tracking-wider font-bold rounded border ${STATUS_COLORS[s] || 'text-slate-600 bg-slate-100 border-slate-200'}`}>{s}</span>;

export default function AdminOrdersPage() {
  const [orders, setOrders] = useState<any[]>([]);
  const [meta, setMeta] = useState<any>(null);
  const [search, setSearch] = useState('');
  const [pStatus, setPStatus] = useState('');
  const [fStatus, setFStatus] = useState('');
  const [loading, setLoading] = useState(true);

  async function load(page = 1) {
    setLoading(true);
    const p: Record<string, string> = { page: String(page) };
    if (search) p.search = search;
    if (pStatus) p.payment_status = pStatus;
    if (fStatus) p.fulfillment_status = fStatus;
    const r = await getOrders(p);
    setOrders(r.data); setMeta(r.meta);
    setLoading(false);
  }

  useEffect(() => { load(); }, []);

  const fmt = (v: number) => `${((v || 0) / 100).toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 2 })} EGP`;
  const date = (d: string) => new Date(d).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });

  return (
    <div className="space-y-6 max-w-6xl">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="font-display font-black uppercase text-2xl tracking-tight text-slate-900">Orders</h1>
          <p className="text-xs text-slate-500 mt-1">{meta?.total ?? '–'} total orders recorded</p>
        </div>
      </div>

      <div className="flex flex-wrap gap-3">
        <input type="text" placeholder="Search order # or email..." value={search} onChange={e => setSearch(e.target.value)} onKeyDown={e => e.key === 'Enter' && load()}
          className="flex-1 min-w-[200px] bg-white border-2 border-stone-200 rounded-xl px-4 py-2.5 text-sm text-stone-900 outline-none focus:border-[#c2410c] placeholder:text-stone-400 font-medium" />
        <select value={pStatus} onChange={e => { setPStatus(e.target.value); setTimeout(load, 0); }}
          className="bg-white border-2 border-stone-200 rounded-xl px-3.5 py-2.5 text-sm text-stone-800 outline-none focus:border-[#c2410c] font-semibold">
          <option value="">Payment Status</option>
          <option value="paid">Paid</option><option value="pending">Pending</option><option value="failed">Failed</option><option value="refunded">Refunded</option>
        </select>
        <select value={fStatus} onChange={e => { setFStatus(e.target.value); setTimeout(load, 0); }}
          className="bg-white border-2 border-stone-200 rounded-xl px-3.5 py-2.5 text-sm text-stone-800 outline-none focus:border-[#c2410c] font-semibold">
          <option value="">Fulfillment Status</option>
          <option value="unfulfilled">Unfulfilled</option><option value="fulfilling">Fulfilling</option><option value="fulfilled">Fulfilled</option><option value="cancelled">Cancelled</option>
        </select>
        <button onClick={() => load()} className="bg-[#c2410c] text-amber-50 rounded-xl px-6 py-2.5 text-xs font-display uppercase tracking-wider font-black hover:bg-[#9a3412] active:scale-95 transition-all shadow-md cursor-pointer">Search</button>
      </div>

      <div className="border border-slate-200 bg-white rounded-lg shadow-xs overflow-hidden">
        {loading ? <div className="flex items-center justify-center h-40 text-xs text-slate-400 font-display uppercase tracking-widest animate-pulse font-semibold">Loading orders...</div> : (
          <table className="w-full text-xs">
            <thead>
              <tr className="border-b border-slate-200 bg-slate-50 text-slate-600 font-semibold">
                <th className="text-left px-4 py-3 font-display uppercase tracking-wider">Order</th>
                <th className="text-left px-3 py-3 font-display uppercase tracking-wider">Customer</th>
                <th className="text-right px-3 py-3 font-display uppercase tracking-wider">Total</th>
                <th className="text-left px-3 py-3 font-display uppercase tracking-wider">Payment</th>
                <th className="text-left px-3 py-3 font-display uppercase tracking-wider">Fulfillment</th>
                <th className="text-left px-3 py-3 font-display uppercase tracking-wider">Date</th>
                <th className="text-right px-4 py-3 font-display uppercase tracking-wider">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100">
              {orders.map((o: any) => (
                <tr key={o.id} className="hover:bg-slate-50 transition-colors">
                  <td className="px-4 py-3"><span className="font-mono font-bold text-slate-900">{o.order_number}</span></td>
                  <td className="px-3 py-3 text-slate-600 truncate max-w-[140px]">{o.customer_email}</td>
                  <td className="px-3 py-3 text-right font-bold text-slate-900">{fmt(o.total_amount_minor)}</td>
                  <td className="px-3 py-3"><Badge s={o.payment_status} /></td>
                  <td className="px-3 py-3"><Badge s={o.fulfillment_status} /></td>
                  <td className="px-3 py-3 text-slate-500 font-mono text-[11px]">{date(o.created_at)}</td>
                  <td className="px-4 py-3 text-right">
                    <Link href={`/admin/orders/${o.id}`} className="font-display text-xs font-semibold uppercase tracking-wider text-slate-900 hover:underline">View →</Link>
                  </td>
                </tr>
              ))}
              {!orders.length && <tr><td colSpan={7} className="px-4 py-16 text-center text-slate-400 font-display uppercase tracking-widest text-xs font-semibold">No orders found</td></tr>}
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
    </div>
  );
}
