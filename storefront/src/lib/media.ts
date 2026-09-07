/**
 * media.ts
 * Centralized Media Resolver for the entire Storefront.
 *
 * All local uploads under `/storage/...` are served relatively or proxied via Next.js rewrite,
 * guaranteeing zero hydration mismatch between SSR and Client render.
 */

export function getBackendOrigin(): string {
  if (typeof window !== 'undefined') {
    const host = window.location.hostname;
    const protocol = window.location.protocol;
    return `${protocol}//${host}:8000`;
  }
  const apiUrl = process.env.NEXT_PUBLIC_API_URL || (process.env.NODE_ENV === 'production' ? 'https://atelier404.store/api/v1' : 'http://127.0.0.1:8000/api/v1');
  try {
    const parsed = new URL(apiUrl);
    return `${parsed.protocol}//${parsed.host}`;
  } catch {
    return process.env.NODE_ENV === 'production' ? 'https://atelier404.store' : 'http://127.0.0.1:8000';
  }
}

/**
 * Resolves a raw media URL into a safe, normalized URL.
 * Storage paths are returned as clean relative paths `/storage/...` which Next.js rewrites to the backend.
 */
export function resolveMediaUrl(url: string | null | undefined): string | null {
  if (!url || typeof url !== 'string') return null;
  const trimmed = url.trim();
  if (!trimmed) return null;

  // 1. Handle relative Laravel storage paths (e.g. "/storage/media/xyz.jpg" or "storage/media/xyz.jpg")
  if (trimmed.startsWith('/storage/') || trimmed.startsWith('storage/')) {
    return trimmed.startsWith('/') ? trimmed : `/${trimmed}`;
  }

  // 2. Handle absolute backend URLs (e.g. "http://localhost:8000/storage/..." or "http://127.0.0.1:8000/storage/...")
  if (trimmed.startsWith('http://') || trimmed.startsWith('https://')) {
    if (trimmed.includes(':8000/storage/')) {
      try {
        const urlObj = new URL(trimmed);
        return `${urlObj.pathname}${urlObj.search}`;
      } catch {
        return trimmed;
      }
    }
    return trimmed;
  }

  // 3. Handle relative local paths (e.g. "/images/foo.jpg")
  if (trimmed.startsWith('/')) {
    return trimmed;
  }

  // 4. Fallback for relative paths without leading slash
  return `/storage/${trimmed}`;
}

export function resolveMediaUrlSsr(url: string | null | undefined): string | null {
  return resolveMediaUrl(url);
}
