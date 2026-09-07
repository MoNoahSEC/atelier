import { notFound } from 'next/navigation';
import { fetchProduct, fetchCatalog } from '@/lib/api';
import type { Metadata } from 'next';
import ProductDetailsClient from './ProductDetailsClient';

export const dynamic = 'force-dynamic';

export async function generateMetadata({
  params,
}: {
  params: Promise<{ slug: string }>;
}): Promise<Metadata> {
  const { slug } = await params;
  const response = await fetchProduct(slug);
  if (!response?.data) return { title: 'Product Not Found' };
  return {
    title: response.data.title,
    description: response.data.description,
  };
}

export default async function ProductPage({
  params,
}: {
  params: Promise<{ slug: string }>;
}) {
  const { slug } = await params;
  const response = await fetchProduct(slug);
  if (!response?.data) notFound();

  const product = response.data;
  const priceMinor = product.retail_price_minor ?? product.price_minor ?? 0;
  const formattedPrice = new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: product.currency || 'USD',
    minimumFractionDigits: 0,
  }).format(priceMinor / 100);

  return <ProductDetailsClient product={product} formattedPrice={formattedPrice} />;
}
