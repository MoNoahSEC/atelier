'use client';
import { useEffect, useState } from 'react';
import { getNotifications, markNotificationRead, markAllNotificationsRead } from '@/lib/admin-api';

function NotificationIcon({ type }: { type: string }) {
  switch (type) {
    case 'new_order':
      return (
        <div className="w-9 h-9 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-600 flex items-center justify-center shrink-0">
          <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
          </svg>
        </div>
      );
    case 'low_stock':
      return (
        <div className="w-9 h-9 rounded-full bg-amber-50 border border-amber-200 text-amber-600 flex items-center justify-center shrink-0">
          <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
          </svg>
        </div>
      );
    case 'payment_failed':
      return (
        <div className="w-9 h-9 rounded-full bg-rose-50 border border-rose-200 text-rose-600 flex items-center justify-center shrink-0">
          <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
          </svg>
        </div>
      );
    case 'new_customer':
      return (
        <div className="w-9 h-9 rounded-full bg-sky-50 border border-sky-200 text-sky-600 flex items-center justify-center shrink-0">
          <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
          </svg>
        </div>
      );
    case 'order_cancelled':
      return (
        <div className="w-9 h-9 rounded-full bg-slate-100 border border-slate-200 text-slate-600 flex items-center justify-center shrink-0">
          <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
          </svg>
        </div>
      );
    default:
      return (
        <div className="w-9 h-9 rounded-full bg-slate-100 border border-slate-200 text-slate-600 flex items-center justify-center shrink-0">
          <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
          </svg>
        </div>
      );
  }
}

export default function AdminNotificationsPage() {
  const [notifications, setNotifications] = useState<any[]>([]);
  const [meta, setMeta] = useState<any>(null);
  const [filter, setFilter] = useState('All');
  const [loading, setLoading] = useState(true);

  const tabs = ['All', 'new_order', 'low_stock', 'payment_failed', 'new_customer', 'order_cancelled'];
  const tabLabels: Record<string, string> = {
    'All': 'All Alerts',
    'new_order': 'New Orders',
    'low_stock': 'Low Stock',
    'payment_failed': 'Payment Failures',
    'new_customer': 'New Customers',
    'order_cancelled': 'Cancelled',
  };

  async function load(page = 1) {
    setLoading(true);
    const p: Record<string, string> = { page: String(page) };
    if (filter !== 'All') p.type = filter;
    const r = await getNotifications(p);
    setNotifications(r.data);
    setMeta(r.meta);
    setLoading(false);
  }

  useEffect(() => { load(); }, [filter]);

  const handleMarkRead = async (id: number) => {
    await markNotificationRead(id);
    setNotifications(prev => prev.map(n => n.id === id ? { ...n, is_read: true } : n));
  };

  const handleMarkAllRead = async () => {
    await markAllNotificationsRead();
    setNotifications(prev => prev.map(n => ({ ...n, is_read: true })));
  };

  const timeAgo = (dateStr: string) => {
    const s = Math.floor((new Date().getTime() - new Date(dateStr).getTime()) / 1000);
    if (s < 60) return `${s}s ago`;
    if (s < 3600) return `${Math.floor(s/60)}m ago`;
    if (s < 86400) return `${Math.floor(s/3600)}h ago`;
    return `${Math.floor(s/86400)}d ago`;
  };

  return (
    <div className="space-y-6 max-w-4xl">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="font-display font-black uppercase text-2xl tracking-tight text-slate-900">Notifications Center</h1>
          <p className="text-xs text-slate-500 mt-1">Real-time activity feed and store alerts</p>
        </div>
        <button onClick={handleMarkAllRead} className="bg-[#c2410c] text-amber-50 px-4.5 py-2.5 font-display uppercase tracking-wider text-xs font-black hover:bg-[#9a3412] active:scale-95 transition-all rounded-xl shadow-md cursor-pointer">
          Mark All as Read
        </button>
      </div>

      <div className="flex gap-2 overflow-x-auto pb-1">
        {tabs.map(t => (
          <button key={t} onClick={() => setFilter(t)}
            className={`px-4 py-2 text-xs font-display font-bold uppercase tracking-wider whitespace-nowrap transition-all rounded-xl border-2 cursor-pointer ${
              filter === t ? 'border-[#c2410c] bg-[#c2410c] text-amber-50 shadow-sm' : 'border-stone-200 bg-white text-stone-700 hover:border-amber-400 hover:bg-amber-50/60'
            }`}>
            {tabLabels[t]}
          </button>
        ))}
      </div>

      <div className="border border-stone-200 bg-white rounded-xl shadow-xs overflow-hidden">
        {loading ? <div className="flex items-center justify-center h-40 text-xs text-stone-400 font-display uppercase tracking-widest animate-pulse font-semibold">Loading notifications...</div> : (
          <div className="divide-y divide-stone-100">
            {notifications.map(n => (
              <div key={n.id} onClick={() => !n.is_read && handleMarkRead(n.id)}
                className={`p-4 flex items-start gap-4 transition-colors ${!n.is_read ? 'border-l-4 border-l-[#c2410c] bg-amber-50/40 cursor-pointer' : 'border-l-4 border-l-transparent hover:bg-stone-50'}`}
              >
                <NotificationIcon type={n.type} />
                <div className="flex-1 min-w-0">
                  <div className="flex items-center justify-between gap-4 mb-1">
                    <p className={`text-sm ${!n.is_read ? 'font-bold text-slate-900' : 'font-medium text-slate-700'}`}>{n.title}</p>
                    <span className="text-[10px] text-slate-400 shrink-0 font-mono uppercase">{timeAgo(n.created_at)}</span>
                  </div>
                  <p className="text-xs text-slate-600 leading-relaxed">{n.message}</p>
                </div>
              </div>
            ))}
            {!notifications.length && (
              <div className="px-5 py-16 text-center text-slate-400 font-display uppercase tracking-widest text-xs font-semibold">No notifications found</div>
            )}
          </div>
        )}
        {meta && meta.last_page > 1 && (
          <div className="flex items-center justify-between px-5 py-3 border-t border-slate-100 bg-slate-50">
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
