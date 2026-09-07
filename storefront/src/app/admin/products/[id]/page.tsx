'use client';
import { useEffect, useRef, useState } from 'react';
import { useRouter, useParams } from 'next/navigation';
import {
  createProduct, updateProduct, getProduct,
  uploadMedia, adjustInventory, getCollectionsAdmin,
  attachProductMedia, detachProductMedia,
} from '@/lib/admin-api';
import Link from 'next/link';
import { ImageEditorModal } from '@/components/ui/ImageEditorModal';
import { resolveMediaUrl } from '@/lib/media';

interface VariantForm {
  id?: number;
  title: string;
  sku: string;
  price_minor: string;
  inventory: string;
  image_url?: string;
  color_hex?: string;
}

const EMPTY = {
  sku: '', title: '', slug: '', description: '',
  image_url: '',
  cost_price_minor: '', retail_price_minor: '',
  currency: 'EGP', inventory: '0', status: 'active',
  supplier_id: '', collections: [] as number[],
  variants: [] as VariantForm[],
};

interface FieldProps {
  label: string;
  name: string;
  type?: string;
  form: typeof EMPTY;
  setForm: React.Dispatch<React.SetStateAction<typeof EMPTY>>;
  colSpan2?: boolean;
  [key: string]: any;
}

function Field({ label, name, type = 'text', form, setForm, colSpan2, ...rest }: FieldProps) {
  return (
    <div className={colSpan2 ? 'sm:col-span-2' : undefined}>
      <label className="block font-display text-[11px] uppercase tracking-wider text-stone-700 font-bold mb-1.5">{label}</label>
      <input
        type={type}
        value={(form as any)[name] ?? ''}
        onChange={e => setForm(f => ({ ...f, [name]: e.target.value }))}
        className="w-full bg-[#faf8f5] border border-stone-200 rounded-xl px-4 py-3 text-sm text-stone-900 outline-none focus:border-stone-900 transition-colors placeholder:text-stone-400 font-medium"
        {...rest}
      />
    </div>
  );
}

interface GalleryImage {
  id?: number;
  url: string;
  uploading?: boolean;
}

