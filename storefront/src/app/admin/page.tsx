'use client';
import { useEffect, useState } from 'react';
import Link from 'next/link';
import { getDashboard } from '@/lib/admin-api';

function KPI({ label, value, sub, accent }: { label: string; value: string | number; sub?: string; accent?: boolean }) {
  return (
    <div className={`p-4 sm:p-5 rounded-2xl border ${accent ? 'border-stone-900 bg-stone-950 text-amber-50 shadow-xl shadow-stone-900/15' : 'border-white/80 bg-white/70 backdrop-blur-xl shadow-[0_14px_30px_-25px_rgba(28,25,23,.55)] text-stone-900'}`}>
      <p className={`font-display text-xs uppercase tracking-wider font-bold ${accent ? 'text-amber-100' : 'text-stone-500'} mb-2`}>{label}</p>
      <p className={`font-display font-black text-2xl tracking-tight ${accent ? 'text-white' : 'text-stone-900'}`}>{value}</p>
      {sub && <p className={`text-xs ${accent ? 'text-amber-100' : 'text-stone-500'} mt-1 font-medium`}>{sub}</p>}
    </div>
  );
}

function StatusBadge({ status }: { status: string }) {
  const map: Record<string, string> = {
    paid: 'text-emerald-800 bg-emerald-50 border-emerald-200',
    pending: 'text-amber-800 bg-amber-50 border-amber-200',
    failed: 'text-rose-800 bg-rose-50 border-rose-200',
    fulfilled: 'text-emerald-800 bg-emerald-50 border-emerald-200',
    unfulfilled: 'text-[#c2410c] bg-amber-50 border-amber-200',
    refunded: 'text-purple-800 bg-purple-50 border-purple-200',
  };
  return (
    <span className={`inline-block px-2.5 py-0.5 text-xs font-display uppercase tracking-wider font-bold rounded-md border ${map[status] || 'text-stone-600 bg-stone-100 border-stone-200'}`}>
      {status}
    </span>
  );
}

