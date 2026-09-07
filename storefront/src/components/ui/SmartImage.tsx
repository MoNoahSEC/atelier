'use client';

import React, { useState, useCallback } from 'react';
import Image, { ImageProps } from 'next/image';
import { resolveMediaUrl } from '@/lib/media';

interface SmartImageProps extends Omit<ImageProps, 'src'> {
  src: string | null | undefined;
  fallbackSrc?: string;
  /** CSS object-position value, e.g. "center", "50% 30%", "top left" */
  objectPosition?: string;
  /** cover: fills container (may crop); contain: shows full image. Default: cover */
  cropMode?: 'cover' | 'contain';
  /** Show a shimmer skeleton while the image loads. Default: true */
  showSkeleton?: boolean;
  /** Custom icon/element shown on error instead of a broken-image glyph */
  errorIcon?: React.ReactNode;
}

/**
 * SmartImage — the ONE reusable image component for the entire storefront.
 *
 * Key behaviours:
 * - Calls resolveMediaUrl() on every src so no consumer ever has to
 * - Uses object-fit: cover with configurable object-position (focal-point fix)
 * - Auto-falls-back to object-fit: contain when the image aspect ratio
 *   differs drastically from its container (avoids awkward crops on logos/icons)
 * - Shows a shimmer skeleton while loading, a styled placeholder on error
 * - Never shows a raw broken-image browser glyph
 * - Works correctly with Next.js 16's Image component (uses onLoad, not the
 *   removed onLoadingComplete)
 */
