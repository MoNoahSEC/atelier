'use client';

import { useState, useEffect, useRef } from 'react';
import Link from 'next/link';
import { SmartImage } from '@/components/ui/SmartImage';
import { useStore } from '@/components/providers/StoreProvider';

interface HeroShowcaseProps {
  products: any[];
}

export default function HeroShowcase({ products }: HeroShowcaseProps) {
  const { formatPrice, currency } = useStore();
  const [currentIndex, setCurrentIndex] = useState(0);
  const [isPaused, setIsPaused] = useState(false);
  const timerRef = useRef<NodeJS.Timeout | null>(null);

  // Use top products or fallback items
  const items = products && products.length > 0 ? products.slice(0, 5) : [];

  useEffect(() => {
    if (items.length <= 1 || isPaused) return;

    timerRef.current = setInterval(() => {
      setCurrentIndex((prev) => (prev + 1) % items.length);
    }, 3500);

    return () => {
      if (timerRef.current) clearInterval(timerRef.current);
    };
  }, [items.length, isPaused]);

  if (items.length === 0) return null;

  const currentItem = items[currentIndex];
  const priceMinor = currentItem.retail_price_minor ?? currentItem.price_minor ?? 0;
  const formattedPrice = formatPrice(priceMinor, currentItem.currency || currency);
  const imageUrl = currentItem.image_url || currentItem.media_assets?.[0]?.url;

  return (
    <section 
      className="px-4 sm:px-6 lg:px-16 py-8 md:py-12 bg-[#faf8f5]"
      onMouseEnter={() => setIsPaused(true)}
      onMouseLeave={() => setIsPaused(false)}
    >
      <div className="max-w-7xl mx-auto">
        {/* Section Header */}
        <div className="flex flex-col sm:flex-row sm:items-end justify-between mb-5 gap-2">
          <div>
            <div className="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-amber-100/80 border border-amber-300/60 text-[#c2410c] text-[11px] font-display font-bold uppercase tracking-wider mb-2">
              <span className="w-2 h-2 rounded-full bg-[#c2410c] animate-ping" />
              <span>Now in Egypt &middot; 24–48h Dispatch</span>
            </div>
            <h2 className="font-display font-black uppercase text-2xl sm:text-3xl md:text-4xl text-stone-900 tracking-tight">
              Wear Your Style
            </h2>
          </div>
          <p className="text-xs font-medium text-stone-500 max-w-sm">
            Auto-curated spotlight series. Handpicked modern accessories, tactical wear & footwear.
          </p>
        </div>

        {/* Showcase Banner Box */}
        <div className="relative overflow-hidden rounded-2xl md:rounded-3xl border border-stone-200/80 bg-[#f4efe6] shadow-md">
          {/* Main Slide Content */}
          <div className="grid grid-cols-1 lg:grid-cols-12 min-h-[380px] md:min-h-[460px] items-center">
            {/* Left Content Area */}
            <div className="lg:col-span-5 p-6 sm:p-8 md:p-12 flex flex-col justify-between h-full z-10">
              <div className="space-y-3">
                {/* Category & Badge */}
                <div className="flex flex-wrap items-center gap-2">
                  <span className="bg-[#c2410c] text-amber-50 font-display font-bold text-[10px] sm:text-xs uppercase tracking-wider px-2.5 py-1 rounded-md shadow-xs">
                    Now In Egypt
                  </span>
                  <span className="bg-white/80 border border-stone-200 text-stone-700 font-display font-bold text-[10px] sm:text-xs uppercase tracking-wider px-2.5 py-1 rounded-md">
                    Featured Drop
                  </span>
                </div>

                {/* Rating */}
                <div className="flex items-center gap-1.5 text-xs text-amber-600 font-bold">
                  <span>★★★★★</span>
                  <span className="text-stone-600 font-medium">(5.0 Verified Reviews)</span>
                </div>

                {/* Title */}
                <h3 className="font-display font-black uppercase text-2xl sm:text-3xl md:text-4xl text-stone-900 tracking-tight leading-tight">
                  {currentItem.title}
                </h3>

                {/* Description snippet */}
                {currentItem.description && (
                  <p className="text-xs sm:text-sm text-stone-600 font-normal line-clamp-2 leading-relaxed">
                    {currentItem.description}
                  </p>
                )}

                {/* Price Display */}
                <div className="pt-2 flex items-baseline gap-3">
                  <span className="font-display font-black text-2xl sm:text-3xl text-[#c2410c]">
                    {formattedPrice}
                  </span>
                  <span className="text-xs font-display font-bold uppercase tracking-wider text-stone-500">
                    Free Egypt Shipping
                  </span>
                </div>
              </div>

              {/* Action Buttons */}
              <div className="pt-6 flex flex-wrap items-center gap-3">
                <Link
                  href={`/products/${currentItem.slug}`}
                  className="bg-[#c2410c] hover:bg-[#9a3412] text-amber-50 font-display font-black text-xs sm:text-sm uppercase tracking-wider px-6 py-3.5 rounded-xl shadow-md transition-all active:scale-95 flex items-center gap-2 cursor-pointer"
                >
                  <span>View Product & Reviews</span>
                  <span>→</span>
                </Link>
                <Link
                  href="/collections/all"
                  className="bg-white/80 hover:bg-white text-stone-800 border border-stone-200/90 font-display font-bold text-xs sm:text-sm uppercase tracking-wider px-5 py-3.5 rounded-xl shadow-xs transition-all active:scale-95 cursor-pointer"
                >
                  Browse All
                </Link>
              </div>
            </div>

            {/* Right Large Product Image Area */}
            <div className="lg:col-span-7 h-[280px] sm:h-[360px] lg:h-[460px] relative bg-stone-100/60 overflow-hidden flex items-center justify-center p-4">
              <Link 
                href={`/products/${currentItem.slug}`}
                className="relative w-full h-full group block cursor-pointer"
              >
                {imageUrl ? (
                  <SmartImage
                    src={imageUrl}
                    alt={currentItem.title}
                    fill
                    cropMode="cover"
                    objectPosition="center"
                    priority
                    className="group-hover:scale-105 transition-transform duration-700 ease-out rounded-xl"
                  />
                ) : (
                  <div className="w-full h-full flex items-center justify-center text-4xl font-black text-stone-300 font-display">
                    ATELIER
                  </div>
                )}
                {/* Subtle gradient vignette */}
                <div className="absolute inset-0 bg-gradient-to-t from-stone-900/40 via-transparent to-transparent opacity-60 pointer-events-none rounded-xl" />
              </Link>
            </div>
          </div>

          {/* Navigation Controls Overlay */}
          <div className="absolute bottom-4 right-4 sm:bottom-6 sm:right-6 flex items-center gap-2 z-20">
            {/* Prev button */}
            <button
              onClick={() => setCurrentIndex((prev) => (prev - 1 + items.length) % items.length)}
              className="w-9 h-9 rounded-full bg-white/90 backdrop-blur-md border border-stone-200 hover:border-[#c2410c] text-stone-800 hover:text-[#c2410c] flex items-center justify-center shadow-sm active:scale-90 transition-all cursor-pointer"
              aria-label="Previous slide"
            >
              ←
            </button>

            {/* Slide dots */}
            <div className="flex items-center gap-1.5 px-3 py-2 rounded-full bg-white/80 backdrop-blur-md border border-stone-200 shadow-xs">
              {items.map((_, idx) => (
                <button
                  key={idx}
                  onClick={() => setCurrentIndex(idx)}
                  className={`h-2 rounded-full transition-all duration-300 cursor-pointer ${
                    idx === currentIndex 
                      ? 'w-6 bg-[#c2410c]' 
                      : 'w-2 bg-stone-300 hover:bg-stone-400'
                  }`}
                  aria-label={`Go to slide ${idx + 1}`}
                />
              ))}
            </div>

            {/* Next button */}
            <button
              onClick={() => setCurrentIndex((prev) => (prev + 1) % items.length)}
              className="w-9 h-9 rounded-full bg-white/90 backdrop-blur-md border border-stone-200 hover:border-[#c2410c] text-stone-800 hover:text-[#c2410c] flex items-center justify-center shadow-sm active:scale-90 transition-all cursor-pointer"
              aria-label="Next slide"
            >
              →
            </button>
          </div>
        </div>
      </div>
    </section>
  );
}