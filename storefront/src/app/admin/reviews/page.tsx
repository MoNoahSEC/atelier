'use client';
import { useEffect, useState } from 'react';
import { getReviews, approveReview, rejectReview, deleteReview } from '@/lib/admin-api';

function Stars({ rating }: { rating: number }) {
  return (
    <div className="flex items-center gap-0.5">
      {[1, 2, 3, 4, 5].map((star) => (
        <svg
          key={star}
          className={`w-3.5 h-3.5 ${star <= rating ? 'text-amber-400 fill-amber-400' : 'text-slate-200 fill-slate-200'}`}
          viewBox="0 0 20 20"
        >
          <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
        </svg>
      ))}
    </div>
  );
}

export default function AdminReviewsPage() {
  const [reviews, setReviews] = useState<any[]>([]);
  const [meta, setMeta] = useState<any>(null);
  const [filter, setFilter] = useState('All');
  const [ratingFilter, setRatingFilter] = useState<number | null>(null);
  const [loading, setLoading] = useState(true);

  async function load(page = 1) {
    setLoading(true);
    const p: Record<string, string> = { page: String(page) };
    if (filter !== 'All') p.status = filter.toLowerCase();
    if (ratingFilter) p.rating = String(ratingFilter);
    const r = await getReviews(p);
    setReviews(r.data);
    setMeta(r.meta);
    setLoading(false);
  }

  useEffect(() => { load(); }, [filter, ratingFilter]);

  const handleApprove = async (id: number) => {
    await approveReview(id);
    load(meta?.current_page || 1);
  };

  const handleReject = async (id: number) => {
    await rejectReview(id);
    load(meta?.current_page || 1);
  };

  const handleDelete = async (id: number) => {
    if (confirm('Delete this review?')) {
      await deleteReview(id);
      load(meta?.current_page || 1);
    }
  };

  const StatusBadge = ({ status }: { status: string }) => {
    const s = (status || 'pending').toLowerCase();
    const map: Record<string, string> = {
      approved: 'text-emerald-700 bg-emerald-50 border-emerald-200',
      pending: 'text-amber-700 bg-amber-50 border-amber-200',
      rejected: 'text-rose-700 bg-rose-50 border-rose-200',
    };
    return (
      <span className={`inline-block px-2.5 py-0.5 text-[10px] font-display uppercase tracking-wider font-bold rounded border ${map[s] || 'text-slate-600 bg-slate-100 border-slate-200'}`}>
        {status}
      </span>
    );
  };

  return (
    <div className="space-y-6 max-w-6xl">
      <div>
        <h1 className="font-display font-black uppercase text-2xl tracking-tight text-slate-900">Reviews & Ratings</h1>
        <p className="text-xs text-slate-500 mt-1">{meta?.total ?? '–'} customer reviews submitted</p>
      </div>
      
      <div className="flex flex-wrap gap-4 items-center justify-between">
        <div className="flex gap-2 overflow-x-auto">
          {['All', 'Pending', 'Approved', 'Rejected'].map(t => (
            <button key={t} onClick={() => setFilter(t)}
              className={`px-3.5 py-1.5 text-xs font-display font-semibold uppercase tracking-wider transition-colors rounded border ${
                filter === t ? 'border-slate-900 bg-slate-900 text-white shadow-xs' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50'
              }`}>
              {t}
            </button>
          ))}
        </div>
        <select value={ratingFilter || ''} onChange={e => setRatingFilter(e.target.value ? Number(e.target.value) : null)}
          className="bg-white border border-slate-300 rounded px-3 py-1.5 text-xs text-slate-800 outline-none focus:border-slate-900 font-semibold">
          <option value="">All Star Ratings</option>
          {[5,4,3,2,1].map(r => <option key={r} value={r}>{r} Stars</option>)}
        </select>
      </div>

      <div className="border border-slate-200 bg-white rounded-lg shadow-xs overflow-hidden">
        {loading ? <div className="flex items-center justify-center h-40 text-xs text-slate-400 font-display uppercase tracking-widest animate-pulse font-semibold">Loading reviews...</div> : (
          <table className="w-full text-xs">
            <thead>
              <tr className="border-b border-slate-200 bg-slate-50 text-slate-600 font-semibold">
                <th className="text-left px-4 py-3 font-display uppercase tracking-wider">Product</th>
                <th className="text-left px-3 py-3 font-display uppercase tracking-wider">Customer</th>
                <th className="text-left px-3 py-3 font-display uppercase tracking-wider">Rating & Content</th>
                <th className="text-left px-3 py-3 font-display uppercase tracking-wider">Status</th>
                <th className="text-left px-3 py-3 font-display uppercase tracking-wider">Date</th>
                <th className="text-right px-4 py-3 font-display uppercase tracking-wider">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100">
              {reviews.map((r: any) => (
                <tr key={r.id} className="hover:bg-slate-50 transition-colors">
                  <td className="px-4 py-3">
                    <div className="text-slate-900 font-bold max-w-[150px] truncate" title={r.product_title || r.product?.title}>{r.product_title || r.product?.title || 'Product'}</div>
                  </td>
                  <td className="px-3 py-3">
                    <div className="text-slate-900 font-semibold">{r.reviewer_name || `${r.customer?.first_name || ''} ${r.customer?.last_name || ''}`}</div>
                    <div className="text-slate-500 font-mono text-[10px]">{r.reviewer_email || r.customer?.email}</div>
                  </td>
                  <td className="px-3 py-3 max-w-[320px]">
                    <Stars rating={r.rating} />
                    <div className="font-bold text-slate-900 mt-1 truncate">{r.title}</div>
                    <div className="text-slate-600 text-[11px] truncate leading-normal" title={r.body || r.content}>{r.body || r.content}</div>
                  </td>
                  <td className="px-3 py-3"><StatusBadge status={r.is_approved ? 'approved' : 'pending'} /></td>
                  <td className="px-3 py-3 text-slate-500 text-[11px] font-mono">{new Date(r.created_at).toLocaleDateString()}</td>
                  <td className="px-4 py-3 text-right">
                    <div className="flex justify-end items-center gap-2">
                      {!r.is_approved ? (
                        <button onClick={() => handleApprove(r.id)} className="px-2.5 py-1 text-[10px] font-display uppercase tracking-wider bg-emerald-50 text-emerald-700 border border-emerald-200 rounded font-bold hover:bg-emerald-100 transition-colors">Approve</button>
                      ) : (
                        <button onClick={() => handleReject(r.id)} className="px-2.5 py-1 text-[10px] font-display uppercase tracking-wider bg-rose-50 text-rose-700 border border-rose-200 rounded font-bold hover:bg-rose-100 transition-colors">Reject</button>
                      )}
                      <button onClick={() => handleDelete(r.id)} className="p-1 text-slate-400 hover:text-rose-600 transition-colors" title="Delete">
                        <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                      </button>
                    </div>
                  </td>
                </tr>
              ))}
              {!reviews.length && <tr><td colSpan={6} className="px-4 py-16 text-center text-slate-400 font-display uppercase tracking-widest text-xs font-semibold">No reviews found</td></tr>}
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
