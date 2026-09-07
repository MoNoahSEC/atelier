'use client';

import React from 'react';
import { ImageProps } from 'next/image';
import { SmartImage } from '@/components/ui/SmartImage';

interface SafeImageProps extends Omit<ImageProps, 'src'> {
  src: string | null | undefined;
  fallbackSrc?: string;
  /** Forward SmartImage crop mode. Default: 'cover' */
  cropMode?: 'cover' | 'contain';
  /** Forward SmartImage focal point. Default: 'center' */
  objectPosition?: string;
  errorIcon?: React.ReactNode;
}

/**
 * SafeImage: backward-compatible alias for SmartImage.
 * All new code should use SmartImage directly.
 */
export function SafeImage({
  src,
  fallbackSrc,
  cropMode,
  objectPosition,
  errorIcon,
  alt,
  ...props
}: SafeImageProps) {
  return (
    <SmartImage
      src={src}
      fallbackSrc={fallbackSrc}
      cropMode={cropMode}
      objectPosition={objectPosition}
      errorIcon={errorIcon}
      alt={alt}
      {...props}
    />
  );
}
