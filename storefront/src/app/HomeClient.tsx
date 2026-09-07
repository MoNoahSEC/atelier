'use client';

import { useEffect, useState, useRef } from 'react';
import Link from 'next/link';
import { ProductCard } from '@/components/ui/ProductCard';
import MagneticElement from '@/components/ui/MagneticElement';
import { useStore } from '@/components/providers/StoreProvider';
import Hero3D from '@/components/ui/Hero3D';
import HeroShowcase from '@/components/home/HeroShowcase';
import { getApiUrl } from '@/lib/api';

import ProductScrollShowcase from '@/components/home/ProductScrollShowcase';
import MinimalHero from '@/components/home/MinimalHero';

interface HomeClientProps {
  initialProducts: any[];
  initialCollections: any[];
}

export default function HomeClient({ initialProducts, initialCollections }: HomeClientProps) {
  const { storeName, settings } = useStore();
  const [products, setProducts] = useState<any[]>(initialProducts);
  const [collections] = useState<any[]>(initialCollections);
  const [loadingProducts, setLoadingProducts] = useState(false);
  const bannerRef = useRef<HTMLElement>(null);

  const guaranteeTitle = settings?.guaranteeTitle || 'The ATELIER Guarantee';
  const guaranteeSubtitle = settings?.guaranteeSubtitle || '100% Genuine Italian & Egyptian Leather · 2-Year Craftsmanship Warranty · Fast Door-to-Door Delivery across Egypt';
  const guaranteeImage = settings?.guaranteeImage || 'https://images.unsplash.com/photo-1548036328-c9fa89d128fa?w=1600&q=80';

  useEffect(() => {
    if (products.length === 0) {
      setLoadingProducts(true);
      fetch(`${getApiUrl()}/public/catalog?per_page=8`)
        .then(r => r.ok ? r.json() : null)
        .then(data => {
          if (data?.data?.length > 0) setProducts(data.data);
        })
        .catch(() => {})
        .finally(() => setLoadingProducts(false));
    }
  }, []);

  return (
    <div className="bg-[#f8f7f4] min-h-screen text-stone-900 pt-16 md:pt-[72px]">

      {/* ── Minimalist Luxury Hero Section (Matching Focus Standard / Wix Template) ── */}
      <MinimalHero featuredProduct={products[0]} />

      {/* ── Category Quick-Nav Rail (Specialized in E-Wallets on Off-White Background) ── */}
      <section className="bg-[#f3f2ee] py-8 md:py-12 border-b border-[#e6e4dc] overflow-hidden">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-12">
          <div className="flex items-center justify-between mb-6">
            <div>
              <span className="font-display font-bold text-[11px] uppercase tracking-wider text-[#fb923c] block mb-0.5">Explore E-Wallets</span>
              <h2 className="font-display font-black uppercase text-lg md:text-2xl tracking-tight text-stone-950">
                Shop By Category
              </h2>
            </div>
            <Link href="/collections/all" className="font-display font-bold text-xs uppercase tracking-wider text-[#fb923c] hover:underline flex items-center gap-1">
              <span>View All</span>
              <span>→</span>
            </Link>
          </div>

          <div className="flex items-center gap-4 md:gap-7 overflow-x-auto pb-4 scrollbar-none snap-x">
            {[
              { name: 'Executive Gift Sets', image: 'https://images.unsplash.com/photo-1548036328-c9fa89d128fa?w=400&auto=format&fit=crop&q=80', slug: 'bundles-gift-sets', count: 'Luxury Box' },
              { name: 'Minimalist Money Clips', image: 'https://images.unsplash.com/photo-1591561954557-26941169b49e?w=400&auto=format&fit=crop&q=80', slug: 'money-clips', count: '3K Carbon Fiber' },
              { name: 'Travel & Passport', image: 'https://images.unsplash.com/photo-1544816155-12df9643f363?w=400&auto=format&fit=crop&q=80', slug: 'travel-wallets', count: 'RFID Passport' },
              { name: 'MagSafe & Battery', image: 'https://images.unsplash.com/photo-1607604276583-eef5d076aa5f?w=400&auto=format&fit=crop&q=80', slug: 'powerbank-wallets', count: 'Magnetic Qi' },
              { name: 'All Collections', image: 'https://images.unsplash.com/photo-1627123424574-724758594e93?w=400&auto=format&fit=crop&q=80', slug: 'all', count: 'Full Catalog' },
            ].map((cat) => (
              <Link
                key={cat.slug}
                href={`/collections/${cat.slug}`}
                className="group flex flex-col items-center shrink-0 snap-start transition-all"
              >
                <div className="w-20 h-20 md:w-24 md:h-24 rounded-none overflow-hidden border-2 border-stone-300 group-hover:border-[#fb923c] shadow-sm transition-all duration-300 group-hover:scale-105 relative bg-white">
                  <img
                    src={cat.image}
                    alt={cat.name}
                    className="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500 rounded-none"
                  />
                  <div className="absolute inset-0 bg-stone-900/5 group-hover:bg-transparent transition-colors" />
                </div>
                <span className="font-display font-bold text-xs uppercase tracking-wider text-stone-800 mt-2.5 group-hover:text-[#fb923c] transition-colors text-center">
                  {cat.name}
                </span>
                <span className="text-[10px] font-semibold text-stone-500 mt-0.5">
                  {cat.count}
                </span>
              </Link>
            ))}
          </div>
        </div>
      </section>

      {/* ── Vertical 0-Gap Product Scroll Showcase (One under another, full background & animations) ── */}
      <section className="w-full p-0 m-0 bg-[#f8f7f4]">
        <div className="w-full bg-[#f8f7f4] py-10 px-4 sm:px-6 lg:px-12 border-b border-[#e6e4dc] flex items-end justify-between">
          <div>
            <span className="font-display font-bold text-xs uppercase tracking-[0.25em] text-[#fb923c] block mb-1">
              Bespoke EDC Collection
            </span>
            <h2 className="font-display font-black uppercase text-3xl sm:text-4xl md:text-5xl leading-none tracking-tight text-stone-950">
              Signature E-Wallets
            </h2>
          </div>
          <Link
            href="/collections/all"
            className="flex items-center gap-2 font-display font-bold text-xs sm:text-sm uppercase tracking-wider text-stone-600 hover:text-[#fb923c] transition-colors"
          >
            <span className="hidden sm:inline">View Full Catalog</span>
            <span className="sm:hidden">All</span>
            <span>→</span>
          </Link>
        </div>

        {products.length > 0 ? (
          <ProductScrollShowcase products={products} />
        ) : loadingProducts ? (
          <div className="w-full min-h-[60vh] flex flex-col items-center justify-center py-20 text-stone-400 gap-4">
            <div className="w-10 h-10 border-3 border-[#fb923c] border-t-transparent rounded-full animate-spin" />
            <p className="font-display font-bold uppercase text-xs tracking-wider text-stone-600">Loading Signature Collection...</p>
          </div>
        ) : (
          <div className="w-full py-20 text-center text-stone-500">
            <p className="font-display font-bold uppercase text-xs tracking-wider">No Products Found</p>
          </div>
        )}
      </section>

      {/* ── Collections Grid ── */}
      <section className="px-4 sm:px-6 lg:px-12 py-12 md:py-20 border-t border-[#e6e4dc] bg-[#f3f2ee]">
        <div className="max-w-7xl mx-auto">
          <div className="flex items-end justify-between mb-8 md:mb-12">
            <div>
              <span className="font-display font-bold text-xs uppercase tracking-wider text-[#fb923c] block mb-1">
                Curated Series
              </span>
              <h2 className="font-display font-black uppercase text-3xl sm:text-4xl md:text-5xl leading-none tracking-tight text-stone-950">
                Shop By Collection
              </h2>
            </div>
            <Link href="/collections/all" className="font-display font-bold text-xs sm:text-sm uppercase tracking-wider text-[#fb923c] hover:underline flex items-center gap-1">
              <span>View All</span>
              <span>→</span>
            </Link>
          </div>

          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5 md:gap-7">
            {(collections.length > 0 ? collections : [
              { title: 'MagSafe Smart Wallets', slug: 'magsafe-wallets', image_url: 'https://images.unsplash.com/photo-1627123424574-724758594e93?w=1000&q=85', description: 'Double-Strength N52 Magnetic Snap' },
              { title: 'Pop-Up RFID Cardholders', slug: 'rfid-cardholders', image_url: 'https://images.unsplash.com/photo-1607604276583-eef5d076aa5f?w=1000&q=85', description: 'Instant Quick-Flick Ejector Mechanism' },
              { title: 'Slim Leather Bifolds', slug: 'leather-wallets', image_url: 'https://images.unsplash.com/photo-1553062407-98eeb64c6a62?w=1000&q=85', description: 'Hand-Crafted Tuscan Vegetable-Tanned Calfskin' },
              { title: 'Minimalist Money Clips', slug: 'money-clips', image_url: 'https://images.unsplash.com/photo-1591561954557-26941169b49e?w=1000&q=85', description: '3K Real Carbon Fiber & High-Tension Strap' },
              { title: 'Passport & Travel Wallets', slug: 'travel-wallets', image_url: 'https://images.unsplash.com/photo-1544816155-12df9643f363?w=1000&q=85', description: 'Passports, Cards, SIM Tray & Cash Organizer' },
              { title: 'MagSafe Battery Wallets', slug: 'powerbank-wallets', image_url: 'https://images.unsplash.com/photo-1607604276583-eef5d076aa5f?w=1000&q=85', description: '5,000mAh Magnetic Wireless Powerbank' },
              { title: 'Executive Gift Sets', slug: 'bundles-gift-sets', image_url: 'https://images.unsplash.com/photo-1548036328-c9fa89d128fa?w=1000&q=85', description: 'Curated Complete EDC Executive Gift Box' },
            ]).map((col: any) => {
              return (
                <Link
                  key={col.slug || col.id}
                  href={`/collections/${col.slug}`}
                  className="group relative overflow-hidden rounded-none aspect-4/3 flex flex-col justify-end p-6 border-2 border-stone-300 hover:border-[#fb923c] transition-all duration-300 shadow-md hover:shadow-xl bg-stone-900"
                >
                  <img
                    src={col.image_url || 'https://images.unsplash.com/photo-1627123424574-724758594e93?w=1000&q=85'}
                    alt={col.title}
                    className="absolute inset-0 w-full h-full object-cover group-hover:scale-108 transition-transform duration-700 ease-out"
                  />
                  <div className="absolute inset-0 bg-gradient-to-t from-stone-950 via-stone-950/60 to-transparent" />
                  <div className="relative z-10">
                    <h3 className="font-display font-black uppercase text-xl text-white tracking-tight leading-none group-hover:text-[#fb923c] transition-colors">
                      {col.title}
                    </h3>
                    {col.description && (
                      <p className="text-xs text-stone-300 font-normal mt-1 line-clamp-1">
                        {col.description}
                      </p>
                    )}
                    <div className="mt-3 flex items-center gap-2 font-display font-bold text-xs uppercase tracking-wider text-[#fb923c] group-hover:text-white transition-colors">
                      <span>Explore Collection</span>
                      <span className="text-sm group-hover:translate-x-1 transition-transform">→</span>
                    </div>
                  </div>
                </Link>
              );
            })}
          </div>
        </div>
      </section>

      {/* Bottom Brand Banner with Dynamic Photo & Text on Off-White */}
      <section
        ref={bannerRef}
        className="px-5 lg:px-16 py-16 md:py-24 border-t border-[#e6e4dc] relative overflow-hidden bg-[#edece7] text-stone-900"
      >
        {guaranteeImage && (
          <img
            src={guaranteeImage}
            alt="Guarantee background"
            className="absolute inset-0 w-full h-full object-cover opacity-10 pointer-events-none filter blur-2xs"
          />
        )}
        <div className="absolute inset-0 bg-gradient-to-r from-[#edece7] via-[#edece7]/90 to-[#edece7]/70 pointer-events-none" />

        <div className="max-w-7xl mx-auto flex flex-col md:flex-row items-start md:items-center justify-between gap-8 relative z-10">
          <div>
            <p className="font-display font-bold text-xs uppercase tracking-wider text-[#fb923c] mb-2">
              {guaranteeTitle}
            </p>
            <h2 className="font-display font-black uppercase text-3xl md:text-5xl lg:text-6xl leading-tight tracking-tight text-stone-950 max-w-2xl">
              Quality Engineered For Lifetime Utility.
            </h2>
            <p className="text-stone-600 font-normal text-sm md:text-base mt-3 max-w-xl">
              {guaranteeSubtitle}
            </p>
          </div>
          <MagneticElement className="shrink-0">
            <Link
              href="/collections/all"
              className="inline-block bg-stone-900 hover:bg-black text-white font-display font-black text-sm md:text-base uppercase tracking-wider px-8 py-4.5 rounded-none shadow-xl hover:shadow-2xl active:scale-95 transition-all cursor-pointer"
            >
              Shop All Accessories →
            </Link>
          </MagneticElement>
        </div>
      </section>

    </div>
  );
}
