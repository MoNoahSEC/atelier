'use client';

import { useEffect, useRef } from 'react';

export default function Spotlight() {
  const spotRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    const handleMouseMove = (e: MouseEvent) => {
      if (spotRef.current) {
        spotRef.current.style.setProperty('--x', `${e.clientX}px`);
        spotRef.current.style.setProperty('--y', `${e.clientY}px`);
      }
    };

    window.addEventListener('mousemove', handleMouseMove, { passive: true });
    return () => window.removeEventListener('mousemove', handleMouseMove);
  }, []);

  return (
    <div
      ref={spotRef}
      className="pointer-events-none fixed inset-0 z-40 overflow-hidden"
      style={{
        // Default CSS variables
        ['--x' as any]: '50vw',
        ['--y' as any]: '50vh',
        background: 'radial-gradient(500px circle at var(--x) var(--y), rgba(232,255,0,0.03), transparent 60%)',
      }}
    />
  );
}
