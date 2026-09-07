import { fetchCatalog, fetchStoreSettings, getApiUrl } from '@/lib/api';
import type { Metadata } from 'next';
import { Suspense } from 'react';
import CollectionClient from './CollectionClient';

async function getCollections() {
  try {
    const res = await fetch(
      `${getApiUrl()}/public/collections`,
      { next: { revalidate: 60 } }
    );
    if (!res.ok) return [];
    const data = await res.json();
    return data.data || [];
  } catch {
    return [];
  }
}

export async function generateMetadata({ params }: { params: Promise<{ slug: string }> }): Promise<Metadata> {
  const { slug } = await params;
  const title = slug === 'all' ? 'All Products' : slug.split('-').map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ');
  return {
    title: `${title} | ATELIER`,
    description: `Shop luxury ${title} in Egypt with fast 24-48h delivery.`,
  };
}

export default async function CollectionPage({
  params,
}: {
  params: Promise<{ slug: string }>;
}) {
  const { slug } = await params;

  // Execute all requests concurrently in parallel for sub-50ms SSR
  const [settingsRes, collectionsRes, catalogRes] = await Promise.allSettled([
    fetchStoreSettings(),
    fetch(`${getApiUrl()}/public/collections`, { next: { revalidate: 120 } }).then(r => r.ok ? r.json() : null),
    fetchCatalog(`collection=${slug}`),
  ]);

  const settings = settingsRes.status === 'fulfilled' ? settingsRes.value : {};
  const storeName = settings.storeName || 'ATELIER';
  const filterCategories = (collectionsRes.status === 'fulfilled' && collectionsRes.value?.data) ? collectionsRes.value.data : [
    { title: 'Executive Gift Sets', slug: 'bundles-gift-sets' },
    { title: 'Minimalist Money Clips', slug: 'money-clips' },
    { title: 'Passport & Travel Wallets', slug: 'travel-wallets' },
    { title: 'MagSafe Battery Wallets', slug: 'powerbank-wallets' },
  ];
  const initialProducts = catalogRes.status === 'fulfilled' ? (catalogRes.value?.data || []) : [];

  return (
    <Suspense fallback={
      <div className="bg-[#faf8f5] min-h-screen flex items-center justify-center pt-24">
        <div className="flex flex-col items-center gap-3">
          <div className="w-8 h-8 border-2 border-stone-300 border-t-[#c2410c] rounded-full animate-spin" />
          <span className="text-stone-500 text-xs font-display font-bold uppercase tracking-wider">Loading Collection...</span>
        </div>
      </div>
    }>
      <CollectionClient 
        slug={slug} 
        storeName={storeName} 
        filterCategories={filterCategories}
        initialProducts={initialProducts} 
      />
    </Suspense>
  );
}
