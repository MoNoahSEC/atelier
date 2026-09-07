'use client';
import { useEffect, useState } from 'react';
import { getCollectionsAdmin, createCollectionAdmin, updateCollectionAdmin, deleteCollectionAdmin } from '@/lib/admin-api';

const EMPTY = { title: '', slug: '', description: '', image_url: '' };

export default function CollectionsPage() {
  const [collections, setCollections] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [editing, setEditing] = useState<any>(null);
  const [form, setForm] = useState({ ...EMPTY });
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState('');

  async function load() {
    setLoading(true);
    try {
      const r = await getCollectionsAdmin();
      setCollections(r || []);
    } catch (e: any) { setError(e.message); }
    finally { setLoading(false); }
  }

  useEffect(() => { load(); }, []);

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault(); setSaving(true); setError('');
    try {
      if (editing?.id) {
        await updateCollectionAdmin(editing.id, form);
      } else {
        await createCollectionAdmin(form);
      }
      setEditing(null); setForm({ ...EMPTY });
      load();
    } catch (e: any) { setError(e.message); }
    finally { setSaving(false); }
  }

  async function handleDelete(id: number) {
    if (!confirm('Delete collection?')) return;
    try { await deleteCollectionAdmin(id); load(); }
    catch (e: any) { alert(e.message); }
  }

  function startEdit(c: any) {
    setEditing(c);
    setForm({ title: c.title, slug: c.slug, description: c.description, image_url: c.image_url || '' });
  }

  return (
    <div className="max-w-6xl space-y-6">
      <div className="flex flex-wrap items-center justify-between gap-4">
        <div>
          <h1 className="font-display font-black uppercase text-2xl tracking-tight text-stone-900">Collections</h1>
          <p className="text-xs text-stone-500 mt-1">{collections.length} categories & collections active</p>
        </div>
        <button onClick={() => { setEditing({}); setForm({ ...EMPTY }); }} className="bg-[#c2410c] text-amber-50 font-display font-bold uppercase text-xs tracking-wider px-5 py-3 rounded-xl hover:bg-[#9a3412] shadow-xs active:scale-95 transition-all cursor-pointer">
          + New Collection
        </button>
      </div>

      {editing !== null && (
        <form onSubmit={handleSubmit} className="border border-stone-200 bg-white rounded-2xl p-6 shadow-xs space-y-4">
          <h2 className="font-display font-bold uppercase text-xs tracking-wider text-stone-800 mb-2">{editing.id ? 'Edit Collection' : 'New Collection'}</h2>
          {error && <div className="p-3 bg-rose-50 border border-rose-200 rounded-xl text-rose-800 text-xs font-bold">{error}</div>}
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label className="block text-[11px] font-bold uppercase tracking-wider text-stone-700 mb-1.5">Title *</label>
              <input value={form.title} onChange={e => setForm(f => ({ ...f, title: e.target.value }))} required className="w-full bg-[#faf8f5] border border-stone-200 rounded-xl px-4 py-2.5 text-sm text-stone-900 outline-none focus:border-[#c2410c] font-medium" />
            </div>
            <div>
              <label className="block text-[11px] font-bold uppercase tracking-wider text-stone-700 mb-1.5">Slug</label>
              <input value={form.slug} onChange={e => setForm(f => ({ ...f, slug: e.target.value }))} placeholder="auto-generated if blank" className="w-full bg-[#faf8f5] border border-stone-200 rounded-xl px-4 py-2.5 text-sm text-stone-900 outline-none focus:border-[#c2410c] font-medium" />
            </div>
            <div className="sm:col-span-2">
              <label className="block text-[11px] font-bold uppercase tracking-wider text-stone-700 mb-1.5">Description</label>
              <textarea value={form.description || ''} onChange={e => setForm(f => ({ ...f, description: e.target.value }))} rows={2} className="w-full bg-[#faf8f5] border border-stone-200 rounded-xl px-4 py-2.5 text-sm text-stone-900 outline-none focus:border-[#c2410c] resize-none font-medium" />
            </div>
            <div className="sm:col-span-2">
              <label className="block text-[11px] font-bold uppercase tracking-wider text-stone-700 mb-1.5">Image URL</label>
              <input value={form.image_url} onChange={e => setForm(f => ({ ...f, image_url: e.target.value }))} placeholder="https://..." className="w-full bg-[#faf8f5] border border-stone-200 rounded-xl px-4 py-2.5 text-sm text-stone-900 outline-none focus:border-[#c2410c] font-medium" />
            </div>
          </div>
          <div className="flex gap-3 pt-2">
            <button type="submit" disabled={saving} className="bg-[#c2410c] text-amber-50 px-6 py-2.5 rounded-xl text-xs font-bold uppercase tracking-wider hover:bg-[#9a3412] active:scale-95 transition-all cursor-pointer">{saving ? 'Saving...' : 'Save Collection'}</button>
            <button type="button" onClick={() => setEditing(null)} className="px-6 py-2.5 border border-stone-200 rounded-xl text-xs font-bold uppercase tracking-wider text-stone-600 hover:text-stone-900 hover:bg-stone-50 cursor-pointer">Cancel</button>
          </div>
        </form>
      )}

      <div className="border border-stone-200 bg-white rounded-xl shadow-xs overflow-hidden">
        {loading ? <div className="p-8 text-center text-stone-400 text-xs font-display uppercase tracking-widest font-semibold animate-pulse">Loading collections...</div> : (
          <div className="overflow-x-auto">
            <table className="w-full text-xs">
              <thead>
                <tr className="border-b border-stone-200 bg-stone-50 text-left text-stone-600 font-semibold uppercase tracking-wider">
                  <th className="px-5 py-3">Collection</th>
                  <th className="px-3 py-3">Slug</th>
                  <th className="px-5 py-3 text-right">Actions</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-stone-100">
                {collections.map(c => (
                  <tr key={c.id} className="hover:bg-stone-50 transition-colors">
                    <td className="px-5 py-3.5 text-stone-900 font-bold">{c.title}</td>
                    <td className="px-3 py-3.5 text-stone-500 font-mono text-[11px]">{c.slug}</td>
                    <td className="px-5 py-3.5 text-right">
                      <button onClick={() => startEdit(c)} className="text-[#c2410c] hover:underline uppercase tracking-wider font-bold mr-4 cursor-pointer">Edit</button>
                      <button onClick={() => handleDelete(c.id)} className="text-rose-600 hover:text-rose-800 uppercase tracking-wider font-bold cursor-pointer">Delete</button>
                    </td>
                  </tr>
                ))}
                {!collections.length && <tr><td colSpan={3} className="p-8 text-center text-stone-400 text-xs font-display uppercase tracking-widest font-semibold">No collections found</td></tr>}
              </tbody>
            </table>
          </div>
        )}
      </div>
    </div>
  );
}
