'use client';
import { useEffect, useState } from 'react';
import Link from 'next/link';
import { useRouter } from 'next/navigation';
import { getCalendarData } from '@/lib/admin-api';

export default function AdminCalendarPage() {
  const router = useRouter();
  const [date, setDate] = useState(new Date());
  const [data, setData] = useState<any>(null);
  const [loading, setLoading] = useState(true);

  const month = date.getMonth() + 1;
  const year = date.getFullYear();

  useEffect(() => {
    setLoading(true);
    getCalendarData(month, year).then(r => {
      setData(r);
      setLoading(false);
    }).catch(() => setLoading(false));
  }, [month, year]);

  const prevMonth = () => setDate(new Date(year, date.getMonth() - 1, 1));
  const nextMonth = () => setDate(new Date(year, date.getMonth() + 1, 1));

  const daysInMonth = new Date(year, month, 0).getDate();
  const firstDay = new Date(year, month - 1, 1).getDay();
  const days = Array.from({ length: daysInMonth }, (_, i) => i + 1);
  const blanks = Array.from({ length: firstDay }, (_, i) => i);

  const fmt = (v: number) => new Intl.NumberFormat('en-US', { style: 'currency', currency: 'EGP', minimumFractionDigits: 0 }).format((v || 0) / 100);

  return (
    <div className="space-y-6 max-w-5xl">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="font-display font-black uppercase text-2xl tracking-tight text-slate-900">Orders Calendar</h1>
          <p className="text-xs text-slate-500 mt-1">Daily order analytics and sales ledger</p>
        </div>
        <div className="flex items-center gap-3">
          <button onClick={prevMonth} className="px-4 py-2 border-2 border-stone-200 rounded-xl bg-white hover:border-[#c2410c] hover:text-[#c2410c] transition-all text-stone-800 font-bold text-xs shadow-xs cursor-pointer active:scale-95">
            ← Prev
          </button>
          <span className="font-display font-black uppercase tracking-wider text-base text-stone-900 min-w-[150px] text-center">
            {date.toLocaleString('default', { month: 'long', year: 'numeric' })}
          </span>
          <button onClick={nextMonth} className="px-4 py-2 border-2 border-stone-200 rounded-xl bg-white hover:border-[#c2410c] hover:text-[#c2410c] transition-all text-stone-800 font-bold text-xs shadow-xs cursor-pointer active:scale-95">
            Next →
          </button>
        </div>
      </div>

      {/* Summary */}
      <div className="grid grid-cols-4 gap-4">
        <div className="border border-slate-200 bg-white p-4 rounded-lg shadow-xs">
          <p className="font-display text-xs uppercase tracking-wider text-slate-500 font-semibold mb-1">Total Paid</p>
          <p className="font-display font-black text-2xl text-emerald-600">{data?.summary?.paid || 0}</p>
        </div>
        <div className="border border-slate-200 bg-white p-4 rounded-lg shadow-xs">
          <p className="font-display text-xs uppercase tracking-wider text-slate-500 font-semibold mb-1">Total Pending</p>
          <p className="font-display font-black text-2xl text-amber-600">{data?.summary?.pending || 0}</p>
        </div>
        <div className="border border-slate-200 bg-white p-4 rounded-lg shadow-xs">
          <p className="font-display text-xs uppercase tracking-wider text-slate-500 font-semibold mb-1">Total Cancelled</p>
          <p className="font-display font-black text-2xl text-rose-600">{data?.summary?.cancelled || 0}</p>
        </div>
        <div className="border border-slate-900 bg-slate-900 p-4 rounded-lg shadow-sm text-white">
          <p className="font-display text-xs uppercase tracking-wider text-slate-300 font-semibold mb-1">Total Revenue</p>
          <p className="font-display font-black text-2xl text-white">{fmt(data?.summary?.revenue_minor || 0)}</p>
        </div>
      </div>

      {/* Calendar Grid */}
      <div className="border border-slate-200 bg-white rounded-lg shadow-xs overflow-hidden">
        <div className="grid grid-cols-7 border-b border-slate-200 bg-slate-50">
          {['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'].map(d => (
            <div key={d} className="p-3 text-center font-display uppercase tracking-wider text-xs font-semibold text-slate-600">{d}</div>
          ))}
        </div>
        
        {loading ? (
          <div className="flex items-center justify-center h-64 text-xs text-slate-400 font-display uppercase tracking-widest animate-pulse font-semibold">Loading calendar...</div>
        ) : (
          <div className="grid grid-cols-7 auto-rows-[100px] divide-x divide-y divide-slate-100">
            {blanks.map(b => (
              <div key={`b-${b}`} className="bg-slate-50/50"></div>
            ))}
            {days.map(d => {
              const dayStr = `${year}-${String(month).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
              const dayData = data?.days?.[dayStr] || {};
              const paid = dayData.paid || 0;
              const pending = dayData.pending || 0;
              const cancelled = dayData.cancelled || 0;
              const hasData = paid > 0 || pending > 0 || cancelled > 0;

              return (
                <div key={d} 
                  onClick={() => router.push(`/admin/orders?date_from=${dayStr}&date_to=${dayStr}`)}
                  className={`p-2 flex flex-col relative transition-colors ${hasData ? 'cursor-pointer hover:bg-slate-100/80 bg-slate-50/30' : 'hover:bg-slate-50'}`}
                >
                  <span className="text-xs font-semibold text-slate-500 mb-1.5">{d}</span>
                  <div className="flex-1 space-y-1">
                    {paid > 0 && <div className="flex items-center justify-between text-[10px] font-semibold"><div className="flex items-center gap-1"><span className="w-1.5 h-1.5 rounded-full bg-emerald-500"></span><span className="text-slate-600">Paid</span></div><span className="text-slate-900">{paid}</span></div>}
                    {pending > 0 && <div className="flex items-center justify-between text-[10px] font-semibold"><div className="flex items-center gap-1"><span className="w-1.5 h-1.5 rounded-full bg-amber-500"></span><span className="text-slate-600">Pend</span></div><span className="text-slate-900">{pending}</span></div>}
                    {cancelled > 0 && <div className="flex items-center justify-between text-[10px] font-semibold"><div className="flex items-center gap-1"><span className="w-1.5 h-1.5 rounded-full bg-rose-500"></span><span className="text-slate-600">Canc</span></div><span className="text-slate-900">{cancelled}</span></div>}
                  </div>
                </div>
              );
            })}
          </div>
        )}
      </div>
    </div>
  );
}
