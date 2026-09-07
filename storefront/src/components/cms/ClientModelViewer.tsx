'use client';

import React from 'react';
import { resolveMediaUrl } from '@/lib/media';

interface ClientModelViewerProps {
  src: string | null;
  poster: string | null;
  alt?: string;
}

export default function ClientModelViewer({ src, poster, alt }: ClientModelViewerProps) {
  const resolvedSrc = resolveMediaUrl(src);
  const resolvedPoster = resolveMediaUrl(poster) || undefined;

  if (!resolvedSrc) {
    return (
      <div className="w-full h-full bg-white/5 border border-white/10 flex items-center justify-center text-white/30 text-xs font-display uppercase tracking-widest text-center p-4">
        3D Model Unavailable
      </div>
    );
  }

  return (
    <div className="w-full h-full relative" dangerouslySetInnerHTML={{ __html: `
      <model-viewer
        src="${resolvedSrc}"
        poster="${resolvedPoster || ''}"
        alt="${alt || '3D Model'}"
        shadow-intensity="1"
        camera-controls
        auto-rotate
        ar
        style="width: 100%; height: 100%;"
      ></model-viewer>
    ` }} />
  );
}