export function SmartImage({
  src,
  fallbackSrc,
  objectPosition = 'center',
  cropMode = 'cover',
  showSkeleton = true,
  errorIcon,
  alt,
  fill,
  width,
  height,
  className,
  style,
  sizes,
  priority,
  unoptimized,
  ...rest
}: SmartImageProps) {
  const [hasError, setHasError] = useState(false);
  const [isLoading, setIsLoading] = useState(true);
  // Whether to override cover with contain due to extreme aspect-ratio mismatch
  const [autoContain, setAutoContain] = useState(cropMode === 'contain');

  const resolvedSrc = resolveMediaUrl(src);

  /**
   * Fires when the image (or native fallback) finishes loading.
   * Checks for a drastic aspect-ratio mismatch between the image and its
   * container. If the ratio difference is >50 % of the larger value, we
   * silently switch to object-fit:contain so nothing important gets cropped.
   */
  const handleLoad = useCallback(
    (e: React.SyntheticEvent<HTMLImageElement>) => {
      const img = e.currentTarget;
      const container = img.closest('[data-smart-image-container]') ?? img.parentElement;

      if (container && img.naturalWidth && img.naturalHeight) {
        const containerAspect = container.clientWidth / Math.max(container.clientHeight, 1);
        const imageAspect = img.naturalWidth / img.naturalHeight;
        const maxAspect = Math.max(containerAspect, imageAspect);
        const diff = Math.abs(containerAspect - imageAspect) / Math.max(maxAspect, 1);
        // >50% difference → auto-contain to avoid awkward crop
        setAutoContain(cropMode === 'contain' || diff > 0.5);
      } else {
        setAutoContain(cropMode === 'contain');
      }
      setIsLoading(false);
    },
    [cropMode],
  );

  const handleError = useCallback(() => {
    setHasError(true);
    setIsLoading(false);
  }, []);

  // ── No src at all ────────────────────────────────────────────────────────────
  if (!resolvedSrc) {
    if (fallbackSrc) {
      return (
        <Image
          src={fallbackSrc}
          alt={alt || 'Fallback image'}
          fill={fill}
          width={!fill ? (width as number) : undefined}
          height={!fill ? (height as number) : undefined}
          sizes={sizes}
          priority={priority}
          unoptimized={unoptimized}
          className={className}
          style={style}
          {...rest}
        />
      );
    }
    return <EmptyPlaceholder fill={fill} width={width} height={height} className={className} errorIcon={errorIcon} />;
  }

  const effectiveFit = autoContain ? 'contain' : 'cover';
  const effectivePosition = autoContain ? 'center' : objectPosition;

  // ── Container style ──────────────────────────────────────────────────────────
  // When fill=true the Next/Image absolutely-positions itself inside its nearest
  // positioned ancestor. We supply that ancestor.
  const containerStyle: React.CSSProperties = fill
    ? { position: 'absolute', inset: 0, width: '100%', height: '100%', overflow: 'hidden' }
    : { position: 'relative', display: 'inline-block', width: '100%', height: '100%', overflow: 'hidden' };

  return (
    <div data-smart-image-container style={containerStyle}>
      {/* Loading skeleton — hidden once the image loads */}
      {isLoading && showSkeleton && !hasError && (
        <div
          aria-hidden="true"
          style={{
            position: 'absolute',
            inset: 0,
            background: 'linear-gradient(90deg, rgba(255,255,255,0.04) 0%, rgba(255,255,255,0.09) 50%, rgba(255,255,255,0.04) 100%)',
            backgroundSize: '200% 100%',
            animation: 'smartimage-shimmer 1.4s ease infinite',
            zIndex: 1,
          }}
        />
      )}

      {/* Error state — styled placeholder, never a raw broken-image icon */}
      {hasError && (
        <div
          style={{
            position: 'absolute',
            inset: 0,
            background: '#141414',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
            color: 'rgba(255,255,255,0.25)',
            fontSize: '1.5rem',
            zIndex: 2,
          }}
        >
          {errorIcon ?? (
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5">
              <rect x="3" y="3" width="18" height="18" rx="2" />
              <circle cx="8.5" cy="8.5" r="1.5" />
              <polyline points="21 15 16 10 5 21" />
            </svg>
          )}
        </div>
      )}

      {/* The actual image */}
      {!hasError && (
        <Image
          src={resolvedSrc}
          alt={alt ?? ''}
          fill={fill}
          width={!fill ? (width as number) : undefined}
          height={!fill ? (height as number) : undefined}
          sizes={sizes}
          priority={priority}
          unoptimized={unoptimized ?? true}
          className={className}
          style={{
            objectFit: effectiveFit,
            objectPosition: effectivePosition,
            opacity: isLoading ? 0 : 1,
            transition: 'opacity 0.3s ease',
            ...style,
          }}
          onLoad={handleLoad}
          onError={handleError}
          {...rest}
        />
      )}

      {/* Shimmer keyframes — injected once per page via a <style> tag */}
      <style>{`
        @keyframes smartimage-shimmer {
          0%   { background-position: 200% 0; }
          100% { background-position: -200% 0; }
        }
      `}</style>
    </div>
  );
}

// ── Small helpers ─────────────────────────────────────────────────────────────

function EmptyPlaceholder({
  fill,
  width,
  height,
  className,
  errorIcon,
}: {
  fill?: boolean;
  width?: ImageProps['width'];
  height?: ImageProps['height'];
  className?: string;
  errorIcon?: React.ReactNode;
}) {
  return (
    <div
      className={className}
      style={{
        position: fill ? 'absolute' : 'relative',
        inset: fill ? 0 : undefined,
        width: fill ? '100%' : (width as string | number | undefined),
        height: fill ? '100%' : (height as string | number | undefined),
        background: '#141414',
        border: '1px solid rgba(255,255,255,0.08)',
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'center',
        color: 'rgba(255,255,255,0.2)',
        fontSize: '0.625rem',
        textTransform: 'uppercase',
        letterSpacing: '0.1em',
      }}
    >
      {errorIcon ?? (
        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5">
          <rect x="3" y="3" width="18" height="18" rx="2" />
          <circle cx="8.5" cy="8.5" r="1.5" />
          <polyline points="21 15 16 10 5 21" />
        </svg>
      )}
    </div>
  );
}
