'use client';

import React, { useState, useEffect } from 'react';
import Link from 'next/link';
import { useStore } from '@/components/providers/StoreProvider';
import { resolveMediaUrl } from '@/lib/media';

export default function MinimalHero({ featuredProduct }: { featuredProduct?: any }) {
  const { formatPrice, settings } = useStore();
  const [isHovered, setIsHovered] = useState(false);
  const [activeImageIdx, setActiveImageIdx] = useState(0);

  const heroHeadline = settings?.homeHeroTitle || 'PRECISION CRAFTED ACCESSORIES';
  const heroSubtitle = settings?.homeHeroSubtitle || 'Handcrafted full-grain leather cases, aerospace titanium hardware, and minimalist everyday carry.';
  const storeBrand = settings?.storeName || 'ATELIER';
  const announcementText = settings?.announcementText || 'FAST DELIVERY · CASH ON DELIVERY · EASY EXCHANGES';
  const heroButtonText = settings?.signatureHeroButtonText || 'VIEW PRODUCT';

  const product = featuredProduct || {
    title: `${storeBrand} MagSafe PowerBank E-Wallet`,
    slug: 'atelier-magsafe-powerbank-battery-wallet',
    price_minor: 78000,
    currency: settings?.currency || 'EGP',
    image_url: 'https://images.unsplash.com/photo-1627123424574-724758594e93?w=1400&q=90',
  };

  const rawCover = product.image_url || 'https://images.unsplash.com/photo-1627123424574-724758594e93?w=1400&q=90';
  const resolvedCover = resolveMediaUrl(rawCover) || rawCover;

  const heroGallery = [
    resolvedCover,
    'https://images.unsplash.com/photo-1607604276583-eef5d076aa5f?w=1400&q=90',
    'https://images.unsplash.com/photo-1591561954557-26941169b49e?w=1400&q=90',
    'https://images.unsplash.com/photo-1548036328-c9fa89d128fa?w=1400&q=90',
  ];

  // Auto Photo Swiping on Hover
  useEffect(() => {
    if (!isHovered) return;
    const timer = setInterval(() => {
      setActiveImageIdx((prev) => (prev + 1) % heroGallery.length);
    }, 1200);
    return () => clearInterval(timer);
  }, [isHovered, heroGallery.length]);

  const formattedPrice = formatPrice(product.price_minor || 78000, product.currency || 'EGP');

  return (
    <section className="relative w-full bg-[#f8f7f4] text-stone-900 overflow-hidden border-b border-[#e6e4dc]">
      
      {/* Top Floating Announcement Capsule Bar */}
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-12 pt-4 sm:pt-5 pb-2 flex flex-col sm:flex-row items-center justify-between gap-3">
        <div className="font-display font-black text-xs sm:text-sm uppercase tracking-[0.25em] text-stone-800 text-center sm:text-left">
          {storeBrand} · EDC SERIES
        </div>

        <div className="flex flex-wrap items-center justify-center gap-2 sm:gap-3 bg-[#fb923c] text-stone-950 px-3.5 sm:px-4 py-1.5 rounded-full text-xs font-display font-bold uppercase tracking-wider shadow-md">
          <Link href="/account" prefetch={true} className="flex items-center gap-1 hover:underline">
            <span>👤</span>
            <span>Account</span>
          </Link>
          <span className="opacity-40">|</span>
          <Link href="/checkout" prefetch={true} className="hover:underline">
            Cart
          </Link>
          <span className="opacity-40 hidden sm:inline">|</span>
          <span className="hidden sm:inline font-bold truncate max-w-xs md:max-w-md">{announcementText}</span>
        </div>
      </div>

      {/* Main Hero Two-Column Layout */}
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-12 py-8 sm:py-12 md:py-16 lg:py-20 flex flex-col lg:flex-row items-center justify-between gap-8 sm:gap-10 lg:gap-16">
        
        {/* Left Column: Bold Typography & Action Buttons */}
        <div className="w-full lg:w-1/2 space-y-4 sm:space-y-6 text-left">
          <div className="inline-block font-display font-black text-[11px] sm:text-xs uppercase tracking-[0.3em] text-[#fb923c]">
            PREMIUM BESPOKE LEATHERCRAFT
          </div>

          <h1 className="font-display font-black uppercase text-3xl sm:text-5xl md:text-6xl lg:text-7xl xl:text-8xl tracking-tight leading-[0.95] text-stone-950">
            {heroHeadline}
          </h1>

          <p className="font-sans text-sm sm:text-base md:text-lg text-stone-600 font-normal leading-relaxed max-w-lg">
            {heroSubtitle}
          </p>

          <div className="flex flex-wrap items-baseline gap-3 pt-1">
            <span className="font-display font-black text-2xl sm:text-3xl md:text-4xl text-stone-950 tracking-tight">
              {formattedPrice}
            </span>
            <span className="font-display font-semibold text-[11px] sm:text-xs uppercase tracking-wider text-emerald-800 bg-emerald-100 border border-emerald-300 px-3 py-1 rounded-none">
              In Stock · Express Dispatch
            </span>
          </div>

          {/* Action Buttons */}
          <div className="flex flex-col sm:flex-row items-stretch sm:items-center gap-3.5 pt-2 sm:pt-4">
            <Link
              href={`/products/${product.slug || 'all'}`}
              prefetch={true}
              className="group relative inline-flex items-center justify-center gap-3 px-8 py-4 bg-black hover:bg-stone-900 text-white font-display font-black text-xs sm:text-sm uppercase tracking-widest shadow-xl active:scale-95 transition-all duration-300 cursor-pointer overflow-hidden text-center rounded-none"
            >
              <span className="relative z-10">{heroButtonText}</span>
              <span className="relative z-10 group-hover:translate-x-1 transition-transform">→</span>
            </Link>

            <Link
              href="/collections/all"
              prefetch={true}
              className="inline-flex items-center justify-center px-7 py-4 bg-white hover:bg-stone-50 text-stone-900 font-display font-black text-xs uppercase tracking-wider border border-stone-300 shadow-md hover:shadow-lg active:scale-95 transition-all duration-300 cursor-pointer text-center rounded-none"
            >
              EXPLORE SERIES
            </Link>
          </div>
        </div>

        {/* Right Column: Hero Product Image with Zero Opacity Glitches */}
        <div
          onMouseEnter={() => setIsHovered(true)}
          onMouseLeave={() => setIsHovered(false)}
          className="w-full lg:w-1/2 flex items-center justify-center"
        >
          <Link
            href={`/products/${product.slug || 'all'}`}
            prefetch={true}
            className="group relative w-full aspect-square max-w-[560px] overflow-hidden bg-stone-900 shadow-[0_30px_70px_rgba(0,0,0,0.22)] hover:shadow-[0_40px_90px_rgba(0,0,0,0.35)] transition-all duration-500 hover:scale-[1.02] active:scale-98 cursor-pointer border-2 border-stone-800 hover:border-[#fb923c] block"
          >
            <img
              src={heroGallery[activeImageIdx]}
              alt={product.title || 'Product'}
              className="w-full h-full object-cover object-center transition-all duration-700 ease-[cubic-bezier(0.16,1,0.3,1)] group-hover:scale-108 block"
              onError={(e) => {
                (e.target as HTMLImageElement).src = 'https://images.unsplash.com/photo-1627123424574-724758594e93?w=1400&q=90';
              }}
            />

            {/* Auto-Swiping Indicator Badge */}
            {isHovered && (
              <div className="absolute top-4 right-4 z-10">
                <span className="bg-[#fb923c] text-stone-950 font-display font-black text-[10px] uppercase tracking-wider px-2.5 py-1 shadow-md animate-pulse">
                  Auto Swiping {activeImageIdx + 1}/{heroGallery.length}
                </span>
              </div>
            )}
            
            {/* View Details floating tag on hover */}
            <div className="absolute bottom-5 inset-x-0 flex justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-300 z-10">
              <span className="bg-black/95 text-white font-display font-black text-xs uppercase tracking-widest px-6 py-2.5 shadow-2xl backdrop-blur-md border border-white/20">
                Click to View Details →
              </span>
            </div>
          </Link>
        </div>

      </div>
    </section>
  );
}
