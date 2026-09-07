'use client';
import { useEffect, useState } from 'react';
import { getPages, createPage, deletePage } from '@/lib/admin-api';
import Link from 'next/link';

export default function PagesPage() {
  const [pages, setPages] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [creating, setCreating] = useState(false);
  const [form, setForm] = useState({ title: '', slug: '' });

  async function load() {
    setLoading(true);
    try {
      const r = await getPages();
      setPages(r || []);
    } catch (e: any) { alert(e.message); }
    finally { setLoading(false); }
  }

  useEffect(() => { load(); }, []);

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    try {
      await createPage(form);
      setCreating(false); setForm({ title: '', slug: '' });
      load();
    } catch (err: any) { alert(err.message); }
  }

  async function handleDelete(id: number) {
    if (!confirm('Delete page?')) return;
    try { await deletePage(id); load(); }
    catch (e: any) { alert(e.message); }
  }

  return (
    <div className="max-w-6xl space-y-6">
      <div className="flex flex-wrap items-center justify-between gap-4">
        <div>
          <h1 className="font-display font-black uppercase text-2xl tracking-tight text-stone-900">CMS Pages</h1>
          <p className="text-xs text-stone-500 mt-1">{pages.length} custom landing and editorial pages</p>
        </div>
        <button onClick={() => setCreating(true)} className="bg-[#c2410c] text-amber-50 font-display font-bold uppercase text-xs tracking-wider px-5 py-3 rounded-xl hover:bg-[#9a3412] shadow-xs active:scale-95 transition-all cursor-pointer">
          + New Page
        </button>
      </div>

      {creating && (
        <form onSubmit={handleSubmit} className="border border-stone-200 bg-white rounded-2xl p-6 shadow-xs space-y-4">
          <h2 className="font-display font-bold uppercase text-xs tracking-wider text-stone-800 mb-2">New Editorial Page</h2>
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label className="block text-[11px] font-bold uppercase tracking-wider text-stone-700 mb-1.5">Title *</label>
              <input value={form.title} onChange={e => setForm(f => ({ ...f, title: e.target.value }))} required className="w-full bg-[#faf8f5] border border-stone-200 rounded-xl px-4 py-2.5 text-sm text-stone-900 outline-none focus:border-[#c2410c] font-medium" />
            </div>
            <div>
              <label className="block text-[11px] font-bold uppercase tracking-wider text-stone-700 mb-1.5">Slug</label>
              <input value={form.slug} onChange={e => setForm(f => ({ ...f, slug: e.target.value }))} placeholder="auto-generated if empty" className="w-full bg-[#faf8f5] border border-stone-200 rounded-xl px-4 py-2.5 text-sm text-stone-900 outline-none focus:border-[#c2410c] font-medium" />
            </div>
          </div>
          <div className="flex gap-3 pt-2">
            <button type="submit" className="bg-[#c2410c] text-amber-50 px-6 py-2.5 rounded-xl text-xs font-bold uppercase tracking-wider hover:bg-[#9a3412] active:scale-95 transition-all cursor-pointer">Create Page</button>
            <button type="button" onClick={() => setCreating(false)} className="px-6 py-2.5 border border-stone-200 rounded-xl text-xs font-bold uppercase tracking-wider text-stone-600 hover:text-stone-900 hover:bg-stone-50 cursor-pointer">Cancel</button>
          </div>
        </form>
      )}

      <div className="border border-stone-200 bg-white rounded-xl shadow-xs overflow-hidden">
        {loading ? <div className="p-8 text-center text-stone-400 text-xs font-display uppercase tracking-widest font-semibold animate-pulse">Loading CMS pages...</div> : (
          <div className="overflow-x-auto">
            <table className="w-full text-xs">
              <thead>
                <tr className="border-b border-stone-200 bg-stone-50 text-left text-stone-600 font-semibold uppercase tracking-wider">
                  <th className="px-5 py-3">Page Title</th>
                  <th className="px-3 py-3">Slug</th>
                  <th className="px-3 py-3">Status</th>
                  <th className="px-5 py-3 text-right">Actions</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-stone-100">
                {pages.map(p => (
                  <tr key={p.id} className="hover:bg-stone-50 transition-colors">
                    <td className="px-5 py-3.5 text-stone-900 font-bold">{p.title}</td>
                    <td className="px-3 py-3.5 text-stone-500 font-mono text-[11px]">{p.slug}</td>
                    <td className="px-3 py-3.5">
                      <span className={`px-2.5 py-0.5 text-[10px] uppercase tracking-wider font-bold rounded border ${p.is_published ? 'text-emerald-800 bg-emerald-50 border-emerald-200' : 'text-amber-800 bg-amber-50 border-amber-200'}`}>
                        {p.is_published ? 'Published' : 'Draft'}
                      </span>
                    </td>
                    <td className="px-5 py-3.5 text-right">
                      <Link href={`/admin/pages/${p.id}`} className="text-[#c2410c] hover:underline uppercase tracking-wider font-bold mr-4">Builder →</Link>
                      <button onClick={() => handleDelete(p.id)} className="text-rose-600 hover:text-rose-800 uppercase tracking-wider font-bold cursor-pointer">Delete</button>
                    </td>
                  </tr>
                ))}
                {!pages.length && <tr><td colSpan={4} className="p-8 text-center text-stone-400 text-xs font-display uppercase tracking-widest font-semibold">No pages found</td></tr>}
              </tbody>
            </table>
          </div>
        )}
      </div>
    </div>
  );
}