export default function AdminDashboardPage() {
  const [data, setData] = useState<any>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    getDashboard().then(r => { setData(r); setLoading(false); }).catch(() => setLoading(false));
  }, []);

  if (loading) return (
    <div className="flex items-center justify-center h-64">
      <div className="font-display uppercase tracking-widest text-xs text-stone-400 animate-pulse font-semibold">Loading dashboard metrics...</div>
    </div>
  );

  const fmt = (v: number) => new Intl.NumberFormat('en-US', { style: 'currency', currency: 'EGP', minimumFractionDigits: 0 }).format((v || 0) / 100);

  return (
    <div className="space-y-5 sm:space-y-6 max-w-6xl">
      <div>
        <h1 className="font-display font-black uppercase text-2xl tracking-tight text-stone-900">Dashboard</h1>
        <p className="text-xs text-stone-500 mt-1">Overview of store performance & metrics</p>
      </div>

      {/* KPIs */}
      <div className="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-3 sm:gap-4">
        <KPI label="Total Revenue" value={fmt(data?.kpis?.total_revenue_minor)} accent />
        <KPI label="Orders Today" value={data?.kpis?.orders_today ?? 0} />
        <KPI label="Pending Fulfillment" value={data?.kpis?.pending_fulfillment ?? 0} />
        <KPI label="Customers" value={data?.kpis?.total_customers ?? 0} />
        <KPI label="Products" value={data?.kpis?.total_products ?? 0} />
        <KPI label="Low Stock" value={data?.kpis?.low_stock_count ?? 0} sub="≤5 units" />
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Recent Orders */}
        <div className="lg:col-span-2 border border-white/80 bg-white/70 backdrop-blur-xl rounded-2xl shadow-[0_18px_36px_-28px_rgba(28,25,23,.6)] overflow-hidden">
          <div className="flex items-center justify-between px-5 py-4 border-b border-stone-100 bg-[#f4efe6]/50">
            <h2 className="font-display font-bold uppercase text-xs tracking-wider text-stone-700">Recent Orders</h2>
            <Link href="/admin/orders" className="font-display text-xs uppercase tracking-wider text-[#c2410c] font-bold hover:underline">View All →</Link>
          </div>
          <div className="overflow-x-auto">
            <table className="w-full text-xs">
              <thead>
                <tr className="border-b border-slate-100 bg-slate-50/50 text-slate-500">
                  <th className="text-left px-5 py-3 font-display uppercase tracking-wider font-semibold">Order</th>
                  <th className="text-left px-3 py-3 font-display uppercase tracking-wider font-semibold">Customer</th>
                  <th className="text-right px-3 py-3 font-display uppercase tracking-wider font-semibold">Total</th>
                  <th className="text-left px-3 py-3 font-display uppercase tracking-wider font-semibold">Status</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100">
                {(data?.recent_orders || []).map((o: any) => (
                  <tr key={o.id} className="hover:bg-slate-50 transition-colors">
                    <td className="px-5 py-3">
                      <Link href={`/admin/orders/${o.id}`} className="text-slate-900 font-bold hover:underline font-mono">{o.order_number}</Link>
                    </td>
                    <td className="px-3 py-3 text-slate-600 truncate max-w-[140px]">{o.customer_email}</td>
                    <td className="px-3 py-3 text-right font-semibold text-slate-900">{fmt(o.total_amount_minor)}</td>
                    <td className="px-3 py-3"><StatusBadge status={o.payment_status} /></td>
                  </tr>
                ))}
                {!data?.recent_orders?.length && (
                  <tr><td colSpan={4} className="px-5 py-8 text-center text-slate-400 font-display uppercase tracking-widest text-[10px]">No orders yet</td></tr>
                )}
              </tbody>
            </table>
          </div>
        </div>

        {/* Low Stock */}
        <div className="border border-white/80 bg-white/70 backdrop-blur-xl rounded-2xl shadow-[0_18px_36px_-28px_rgba(28,25,23,.6)] overflow-hidden">
          <div className="flex items-center justify-between px-5 py-4 border-b border-slate-100 bg-slate-50">
            <h2 className="font-display font-bold uppercase text-xs tracking-wider text-slate-700">Low Stock Items</h2>
            <Link href="/admin/products" className="font-display text-xs uppercase tracking-wider text-slate-900 font-semibold hover:underline">View All →</Link>
          </div>
          <div className="divide-y divide-slate-100">
            {(data?.low_stock || []).map((p: any) => (
              <div key={p.id} className="flex items-center gap-3 px-5 py-3 hover:bg-slate-50 transition-colors">
                <div className="w-9 h-9 bg-slate-100 border border-slate-200 rounded shrink-0 flex items-center justify-center overflow-hidden">
                  {p.image_url ? <img src={p.image_url} alt={p.title} className="w-full h-full object-cover" /> : <span className="text-slate-400 text-xs font-bold">?</span>}
                </div>
                <div className="flex-1 min-w-0">
                  <p className="text-xs font-semibold text-slate-900 truncate">{p.title}</p>
                  <p className="text-[10px] text-slate-500 font-mono">{p.sku}</p>
                </div>
                <span className={`text-xs font-bold shrink-0 px-2 py-0.5 rounded ${p.inventory === 0 ? 'text-rose-700 bg-rose-50' : 'text-amber-700 bg-amber-50'}`}>{p.inventory} left</span>
              </div>
            ))}
            {!data?.low_stock?.length && (
              <p className="px-5 py-8 text-center text-slate-400 font-display uppercase tracking-widest text-xs font-semibold">All products stocked</p>
            )}
          </div>
        </div>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Orders by Status */}
        <div className="lg:col-span-1 border border-white/80 bg-white/70 backdrop-blur-xl rounded-2xl shadow-[0_18px_36px_-28px_rgba(28,25,23,.6)] p-5 flex flex-col justify-center">
          <h2 className="font-display font-bold uppercase text-xs tracking-wider text-slate-700 mb-4">Orders Breakdown</h2>
          {(() => {
            const obs = data?.orders_by_status || {};
            const total = Object.values(obs).reduce((a: any, b: any) => a + (Number(b) || 0), 0) as number;
            if (total === 0) return <p className="text-slate-400 font-display uppercase tracking-widest text-xs">No orders recorded</p>;
            
            const paid = Number(obs.paid || 0);
            const pending = Number(obs.pending || 0);
            const failed = Number(obs.failed || 0);
            const refunded = Number(obs.refunded || 0);
            
            return (
              <div className="space-y-4">
                <div className="w-full h-3 bg-slate-100 rounded-full flex overflow-hidden">
                  <div style={{ width: `${(paid/total)*100}%` }} className="bg-emerald-500"></div>
                  <div style={{ width: `${(pending/total)*100}%` }} className="bg-amber-400"></div>
                  <div style={{ width: `${(failed/total)*100}%` }} className="bg-rose-500"></div>
                  <div style={{ width: `${(refunded/total)*100}%` }} className="bg-purple-500"></div>
                </div>
                <div className="grid grid-cols-2 gap-2 text-xs">
                  <div className="flex items-center justify-between"><span className="text-slate-600 flex items-center gap-1.5"><span className="w-2.5 h-2.5 bg-emerald-500 rounded-full"></span>Paid</span><span className="font-bold text-slate-900">{paid}</span></div>
                  <div className="flex items-center justify-between"><span className="text-slate-600 flex items-center gap-1.5"><span className="w-2.5 h-2.5 bg-amber-400 rounded-full"></span>Pending</span><span className="font-bold text-slate-900">{pending}</span></div>
                  <div className="flex items-center justify-between"><span className="text-slate-600 flex items-center gap-1.5"><span className="w-2.5 h-2.5 bg-rose-500 rounded-full"></span>Failed</span><span className="font-bold text-slate-900">{failed}</span></div>
                  <div className="flex items-center justify-between"><span className="text-slate-600 flex items-center gap-1.5"><span className="w-2.5 h-2.5 bg-purple-500 rounded-full"></span>Refunded</span><span className="font-bold text-slate-900">{refunded}</span></div>
                </div>
              </div>
            );
          })()}
        </div>

        {/* Top Products */}
        <div className="lg:col-span-1 border border-white/80 bg-white/70 backdrop-blur-xl rounded-2xl shadow-[0_18px_36px_-28px_rgba(28,25,23,.6)] overflow-hidden">
          <div className="flex items-center justify-between px-5 py-4 border-b border-slate-100 bg-slate-50">
            <h2 className="font-display font-bold uppercase text-xs tracking-wider text-slate-700">Top Selling Products</h2>
          </div>
          <div className="divide-y divide-slate-100">
            {(data?.top_products || []).map((p: any, i: number) => (
              <div key={p.id || i} className="flex items-center gap-3 px-5 py-3 hover:bg-slate-50 transition-colors">
                <div className="w-6 h-6 shrink-0 flex items-center justify-center font-display font-bold text-xs text-slate-600 bg-slate-100 border border-slate-200 rounded-full">{i + 1}</div>
                <div className="flex-1 min-w-0">
                  <p className="text-xs font-semibold text-slate-900 truncate">{p.title || p.name}</p>
                </div>
                <span className="text-xs font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded">{p.units_sold || p.sold} sold</span>
              </div>
            ))}
            {!data?.top_products?.length && (
              <p className="px-5 py-8 text-center text-slate-400 font-display uppercase tracking-widest text-xs font-semibold">No sales data</p>
            )}
          </div>
        </div>

        {/* Recent Customers */}
        <div className="lg:col-span-1 border border-white/80 bg-white/70 backdrop-blur-xl rounded-2xl shadow-[0_18px_36px_-28px_rgba(28,25,23,.6)] overflow-hidden">
          <div className="flex items-center justify-between px-5 py-4 border-b border-slate-100 bg-slate-50">
            <h2 className="font-display font-bold uppercase text-xs tracking-wider text-slate-700">Recent Customers</h2>
            <Link href="/admin/customers" className="font-display text-xs uppercase tracking-wider text-slate-900 font-semibold hover:underline">View All →</Link>
          </div>
          <div className="divide-y divide-slate-100">
            {(data?.recent_customers || []).map((c: any, i: number) => (
              <div key={c.id || i} className="flex items-center gap-3 px-5 py-3 hover:bg-slate-50 transition-colors">
                <div className="flex-1 min-w-0">
                  <p className="text-xs font-semibold text-slate-900 truncate">{c.first_name} {c.last_name}</p>
                  <p className="text-[10px] text-slate-500 font-mono truncate">{c.email}</p>
                </div>
                <span className="text-[10px] text-slate-500 font-medium">{new Date(c.created_at).toLocaleDateString()}</span>
              </div>
            ))}
            {!data?.recent_customers?.length && (
              <p className="px-5 py-8 text-center text-slate-400 font-display uppercase tracking-widest text-xs font-semibold">No customers registered</p>
            )}
          </div>
        </div>
      </div>
    </div>
  );
}
