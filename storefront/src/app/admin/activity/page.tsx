'use client';
import { useEffect, useState } from 'react';
import { getActivityLog } from '@/lib/admin-api';

const ACTION_COLORS: Record<string, string> = {
  create: 'text-emerald-700 bg-emerald-50 border-emerald-200',
  update: 'text-sky-700 bg-sky-50 border-sky-200',
  delete: 'text-rose-700 bg-rose-50 border-rose-200',
  login: 'text-purple-700 bg-purple-50 border-purple-200',
  export: 'text-amber-700 bg-amber-50 border-amber-200',
};

export default function AdminActivityPage() {
  const [logs, setLogs] = useState<any[]>([]);
  const [meta, setMeta] = useState<any>(null);
  const [filterAction, setFilterAction] = useState('');
  const [filterEntity, setFilterEntity] = useState('');
  const [dateFrom, setDateFrom] = useState('');
  const [dateTo, setDateTo] = useState('');
  const [loading, setLoading] = useState(true);
  const [expanded, setExpanded] = useState<Record<number, boolean>>({});

  async function load(page = 1) {
    setLoading(true);
    const p: Record<string, string> = { page: String(page) };
    if (filterAction) p.action = filterAction;
    if (filterEntity) p.entity_type = filterEntity;
    if (dateFrom) p.date_from = dateFrom;
    if (dateTo) p.date_to = dateTo;
    const r = await getActivityLog(p);
    setLogs(r.data);
    setMeta(r.meta);
    setLoading(false);
  }

  useEffect(() => { load(); }, [filterAction, filterEntity, dateFrom, dateTo]);

  const toggleExpand = (id: number) => setExpanded(prev => ({ ...prev, [id]: !prev[id] }));

  return (
    <div className="space-y-6 max-w-5xl">
      <div>
        <h1 className="font-display font-black uppercase text-2xl tracking-tight text-slate-900">Activity & Audit Log</h1>
        <p className="text-xs text-slate-500 mt-1">System audit trail and administrative actions</p>
      </div>

      <div className="flex flex-wrap gap-3 p-4 border border-slate-200 bg-white rounded-lg shadow-xs">
        <select value={filterAction} onChange={e => setFilterAction(e.target.value)} className="bg-white border border-slate-300 rounded px-3 py-1.5 text-xs text-slate-800 outline-none focus:border-slate-900 font-semibold">
          <option value="">All Actions</option>
          <option value="create">Create</option>
          <option value="update">Update</option>
          <option value="delete">Delete</option>
          <option value="login">Login</option>
          <option value="export">Export</option>
        </select>
        <select value={filterEntity} onChange={e => setFilterEntity(e.target.value)} className="bg-white border border-slate-300 rounded px-3 py-1.5 text-xs text-slate-800 outline-none focus:border-slate-900 font-semibold">
          <option value="">All Entities</option>
          <option value="product">Product</option>
          <option value="order">Order</option>
          <option value="customer">Customer</option>
          <option value="coupon">Coupon</option>
          <option value="review">Review</option>
        </select>
        <input type="date" value={dateFrom} onChange={e => setDateFrom(e.target.value)} className="bg-white border border-slate-300 rounded px-3 py-1.5 text-xs text-slate-800 outline-none focus:border-slate-900 font-semibold" placeholder="From" />
        <input type="date" value={dateTo} onChange={e => setDateTo(e.target.value)} className="bg-white border border-slate-300 rounded px-3 py-1.5 text-xs text-slate-800 outline-none focus:border-slate-900 font-semibold" placeholder="To" />
        {(filterAction || filterEntity || dateFrom || dateTo) && (
          <button onClick={() => { setFilterAction(''); setFilterEntity(''); setDateFrom(''); setDateTo(''); }} className="px-3 py-1.5 text-xs font-display font-semibold uppercase tracking-wider text-slate-500 hover:text-slate-900">Clear Filters</button>
        )}
      </div>

      <div className="border border-slate-200 bg-white rounded-lg shadow-xs overflow-hidden">
        {loading ? <div className="flex items-center justify-center h-40 text-xs text-slate-400 font-display uppercase tracking-widest animate-pulse font-semibold">Loading activity logs...</div> : (
          <div className="divide-y divide-slate-100">
            {logs.map(log => (
              <div key={log.id} className="p-4 hover:bg-slate-50 transition-colors">
                <div className="flex items-start gap-4">
                  <div className={`w-8 h-8 rounded-full border flex items-center justify-center shrink-0 ${ACTION_COLORS[log.action] || 'text-slate-600 bg-slate-100 border-slate-200'}`}>
                    <span className="text-xs uppercase font-bold">{log.action ? log.action[0] : 'a'}</span>
                  </div>
                  <div className="flex-1 min-w-0">
                    <div className="flex items-center justify-between gap-4 mb-1">
                      <p className="text-sm text-slate-800">
                        <span className="font-bold text-slate-900">{log.actor_name || 'System'}</span>
                        <span className="text-slate-500 mx-2">{log.action}d</span>
                        <span className="font-bold text-slate-900">{log.entity_type} {log.entity_id ? `#${log.entity_id}` : ''}</span>
                      </p>
                      <span className="text-[10px] text-slate-400 shrink-0 font-mono uppercase">{new Date(log.created_at).toLocaleString()}</span>
                    </div>
                    {log.description && <p className="text-xs text-slate-500 mb-2">{log.description}</p>}
                    
                    {log.changes && Object.keys(log.changes).length > 0 && (
                      <div>
                        <button onClick={() => toggleExpand(log.id)} className="text-[10px] font-display uppercase tracking-wider text-slate-500 hover:text-slate-900 font-semibold mb-2">
                          {expanded[log.id] ? 'Hide Details' : 'View Changes Diff'}
                        </button>
                        {expanded[log.id] && (
                          <div className="bg-slate-900 rounded p-3 text-[11px] font-mono overflow-x-auto text-slate-200">
                            <pre className="m-0">{JSON.stringify(log.changes, null, 2)}</pre>
                          </div>
                        )}
                      </div>
                    )}
                  </div>
                </div>
              </div>
            ))}
            {!logs.length && (
              <div className="px-5 py-16 text-center text-slate-400 font-display uppercase tracking-widest text-xs font-semibold">No activity logs found</div>
            )}
          </div>
        )}
        {meta && meta.last_page > 1 && (
          <div className="flex items-center justify-between px-5 py-3 border-t border-slate-200 bg-slate-50">
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
