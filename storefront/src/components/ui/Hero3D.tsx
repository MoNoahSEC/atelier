'use client';

import { useEffect, useRef } from 'react';
import { useStore } from '@/components/providers/StoreProvider';
import Link from 'next/link';

export default function Hero3D() {
  const { storeName, settings } = useStore();
  const sectionRef = useRef<HTMLElement>(null);
  const rafRef = useRef<number>(0);
  const targetMouse = useRef({ x: 0, y: 0 });
  const currentMouse = useRef({ x: 0, y: 0 });
  const glowOrbRef = useRef<HTMLDivElement>(null);
  const ringsContainerRef = useRef<HTMLDivElement>(null);

  const heroImage = settings?.homeHeroImage || 'https://images.unsplash.com/photo-1620138290379-3d12234551d0?w=1800&q=85';
  const heroTitle = settings?.homeHeroTitle || 'WEAR YOUR STORY';
  const heroSubtitle = settings?.homeHeroSubtitle || 'Egyptian luxury leather cases, Apple Watch bands, MagSafe wallets & fast charging accessories.';
  const heroCtaText = settings?.homeHeroCtaText || 'Explore Catalog';
  const heroCtaLink = settings?.homeHeroCtaLink || '/collections/all';
  const bgColor = settings?.homeBgColor || '#faf8f5';
  const bgImage = settings?.homeBgImage || '';

  useEffect(() => {
    let isVisible = true;
    const rafObserver = new IntersectionObserver(
      (entries) => { isVisible = entries[0].isIntersecting; },
      { threshold: 0 }
    );
    if (sectionRef.current) rafObserver.observe(sectionRef.current);

    const handleMouse = (e: MouseEvent) => {
      if (!isVisible) return;
      targetMouse.current = {
        x: (e.clientX / window.innerWidth - 0.5) * 2,
        y: (e.clientY / window.innerHeight - 0.5) * 2,
      };
    };

    const handleTouch = (e: TouchEvent) => {
      if (!isVisible || !e.touches[0]) return;
      targetMouse.current = {
        x: (e.touches[0].clientX / window.innerWidth - 0.5) * 2,
        y: (e.touches[0].clientY / window.innerHeight - 0.5) * 2,
      };
    };

    let idleTime = 0;
    const lerp = (a: number, b: number, t: number) => a + (b - a) * t;
    const tick = () => {
      if (isVisible) {
        idleTime += 0.02;
        // Ambient subtle breathing motion when idle
        const ambientX = Math.sin(idleTime * 0.5) * 0.15;
        const ambientY = Math.cos(idleTime * 0.4) * 0.15;

        currentMouse.current.x = lerp(currentMouse.current.x, targetMouse.current.x + ambientX, 0.06);
        currentMouse.current.y = lerp(currentMouse.current.y, targetMouse.current.y + ambientY, 0.06);
        if (sectionRef.current) {
          sectionRef.current.style.setProperty('--mx', currentMouse.current.x.toFixed(4));
          sectionRef.current.style.setProperty('--my', currentMouse.current.y.toFixed(4));
        }
      }
      rafRef.current = requestAnimationFrame(tick);
    };

    window.addEventListener('mousemove', handleMouse, { passive: true });
    window.addEventListener('touchmove', handleTouch, { passive: true });
    rafRef.current = requestAnimationFrame(tick);

    return () => {
      window.removeEventListener('mousemove', handleMouse);
      window.removeEventListener('touchmove', handleTouch);
      cancelAnimationFrame(rafRef.current);
      rafObserver.disconnect();
    };
  }, []);

  useEffect(() => {
    const targets = [glowOrbRef.current, ringsContainerRef.current].filter(Boolean) as HTMLElement[];
    if (targets.length === 0) return;

    const cssObserver = new IntersectionObserver(
      ([entry]) => {
        const state = entry.isIntersecting ? 'running' : 'paused';
        targets.forEach((el) => {
          el.style.animationPlayState = state;
          el.querySelectorAll<HTMLElement>('[class*="animate-"]').forEach((child) => {
            child.style.animationPlayState = state;
          });
        });
      },
      { threshold: 0 }
    );

    if (sectionRef.current) cssObserver.observe(sectionRef.current);
    return () => cssObserver.disconnect();
  }, []);

  return (
    <section
      ref={sectionRef}
      className="relative min-h-[85vh] md:min-h-screen flex flex-col justify-center items-center overflow-hidden perspective-1200 will-change-transform border-b border-stone-200/80 py-16 md:py-0"
      style={{
        backgroundColor: bgColor || '#faf8f5',
        backgroundImage: bgImage ? `url(${bgImage})` : undefined,
        backgroundSize: 'cover',
        backgroundPosition: 'center',
        ['--mx' as any]: 0,
        ['--my' as any]: 0,
      }}
    >
      {/* Background Ambience Image */}
      {heroImage && (
        <div className="absolute inset-0 pointer-events-none overflow-hidden opacity-25">
          <img
            src={heroImage}
            alt="Hero background"
            className="w-full h-full object-cover scale-105 filter blur-xs"
          />
          <div className="absolute inset-0 bg-gradient-to-t from-[#faf8f5] via-[#faf8f5]/80 to-transparent" />
        </div>
      )}

      {/* Subtle Grid */}
      <div
        className="absolute inset-0 opacity-[0.05] pointer-events-none"
        style={{
          backgroundImage:
            'linear-gradient(rgba(15,23,42,0.8) 1px, transparent 1px), linear-gradient(90deg, rgba(15,23,42,0.8) 1px, transparent 1px)',
          backgroundSize: '70px 70px',
          transform: 'rotateX(calc(60deg + var(--my) * 8deg)) rotateY(calc(var(--mx) * 5deg)) scale(2)',
          transformOrigin: '50% 0%',
        }}
      />

      {/* Orbiting rings */}
      <div
        ref={ringsContainerRef}
        className="absolute inset-0 flex items-center justify-center preserve-3d pointer-events-none"
        style={{ perspective: '800px' }}
      >
        {[
          { size: 350, anim: 'animate-spin-ring-cw',  border: 'rgba(194,65,12,0.12)' },
          { size: 550, anim: 'animate-spin-ring-ccw', border: 'rgba(15,23,42,0.06)' },
          { size: 750, anim: 'animate-spin-ring-cw2', border: 'rgba(194,65,12,0.04)' },
        ].map((ring, i) => (
          <div
            key={i}
            className={`absolute rounded-full border ${ring.anim}`}
            style={{
              width: `${ring.size}px`,
              height: `${ring.size}px`,
              borderColor: ring.border,
              borderWidth: '1px',
            }}
          />
        ))}
      </div>

      {/* Main 3D content */}
      <div
        className="relative z-10 text-center px-4 max-w-5xl preserve-3d"
        style={{
          transform: 'perspective(1200px) rotateX(calc(var(--my) * -6deg)) rotateY(calc(var(--mx) * 6deg))',
          transformStyle: 'preserve-3d',
        }}
      >
        <div style={{ transform: 'translateZ(60px)', display: 'block' }}>
          <span className="inline-block bg-amber-100/90 text-amber-900 font-display font-black text-xs md:text-sm uppercase tracking-[0.35em] px-5 py-2 rounded-full border border-amber-300/80 mb-6 shadow-xs">
            {storeName} &middot; Egyptian Luxury Craftsmanship
          </span>
        </div>

        <div className="relative block" style={{ transform: 'translateZ(40px)', transformStyle: 'preserve-3d' }}>
          <h1
            className="font-display font-black uppercase leading-[0.9] tracking-tight text-stone-900"
            style={{ fontSize: 'clamp(2.75rem, 10vw, 7.5rem)' }}
          >
            {heroTitle}
          </h1>
        </div>

        <div style={{ transform: 'translateZ(30px)' }} className="mt-4 max-w-2xl mx-auto">
          <p className="text-stone-600 font-medium text-sm md:text-lg leading-relaxed">
            {heroSubtitle}
          </p>
        </div>

        <div
          className="mt-8 md:mt-12 flex flex-wrap items-center justify-center gap-4 md:gap-6"
          style={{ transform: 'translateZ(80px)' }}
        >
          <Link
            href={heroCtaLink}
            className="inline-flex items-center gap-3 bg-[#c2410c] text-amber-50 font-display font-black text-sm md:text-base uppercase tracking-wider px-8 py-4 md:px-10 md:py-4.5 rounded-xl shadow-md hover:bg-[#9a3412] active:scale-95 transition-all cursor-pointer"
          >
            <span>{heroCtaText}</span>
            <svg className="w-5 h-5" fill="none" stroke="currentColor" strokeWidth="2.5" viewBox="0 0 24 24">
              <path d="M5 12h14M12 5l7 7-7 7" />
            </svg>
          </Link>
          <Link
            href="/collections/bundles-gift-sets"
            className="inline-flex items-center justify-center bg-white/95 border-2 border-stone-300 text-stone-800 font-display font-black text-sm md:text-base uppercase tracking-wider px-7 py-4 md:px-8 md:py-4.5 rounded-xl hover:border-[#c2410c] hover:text-[#c2410c] active:scale-95 transition-all shadow-xs cursor-pointer"
          >
            Gift Sets →
          </Link>
        </div>
      </div>

      {/* Scroll indicator */}
      <div className="absolute bottom-4 left-1/2 -translate-x-1/2 flex flex-col items-center gap-1.5 pointer-events-none">
        <span className="font-display text-[10px] uppercase tracking-widest text-stone-400 font-bold">Scroll Down</span>
        <div className="w-px h-6 relative overflow-hidden bg-stone-300">
          <div className="absolute inset-0 bg-[#c2410c] animate-scroll-drop" />
        </div>
      </div>
    </section>
  );
}
