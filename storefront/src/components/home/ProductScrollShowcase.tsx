'use client';

import React, { useState, useEffect, useRef } from 'react';
import Link from 'next/link';
import { useStore } from '@/components/providers/StoreProvider';
import { resolveMediaUrl } from '@/lib/media';

interface ProductScrollShowcaseProps {
  products: any[];
}

function getProductGallery(product: any): string[] {
  const gallery: string[] = [];

  const rawCover = product.image_url;
  if (rawCover) {
    const resolved = resolveMediaUrl(rawCover);
    if (resolved) gallery.push(resolved);
  }

  const rawAssets = Array.isArray(product.media_assets)
    ? product.media_assets
    : Array.isArray(product.mediaAssets)
    ? product.mediaAssets
    : [];

  for (const asset of rawAssets) {
    if (asset?.url) {
      const resolved = resolveMediaUrl(asset.url);
      if (resolved && !gallery.includes(resolved)) {
        gallery.push(resolved);
      }
    }
  }

  if (gallery.length === 0) {
    gallery.push('https://images.unsplash.com/photo-1627123424574-724758594e93?w=1200&q=90');
  }

  return gallery;
}

export default function ProductScrollShowcase({ products }: ProductScrollShowcaseProps) {
  const { addToCart, formatPrice } = useStore();

  if (!products || products.length === 0) return null;

  return (
    <div className="w-full flex flex-col gap-0 p-0 m-0 overflow-hidden bg-[#f8f7f4]">
      {products.map((product, index) => (
        <ProductScrollItem
          key={product.slug || product.id || index}
          product={product}
          index={index}
          total={products.length}
          addToCart={addToCart}
          formatPrice={formatPrice}
        />
      ))}
    </div>
  );
}

