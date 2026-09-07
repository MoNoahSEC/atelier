'use client';

import { useEffect, useState } from 'react';
import { useSearchParams } from 'next/navigation';
import Link from 'next/link';
import { ProductCard } from '@/components/ui/ProductCard';
import { SortDropdown } from '@/components/ui/SortDropdown';
import { fetchCatalog } from '@/lib/api';

const SORT_OPTIONS = [
  { label: 'Newest', value: 'newest' },
  { label: 'Price: Low–High', value: 'price_asc' },
  { label: 'Price: High–Low', value: 'price_desc' },
  { label: 'Title: A–Z', value: 'title_asc' },
];

const PRICE_RANGES = [
  { label: 'All Prices', min: null, max: null },
  { label: 'Under 1,000 EGP', min: 0, max: 100000 },
  { label: '1,000 – 2,500 EGP', min: 100000, max: 250000 },
  { label: '2,500 – 5,000 EGP', min: 250000, max: 500000 },
  { label: 'Over 5,000 EGP', min: 500000, max: null },
];

export default function CollectionClient({
  slug,
  storeName,
  filterCategories,
  initialProducts
}: {
  slug: string;
  storeName: string;
  filterCategories: any[];
  initialProducts: any[];
}) {
  const sp = useSearchParams();
  const [products, setProducts] = useState(initialProducts);
  const [loading, setLoading] = useState(false);

  const activeSort = sp.get('sort') || 'newest';
  const activeMinPrice = sp.get('min_price') ? Number(sp.get('min_price')) : null;
  const activeMaxPrice = sp.get('max_price') ? Number(sp.get('max_price')) : null;

  useEffect(() => {
    // If no search params are present, we can just use the initial products rendered at build time
    if (!sp.get('sort') && !sp.get('min_price') && !sp.get('max_price')) {
      setProducts(initialProducts);
      return;
    }

    const query = new URLSearchParams();
    if (sp.get('sort')) query.set('sort', sp.get('sort')!);
    if (sp.get('min_price')) query.set('min_price', sp.get('min_price')!);
    if (sp.get('max_price')) query.set('max_price', sp.get('max_price')!);
    query.set('collection', slug);

    let isMounted = true;
    setLoading(true);

    fetchCatalog(query.toString())
      .then(res => {
        if (isMounted) {
          setProducts(res?.data || []);
          setLoading(false);
        }
      })
      .catch(() => {
        if (isMounted) setLoading(false);
      });

    return () => {
      isMounted = false;
    };
  }, [sp, slug, initialProducts]);

  const collectionTitle =
    slug === 'all'
      ? 'All Products'
      : slug
          .split('-')
          .map((w) => w.charAt(0).toUpperCase() + w.slice(1))
          .join(' ');

  function getFilterHref(min: number | null, max: number | null) {
    const paramsObj = new URLSearchParams();
    if (activeSort && activeSort !== 'newest') paramsObj.set('sort', activeSort);
    if (min !== null) paramsObj.set('min_price', String(min));
    if (max !== null) paramsObj.set('max_price', String(max));
    const qs = paramsObj.toString();
    return qs ? `?${qs}` : `/collections/${slug}`;
  }

  return (
    <div className="bg-[#faf8f5] min-h-screen pt-16 md:pt-[72px] text-stone-900">
      {/* ── Hero ──────────────────────────────────────────────────── */}
      <div className="relative border-b border-stone-200/80 bg-[#f4efe6] overflow-hidden">
        <div className="absolute inset-0 flex items-center justify-center overflow-hidden select-none pointer-events-none">
          <span className="font-display font-black uppercase text-[18vw] leading-none text-stone-900/[0.03] tracking-tighter whitespace-nowrap">
            {collectionTitle}
          </span>
        </div>

        <div className="relative z-10 px-6 lg:px-16 py-10 md:py-16">
          <div className="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-amber-100/80 border border-amber-300/60 text-[#c2410c] text-[11px] font-display font-bold uppercase tracking-wider mb-3">
            <span>{storeName} Catalog</span>
          </div>
          <h1 className="font-display font-black uppercase text-3xl md:text-6xl lg:text-7xl leading-[0.95] tracking-tight text-stone-900">
            {collectionTitle}
          </h1>
          <p className="text-xs md:text-sm text-stone-600 mt-3 max-w-md leading-relaxed font-normal">
            Curated accessories and lifestyle essentials. Available across Egypt with 24–48h express delivery.
          </p>
        </div>
      </div>

      {/* ── Toolbar ───────────────────────────────────────────────── */}
      <div className="sticky top-16 md:top-[72px] z-30 border-b border-stone-200/80 bg-white/90 backdrop-blur-md px-6 lg:px-16 py-3 flex items-center justify-between gap-4 shadow-2xs">
        {/* Category pills */}
        <div className="flex items-center gap-2 overflow-x-auto scrollbar-none py-1">
          <Link
            href="/collections/all"
            className={`shrink-0 px-4 py-2 rounded-xl font-display font-bold text-xs uppercase tracking-wider border transition-all duration-200 ${
              slug === 'all'
                ? 'border-[#c2410c] bg-[#c2410c] text-amber-50 shadow-xs'
                : 'border-stone-200 text-stone-700 hover:border-stone-300 hover:bg-stone-50 bg-white'
            }`}
          >
            All Products
          </Link>
          {filterCategories.map((cat: any) => (
            <Link
              key={cat.slug}
              href={`/collections/${cat.slug}`}
              className={`shrink-0 px-4 py-2 rounded-xl font-display font-bold text-xs uppercase tracking-wider border transition-all duration-200 ${
                slug === cat.slug
                  ? 'border-[#c2410c] bg-[#c2410c] text-amber-50 shadow-xs'
                  : 'border-stone-200 text-stone-700 hover:border-stone-300 hover:bg-stone-50 bg-white'
              }`}
            >
              {cat.title}
            </Link>
          ))}
        </div>

        <div className="flex items-center gap-4 ml-auto shrink-0">
          <span className="text-xs text-stone-500 font-display font-semibold uppercase tracking-wider hidden sm:block">
            {products.length} {products.length === 1 ? 'Product' : 'Products'}
          </span>
          <SortDropdown activeSort={activeSort} />
        </div>
      </div>

      {/* ── Main Content ──────────────────────────────────────────── */}
      <div className="flex max-w-7xl mx-auto">
        {/* Sidebar Filter */}
        <aside className="hidden xl:block w-64 shrink-0 border-r border-stone-200/80 px-8 py-10 sticky top-[120px] h-[calc(100vh-120px)] overflow-y-auto">
          <div className="space-y-8 text-xs">
            {/* Sort */}
            <div>
              <h3 className="font-display font-bold text-[11px] uppercase tracking-wider text-[#c2410c] mb-3">
                Sort By
              </h3>
              <ul className="space-y-2">
                {SORT_OPTIONS.map((o) => (
                  <li key={o.value}>
                    <Link
                      href={`?sort=${o.value}${activeMinPrice !== null ? `&min_price=${activeMinPrice}` : ''}${activeMaxPrice !== null ? `&max_price=${activeMaxPrice}` : ''}`}
                      className={`font-display text-xs uppercase tracking-wider transition-colors block py-0.5 ${
                        activeSort === o.value
                          ? 'text-[#c2410c] font-bold'
                          : 'text-stone-600 hover:text-stone-900'
                      }`}
                    >
                      {activeSort === o.value && <span className="text-[#c2410c] mr-1.5">→</span>}
                      {o.label}
                    </Link>
                  </li>
                ))}
              </ul>
            </div>

            {/* Categories */}
            <div>
              <h3 className="font-display font-bold text-[11px] uppercase tracking-wider text-[#c2410c] mb-3">
                Collections
              </h3>
              <ul className="space-y-2">
                <li>
                  <Link
                    href="/collections/all"
                    className={`font-display text-xs uppercase tracking-wider transition-colors block py-0.5 ${
                      slug === 'all' ? 'text-[#c2410c] font-bold' : 'text-stone-600 hover:text-stone-900'
                    }`}
                  >
                    {slug === 'all' && <span className="text-[#c2410c] mr-1.5">→</span>}All Products
                  </Link>
                </li>
                {filterCategories.map((cat: any) => (
                  <li key={cat.slug}>
                    <Link
                      href={`/collections/${cat.slug}`}
                      className={`font-display text-xs uppercase tracking-wider transition-colors block py-0.5 ${
                        slug === cat.slug ? 'text-[#c2410c] font-bold' : 'text-stone-600 hover:text-stone-900'
                      }`}
                    >
                      {slug === cat.slug && <span className="text-[#c2410c] mr-1.5">→</span>}
                      {cat.title}
                    </Link>
                  </li>
                ))}
              </ul>
            </div>

            {/* Price Ranges */}
            <div>
              <h3 className="font-display font-bold text-[11px] uppercase tracking-wider text-[#c2410c] mb-3">
                Price Range (EGP)
              </h3>
              <ul className="space-y-2">
                {PRICE_RANGES.map((range) => {
                  const isSelected = range.min === activeMinPrice && range.max === activeMaxPrice;
                  return (
                    <li key={range.label}>
                      <Link
                        href={getFilterHref(range.min, range.max)}
                        className={`font-display text-xs uppercase tracking-wider transition-colors block py-0.5 ${
                          isSelected
                            ? 'text-[#c2410c] font-bold'
                            : 'text-stone-600 hover:text-stone-900'
                        }`}
                      >
                        {isSelected && <span className="text-[#c2410c] mr-1.5">✓</span>}
                        {range.label}
                      </Link>
                    </li>
                  );
                })}
              </ul>
            </div>
          </div>
        </aside>

        {/* Product Grid */}
        <div className="flex-1 min-w-0 px-4 sm:px-6 lg:px-10 py-8 relative">
          {loading && (
             <div className="absolute inset-0 bg-white/60 backdrop-blur-xs flex justify-center py-20 z-10">
                <span className="text-[#c2410c] text-xs font-display font-bold uppercase tracking-wider">Updating catalog...</span>
             </div>
          )}
          
          {products.length === 0 && !loading ? (
            <div className="flex flex-col items-center justify-center py-24 gap-4 text-center border border-stone-200 bg-white rounded-2xl p-8 shadow-xs">
              <span className="font-display font-black text-5xl text-stone-300 select-none">∅</span>
              <p className="font-display font-bold uppercase tracking-wider text-xs text-stone-600">
                No Products Found In This Filter
              </p>
              <p className="text-xs text-stone-500 max-w-sm">Try choosing another price range or category.</p>
              <Link href="/collections/all" className="mt-2 bg-[#c2410c] hover:bg-[#9a3412] text-amber-50 font-display font-bold text-xs uppercase tracking-wider px-6 py-3 rounded-xl shadow-xs transition-all cursor-pointer">
                Reset Filters
              </Link>
            </div>
          ) : (
            <div className={`grid grid-cols-2 lg:grid-cols-3 gap-4 md:gap-6 transition-opacity ${loading ? 'opacity-50' : 'opacity-100'}`}>
              {products.map((product: any, i: number) => (
                <ProductCard
                  key={product.slug || product.id}
                  product={{
                    ...product,
                    price_minor: product.retail_price_minor ?? product.price_minor ?? 0,
                  }}
                  index={i}
                />
              ))}
            </div>
          )}
        </div>
      </div>
    </div>
  );
}