export default function ProductFormPage() {
  const router = useRouter();
  const params = useParams();
  const isNew = params?.id === 'new' || !params?.id;
  const id = isNew ? null : params?.id as string;

  const [form, setForm] = useState({ ...EMPTY });
  const [loading, setLoading] = useState(!isNew);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');
  const [invAdj, setInvAdj] = useState('');
  const [invReason, setInvReason] = useState('');
  const [dragging, setDragging] = useState(false);
  const dropRef = useRef<HTMLDivElement>(null);
  const fileRef = useRef<HTMLInputElement>(null);

  const [editorOpen, setEditorOpen] = useState(false);
  const [editorImage, setEditorImage] = useState('');
  const [editorFile, setEditorFile] = useState<File | null>(null);
  const [availableCollections, setAvailableCollections] = useState<any[]>([]);

  const [gallery, setGallery] = useState<GalleryImage[]>([]);
  const [pendingFiles, setPendingFiles] = useState<File[]>([]);

  useEffect(() => {
    getCollectionsAdmin().then(setAvailableCollections).catch(() => {});
    if (!isNew && id) {
      getProduct(id).then(p => {
        const colIds = (p.collections || []).map((c: any) => c.id);
        const variantsFormatted: VariantForm[] = (p.variants || []).map((v: any) => ({
          id: v.id,
          title: v.title || '',
          sku: v.sku || '',
          price_minor: String((v.retail_price_minor || v.price_minor || 0) / 100),
          inventory: String(v.inventory || 0),
          image_url: v.attributes_json?.image_url || v.image_url || '',
          color_hex: v.attributes_json?.color_hex || v.color_hex || '',
        }));

        setForm({
          ...EMPTY, ...p,
          currency: p.currency || 'EGP',
          cost_price_minor: String((p.cost_price_minor || 0) / 100),
          retail_price_minor: String((p.retail_price_minor || 0) / 100),
          inventory: String(p.inventory || 0),
          collections: colIds,
          variants: variantsFormatted,
        });

        const assets: GalleryImage[] = (p.mediaAssets || []).map((a: any) => ({ id: a.id, url: a.url }));
        if (p.image_url && !assets.find(a => a.url === p.image_url)) {
          assets.unshift({ url: p.image_url });
        }
        setGallery(assets);
        setLoading(false);
      }).catch(() => setLoading(false));
    }
  }, [id, isNew]);

  function openEditor(file: File) {
    if (!file.type.startsWith('image/')) { setError('Only images allowed.'); return; }
    const url = URL.createObjectURL(file);
    setEditorImage(url);
    setEditorFile(file);
    setEditorOpen(true);
  }

  async function handleEditorConfirm(croppedFile: File) {
    setEditorOpen(false);
    URL.revokeObjectURL(editorImage);

    if (isNew) {
      const previewUrl = URL.createObjectURL(croppedFile);
      setPendingFiles(prev => [...prev, croppedFile]);
      setGallery(prev => [...prev, { url: previewUrl }]);
      if (gallery.length === 0) {
        setForm(f => ({ ...f, image_url: previewUrl }));
      }
    } else {
      const tempId = Date.now();
      setGallery(prev => [...prev, { url: URL.createObjectURL(croppedFile), uploading: true }]);
      try {
        const mediaResult = await uploadMedia(croppedFile);
        await attachProductMedia(id!, mediaResult.id ?? tempId);
        setGallery(prev => {
          const updated = [...prev];
          const idx = updated.findIndex(g => g.uploading);
          if (idx !== -1) updated[idx] = { id: mediaResult.id, url: mediaResult.url };
          return updated;
        });
        if (gallery.length === 0) {
          setForm(f => ({ ...f, image_url: mediaResult.url }));
          await updateProduct(id!, { image_url: mediaResult.url });
        }
        setSuccess('Image uploaded!');
      } catch (e: any) {
        setError(e.message);
        setGallery(prev => prev.filter(g => !g.uploading));
      }
    }
  }

  async function removeGalleryImage(idx: number) {
    const img = gallery[idx];
    if (!isNew && img.id) {
      try { await detachProductMedia(id!, img.id); } catch {}
    }
    const newGallery = gallery.filter((_, i) => i !== idx);
    setGallery(newGallery);
    setPendingFiles(prev => prev.filter((_, i) => {
      const nonIdIdx = gallery.slice(0, idx).filter(g => !g.id).length;
      return i !== nonIdIdx;
    }));

    // Always update the primary image_url when the gallery changes
    const newPrimary = newGallery[0]?.url || '';
    setForm(f => ({ ...f, image_url: newPrimary }));

    // Persist updated primary image to backend immediately in edit mode
    if (!isNew && id) {
      try {
        const saveUrl = newPrimary.startsWith('blob:') ? '' : newPrimary;
        await updateProduct(id, { image_url: saveUrl });
        setSuccess('Image removed & saved!');
      } catch {}
    }
  }

  function addVariant() {
    setForm(f => ({
      ...f,
      variants: [
        ...f.variants,
        {
          title: '',
          sku: `${f.sku || 'SKU'}-V${f.variants.length + 1}`,
          price_minor: f.retail_price_minor || '0',
          inventory: '10',
          image_url: '',        // Admin must choose image manually
          color_hex: '#1c1917',
        }
      ]
    }));
  }

  function updateVariantField(index: number, field: keyof VariantForm, value: string) {
    setForm(f => {
      const updated = [...f.variants];
      updated[index] = { ...updated[index], [field]: value };
      return { ...f, variants: updated };
    });
  }

  function removeVariant(index: number) {
    setForm(f => ({
      ...f,
      variants: f.variants.filter((_, i) => i !== index)
    }));
  }

  const [imageUrlInput, setImageUrlInput] = useState('');

  async function handleAddImageUrl() {
    if (!imageUrlInput.trim()) return;
    const url = imageUrlInput.trim();
    const isFirstImage = gallery.length === 0 || !form.image_url;
    setGallery(prev => [...prev, { url }]);
    if (isFirstImage) {
      setForm(f => ({ ...f, image_url: url }));
    }
    setImageUrlInput('');

    // Persist to backend immediately in edit mode
    if (!isNew && id) {
      try {
        if (isFirstImage) {
          await updateProduct(id, { image_url: url });
        }
        setSuccess('Image URL added!');
      } catch {}
    }
  }

  async function makePrimaryImage(idx: number) {
    const selected = gallery[idx];
    const rest = gallery.filter((_, i) => i !== idx);
    const updated = [selected, ...rest];
    setGallery(updated);
    setForm(f => ({ ...f, image_url: selected.url }));

    // Persist to backend immediately in edit mode
    if (!isNew && id && !selected.url.startsWith('blob:')) {
      try {
        await updateProduct(id, { image_url: selected.url });
        setSuccess('Primary image updated!');
      } catch {}
    }
  }

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault(); setSaving(true); setError(''); setSuccess('');
    try {
      const primaryImage = gallery[0]?.url || form.image_url || 'https://images.unsplash.com/photo-1627123424574-724758594e93?w=1200&q=90';
      
      const variantsPayload = form.variants.map(v => ({
        id: v.id,
        title: v.title || form.title || 'Standard',
        sku: v.sku || `${form.sku || 'SKU'}-V1`,
        cost_price_minor: Math.round(parseFloat(form.cost_price_minor || '0') * 100),
        retail_price_minor: Math.round(parseFloat(v.price_minor || form.retail_price_minor || '0') * 100),
        inventory: parseInt(v.inventory || '0', 10),
        attributes_json: {
          ...(v.image_url ? { image_url: v.image_url } : {}),
          ...(v.color_hex ? { color_hex: v.color_hex } : {}),
        },
      }));

      const autoSku = form.sku ? form.sku.trim() : `ATL-WLT-${Date.now().toString().slice(-6)}`;
      const autoSlug = form.slug ? form.slug.trim() : form.title.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '');

      const payload = {
        ...form,
        sku: autoSku,
        slug: autoSlug || `product-${Date.now()}`,
        image_url: primaryImage.startsWith('blob:') ? '' : primaryImage,
        cost_price_minor: Math.round(parseFloat(form.cost_price_minor || '0') * 100),
        retail_price_minor: Math.round(parseFloat(form.retail_price_minor || '0') * 100),
        inventory: parseInt(form.inventory || '0', 10),
        currency: form.currency || 'EGP',
        status: form.status || 'active',
        variants: variantsPayload,
      };

      if (isNew) {
        const created = await createProduct(payload);
        const productId = created?.id || created?.data?.id;

        if (productId) {
          for (const file of pendingFiles) {
            try {
              const mediaResult = await uploadMedia(file);
              await attachProductMedia(productId, mediaResult.id);
              if (pendingFiles.indexOf(file) === 0) {
                await updateProduct(productId, { image_url: mediaResult.url });
              }
            } catch {}
          }
        }

        setSuccess('Product created successfully!');
        setTimeout(() => router.push('/admin/products'), 600);
      } else {
        await updateProduct(id!, payload);
        setSuccess('Product updated successfully!');
      }
    } catch (e: any) {
      setError(e.message || 'Failed to save product');
    } finally {
      setSaving(false);
    }
  }

  async function handleInventoryAdj() {
    const qty = parseInt(invAdj, 10);
    if (isNaN(qty) || qty === 0) { setError('Enter a non-zero quantity.'); return; }
    try {
      const r = await adjustInventory(id!, qty, invReason);
      setForm(f => ({ ...f, inventory: String(r.inventory) }));
      setInvAdj(''); setInvReason(''); setSuccess('Inventory updated!');
    } catch (e: any) { setError(e.message); }
  }

  if (loading) return <div className="flex items-center justify-center h-64 text-xs text-stone-400 font-display uppercase tracking-widest animate-pulse font-semibold">Loading product data...</div>;

  return (
    <div className="max-w-4xl space-y-6">
      <div className="flex items-center justify-between">
        <div className="flex items-center gap-4">
          <Link href="/admin/products" className="text-stone-500 hover:text-stone-900 transition-colors text-xs font-display uppercase tracking-wider font-bold">← Back to Catalog</Link>
          <h1 className="font-display font-black uppercase text-2xl tracking-tight text-stone-900">{isNew ? 'New Product' : 'Edit Product'}</h1>
        </div>
        <button
          type="button"
          onClick={handleSubmit}
          disabled={saving}
          className="bg-stone-900 text-white font-display font-bold uppercase text-xs tracking-wider px-6 py-3 rounded-xl hover:bg-black shadow-xs active:scale-95 transition-all disabled:opacity-50 cursor-pointer"
        >
          {saving ? 'Saving…' : 'Save Product'}
        </button>
      </div>

      {error && <div className="px-4 py-3 bg-rose-50 border border-rose-200 rounded-xl text-rose-800 text-xs font-display font-bold uppercase tracking-wider">{error}</div>}
      {success && <div className="px-4 py-3 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-800 text-xs font-display font-bold uppercase tracking-wider">{success}</div>}

      <form onSubmit={handleSubmit} className="space-y-6">

        {/* ── Multi-Image Gallery ── */}
        <div className="border border-stone-200 bg-white rounded-2xl p-6 shadow-xs">
          <p className="font-display text-xs uppercase tracking-wider text-stone-700 font-bold mb-4">
            Product Images <span className="text-stone-400 font-normal normal-case">({gallery.length} uploaded)</span>
          </p>

          {gallery.length > 0 && (
            <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3.5 mb-4">
              {gallery.map((img, idx) => {
                const resolved = resolveMediaUrl(img.url);
                return (
                  <div key={idx} className={`relative group aspect-square border-2 rounded-xl overflow-hidden bg-stone-100 ${idx === 0 ? 'border-[#c2410c] ring-2 ring-[#c2410c]/20' : 'border-stone-200'}`}>
                    <img
                      src={resolved || img.url}
                      alt=""
                      className="w-full h-full object-cover"
                      onError={(e) => {
                        (e.target as HTMLElement).style.opacity = '0.3';
                      }}
                    />

                    {/* Top left indicator badge */}
                    {idx === 0 && (
                      <div className="absolute top-2 left-2 z-10 pointer-events-none">
                        <span className="bg-[#c2410c] text-white px-2 py-0.5 rounded-md text-[10px] font-display uppercase tracking-wider font-bold shadow-xs">
                          ★ Cover
                        </span>
                      </div>
                    )}

                    {img.uploading && (
                      <div className="absolute inset-0 bg-stone-900/60 flex items-center justify-center z-20">
                        <span className="text-white text-[10px] font-display uppercase tracking-wider animate-pulse font-bold">Uploading…</span>
                      </div>
                    )}

                    {!img.uploading && (
                      <div className="absolute inset-0 bg-stone-950/80 flex flex-col items-center justify-center gap-2 opacity-0 group-hover:opacity-100 transition-opacity p-2 z-20">
                        {idx === 0 ? (
                          <span className="bg-[#c2410c] text-white px-3 py-1.5 rounded-lg text-[10px] font-display uppercase tracking-wider font-bold shadow-md">
                            ★ Active Cover
                          </span>
                        ) : (
                          <button
                            type="button"
                            onClick={() => makePrimaryImage(idx)}
                            className="bg-emerald-600 hover:bg-emerald-700 text-white text-[10px] font-display uppercase tracking-wider px-3 py-1.5 rounded-lg font-bold shadow-md cursor-pointer transition-all active:scale-95"
                          >
                            ★ Set as Cover
                          </button>
                        )}
                        <button
                          type="button"
                          onClick={() => removeGalleryImage(idx)}
                          className="bg-rose-600 text-white text-[10px] font-display uppercase tracking-wider px-3 py-1.5 rounded-lg hover:bg-rose-700 font-bold shadow-md cursor-pointer transition-all active:scale-95"
                        >
                          Remove Photo
                        </button>
                      </div>
                    )}
                  </div>
                );
              })}
            </div>
          )}

          {/* Direct URL Image Input */}
          <div className="flex gap-2 mb-4">
            <input
              type="url"
              placeholder="Or paste direct image URL (https://...)..."
              value={imageUrlInput}
              onChange={e => setImageUrlInput(e.target.value)}
              onKeyDown={e => e.key === 'Enter' && (e.preventDefault(), handleAddImageUrl())}
              className="flex-1 bg-[#faf8f5] border border-stone-300 rounded-xl px-4 py-2.5 text-xs text-stone-900 outline-none focus:border-stone-900 transition-colors placeholder:text-stone-400 font-medium"
            />
            <button
              type="button"
              onClick={handleAddImageUrl}
              className="bg-stone-900 hover:bg-black text-white px-4 py-2.5 rounded-xl text-xs font-display font-bold uppercase tracking-wider transition-colors cursor-pointer"
            >
              + Add URL
            </button>
          </div>

          <div
            ref={dropRef}
            onDragOver={e => { e.preventDefault(); setDragging(true); }}
            onDragLeave={() => setDragging(false)}
            onDrop={e => {
              e.preventDefault(); setDragging(false);
              Array.from(e.dataTransfer.files).forEach(f => openEditor(f));
            }}
            onClick={() => fileRef.current?.click()}
            className={`relative border-2 border-dashed rounded-2xl cursor-pointer transition-all duration-200 flex flex-col items-center justify-center py-7 ${dragging ? 'border-stone-900 bg-stone-100' : 'border-stone-300 hover:border-stone-400 bg-stone-50/50'}`}
          >
            <div className="text-2xl mb-1 text-stone-400">↑</div>
            <p className="text-xs text-stone-700 font-display font-bold uppercase tracking-wider">
              {gallery.length === 0 ? 'Drag & drop or click to upload photos' : 'Add more photos from device'}
            </p>
            <p className="text-[11px] text-stone-500 mt-1">JPEG, PNG, WEBP, AVIF — all formats supported</p>
            <input
              ref={fileRef}
              type="file"
              accept="image/*"
              multiple
              className="hidden"
              onChange={e => {
                Array.from(e.target.files || []).forEach(f => openEditor(f));
                e.target.value = '';
              }}
            />
          </div>
        </div>

        {/* Core Fields */}
        <div className="border border-stone-200 bg-white rounded-2xl p-6 shadow-xs grid grid-cols-1 sm:grid-cols-2 gap-4">
          <Field label="Title *" name="title" form={form} setForm={setForm} colSpan2 required />
          <Field label="SKU *" name="sku" form={form} setForm={setForm} required />
          <Field label="Slug" name="slug" form={form} setForm={setForm} placeholder="auto-generated if empty" />
          <div className="sm:col-span-2">
            <label className="block font-display text-[11px] uppercase tracking-wider text-stone-700 font-bold mb-1.5">Description</label>
            <textarea
              value={form.description || ''}
              onChange={e => setForm(f => ({ ...f, description: e.target.value }))}
              rows={3}
              className="w-full bg-[#faf8f5] border border-stone-200 rounded-xl px-4 py-3 text-sm text-stone-900 outline-none focus:border-stone-900 transition-colors placeholder:text-stone-400 resize-none font-medium"
            />
          </div>
        </div>

        {/* Pricing & Currency */}
        <div className="border border-stone-200 bg-white rounded-2xl p-6 shadow-xs grid grid-cols-1 sm:grid-cols-3 gap-4">
          <Field label="Retail Price (EGP) *" name="retail_price_minor" type="number" step="0.01" min="0" form={form} setForm={setForm} required />
          <Field label="Cost Price (EGP)" name="cost_price_minor" type="number" step="0.01" min="0" form={form} setForm={setForm} />
          <div>
            <label className="block font-display text-[11px] uppercase tracking-wider text-stone-700 font-bold mb-1.5">Currency</label>
            <select value={form.currency} onChange={e => setForm(f => ({ ...f, currency: e.target.value }))}
              className="w-full bg-[#faf8f5] border border-stone-200 rounded-xl px-4 py-3 text-sm text-stone-900 outline-none focus:border-stone-900 transition-colors cursor-pointer font-medium">
              <option value="EGP">EGP (Egyptian Pound)</option>
              <option value="USD">USD ($)</option>
              <option value="EUR">EUR (€)</option>
              <option value="SAR">SAR (Saudi Riyal)</option>
            </select>
          </div>
        </div>

        {/* Inventory & Status */}
        <div className="border border-stone-200 bg-white rounded-2xl p-6 shadow-xs grid grid-cols-1 sm:grid-cols-2 gap-4">
          <Field label="Total Inventory" name="inventory" type="number" min="0" form={form} setForm={setForm} />
          <div>
            <label className="block font-display text-[11px] uppercase tracking-wider text-stone-700 font-bold mb-1.5">Status</label>
            <select value={form.status} onChange={e => setForm(f => ({ ...f, status: e.target.value }))}
              className="w-full bg-[#faf8f5] border border-stone-200 rounded-xl px-4 py-3 text-sm text-stone-900 outline-none focus:border-stone-900 transition-colors cursor-pointer font-medium">
              <option value="active">Active (Visible)</option>
              <option value="inactive">Inactive</option>
              <option value="archived">Archived</option>
            </select>
          </div>
        </div>

        {/* Variants Matrix (Sizes, Colors, Options & Photo Linking) */}
        <div className="border border-stone-200 bg-white rounded-2xl p-6 shadow-xs space-y-4">
          <div className="flex items-center justify-between">
            <div>
              <p className="font-display text-xs uppercase tracking-wider text-stone-700 font-bold">Product Colors & Variants</p>
              <p className="text-xs text-stone-500 mt-0.5">Link each color/option directly to its photo so when selected on the storefront, its picture displays immediately.</p>
            </div>
            <button
              type="button"
              onClick={addVariant}
              className="border-2 border-stone-200 text-stone-800 hover:border-[#c2410c] hover:text-[#c2410c] font-display font-bold uppercase text-xs tracking-wider px-3.5 py-1.5 rounded-lg transition-colors cursor-pointer"
            >
              + Add Color / Variant
            </button>
          </div>

          {form.variants.length > 0 ? (
            <div className="space-y-3">
              {form.variants.map((v, idx) => {
                const resolvedVariantImg = resolveMediaUrl(v.image_url);
                return (
                  <div key={idx} className="p-4 bg-stone-50 border border-stone-200 rounded-xl space-y-3">
                    {/* Top row: Color Title, Color Picker, Linked Photo Selector */}
                    <div className="grid grid-cols-1 sm:grid-cols-12 gap-3 items-center">
                      {/* Variant Photo Preview */}
                      <div className="sm:col-span-1 flex items-center justify-center">
                        <div className="w-10 h-10 rounded-lg bg-stone-200 border border-stone-300 overflow-hidden flex items-center justify-center relative shadow-2xs">
                          {resolvedVariantImg ? (
                            <img src={resolvedVariantImg} alt="" className="w-full h-full object-cover" />
                          ) : (
                            <span className="text-[9px] text-stone-500 font-bold">NO IMG</span>
                          )}
                        </div>
                      </div>

                      {/* Color Title */}
                      <div className="sm:col-span-4">
                        <label className="block text-[10px] font-display font-bold uppercase tracking-wider text-stone-600 mb-1">Color / Variant Name *</label>
                        <input
                          type="text"
                          placeholder="e.g. Matte Obsidian, Vintage Brown"
                          value={v.title}
                          onChange={e => updateVariantField(idx, 'title', e.target.value)}
                          className="w-full bg-white border border-stone-200 rounded-lg px-3 py-2 text-xs text-stone-900 outline-none focus:border-[#c2410c] font-medium"
                          required
                        />
                      </div>

                      {/* Color Hex Swatch */}
                      <div className="sm:col-span-2">
                        <label className="block text-[10px] font-display font-bold uppercase tracking-wider text-stone-600 mb-1">Swatch Hex</label>
                        <div className="flex items-center gap-1.5">
                          <input
                            type="color"
                            value={v.color_hex || '#1c1917'}
                            onChange={e => updateVariantField(idx, 'color_hex', e.target.value)}
                            className="w-7 h-7 rounded border border-stone-300 p-0.5 cursor-pointer bg-white"
                          />
                          <input
                            type="text"
                            value={v.color_hex || '#1c1917'}
                            onChange={e => updateVariantField(idx, 'color_hex', e.target.value)}
                            className="w-full bg-white border border-stone-200 rounded-lg px-2 py-1.5 text-[11px] font-mono text-stone-800 outline-none focus:border-[#c2410c]"
                          />
                        </div>
                      </div>

                      {/* Linked Photo Selector */}
                      <div className="sm:col-span-4">
                        <label className="block text-[10px] font-display font-bold uppercase tracking-wider text-stone-600 mb-1">Linked Image from Gallery</label>
                        <select
                          value={v.image_url || ''}
                          onChange={e => updateVariantField(idx, 'image_url', e.target.value)}
                          className="w-full bg-white border border-stone-200 rounded-lg px-2.5 py-2 text-xs text-stone-800 outline-none focus:border-[#c2410c] font-medium cursor-pointer"
                        >
                          <option value="">(Default Product Cover)</option>
                          {gallery.map((g, gIdx) => (
                            <option key={gIdx} value={g.url}>
                              Photo #{gIdx + 1} {gIdx === 0 ? '★ (Cover)' : ''}
                            </option>
                          ))}
                        </select>
                      </div>

                      {/* Delete Variant */}
                      <div className="sm:col-span-1 text-right">
                        <button
                          type="button"
                          onClick={() => removeVariant(idx)}
                          className="text-rose-600 hover:text-rose-800 hover:bg-rose-50 p-1.5 rounded-lg text-xs cursor-pointer font-bold transition-colors"
                          title="Remove variant"
                        >
                          ✕
                        </button>
                      </div>
                    </div>

                    {/* Bottom row: SKU, Price, Inventory */}
                    <div className="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-2 border-t border-stone-200/60">
                      <div>
                        <label className="block text-[10px] font-display font-bold uppercase tracking-wider text-stone-500 mb-1">Variant SKU</label>
                        <input
                          type="text"
                          placeholder="SKU"
                          value={v.sku}
                          onChange={e => updateVariantField(idx, 'sku', e.target.value)}
                          className="w-full bg-white border border-stone-200 rounded-lg px-3 py-1.5 text-xs text-stone-900 outline-none focus:border-[#c2410c] font-mono"
                        />
                      </div>
                      <div>
                        <label className="block text-[10px] font-display font-bold uppercase tracking-wider text-stone-500 mb-1">Price (EGP)</label>
                        <input
                          type="number"
                          placeholder="Price"
                          step="0.01"
                          value={v.price_minor}
                          onChange={e => updateVariantField(idx, 'price_minor', e.target.value)}
                          className="w-full bg-white border border-stone-200 rounded-lg px-3 py-1.5 text-xs text-stone-900 outline-none focus:border-[#c2410c] font-medium"
                        />
                      </div>
                      <div>
                        <label className="block text-[10px] font-display font-bold uppercase tracking-wider text-stone-500 mb-1">Stock Quantity</label>
                        <input
                          type="number"
                          placeholder="Stock"
                          value={v.inventory}
                          onChange={e => updateVariantField(idx, 'inventory', e.target.value)}
                          className="w-full bg-white border border-stone-200 rounded-lg px-3 py-1.5 text-xs text-stone-900 outline-none focus:border-[#c2410c] font-medium"
                        />
                      </div>
                    </div>
                  </div>
                );
              })}
            </div>
          ) : (
            <div className="text-center py-6 border border-dashed border-stone-200 rounded-xl text-stone-400 text-xs font-display uppercase tracking-wider font-semibold">
              No color variants added. Product will use the primary cover image.
            </div>
          )}
        </div>

        {/* Collections */}
        <div className="border border-stone-200 bg-white rounded-2xl p-6 shadow-xs">
          <label className="block font-display text-xs uppercase tracking-wider text-stone-700 font-bold mb-3">Assign Collections</label>
          <div className="grid grid-cols-2 sm:grid-cols-3 gap-2.5">
            {availableCollections.map(col => {
              const isSelected = form.collections.includes(col.id);
              return (
                <label key={col.id} className={`flex items-center gap-2 border-2 rounded-xl px-3.5 py-2.5 cursor-pointer transition-all ${isSelected ? 'border-[#c2410c] bg-amber-50/50 text-[#c2410c] font-bold' : 'border-stone-200 text-stone-700 hover:border-stone-300'}`}>
                  <input type="checkbox" className="hidden" checked={isSelected} onChange={(e) => {
                    const checked = e.target.checked;
                    setForm(f => ({
                      ...f,
                      collections: checked ? [...f.collections, col.id] : f.collections.filter(cid => cid !== col.id)
                    }));
                  }} />
                  <span className="text-xs uppercase tracking-wider">{col.title}</span>
                </label>
              );
            })}
          </div>
        </div>

        {/* Save */}
        <div className="flex gap-3">
          <button type="submit" disabled={saving}
            className="bg-[#c2410c] text-amber-50 font-display font-bold uppercase text-xs tracking-wider px-8 py-4 rounded-xl hover:bg-[#9a3412] shadow-xs active:scale-95 transition-all disabled:opacity-50 cursor-pointer">
            {saving ? (isNew ? 'Creating...' : 'Saving...') : (isNew ? 'Create Product' : 'Save Changes')}
          </button>
          <Link href="/admin/products" className="border border-stone-200 px-8 py-4 rounded-xl font-display font-bold uppercase text-xs tracking-wider text-stone-600 hover:text-stone-900 hover:bg-stone-50 transition-colors">Cancel</Link>
        </div>
      </form>

      {/* Inventory Adjustment (edit mode only) */}
      {!isNew && (
        <div className="border border-stone-200 bg-white rounded-2xl p-6 shadow-xs mt-6">
          <p className="font-display text-xs uppercase tracking-wider text-stone-700 font-bold mb-1">Quick Stock Adjustment</p>
          <p className="text-xs text-stone-500 mb-4">Current stock: <span className="text-stone-900 font-bold">{form.inventory}</span> units. Enter a positive number to add stock, negative to remove.</p>
          <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <input type="number" value={invAdj} onChange={e => setInvAdj(e.target.value)} placeholder="+10 or -5"
              className="bg-[#faf8f5] border border-stone-200 rounded-xl px-4 py-3 text-sm text-stone-900 outline-none focus:border-[#c2410c] transition-colors placeholder:text-stone-400 font-medium" />
            <input type="text" value={invReason} onChange={e => setInvReason(e.target.value)} placeholder="Reason (optional)"
              className="bg-[#faf8f5] border border-stone-200 rounded-xl px-4 py-3 text-sm text-stone-900 outline-none focus:border-[#c2410c] transition-colors placeholder:text-stone-400 font-medium sm:col-span-1" />
            <button type="button" onClick={handleInventoryAdj}
              className="bg-stone-900 text-amber-50 font-display font-bold uppercase text-xs tracking-wider px-4 py-3 rounded-xl hover:bg-stone-800 transition-colors cursor-pointer">
              Apply Stock
            </button>
          </div>
        </div>
      )}

      <ImageEditorModal
        isOpen={editorOpen}
        onClose={() => { setEditorOpen(false); URL.revokeObjectURL(editorImage); }}
        imageSrc={editorImage}
        onConfirm={handleEditorConfirm}
      />
    </div>
  );
}



