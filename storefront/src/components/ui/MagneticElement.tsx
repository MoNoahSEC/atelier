'use client';

import React, { useRef } from 'react';

export default function MagneticElement({ children, className = '' }: { children: React.ReactNode, className?: string }) {
  const ref = useRef<HTMLDivElement>(null);

  const handleMouse = (e: React.MouseEvent<HTMLDivElement>) => {
    if (!ref.current) return;
    const { clientX, clientY } = e;
    const { height, width, left, top } = ref.current.getBoundingClientRect();
    const middleX = clientX - (left + width / 2);
    const middleY = clientY - (top + height / 2);
    
    const factor = 0.15;
    ref.current.style.transform = `translate(${middleX * factor}px, ${middleY * factor}px)`;
    ref.current.style.transition = 'transform 0.1s ease-out';
  };

  const reset = () => {
    if (!ref.current) return;
    ref.current.style.transform = 'translate(0px, 0px)';
    ref.current.style.transition = 'transform 0.4s cubic-bezier(0.25, 1, 0.5, 1)';
  };

  return (
    <div
      ref={ref}
      onMouseMove={handleMouse}
      onMouseLeave={reset}
      className={className}
      style={{ willChange: 'transform' }}
    >
      {children}
    </div>
  );
}
