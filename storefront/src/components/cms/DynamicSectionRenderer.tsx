'use client';

import React from 'react';
import Link from 'next/link';
import dynamic from 'next/dynamic';
import { SafeImage } from '@/components/ui/SafeImage';
import { useStore } from '@/components/providers/StoreProvider';
import { getApiUrl } from '@/lib/api';

// Lazy load heavy client components
const ClientModelViewer = dynamic(() => import('./ClientModelViewer'), { ssr: false });
const ClientVideoPlayer = dynamic(() => import('./ClientVideoPlayer'), { ssr: false });

interface SectionProps {
  section: any;
}

export default function DynamicSectionRenderer({ section }: SectionProps) {
  const { type, settings } = section;

  switch (type) {
    case 'hero':
      return <HeroSection section={section} />;
    case 'product_carousel':
      return <ProductCarouselSection section={section} />;
    case 'video_feature':
      return <VideoFeatureSection section={section} />;
    case '3d_showcase':
      return <Model3DShowcase section={section} />;
    default:
      return (
        <div className="py-20 text-center text-white/40">
          Unknown section type: {type}
        </div>
      );
  }
}

// ─── Hero Section ────────────────────────────────────────────────────────
function HeroSection({ section }: { section: any }) {
  const { storeName } = useStore();
  const { title, subtitle, cta_text, cta_link, bg_image } = section.settings;
  const imageUrl = section.media_assets?.[0]?.url || bg_image;

  return (
    <section className="relative h-screen min-h-[600px] flex items-center justify-center overflow-hidden">
      {imageUrl && (
        <SafeImage src={imageUrl} alt={title || 'Hero'} fill sizes="100vw" className="object-cover opacity-60" priority />

      )}
      <div className="absolute inset-0 bg-gradient-to-t from-black via-black/20 to-transparent" />
      <div className="relative z-10 text-center px-6 max-w-4xl mx-auto flex flex-col items-center">
        <h1 className="font-display font-black text-6xl md:text-8xl lg:text-[10rem] leading-[0.85] tracking-tighter uppercase text-white mb-6 animate-slideUp text-glow">
          {title || storeName}
        </h1>
        {subtitle && (
          <p className="text-sm md:text-lg text-white/70 max-w-lg mb-10 animate-fadeIn delay-300">
            {subtitle}
          </p>
        )}
        {cta_text && cta_link && (
          <Link
            href={cta_link}
            className="group relative inline-flex items-center justify-center px-8 py-4 bg-white text-black font-display font-bold uppercase tracking-[0.2em] text-xs hover:bg-[#e8ff47] transition-colors duration-300 animate-slideUp delay-500 overflow-hidden"
          >
            <span className="relative z-10 flex items-center gap-2">
              {cta_text}
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" className="group-hover:translate-x-1 transition-transform">
                <path d="M5 12h14m-7-7 7 7-7 7"/>
              </svg>
            </span>
          </Link>
        )}
      </div>
    </section>
  );
}

// ─── Product Carousel ────────────────────────────────────────────────────
function ProductCarouselSection({ section }: { section: any }) {
  const { settings } = section;
  const [products, setProducts] = React.useState<any[]>([]);
  
  React.useEffect(() => {
    fetch(`${getApiUrl()}/public/catalog`)
      .then(r => r.json())
      .then(d => setProducts(d.data?.slice(0, 8) || []))
      .catch(console.error);
  }, []);

  return (
    <section className="py-24 md:py-32 bg-[#0a0a0a]">
      <div className="container mx-auto px-6">
        <div className="flex justify-between items-end mb-16">
          <h2 className="font-display font-black text-4xl md:text-5xl uppercase tracking-tight text-white">
            {settings.title || 'Featured'}
          </h2>
        </div>
        <div className="flex gap-6 overflow-x-auto pb-8 snap-x scrollbar-hide">
          {products.map(p => (
            <Link key={p.slug} href={`/products/${p.slug}`} className="min-w-[280px] w-[280px] group cursor-pointer snap-start flex flex-col block">
              <div className="aspect-[4/5] bg-white/5 relative overflow-hidden mb-4 border border-white/10 group-hover:border-white/30 transition-colors">
                {p.image_url && <SafeImage src={p.image_url} alt={p.title} fill sizes="(max-width: 768px) 100vw, 280px" className="object-cover group-hover:scale-105 transition-transform duration-700" />}

              </div>
              <h3 className="font-display font-bold uppercase tracking-widest text-sm text-white mb-1 truncate">{p.title}</h3>
              <p className="text-white/50 text-xs font-mono uppercase tracking-widest">
                 {new Intl.NumberFormat('en-US', { style: 'currency', currency: p.currency || 'USD', minimumFractionDigits: 0 }).format((p.price_minor || 0) / 100)}
              </p>
            </Link>
          ))}
          {!products.length && (
            <div className="min-w-[300px] h-[400px] bg-white/5 border border-white/10 flex items-center justify-center text-white/30 text-xs font-display uppercase tracking-widest">
              Loading Products...
            </div>
          )}
        </div>
      </div>
    </section>
  );
}

// ─── Video Feature ───────────────────────────────────────────────────────
function VideoFeatureSection({ section }: { section: any }) {
  const { settings } = section;
  const videoUrl = section.media_assets?.[0]?.url || settings.video_url;

  return (
    <section className="py-0 relative h-[80vh]">
      <ClientVideoPlayer 
        src={videoUrl} 
        autoplay={settings.autoplay ?? true}
        loop={settings.loop ?? true}
        muted={settings.muted ?? true}
        className="absolute inset-0 w-full h-full object-cover" 
      />
      <div className="absolute inset-0 bg-black/40 flex items-center justify-center pointer-events-none">
        <h2 className="font-display font-black text-5xl uppercase tracking-tight text-white drop-shadow-lg">
          {settings.title}
        </h2>
      </div>
    </section>
  );
}

// ─── 3D Showcase ─────────────────────────────────────────────────────────
function Model3DShowcase({ section }: { section: any }) {
  const { settings } = section;
  const modelUrl = section.media_assets?.[0]?.url || settings.model_url;

  const { storeName } = useStore();

  return (
    <section className="py-24 bg-[#111] overflow-hidden">
      <div className="container mx-auto px-6 grid md:grid-cols-2 gap-12 items-center">
        <div>
          <h2 className="font-display font-black text-4xl md:text-6xl uppercase tracking-tighter text-white mb-6">
            {settings.title || 'Interactive 3D'}
          </h2>
          <p className="text-white/60 mb-8 max-w-md">
            {settings.description || 'Explore the details in 3D.'}
          </p>
        </div>
        <div className="h-[500px] w-full bg-black/50 rounded-2xl overflow-hidden relative">
           <ClientModelViewer 
             src={modelUrl}
             poster={settings.poster_image}
             alt={settings.title}
           />
        </div>
      </div>
    </section>
  );
}
