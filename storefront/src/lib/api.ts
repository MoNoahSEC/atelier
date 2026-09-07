export function getApiUrl(): string {
  if (typeof window !== 'undefined') {
    return '/api/v1';
  }
  return process.env.NEXT_PUBLIC_API_URL || (process.env.NODE_ENV === 'production' ? 'https://atelier404.store/api/v1' : 'http://127.0.0.1:8000/api/v1');
}

export const API_URL = getApiUrl();

export function generateUUID(): string {
  if (typeof window !== 'undefined' && window.crypto && typeof window.crypto.randomUUID === 'function') {
    try {
      return window.crypto.randomUUID();
    } catch {}
  }
  return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
    const r = Math.random() * 16 | 0;
    const v = c === 'x' ? r : (r & 0x3 | 0x8);
    return v.toString(16);
  });
}

export async function fetchCatalog(searchParams: URLSearchParams | string = '') {
  const query = searchParams ? `?${searchParams.toString()}` : '';
  const res = await fetch(`${getApiUrl()}/public/catalog${query}`, {
    cache: 'no-store',
  });
  
  if (!res.ok) {
    throw new Error('Failed to fetch catalog');
  }
  
  return res.json();
}

export async function fetchProduct(slug: string) {
  const res = await fetch(`${getApiUrl()}/public/catalog/${slug}`, {
    cache: 'no-store',
  });
  
  if (!res.ok) {
    if (res.status === 404) return null;
    throw new Error('Failed to fetch product');
  }
  
  return res.json();
}

// No caching on settings — changes must reflect immediately everywhere
export async function fetchStoreSettings(): Promise<Record<string, string>> {
  try {
    const res = await fetch(`${getApiUrl()}/public/settings`, {
      cache: 'no-store',
    });
    if (!res.ok) return {};
    const json = await res.json();
    return json.data || {};
  } catch {
    return {};
  }
}

// Client-side version: call from browser components to get fresh settings
export async function fetchStoreSettingsClient(): Promise<Record<string, string>> {
  try {
    const res = await fetch(`${API_URL}/public/settings`, {
      cache: 'no-store',
    });
    if (!res.ok) return {};
    const json = await res.json();
    return json.data || {};
  } catch {
    return {};
  }
}
