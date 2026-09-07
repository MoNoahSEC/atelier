'use client';
import { useEffect, useState } from 'react';
import Link from 'next/link';
import { getCustomers, exportCustomersCSV } from '@/lib/admin-api';

export default function AdminCustomersPage() {
  const [customers, setCustomers] = useState<any[]>([]);
  const [meta, setMeta] = useState<any>(null);
  const [search, setSearch] = useState('');
  const [loading, setLoading] = useState(true);

  async function load(page = 1) {
    setLoading(true);
    const p: Record<string, string> = { page: String(page) };
    if (search) p.search = search;
    const r = await getCustomers(p);
    setCustomers(r.data); setMeta(r.meta);
    setLoading(false);
  }
  useEffect(() => { load(); }, []);

  const fmt = (v: number) => new Intl.NumberFormat('en-US', { style: 'currency', currency: 'EGP', minimumFractionDigits: 0 }).format((v || 0) / 100);
  const date = (d: string) => new Date(d).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });

  return (
    <div className="space-y-6 max-w-5xl">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="font-display font-black uppercase text-2xl tracking-tight text-slate-900">Customers</h1>
          <p className="text-xs text-slate-500 mt-1">{meta?.total ?? '–'} registered customer profiles</p>
        </div>
        <button onClick={() => exportCustomersCSV().catch(e => alert(e.message))} className="bg-[#c2410c] text-amber-50 px-5 py-2.5 font-display uppercase tracking-wider text-xs font-black hover:bg-[#9a3412] active:scale-95 transition-all rounded-xl shadow-md cursor-pointer">
          Export CSV ↗
        </button>
      </div>
      <div className="flex gap-3">
        <input type="text" placeholder="Search by email or name..." value={search} onChange={e => setSearch(e.target.value)} onKeyDown={e => e.key === 'Enter' && load()}
          className="flex-1 bg-white border-2 border-stone-200 rounded-xl px-4 py-2.5 text-sm text-stone-900 outline-none focus:border-[#c2410c] placeholder:text-stone-400 font-medium" />
        <button onClick={() => load()} className="bg-[#c2410c] text-amber-50 rounded-xl px-6 py-2.5 text-xs font-display uppercase tracking-wider font-black hover:bg-[#9a3412] active:scale-95 transition-all shadow-md cursor-pointer">Search</button>
      </div>

      <div className="border border-slate-200 bg-white rounded-lg shadow-xs overflow-hidden">
        {loading ? <div className="flex items-center justify-center h-40 text-xs text-slate-400 font-display uppercase tracking-widest animate-pulse font-semibold">Loading customers...</div> : (
          <table className="w-full text-xs">
            <thead>
              <tr className="border-b border-slate-200 bg-slate-50 text-slate-600 font-semibold">
                <th className="text-left px-4 py-3 font-display uppercase tracking-wider">Customer</th>
                <th className="text-left px-3 py-3 font-display uppercase tracking-wider">Phone</th>
                <th className="text-right px-3 py-3 font-display uppercase tracking-wider">Orders</th>
                <th className="text-right px-3 py-3 font-display uppercase tracking-wider">Spent</th>
                <th className="text-left px-3 py-3 font-display uppercase tracking-wider">Joined</th>
                <th className="text-right px-4 py-3 font-display uppercase tracking-wider">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100">
              {customers.map((c: any) => (
                <tr key={c.id} className="hover:bg-slate-50 transition-colors">
                  <td className="px-4 py-3">
                    <div className="font-bold text-slate-900">{c.first_name} {c.last_name}</div>
                    <div className="text-slate-500 font-mono text-[10px]">{c.email}</div>
                  </td>
                  <td className="px-3 py-3 text-slate-600 font-mono">{c.phone || '–'}</td>
                  <td className="px-3 py-3 text-right font-semibold text-slate-900">{c.orders_count ?? 0}</td>
                  <td className="px-3 py-3 text-right font-bold text-slate-900">{fmt(c.total_spent_minor)}</td>
                  <td className="px-3 py-3 text-slate-500 font-mono text-[11px]">{date(c.created_at)}</td>
                  <td className="px-4 py-3 text-right">
                    <Link href={`/admin/customers/${c.id}`} className="font-display text-xs font-semibold uppercase tracking-wider text-slate-900 hover:underline">View →</Link>
                  </td>
                </tr>
              ))}
              {!customers.length && <tr><td colSpan={6} className="px-4 py-16 text-center text-slate-400 font-display uppercase tracking-widest text-xs font-semibold">No customers found</td></tr>}
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
