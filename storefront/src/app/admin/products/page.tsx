'use client';

import { useEffect, useState } from 'react';
import Link from 'next/link';
import { getProducts, deleteProduct, updateProduct, adjustInventory } from '@/lib/admin-api';
import { resolveMediaUrl } from '@/lib/media';

export default function AdminProductsPage() {
  const [products, setProducts] = useState<any[]>([]);
  const [meta, setMeta] = useState<any>(null);
  const [search, setSearch] = useState('');
  const [status, setStatus] = useState('');
  const [loading, setLoading] = useState(true);
  const [deleting, setDeleting] = useState<number | null>(null);
  const [updatingStatus, setUpdatingStatus] = useState<number | null>(null);
  const [quickAdjusting, setQuickAdjusting] = useState<number | null>(null);
  const [toast, setToast] = useState<{ type: 'success' | 'error'; msg: string } | null>(null);

  function showToast(msg: string, type: 'success' | 'error' = 'success') {
    setToast({ msg, type });
    setTimeout(() => setToast(null), 3000);
  }

  async function load(page = 1) {
    setLoading(true);
    try {
      const params: Record<string, string> = { page: String(page), per_page: '30' };
      if (search) params.search = search;
      if (status) params.status = status;
      const r = await getProducts(params);
      setProducts(r.data || []);
      setMeta(r.meta);
    } catch (e: any) {
      showToast(e.message || 'Failed to load products', 'error');
    } finally {
      setLoading(false);
    }
  }

  useEffect(() => { load(); }, []);

  async function handleDelete(id: number, title: string) {
    if (!confirm(`Are you sure you want to delete "${title}"? This cannot be undone.`)) return;
    setDeleting(id);
    try {
      await deleteProduct(id);
      setProducts(p => p.filter(x => x.id !== id));
      showToast('Product deleted successfully');
    } catch (e: any) {
      showToast(e.message || 'Failed to delete product', 'error');
    } finally {
      setDeleting(null);
    }
  }

  async function handleToggleStatus(product: any) {
    const nextStatus = product.status === 'active' ? 'inactive' : 'active';
    setUpdatingStatus(product.id);
    try {
      await updateProduct(product.id, { status: nextStatus });
      setProducts(prev => prev.map(p => p.id === product.id ? { ...p, status: nextStatus } : p));
      showToast(`Product set to ${nextStatus.toUpperCase()}`);
    } catch (e: any) {
      showToast(e.message || 'Failed to update status', 'error');
    } finally {
      setUpdatingStatus(null);
    }
  }

  async function handleQuickStock(productId: number, delta: number) {
    setQuickAdjusting(productId);
    try {
      const res = await adjustInventory(productId, delta, `Quick table adjustment (${delta > 0 ? '+' : ''}${delta})`);
      const newInventory = res?.data?.inventory ?? res?.inventory;
      setProducts(prev => prev.map(p => p.id === productId ? { ...p, inventory: newInventory ?? (p.inventory + delta) } : p));
      showToast(`Stock updated: ${delta > 0 ? `+${delta}` : delta} units`);
    } catch (e: any) {
      showToast(e.message || 'Failed to adjust stock', 'error');
    } finally {
      setQuickAdjusting(null);
    }
  }

  const fmt = (v: number) => `${((v || 0) / 100).toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 0 })} EGP`;

  // Quick KPIs
  const totalProducts = meta?.total ?? products.length;
  const activeCount = products.filter(p => p.status === 'active').length;
  const lowStockCount = products.filter(p => p.inventory <= 5 && p.inventory > 0).length;
  const outOfStockCount = products.filter(p => p.inventory <= 0).length;
  const totalUnits = products.reduce((acc, p) => acc + (p.inventory || 0), 0);

  return (
    <div className="space-y-6 max-w-7xl mx-auto">
      {/* Toast Notification */}
      {toast && (
        <div className={`fixed top-4 right-4 z-50 px-4 py-3 rounded-xl shadow-lg border text-xs font-display font-bold uppercase tracking-wider animate-bounce ${
          toast.type === 'success' ? 'bg-emerald-900 text-emerald-100 border-emerald-700' : 'bg-rose-900 text-rose-100 border-rose-700'
        }`}>
          {toast.msg}
        </div>
      )}

      {/* Header */}
      <div className="flex flex-wrap items-center justify-between gap-4">
        <div>
          <h1 className="font-display font-black uppercase text-2xl sm:text-3xl tracking-tight text-stone-900">
            Catalog & Products
          </h1>
          <p className="text-xs text-stone-500 mt-1 font-medium">
            Manage product listings, inventory quantities, images, and live prices across all channels.
          </p>
        </div>
        <Link
          href="/admin/products/new"
          className="bg-[#c2410c] text-amber-50 font-display font-black uppercase text-xs tracking-wider px-5 py-3 rounded-xl hover:bg-[#9a3412] shadow-sm active:scale-95 transition-all flex items-center gap-2"
        >
          <span className="text-base font-bold">+</span>
          <span>Add New Product</span>
        </Link>
      </div>

      {/* Quick KPI Stats Row */}
      <div className="grid grid-cols-2 sm:grid-cols-5 gap-3">
        <div className="bg-white border border-stone-200 rounded-xl p-4 shadow-2xs">
          <span className="text-[10px] font-display font-bold uppercase tracking-wider text-stone-500 block mb-1">Total Products</span>
          <span className="text-xl font-display font-black text-stone-900">{totalProducts}</span>
        </div>
        <div className="bg-white border border-stone-200 rounded-xl p-4 shadow-2xs">
          <span className="text-[10px] font-display font-bold uppercase tracking-wider text-emerald-600 block mb-1">Active Catalog</span>
          <span className="text-xl font-display font-black text-emerald-700">{activeCount}</span>
        </div>
        <div className="bg-white border border-stone-200 rounded-xl p-4 shadow-2xs">
          <span className="text-[10px] font-display font-bold uppercase tracking-wider text-amber-600 block mb-1">Low Stock (≤5)</span>
          <span className="text-xl font-display font-black text-amber-700">{lowStockCount}</span>
        </div>
        <div className="bg-white border border-stone-200 rounded-xl p-4 shadow-2xs">
          <span className="text-[10px] font-display font-bold uppercase tracking-wider text-rose-600 block mb-1">Out of Stock</span>
          <span className="text-xl font-display font-black text-rose-700">{outOfStockCount}</span>
        </div>
        <div className="bg-white border border-stone-200 rounded-xl p-4 shadow-2xs col-span-2 sm:col-span-1">
          <span className="text-[10px] font-display font-bold uppercase tracking-wider text-stone-500 block mb-1">Total Stock Units</span>
          <span className="text-xl font-display font-black text-stone-900">{totalUnits.toLocaleString()}</span>
        </div>
      </div>

      {/* Search & Filter Bar */}
      <div className="flex flex-wrap gap-3 bg-white border border-stone-200 p-3 rounded-xl shadow-2xs">
        <div className="flex-1 min-w-[240px] relative">
          <input
            type="text"
            placeholder="Search by product title, SKU, or keyword..."
            value={search}
            onChange={e => setSearch(e.target.value)}
            onKeyDown={e => e.key === 'Enter' && load(1)}
            className="w-full bg-stone-50 border border-stone-300 rounded-lg pl-9 pr-4 py-2.5 text-xs text-stone-900 outline-none focus:border-stone-900 focus:bg-white transition-colors placeholder:text-stone-400 font-medium"
          />
          <svg className="w-4 h-4 text-stone-400 absolute left-3 top-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
        </div>

        <select
          value={status}
          onChange={e => { setStatus(e.target.value); setTimeout(() => load(1), 0); }}
          className="bg-stone-50 border border-stone-300 rounded-lg px-3 py-2 text-xs text-stone-800 outline-none focus:border-stone-900 font-semibold cursor-pointer"
        >
          <option value="">All Statuses</option>
          <option value="active">Active Only</option>
          <option value="inactive">Inactive Only</option>
          <option value="archived">Archived Only</option>
        </select>

        <button
          onClick={() => load(1)}
          className="bg-stone-900 text-white rounded-lg px-5 py-2 text-xs font-display font-bold uppercase tracking-wider hover:bg-black transition-colors cursor-pointer"
        >
          Filter
        </button>

        {(search || status) && (
          <button
            onClick={() => { setSearch(''); setStatus(''); setTimeout(() => load(1), 0); }}
            className="border border-stone-300 bg-stone-100 hover:bg-stone-200 text-stone-700 rounded-lg px-3 py-2 text-xs font-display font-bold uppercase tracking-wider transition-colors cursor-pointer"
          >
            Reset
          </button>
        )}
      </div>

      {/* Table Card */}
      <div className="border border-stone-200 bg-white rounded-xl shadow-2xs overflow-hidden">
        {loading ? (
          <div className="flex flex-col items-center justify-center h-56 gap-2">
            <div className="w-6 h-6 border-2 border-stone-900 border-t-transparent rounded-full animate-spin" />
            <div className="text-xs text-stone-400 font-display uppercase tracking-widest font-bold">Loading product catalog...</div>
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-xs text-left">
              <thead>
                <tr className="border-b border-stone-200 bg-stone-50 text-stone-600 font-display uppercase tracking-wider text-[11px]">
                  <th className="px-5 py-3.5">Product & Collection</th>
                  <th className="px-3 py-3.5">SKU / Identifier</th>
                  <th className="px-3 py-3.5 text-right">Retail Price</th>
                  <th className="px-3 py-3.5 text-right">Cost / Margin</th>
                  <th className="px-3 py-3.5 text-center">Stock Level</th>
                  <th className="px-3 py-3.5 text-center">Visibility</th>
                  <th className="px-5 py-3.5 text-right">Quick Actions</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-stone-100">
                {products.map((p: any) => {
                  const resolvedImg = resolveMediaUrl(p.image_url);
                  const costMinor = p.cost_price_minor || 0;
                  const retailMinor = p.retail_price_minor || 0;
                  const marginPct = retailMinor > 0 && costMinor > 0
                    ? Math.round(((retailMinor - costMinor) / retailMinor) * 100)
                    : null;

                  return (
                    <tr key={p.id} className="hover:bg-stone-50/80 transition-colors">
                      {/* Product Column */}
                      <td className="px-5 py-3">
                        <div className="flex items-center gap-3">
                          <div className="w-12 h-12 rounded-lg bg-stone-100 border border-stone-200 shrink-0 overflow-hidden relative flex items-center justify-center shadow-2xs">
                            {resolvedImg ? (
                              <img
                                src={resolvedImg}
                                alt={p.title}
                                className="w-full h-full object-cover"
                                onError={(e) => {
                                  // Fallback gracefully on broken images
                                  (e.target as HTMLElement).style.display = 'none';
                                }}
                              />
                            ) : (
                              <span className="font-display font-bold text-stone-400 text-xs">NO IMG</span>
                            )}
                          </div>
                          <div className="min-w-0 max-w-[220px]">
                            <span className="text-stone-900 font-bold block truncate leading-tight hover:text-[#c2410c]">
                              {p.title}
                            </span>
                            <div className="flex items-center gap-1.5 mt-0.5">
                              {p.collections && p.collections.length > 0 ? (
                                p.collections.slice(0, 2).map((c: any) => (
                                  <span key={c.id} className="text-[9px] bg-stone-100 text-stone-600 px-1.5 py-0.5 rounded font-display uppercase tracking-wider font-semibold">
                                    {c.title}
                                  </span>
                                ))
                              ) : (
                                <span className="text-[10px] text-stone-400 font-mono">General</span>
                              )}
                            </div>
                          </div>
                        </div>
                      </td>

                      {/* SKU */}
                      <td className="px-3 py-3 font-mono text-[11px] text-stone-600">
                        <span className="bg-stone-100 px-2 py-1 rounded text-stone-800 font-bold border border-stone-200">
                          {p.sku || '–'}
                        </span>
                      </td>

                      {/* Retail Price */}
                      <td className="px-3 py-3 text-right">
                        <span className="font-display font-black text-stone-900 text-xs sm:text-sm">
                          {fmt(p.retail_price_minor)}
                        </span>
                      </td>

                      {/* Cost / Margin */}
                      <td className="px-3 py-3 text-right text-stone-500">
                        {costMinor > 0 ? (
                          <div>
                            <span className="text-[11px] font-semibold block">{fmt(costMinor)}</span>
                            {marginPct !== null && (
                              <span className="text-[9px] text-emerald-700 font-bold bg-emerald-50 px-1.5 py-0.2 rounded inline-block">
                                +{marginPct}% margin
                              </span>
                            )}
                          </div>
                        ) : (
                          <span className="text-[10px] text-stone-400">—</span>
                        )}
                      </td>

                      {/* Stock Level & Quick Adjust */}
                      <td className="px-3 py-3 text-center">
                        <div className="inline-flex items-center gap-1.5">
                          <button
                            type="button"
                            onClick={() => handleQuickStock(p.id, -1)}
                            disabled={quickAdjusting === p.id || p.inventory <= 0}
                            className="w-5 h-5 rounded bg-stone-100 hover:bg-stone-200 text-stone-700 font-bold flex items-center justify-center text-xs transition-colors disabled:opacity-30 cursor-pointer"
                            title="Quick -1 stock"
                          >
                            –
                          </button>
                          
                          <span className={`px-2 py-0.5 rounded text-xs font-display font-black min-w-[36px] inline-block ${
                            p.inventory <= 0
                              ? 'bg-rose-100 text-rose-800 border border-rose-200'
                              : p.inventory <= 5
                              ? 'bg-amber-100 text-amber-900 border border-amber-300'
                              : 'bg-emerald-50 text-emerald-800 border border-emerald-200'
                          }`}>
                            {p.inventory}
                          </span>

                          <button
                            type="button"
                            onClick={() => handleQuickStock(p.id, 1)}
                            disabled={quickAdjusting === p.id}
                            className="w-5 h-5 rounded bg-stone-100 hover:bg-stone-200 text-stone-700 font-bold flex items-center justify-center text-xs transition-colors disabled:opacity-30 cursor-pointer"
                            title="Quick +1 stock"
                          >
                            +
                          </button>
                        </div>
                        {p.inventory <= 0 && (
                          <span className="block text-[9px] text-rose-600 font-bold mt-0.5">SOLD OUT</span>
                        )}
                      </td>

                      {/* Status Toggle Badge */}
                      <td className="px-3 py-3 text-center">
                        <button
                          type="button"
                          onClick={() => handleToggleStatus(p)}
                          disabled={updatingStatus === p.id}
                          className={`px-2.5 py-1 text-[10px] font-display uppercase tracking-wider font-bold rounded-full border transition-all cursor-pointer ${
                            p.status === 'active'
                              ? 'bg-emerald-50 text-emerald-800 border-emerald-200 hover:bg-rose-50 hover:text-rose-800 hover:border-rose-200'
                              : p.status === 'inactive'
                              ? 'bg-amber-50 text-amber-800 border-amber-200 hover:bg-emerald-50 hover:text-emerald-800 hover:border-emerald-200'
                              : 'bg-stone-100 text-stone-600 border-stone-200'
                          }`}
                          title="Click to toggle status"
                        >
                          {updatingStatus === p.id ? '...' : p.status}
                        </button>
                      </td>

                      {/* Actions */}
                      <td className="px-5 py-3 text-right">
                        <div className="flex items-center justify-end gap-2">
                          <Link
                            href={`/products/${p.slug}`}
                            target="_blank"
                            className="text-stone-500 hover:text-stone-900 p-1.5 rounded-lg hover:bg-stone-100 transition-colors"
                            title="View live on storefront"
                          >
                            <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                            </svg>
                          </Link>

                          <Link
                            href={`/admin/products/${p.id}`}
                            className="bg-stone-900 text-white hover:bg-black font-display font-bold text-[11px] uppercase tracking-wider px-3 py-1.5 rounded-lg transition-all"
                          >
                            Edit
                          </Link>

                          <button
                            onClick={() => handleDelete(p.id, p.title)}
                            disabled={deleting === p.id}
                            className="text-rose-600 hover:text-rose-800 hover:bg-rose-50 p-1.5 rounded-lg transition-colors disabled:opacity-30 cursor-pointer"
                            title="Delete product"
                          >
                            {deleting === p.id ? '...' : (
                              <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                              </svg>
                            )}
                          </button>
                        </div>
                      </td>
                    </tr>
                  );
                })}

                {!products.length && (
                  <tr>
                    <td colSpan={7} className="px-5 py-20 text-center text-stone-400 font-display uppercase tracking-widest text-xs font-semibold">
                      No products matching your search criteria.
                    </td>
                  </tr>
                )}
              </tbody>
            </table>
          </div>
        )}

        {/* Pagination */}
        {meta && meta.last_page > 1 && (
          <div className="flex items-center justify-between px-5 py-3.5 border-t border-stone-200 bg-stone-50">
            <p className="text-xs text-stone-500 font-medium">
              Showing page <span className="font-bold text-stone-900">{meta.current_page}</span> of <span className="font-bold text-stone-900">{meta.last_page}</span> ({meta.total} products)
            </p>
            <div className="flex gap-2">
              <button
                onClick={() => load(meta.current_page - 1)}
                disabled={meta.current_page <= 1}
                className="px-3 py-1.5 border border-stone-200 bg-white rounded-lg text-xs text-stone-700 hover:bg-stone-100 font-semibold cursor-pointer disabled:opacity-40"
              >
                ← Prev
              </button>
              <button
                onClick={() => load(meta.current_page + 1)}
                disabled={meta.current_page >= meta.last_page}
                className="px-3 py-1.5 border border-stone-200 bg-white rounded-lg text-xs text-stone-700 hover:bg-stone-100 font-semibold cursor-pointer disabled:opacity-40"
              >
                Next →
              </button>
            </div>
          </div>
        )}
      </div>
    </div>
  );
}

