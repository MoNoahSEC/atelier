'use client';
import { useEffect, useState } from 'react';
import { useParams, useRouter } from 'next/navigation';
import { getPage, updatePage, updatePageSection, createPageSection, deletePageSection } from '@/lib/admin-api';
import Link from 'next/link';

export default function PageBuilderPage() {
  const params = useParams();
  const id = params?.id as string;
  const router = useRouter();

  const [page, setPage] = useState<any>(null);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState('');

  async function load() {
    setLoading(true);
    try {
      const r = await getPage(id);
      setPage(r);
    } catch (e: any) { setError(e.message); }
    finally { setLoading(false); }
  }

  useEffect(() => { if (id) load(); }, [id]);

  async function handleUpdatePage(is_published: boolean) {
    setSaving(true); setError('');
    try {
      await updatePage(id, { title: page.title, slug: page.slug, is_published });
      await load();
    } catch (e: any) { setError(e.message); }
    finally { setSaving(false); }
  }

  async function addSection() {
    try {
      await createPageSection(id, {
        type: 'hero',
        settings: { title: 'New Section' },
        sort_order: (page.sections?.length || 0) + 1,
        is_active: true
      });
      load();
    } catch (e: any) { setError(e.message); }
  }

  async function removeSection(sectionId: number) {
    if (!confirm('Remove section?')) return;
    try {
      await deletePageSection(id, sectionId);
      load();
    } catch (e: any) { setError(e.message); }
  }

  if (loading) return <div className="p-8 text-stone-400 uppercase tracking-widest text-xs font-semibold animate-pulse">Loading page builder...</div>;
  if (!page) return <div className="p-8 text-rose-600 font-bold">Page not found</div>;

  return (
    <div className="max-w-5xl space-y-6">
      <div className="flex flex-wrap items-center justify-between gap-4">
        <div className="flex items-center gap-4">
          <Link href="/admin/pages" className="text-stone-500 hover:text-stone-900 transition-colors text-xs font-display uppercase tracking-wider font-bold">← Back to CMS</Link>
          <h1 className="font-display font-black uppercase text-2xl tracking-tight text-stone-900">Page Builder</h1>
        </div>
        <div className="flex items-center gap-3">
          <button disabled={saving} onClick={() => handleUpdatePage(false)} className="border border-stone-200 bg-white px-5 py-2.5 rounded-xl text-xs font-bold uppercase tracking-wider text-stone-700 hover:bg-stone-50 cursor-pointer">
            Save Draft
          </button>
          <button disabled={saving} onClick={() => handleUpdatePage(true)} className="bg-[#c2410c] text-amber-50 px-6 py-2.5 rounded-xl font-display font-bold uppercase text-xs tracking-wider hover:bg-[#9a3412] shadow-xs active:scale-95 transition-all cursor-pointer">
            {page.is_published ? 'Update Published' : 'Publish Page'}
          </button>
        </div>
      </div>

      {error && <div className="p-3 bg-rose-50 border border-rose-200 rounded-xl text-rose-800 text-xs font-bold">{error}</div>}

      <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
        {/* Main Content Area: Sections */}
        <div className="md:col-span-2 space-y-4">
          <div className="flex items-center justify-between">
            <h2 className="font-display font-bold uppercase tracking-wider text-stone-800 text-xs">Sections ({page.sections?.length || 0})</h2>
            <button onClick={addSection} className="text-[#c2410c] hover:underline text-xs font-bold uppercase tracking-wider cursor-pointer">+ Add Section</button>
          </div>

          {(page.sections || []).map((s: any, idx: number) => (
            <div key={s.id} className="border border-stone-200 bg-white rounded-2xl p-5 shadow-xs flex gap-4">
              <div className="text-stone-400 font-mono text-xl pt-1 select-none">≡</div>
              <div className="flex-1 space-y-2">
                <div className="flex items-center justify-between">
                  <span className="font-bold uppercase tracking-wider text-sm text-stone-900">{s.type} Section</span>
                  <button onClick={() => removeSection(s.id)} className="text-rose-600 hover:text-rose-800 text-xs font-bold uppercase cursor-pointer">Remove</button>
                </div>
                <div className="text-xs text-stone-500 space-y-1">
                  <p>Section ID: <span className="font-mono font-bold text-stone-700">#{s.id}</span> | Status: <span className={`font-bold ${s.is_active ? 'text-emerald-700' : 'text-stone-400'}`}>{s.is_active ? 'Active' : 'Hidden'}</span></p>
                  <p className="text-[11px] text-stone-400">Settings: {JSON.stringify(s.settings || {})}</p>
                </div>
              </div>
            </div>
          ))}

          {!(page.sections?.length > 0) && (
            <div className="border-2 border-dashed border-stone-200 rounded-2xl p-12 text-center bg-stone-50/50">
              <p className="text-stone-500 text-xs uppercase tracking-wider font-semibold mb-3">No sections added to this page yet</p>
              <button onClick={addSection} className="border-2 border-stone-300 bg-white text-stone-800 px-5 py-2 rounded-xl text-xs uppercase tracking-wider font-bold hover:border-[#c2410c] hover:text-[#c2410c] cursor-pointer transition-all">Add First Section</button>
            </div>
          )}
        </div>

        {/* Sidebar: Page Settings */}
        <div className="border border-stone-200 bg-white rounded-2xl p-6 h-fit space-y-4 shadow-xs">
          <h2 className="font-display font-bold uppercase tracking-wider text-stone-800 text-xs mb-2">Page Metadata</h2>
          
          <div>
            <label className="block text-[11px] font-bold uppercase tracking-wider text-stone-700 mb-1.5">Page Title</label>
            <input value={page.title} onChange={e => setPage((p: any) => ({ ...p, title: e.target.value }))} className="w-full bg-[#faf8f5] border border-stone-200 rounded-xl px-3.5 py-2 text-sm text-stone-900 outline-none focus:border-[#c2410c] font-medium" />
          </div>
          
          <div>
            <label className="block text-[11px] font-bold uppercase tracking-wider text-stone-700 mb-1.5">Slug URL</label>
            <input value={page.slug} onChange={e => setPage((p: any) => ({ ...p, slug: e.target.value }))} className="w-full bg-[#faf8f5] border border-stone-200 rounded-xl px-3.5 py-2 text-sm text-stone-900 outline-none focus:border-[#c2410c] font-medium" />
          </div>

          <div className="pt-4 border-t border-stone-100 space-y-1">
            <p className="text-xs text-stone-600 font-medium">Status: {page.is_published ? <span className="text-emerald-800 bg-emerald-50 px-2 py-0.5 rounded font-bold">Published</span> : <span className="text-amber-800 bg-amber-50 px-2 py-0.5 rounded font-bold">Draft</span>}</p>
            <p className="text-[11px] text-stone-400">Last updated: {new Date(page.updated_at).toLocaleString()}</p>
          </div>
        </div>
      </div>
    </div>
  );
}



