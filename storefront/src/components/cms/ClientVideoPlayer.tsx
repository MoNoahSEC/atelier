'use client';

import React from 'react';
import { resolveMediaUrl } from '@/lib/media';

interface ClientVideoPlayerProps {
  src: string | null;
  autoplay?: boolean;
  loop?: boolean;
  muted?: boolean;
  className?: string;
}

export default function ClientVideoPlayer({ src, autoplay = true, loop = true, muted = true, className }: ClientVideoPlayerProps) {
  const resolvedSrc = resolveMediaUrl(src);

  if (!resolvedSrc) {
    return (
      <div className={`bg-white/5 border border-white/10 flex items-center justify-center text-white/30 text-xs font-display uppercase tracking-widest text-center p-4 ${className || ''}`}>
        Video Unavailable
      </div>
    );
  }

  return (
    <video
      src={resolvedSrc}
      autoPlay={autoplay}
      loop={loop}
      muted={muted}
      playsInline
      className={className}
    />
  );
}
