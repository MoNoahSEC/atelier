import { fetchCatalog, getApiUrl } from '@/lib/api';
import type { Metadata } from 'next';
import HomeClient from './HomeClient';

export const dynamic = 'force-dynamic';

export const metadata: Metadata = {
  title: 'ATELIER — Luxury Accessories & Leathercraft',
  description: 'Precision crafted luxury iPhone cases, Apple Watch bands, MagSafe wallets & EDC accessories.',
};

async function getProducts() {
  try {
    const res = await fetchCatalog('per_page=12');
    return res?.data || [];
  } catch (err) {
    console.error('Failed to get products for homepage:', err);
    return [];
  }
}

async function getCollections() {
  try {
    const res = await fetch(
      `${getApiUrl()}/public/collections`,
      { cache: 'no-store' }
    );
    if (!res.ok) return [];
    const data = await res.json();
    return data.data || [];
  } catch (err) {
    console.error('Failed to get collections for homepage:', err);
    return [];
  }
}

export default async function Home() {
  const [products, collections] = await Promise.all([getProducts(), getCollections()]);

  return <HomeClient initialProducts={products} initialCollections={collections} />;
}
