'use client';

import { useState, useEffect } from 'react';
import Link from 'next/link';
import { useStore } from '@/components/providers/StoreProvider';
import ProductReviews from '@/components/product/ProductReviews';
import { resolveMediaUrl, resolveMediaUrlSsr } from '@/lib/media';

interface ProductDetailsClientProps {
  product: any;
  formattedPrice: string;
}

// Build raw image list from product data — no host rewriting, safe for SSR
function buildRawImages(product: any): string[] {
  const mediaAssets = Array.isArray(product.media_assets)
    ? product.media_assets
    : Array.isArray(product.mediaAssets)
    ? product.mediaAssets
    : [];

  const rawImages: string[] = [];
  if (product.image_url) rawImages.push(product.image_url);

  mediaAssets.forEach((asset: any) => {
    if (asset?.url && !rawImages.includes(asset.url)) {
      rawImages.push(asset.url);
    }
  });

  // NOTE: variant images are NOT added to the main gallery here.
  // They appear by switching activeImageIndex when the user selects a color.

  if (rawImages.length === 0) {
    rawImages.push('https://images.unsplash.com/photo-1627123424574-724758594e93?w=1000&q=85');
  }
  return rawImages;
}

export default function ProductDetailsClient({ product, formattedPrice: ssrFormattedPrice }: ProductDetailsClientProps) {
  const { storeName, addToCart, formatPrice, currency } = useStore();
  const [selectedVariant, setSelectedVariant] = useState<any>(product.variants?.[0] || null);
  const [quantity, setQuantity] = useState(1);
  const [adding, setAdding] = useState(false);
  const [activeImageIndex, setActiveImageIndex] = useState(0);
  const [hydrated, setHydrated] = useState(false);
  const [copied, setCopied] = useState(false);
  const [animating, setAnimating] = useState(false);

  // HYDRATION-SAFE: Use resolveMediaUrlSsr for initial state so SSR and client
  // first render produce identical output (both use 127.0.0.1:8000 or env URL).
  // After mount, useEffect rewrites to the real browser-visible host.
  const [images, setImages] = useState<string[]>(() =>
    buildRawImages(product).map(url => resolveMediaUrlSsr(url) || url)
  );

  useEffect(() => {
    // After hydration: rewrite all image URLs to match the actual browser host
    setHydrated(true);
    const clientImages = buildRawImages(product).map(url => resolveMediaUrl(url) || url);
    setImages(clientImages);
  }, []); // eslint-disable-line react-hooks/exhaustive-deps

  function handleSelectVariant(v: any) {
    setSelectedVariant(v);

    const variantImg = v.image_url || v.attributes?.image_url || v.attributes_json?.image_url;
    if (!variantImg) return;

    const resolved = resolveMediaUrl(variantImg) || variantImg;

    // Try to find by pathname comparison (handles host differences)
    const foundIdx = images.findIndex(img => {
      try {
        const a = new URL(img); const b = new URL(resolved);
        return a.pathname === b.pathname;
      } catch { return img === resolved; }
    });

    if (foundIdx !== -1) {
      switchImage(foundIdx);
    } else {
      // Add the variant image dynamically if not in gallery
      setImages(prev => [...prev, resolved]);
      setTimeout(() => setActiveImageIndex(images.length), 0);
    }
  }

  const displayPriceMinor = selectedVariant?.price_minor
    || selectedVariant?.retail_price_minor
    || product.price_minor
    || product.retail_price_minor
    || 0;

  const currentFormattedPrice = hydrated
    ? formatPrice(displayPriceMinor, product.currency || currency)
    : ssrFormattedPrice;

  const originalPriceFormatted = hydrated
    ? formatPrice(Math.round(displayPriceMinor * 1.4), product.currency || currency)
    : 'EGP 900';

  const isOOS = product.is_available === false || (product.inventory !== undefined && product.inventory <= 0);

  function switchImage(index: number) {
    if (index === activeImageIndex) return;
    setAnimating(true);
    setActiveImageIndex(index);
    setTimeout(() => setAnimating(false), 400);
  }

  async function handleShare() {
    const shareUrl = typeof window !== 'undefined' ? window.location.href : '';
    const shareData = {
      title: `${product.title} | ${storeName}`,
      text: `${product.title} - Available for order now on ${storeName}`,
      url: shareUrl,
    };

    if (typeof navigator !== 'undefined' && navigator.share && navigator.canShare && navigator.canShare(shareData)) {
      try { await navigator.share(shareData); return; } catch {}
    }

    if (typeof navigator !== 'undefined' && navigator.clipboard) {
      try {
        await navigator.clipboard.writeText(shareUrl);
        setCopied(true);
        setTimeout(() => setCopied(false), 2500);
      } catch {}
    }
  }

  async function handleAddToCart() {
    if (adding || isOOS) return;
    setAdding(true);
    try {
      await addToCart(product.id || product.slug, selectedVariant?.id ?? null, quantity);
    } catch (err) {
      console.error(err);
    } finally {
      setAdding(false);
    }
  }

  const accordions = [];
  if (product.description) {
    accordions.push({ title: 'Product Details & Craftsmanship', content: product.description });
  }
  if (product.attributes_json && Object.keys(product.attributes_json).length > 0) {
    const attrs = Object.entries(product.attributes_json).map(([k, v]) => `• ${k}: ${v}`).join('\n');
    accordions.push({ title: 'Specifications & Materials', content: attrs });
  }
  accordions.push(
    { title: 'Fast Door-to-Door Delivery across Egypt', content: 'Express delivery to Cairo, Giza, Alexandria & all Governorates within 24–48 hours. Cash on Delivery (COD) and Paymob online cards accepted.' },
    { title: '2-Year Craftsmanship Warranty', content: 'Full 2-year warranty against any leather craftsmanship defects, stitching, or magnetic hardware.' }
  );

  return (
    <div className="bg-[#faf8f5] min-h-screen pt-16 md:pt-[72px] text-stone-900">
      {/* Breadcrumb Navigation & Top Toolbar */}
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-12 py-3.5 border-b border-stone-200/80 flex items-center justify-between gap-4">
        <nav className="flex items-center gap-2 text-xs font-display uppercase tracking-wider text-stone-500 min-w-0">
          <Link href="/" className="hover:text-[#c2410c] transition-colors shrink-0 font-medium">Home</Link>
          <span className="text-stone-300 shrink-0">/</span>
          <Link href="/collections/all" className="hover:text-[#c2410c] transition-colors shrink-0 font-medium">Catalog</Link>
          <span className="text-stone-300 shrink-0">/</span>
          <span className="text-stone-900 font-bold truncate">{product.title}</span>
        </nav>

        {/* Top Share Button */}
        <button
          type="button"
          onClick={handleShare}
          className="flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-stone-300 hover:border-stone-900 bg-white text-stone-700 hover:text-black text-xs font-display font-bold uppercase tracking-wider transition-all shadow-2xs cursor-pointer active:scale-95 shrink-0"
          title="Share product link"
        >
          <svg className="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2.5}>
            <path strokeLinecap="round" strokeLinejoin="round" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
          </svg>
          <span>{copied ? 'Link Copied! ✓' : 'Share'}</span>
        </button>
      </div>

      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-12 py-8 lg:py-12">
        <div className="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 items-start">

          {/* ── Left Column: Animated Luxury Product Gallery ── */}
          <div className="lg:col-span-7 space-y-4">
            {/* Main Active Image */}
            <div className="relative w-full aspect-square sm:aspect-[4/3] md:aspect-square bg-stone-100 rounded-2xl overflow-hidden border border-stone-200 shadow-sm group">
              <img
                key={activeImageIndex}
                src={images[activeImageIndex] || images[0]}
                alt={`${product.title} view ${activeImageIndex + 1}`}
                onError={(e) => {
                  (e.target as HTMLImageElement).src = 'https://images.unsplash.com/photo-1627123424574-724758594e93?w=1000&q=85';
                }}
                className={`w-full h-full object-cover object-center group-hover:scale-105 transition-all duration-500 ${
                  animating ? 'opacity-0 scale-95' : 'opacity-100 scale-100'
                }`}
              />

              {/* In-Stock / Sale Badges */}
              <div className="absolute top-3.5 left-3.5 flex flex-col gap-1.5 z-10">
                {!isOOS ? (
                  <span className="bg-emerald-600/90 backdrop-blur-md text-white text-[10px] font-display font-bold uppercase tracking-wider px-2.5 py-1 rounded-md shadow-xs">
                    In Stock
                  </span>
                ) : (
                  <span className="bg-rose-600/90 backdrop-blur-md text-white text-[10px] font-display font-bold uppercase tracking-wider px-2.5 py-1 rounded-md shadow-xs">
                    Sold Out
                  </span>
                )}
              </div>

              {/* Prev / Next Buttons */}
              {images.length > 1 && (
                <>
                  <button
                    type="button"
                    onClick={() => switchImage(activeImageIndex === 0 ? images.length - 1 : activeImageIndex - 1)}
                    className="absolute left-3.5 top-1/2 -translate-y-1/2 w-10 h-10 rounded-full bg-white/90 backdrop-blur-md text-stone-800 hover:bg-white hover:text-black hover:scale-110 active:scale-95 flex items-center justify-center shadow-lg transition-all opacity-0 group-hover:opacity-100 cursor-pointer z-20"
                    aria-label="Previous photo"
                  >←</button>
                  <button
                    type="button"
                    onClick={() => switchImage(activeImageIndex === images.length - 1 ? 0 : activeImageIndex + 1)}
                    className="absolute right-3.5 top-1/2 -translate-y-1/2 w-10 h-10 rounded-full bg-white/90 backdrop-blur-md text-stone-800 hover:bg-white hover:text-black hover:scale-110 active:scale-95 flex items-center justify-center shadow-lg transition-all opacity-0 group-hover:opacity-100 cursor-pointer z-20"
                    aria-label="Next photo"
                  >→</button>
                </>
              )}
            </div>

            {/* Thumbnail Strip */}
            {images.length > 1 && (
              <div className="flex gap-3 overflow-x-auto pb-2 scrollbar-none">
                {images.map((imgUrl, idx) => (
                  <button
                    key={idx}
                    type="button"
                    onClick={() => switchImage(idx)}
                    className={`relative w-20 h-20 rounded-xl overflow-hidden border-2 shrink-0 transition-all duration-300 cursor-pointer bg-stone-100 ${
                      activeImageIndex === idx
                        ? 'border-[#c2410c] ring-2 ring-[#c2410c]/30 scale-105 shadow-sm'
                        : 'border-stone-200 hover:border-stone-400 opacity-60 hover:opacity-100'
                    }`}
                  >
                    <img
                      src={imgUrl}
                      alt=""
                      className="w-full h-full object-cover"
                      onError={(e) => {
                        (e.target as HTMLElement).parentElement?.classList.add('hidden');
                      }}
                    />
                  </button>
                ))}
              </div>
            )}
          </div>

          {/* ── Right Column: Product Purchase Panel ── */}
          <div className="lg:col-span-5 bg-white border border-stone-200 rounded-2xl p-6 sm:p-8 space-y-6 shadow-xs sticky lg:top-24">
            {/* Header / Brand */}
            <div>
              <span className="font-display font-bold text-xs uppercase tracking-wider text-[#c2410c] block mb-1">
                {storeName}
              </span>
              <h1 className="font-display font-black uppercase text-2xl sm:text-3xl text-stone-900 tracking-tight leading-tight">
                {product.title}
              </h1>
            </div>

            {/* Rating Stars */}
            <div className="flex items-center gap-2 text-xs text-amber-500 font-semibold border-b border-stone-100 pb-4">
              <span className="tracking-tight text-amber-500 text-sm">★★★★★</span>
              <span className="text-stone-800 font-bold">5.0</span>
              <span className="text-stone-400">·</span>
              <span className="text-stone-600 font-medium">Verified Craftsmanship Reviews</span>
            </div>

            {/* Pricing Section */}
            <div className="flex items-baseline gap-3">
              <span className="font-display font-black text-3xl text-stone-950">
                {currentFormattedPrice}
              </span>
              <span className="font-display font-semibold text-base text-stone-400 line-through">
                {originalPriceFormatted}
              </span>
              <span className="text-[10px] font-display font-bold uppercase tracking-wider bg-rose-50 text-rose-700 px-2 py-0.5 rounded border border-rose-200">
                Save 30%
              </span>
            </div>

            {/* Short Description */}
            <p className="text-xs sm:text-sm text-stone-600 leading-relaxed">
              {product.description || 'Precision crafted with full-grain leather and aerospace hardware. Designed for maximum everyday elegance and durability.'}
            </p>

            {/* Variants Selector (Colors / Sizes / Finishes) */}
            {product.variants && product.variants.length > 0 && (
              <div className="space-y-2 pt-2 border-t border-stone-100">
                <label className="font-display font-bold text-xs uppercase tracking-wider text-stone-800 block">
                  Select Color / Option: <strong className="text-[#c2410c]">{selectedVariant?.title || 'Standard'}</strong>
                </label>
                <div className="flex flex-wrap gap-2.5">
                  {product.variants.map((v: any) => {
                    const hex = v.color_hex || v.attributes?.color_hex || v.attributes_json?.color_hex;
                    const isSelected = selectedVariant?.id === v.id;
                    return (
                      <button
                        key={v.id}
                        type="button"
                        onClick={() => handleSelectVariant(v)}
                        className={`px-3.5 py-2 rounded-xl text-xs font-display font-bold uppercase tracking-wider transition-all cursor-pointer border flex items-center gap-2 ${
                          isSelected
                            ? 'border-[#c2410c] bg-[#c2410c] text-amber-50 shadow-xs'
                            : 'border-stone-200 bg-stone-50 text-stone-800 hover:border-stone-400 hover:bg-white'
                        }`}
                      >
                        {hex && (
                          <span
                            className="w-3.5 h-3.5 rounded-full border border-black/20 shrink-0 shadow-2xs"
                            style={{ backgroundColor: hex }}
                          />
                        )}
                        <span>{v.title}</span>
                      </button>
                    );
                  })}
                </div>
              </div>
            )}

            {/* Quantity Selector */}
            <div className="flex items-center gap-4 pt-1">
              <span className="font-display font-bold text-xs uppercase tracking-wider text-stone-700">Quantity</span>
              <div className="flex items-center border border-stone-300 bg-stone-50 rounded-xl overflow-hidden shadow-2xs">
                <button
                  type="button"
                  onClick={() => setQuantity(Math.max(1, quantity - 1))}
                  className="w-9 h-9 flex items-center justify-center font-bold text-base text-stone-700 hover:bg-stone-200 transition-colors cursor-pointer"
                >−</button>
                <span className="w-10 text-center font-display font-bold text-sm text-stone-900">{quantity}</span>
                <button
                  type="button"
                  onClick={() => setQuantity(Math.min(10, quantity + 1))}
                  className="w-9 h-9 flex items-center justify-center font-bold text-base text-stone-700 hover:bg-stone-200 transition-colors cursor-pointer"
                >+</button>
              </div>
            </div>

            {/* Primary Action Buttons */}
            <div className="space-y-2.5 pt-2">
              <button
                type="button"
                disabled={isOOS || adding}
                onClick={handleAddToCart}
                className="w-full bg-[#c2410c] hover:bg-[#9a3412] text-amber-50 font-display font-black text-xs sm:text-sm uppercase tracking-wider py-4 rounded-xl shadow-md transition-all active:scale-98 disabled:opacity-30 flex items-center justify-center gap-3 cursor-pointer"
              >
                {adding ? (
                  <>
                    <div className="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin" />
                    <span>Adding to Bag...</span>
                  </>
                ) : !isOOS ? (
                  <span>Add to Bag — {currentFormattedPrice}</span>
                ) : (
                  <span>Sold Out</span>
                )}
              </button>

              {!isOOS && (
                <Link
                  href="/checkout"
                  onClick={async () => {
                    await addToCart(product.id || product.slug, selectedVariant?.id ?? null, quantity);
                  }}
                  className="block w-full border-2 border-stone-900 hover:bg-stone-900 hover:text-white bg-white text-stone-900 font-display font-black text-xs uppercase tracking-wider py-3.5 text-center rounded-xl transition-all shadow-xs active:scale-98 cursor-pointer"
                >
                  Buy with Cash on Delivery / Paymob →
                </Link>
              )}
            </div>

            {/* Trust Badges */}
            <div className="grid grid-cols-2 gap-2 pt-2 border-t border-stone-100 text-[11px] text-stone-600">
              <div className="flex items-center gap-2 p-2 bg-stone-50 rounded-lg">
                <span className="text-base">⚡</span>
                <span className="font-medium">24–48h Dispatch in Egypt</span>
              </div>
              <div className="flex items-center gap-2 p-2 bg-stone-50 rounded-lg">
                <span className="text-base">🛡️</span>
                <span className="font-medium">2-Year Craft Warranty</span>
              </div>
            </div>

            {/* Accordions */}
            <div className="border-t border-stone-200 divide-y divide-stone-200 pt-2">
              {accordions.map(({ title, content }) => (
                <details key={title} className="group py-3">
                  <summary className="flex items-center justify-between cursor-pointer list-none select-none hover:text-[#c2410c] transition-colors">
                    <span className="font-display font-bold text-xs uppercase tracking-wider text-stone-800">{title}</span>
                    <span className="text-[#c2410c] font-display font-black text-base leading-none transition-transform group-open:rotate-45">+</span>
                  </summary>
                  <div className="pt-2 text-xs text-stone-600 leading-relaxed whitespace-pre-wrap font-normal">
                    {content}
                  </div>
                </details>
              ))}
            </div>
          </div>
        </div>
      </div>

      {/* Customer Reviews Section */}
      <ProductReviews productSlug={product.slug} productTitle={product.title} />
    </div>
  );
}