function ProductScrollItem({
  product,
  index,
  total,
  addToCart,
  formatPrice,
}: {
  product: any;
  index: number;
  total: number;
  addToCart: (id: any, variantId: any, qty: number) => Promise<any>;
  formatPrice: (priceMinor: number, currency?: string) => string;
}) {
  const itemRef = useRef<HTMLDivElement>(null);
  const [isVisible, setIsVisible] = useState(false);
  const [isHovered, setIsHovered] = useState(false);
  const [adding, setAdding] = useState(false);
  const [added, setAdded] = useState(false);
  const [activeImageIdx, setActiveImageIdx] = useState(0);

  const variants = Array.isArray(product.variants) ? product.variants : [];
  const [selectedVariant, setSelectedVariant] = useState<any>(variants[0] || null);

  const displayPriceMinor = selectedVariant?.retail_price_minor
    || selectedVariant?.price_minor
    || product.retail_price_minor
    || product.price_minor
    || 0;

  const formattedPrice = formatPrice(displayPriceMinor, product.currency || 'EGP');
  const galleryUrls = getProductGallery(product);
  const activeImageUrl = galleryUrls[activeImageIdx] || galleryUrls[0];

  useEffect(() => {
    const observer = new IntersectionObserver(
      ([entry]) => {
        setIsVisible(entry.isIntersecting);
      },
      { threshold: 0.1, rootMargin: '0px' }
    );

    if (itemRef.current) observer.observe(itemRef.current);
    return () => observer.disconnect();
  }, []);

  // Automatic Photo Swiping with Animation on Mouse Hover
  useEffect(() => {
    if (!isHovered || galleryUrls.length <= 1) return;
    const timer = setInterval(() => {
      setActiveImageIdx((prev) => (prev + 1) % galleryUrls.length);
    }, 1300);
    return () => clearInterval(timer);
  }, [isHovered, galleryUrls.length]);

  function handleSelectVariant(v: any) {
    setSelectedVariant(v);
    const vImg = v.image_url || v.attributes_json?.image_url;
    if (vImg) {
      const resolved = resolveMediaUrl(vImg) || vImg;
      const foundIdx = galleryUrls.findIndex(url => url === resolved || url.includes(vImg));
      if (foundIdx !== -1) {
        setActiveImageIdx(foundIdx);
      }
    }
  }

  const handleQuickAdd = async (e: React.MouseEvent) => {
    e.preventDefault();
    if (adding) return;
    setAdding(true);
    try {
      await addToCart(product.id || product.slug, selectedVariant?.id ?? null, 1);
      setAdded(true);
      setTimeout(() => setAdded(false), 2000);
    } catch (err) {
      console.error(err);
    } finally {
      setAdding(false);
    }
  };

  const isEven = index % 2 === 0;

  return (
    <section
      ref={itemRef}
      className={`relative w-full min-h-[90vh] lg:min-h-screen flex items-center justify-center p-0 m-0 border-b border-[#e6e4dc] overflow-hidden transition-all duration-700 ${
        isEven ? 'bg-[#f8f7f4]' : 'bg-[#f3f2ee]'
      }`}
    >
      {/* Subtle Atmospheric Ambient Layer */}
      <div className="absolute inset-0 pointer-events-none overflow-hidden">
        <img
          src={activeImageUrl}
          alt=""
          className={`w-full h-full object-cover filter blur-3xl opacity-10 scale-125 transition-transform duration-1000 ease-out ${
            isVisible ? 'scale-110 opacity-15' : 'scale-125 opacity-5'
          }`}
          onError={(e) => {
            (e.target as HTMLElement).style.display = 'none';
          }}
        />
        <div className="absolute inset-0 bg-[#f8f7f4]/85" />
      </div>

      {/* Content Container */}
      <div className="relative z-10 w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-12 py-12 md:py-20 flex flex-col lg:flex-row items-center justify-between gap-8 lg:gap-14">
        
        {/* Left: Product Photo & Multi-Angle Thumbnails */}
        <div
          onMouseEnter={() => setIsHovered(true)}
          onMouseLeave={() => setIsHovered(false)}
          className="w-full lg:w-1/2 flex flex-col items-center justify-center opacity-100 translate-y-0 scale-100 transition-all duration-500"
        >
          <Link
            href={`/products/${product.slug}`}
            prefetch={true}
            className="group/img relative w-full aspect-square min-h-[300px] sm:min-h-[400px] lg:min-h-[480px] rounded-none overflow-hidden shadow-[0_20px_50px_rgba(0,0,0,0.12)] flex items-center justify-center cursor-pointer transition-all duration-300 hover:shadow-[0_30px_70px_rgba(0,0,0,0.22)] hover:scale-[1.02] active:scale-95 border-2 border-stone-850 hover:border-[#fb923c] bg-stone-900 block"
          >
            <img
              src={activeImageUrl}
              alt={product.title}
              className="w-full h-full object-cover transition-all duration-700 ease-[cubic-bezier(0.16,1,0.3,1)] group-hover/img:scale-108 block"
              onError={(e) => {
                (e.target as HTMLImageElement).src = 'https://images.unsplash.com/photo-1627123424574-724758594e93?w=1200&q=90';
              }}
            />

            {/* Subtle Gradient Shade on bottom of photo */}
            <div className="absolute inset-0 bg-gradient-to-t from-black/50 via-transparent to-black/20 pointer-events-none" />

            {/* Top Product Badges */}
            <div className="absolute top-5 left-5 z-10 flex items-center gap-2">
              <span className="font-display font-black text-xs uppercase tracking-[0.2em] text-[#fb923c] bg-black/85 backdrop-blur-md px-3.5 py-1 rounded-none border border-white/20">
                SERIES 0{index + 1} / 0{total}
              </span>
              {isHovered && galleryUrls.length > 1 && (
                <span className="bg-[#fb923c] text-stone-950 font-display font-black text-[10px] uppercase tracking-wider px-2.5 py-1 rounded-none shadow-md animate-pulse">
                  Auto Swiping {activeImageIdx + 1}/{galleryUrls.length}
                </span>
              )}
            </div>

            {/* Hover Floating Glassy Hint */}
            <div className="absolute bottom-5 inset-x-0 flex justify-center opacity-0 group-hover/img:opacity-100 transition-opacity duration-200 z-10">
              <span className="bg-black/90 hover:bg-black text-white font-display font-black text-xs uppercase tracking-widest px-6 py-2.5 rounded-none shadow-2xl backdrop-blur-xl border border-white/20">
                View Details →
              </span>
            </div>
          </Link>

          {/* Multi-Angle Gallery Thumbnails (Only if product has > 1 image) */}
          {galleryUrls.length > 1 && (
            <div className="flex items-center gap-2.5 sm:gap-3.5 mt-5">
              {galleryUrls.map((imgUrl: string, idx: number) => (
                <button
                  key={idx}
                  onClick={() => setActiveImageIdx(idx)}
                  className={`relative w-14 h-14 sm:w-16 sm:h-16 rounded-none overflow-hidden border-2 transition-all duration-300 cursor-pointer p-0 active:scale-90 shadow-md ${
                    activeImageIdx === idx
                      ? 'border-[#fb923c] scale-110 shadow-lg shadow-[#fb923c]/30 ring-2 ring-[#fb923c]/40'
                      : 'border-stone-300 opacity-80 hover:opacity-100 hover:border-stone-900 bg-white'
                  }`}
                  aria-label={`View angle ${idx + 1}`}
                >
                  <img
                    src={imgUrl}
                    alt=""
                    className="w-full h-full object-cover rounded-none"
                    onError={(e) => {
                      (e.target as HTMLElement).parentElement?.classList.add('hidden');
                    }}
                  />
                </button>
              ))}
            </div>
          )}
        </div>

        {/* Right: Product Details & Dynamic Options */}
        <div className="w-full lg:w-1/2 space-y-5 sm:space-y-6 text-left opacity-100 translate-y-0 transition-all duration-500">
          {/* Brand & Reviews */}
          <div className="flex items-center gap-3">
            <span className="font-display font-black text-xs uppercase tracking-[0.25em] text-[#fb923c]">
              ATELIER LUXURY EDC
            </span>
            <span className="text-stone-400">·</span>
            <div className="flex items-center gap-1.5 text-xs text-amber-500 font-semibold">
              <span>★★★★★</span>
              <span className="text-stone-600 font-medium">5.0 Verified Reviews</span>
            </div>
          </div>

          {/* Product Title */}
          <h2 className="font-display font-black uppercase text-2xl sm:text-4xl lg:text-5xl text-stone-950 tracking-tight leading-tight">
            <Link
              href={`/products/${product.slug}`}
              prefetch={true}
              className="hover:text-[#fb923c] transition-colors active:scale-98 inline-block"
            >
              {product.title}
            </Link>
          </h2>

          {/* Pricing Row */}
          <div className="flex items-baseline gap-3 pt-1">
            <span className="font-display font-black text-3xl sm:text-4xl text-stone-950 tracking-tight">
              {formattedPrice}
            </span>
            <span className="text-xs font-display font-bold uppercase tracking-wider text-emerald-800 bg-emerald-100 border border-emerald-300 px-3 py-1 rounded-none">
              In Stock · Express Dispatch
            </span>
          </div>

          {/* Description */}
          <p className="text-sm sm:text-base text-stone-600 font-normal leading-relaxed max-w-xl">
            {product.description || 'Engineered with double-strength N52 neodymium magnets, patented RFID shielding, and handcrafted vegetable-tanned leather for everyday precision.'}
          </p>

          {/* Real Dynamic Variants / Colors (If variants exist) */}
          {variants.length > 0 ? (
            <div className="space-y-2 pt-2">
              <span className="font-display font-bold text-xs uppercase tracking-wider text-stone-800 block">
                Select Option / Finish: <strong className="text-[#fb923c]">{selectedVariant?.title || 'Standard'}</strong>
              </span>
              <div className="flex flex-wrap gap-2.5">
                {variants.map((v: any) => {
                  const hex = v.attributes_json?.color_hex || v.color_hex;
                  const isSelected = selectedVariant?.id === v.id;
                  return (
                    <button
                      key={v.id}
                      onClick={() => handleSelectVariant(v)}
                      className={`px-4 py-2 rounded-none text-xs font-display font-bold uppercase tracking-wider transition-all duration-200 cursor-pointer border flex items-center gap-2 ${
                        isSelected
                          ? 'border-[#fb923c] bg-stone-900 text-white shadow-md font-black ring-1 ring-[#fb923c]'
                          : 'border-stone-300 bg-white/90 text-stone-700 hover:border-stone-900 hover:bg-white hover:text-stone-950'
                      }`}
                    >
                      {hex && (
                        <span
                          className="w-3 h-3 rounded-full border border-black/20 shrink-0"
                          style={{ backgroundColor: hex }}
                        />
                      )}
                      <span>{v.title}</span>
                    </button>
                  );
                })}
              </div>
            </div>
          ) : (
            <div className="flex flex-wrap gap-2 pt-1">
              <span className="text-[11px] font-display font-bold uppercase tracking-wider px-3 py-1.5 bg-stone-200/80 text-stone-800 border border-stone-300">
                100% Genuine Full-Grain Leather
              </span>
              <span className="text-[11px] font-display font-bold uppercase tracking-wider px-3 py-1.5 bg-stone-200/80 text-stone-800 border border-stone-300">
                MagSafe N52 Snap
              </span>
              <span className="text-[11px] font-display font-bold uppercase tracking-wider px-3 py-1.5 bg-stone-200/80 text-stone-800 border border-stone-300">
                2-Year Warranty
              </span>
            </div>
          )}

          {/* Action Buttons */}
          <div className="pt-3 flex flex-col sm:flex-row items-stretch sm:items-center gap-3.5 max-w-lg">
            <button
              onClick={handleQuickAdd}
              disabled={adding}
              className={`flex-1 py-4 px-6 rounded-none font-display font-black text-sm uppercase tracking-wider transition-all duration-300 cursor-pointer flex items-center justify-center gap-2 shadow-[0_15px_30px_rgba(0,0,0,0.15)] border active:scale-95 ${
                added
                  ? 'bg-emerald-700 text-white border-emerald-600'
                  : 'bg-stone-900 hover:bg-black text-white border-stone-800 hover:shadow-[0_15px_35px_rgba(0,0,0,0.3)]'
              }`}
            >
              {adding ? (
                <div className="w-5 h-5 border-2 border-white border-t-transparent rounded-full animate-spin" />
              ) : added ? (
                <>
                  <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={3}>
                    <path strokeLinecap="round" strokeLinejoin="round" d="M5 13l4 4L19 7" />
                  </svg>
                  <span>Added To Bag!</span>
                </>
              ) : (
                <>
                  <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2.5}>
                    <path strokeLinecap="round" strokeLinejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                  </svg>
                  <span>Add To Bag</span>
                </>
              )}
            </button>

            <Link
              href="/checkout"
              prefetch={true}
              onClick={async () => {
                try {
                  await addToCart(product.id || product.slug, selectedVariant?.id ?? null, 1);
                } catch {}
              }}
              className="py-4 px-6 rounded-none bg-white hover:bg-stone-50 text-stone-950 font-display font-black text-sm uppercase tracking-wider text-center transition-all duration-200 active:scale-95 shadow-md cursor-pointer border border-stone-300"
            >
              Cash on Delivery →
            </Link>
          </div>

          {/* Fast Link to Full Product Specs */}
          <div className="pt-1">
            <Link
              href={`/products/${product.slug}`}
              prefetch={true}
              className="text-xs font-display font-bold uppercase tracking-wider text-stone-600 hover:text-[#fb923c] flex items-center gap-1.5 transition-colors group/link"
            >
              <span>View Full Specifications & 2-Year Warranty</span>
              <span className="group-hover/link:translate-x-1 transition-transform">→</span>
            </Link>
          </div>

        </div>
      </div>
    </section>
  );
}
