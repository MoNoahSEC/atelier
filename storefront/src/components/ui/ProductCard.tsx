'use client';

import React, { useState } from 'react';
import Link from 'next/link';
import { useStore } from '@/components/providers/StoreProvider';
import { resolveMediaUrl } from '@/lib/media';

interface ProductCardProps {
  product: {
    id: number | string;
    slug: string;
    title: string;
    price_minor: number;
    retail_price_minor?: number;
    currency: string;
    image_url?: string;
    status: string;
    is_available?: boolean;
    label?: string;
    inventory?: number;
    created_at?: string;
    media_assets?: Array<{ id: number; url: string; type?: string }>;
    mediaAssets?: Array<{ id: number; url: string; type?: string }>;
  };
  className?: string;
  index?: number;
}

export function ProductCard({ product, className = '', index = 0 }: ProductCardProps) {
  const { storeName, addToCart, formatPrice } = useStore();
  const [hovered, setHovered] = useState(false);
  const [adding, setAdding] = useState(false);
  const [isWishlisted, setIsWishlisted] = useState(false);
  const [imgError, setImgError] = useState(false);
  const [hoverImgError, setHoverImgError] = useState(false);

  const priceMinor = product.price_minor ?? product.retail_price_minor ?? 0;
  const formattedPrice = formatPrice(priceMinor, product.currency);
  const isOOS = product.is_available === false || (product.inventory !== undefined && product.inventory <= 0);

  const mediaAssets = Array.isArray(product.media_assets)
    ? product.media_assets
    : Array.isArray(product.mediaAssets)
    ? product.mediaAssets
    : [];

  const rawPrimary = product.image_url || mediaAssets[0]?.url;
  const primaryImage = resolveMediaUrl(rawPrimary);

  const secondaryAsset = mediaAssets.find(m => m?.url && resolveMediaUrl(m.url) !== primaryImage)
    || (mediaAssets.length > 1 ? mediaAssets[1] : null);
  const secondaryImage = secondaryAsset?.url ? resolveMediaUrl(secondaryAsset.url) : null;

  return (
    <Link
      href={`/products/${product.slug}`}
      className={`group relative flex flex-col ${className} bg-white border border-stone-200/80 hover:border-stone-900 rounded-xl overflow-hidden shadow-xs hover:shadow-lg hover:-translate-y-1 transition-all duration-300 cursor-pointer`}
      onMouseEnter={() => setHovered(true)}
      onMouseLeave={() => setHovered(false)}
    >
      {/* ── Image Container (Edge-to-Edge, NO outer padding or frame) ── */}
      <div className="relative w-full aspect-square bg-stone-100 overflow-hidden">
        {/* Primary Cover Image */}
        <img
          src={primaryImage || 'https://images.unsplash.com/photo-1627123424574-724758594e93?w=800&q=80'}
          alt={product.title}
          loading={index < 4 ? 'eager' : 'lazy'}
          onError={(e) => {
            (e.target as HTMLImageElement).src = 'https://images.unsplash.com/photo-1627123424574-724758594e93?w=800&q=80';
          }}
          className={`w-full h-full object-cover object-center transition-all duration-500 ease-out ${
            hovered && secondaryImage && !hoverImgError
              ? 'opacity-0 scale-105'
              : 'opacity-100 group-hover:scale-105'
          }`}
        />

        {/* Secondary Hover Image (if exists) */}
        {secondaryImage && !hoverImgError && (
          <img
            src={secondaryImage}
            alt={`${product.title} alternate view`}
            onError={() => setHoverImgError(true)}
            className={`absolute inset-0 w-full h-full object-cover object-center transition-all duration-500 ease-out ${
              hovered ? 'opacity-100 scale-105' : 'opacity-0 scale-100'
            }`}
          />
        )}

        {/* Wishlist Button */}
        <button
          type="button"
          onClick={(e) => {
            e.preventDefault();
            e.stopPropagation();
            setIsWishlisted(!isWishlisted);
          }}
          className={`absolute top-2.5 right-2.5 z-20 w-8 h-8 rounded-full bg-white/90 backdrop-blur-md flex items-center justify-center transition-all shadow-xs cursor-pointer ${
            isWishlisted ? 'text-rose-600 scale-110' : 'text-stone-700 hover:text-rose-600 hover:scale-110 active:scale-90'
          }`}
          aria-label="Wishlist item"
        >
          <svg className="w-4 h-4 fill-current" viewBox="0 0 24 24">
            <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" />
          </svg>
        </button>

        {/* Out of Stock Overlay */}
        {isOOS && (
          <div className="absolute inset-0 bg-stone-900/70 backdrop-blur-2xs flex items-center justify-center z-20">
            <span className="border border-white/80 text-white font-display font-bold text-[10px] uppercase tracking-widest px-3 py-1.5 rounded-md bg-black/40">
              Sold Out
            </span>
          </div>
        )}

        {/* Quick Add Floating Button */}
        {!isOOS && (
          <button
            type="button"
            onClick={async (e) => {
              e.preventDefault();
              e.stopPropagation();
              if (adding) return;
              setAdding(true);
              try {
                await addToCart(product.id || product.slug, null, 1);
              } catch (err) {
                console.error(err);
              } finally {
                setAdding(false);
              }
            }}
            disabled={adding}
            title="Quick Add to Bag"
            aria-label="Quick Add to Bag"
            className="absolute bottom-2.5 right-2.5 z-20 w-9 h-9 rounded-full bg-stone-900 text-white shadow-md hover:bg-[#c2410c] hover:scale-105 active:scale-95 transition-all flex items-center justify-center cursor-pointer group/btn"
          >
            {adding ? (
              <div className="w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin" />
            ) : (
              <svg className="w-4 h-4 transition-transform group-hover/btn:rotate-90 duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2.5}>
                <path strokeLinecap="round" strokeLinejoin="round" d="M12 4v16m8-8H4" />
              </svg>
            )}
          </button>
        )}
      </div>

      {/* ── Product Meta (Section directly under the image) ── */}
      <div className="p-3 sm:p-4 bg-white flex flex-col justify-between flex-1 space-y-1">
        {/* Rating Stars */}
        <div className="flex items-center gap-1 text-[10px] text-amber-500 font-semibold">
          <span>★★★★★</span>
          <span className="text-stone-500 font-medium ml-0.5">5.0</span>
        </div>

        {/* Title */}
        <h3 className="font-display font-bold uppercase text-xs sm:text-sm leading-tight text-stone-900 line-clamp-1 group-hover:text-[#c2410c] transition-colors">
          {product.title}
        </h3>

        {/* Price */}
        <div className="pt-1">
          <span className="font-display font-black text-sm sm:text-base text-stone-950">
            {formattedPrice}
          </span>
        </div>
      </div>
    </Link>
  );
}

