import Link from 'next/link';
import { Metadata } from 'next';
import { fetchStoreSettings } from '@/lib/api';
import OrderDetails from './OrderDetails';

import { Suspense } from 'react';
import OrderConfirmationClient from './OrderConfirmationClient';

export const metadata: Metadata = { title: 'Order Confirmed' };

export default async function OrderConfirmationPage() {
  const settings = await fetchStoreSettings();
  const storeName = settings.storeName || 'ATELIER';

  return (
    <div className="pt-16 md:pt-20 bg-background min-h-screen flex flex-col">
      <div className="border-b border-border px-6 md:px-12 py-5">
        <Link href="/" className="font-display font-black uppercase text-xl tracking-tight hover:text-accent transition-colors">
          {storeName}
        </Link>
      </div>

      <div className="flex-1 flex flex-col items-center justify-center px-6 py-16 text-center">
        <div className="relative w-20 h-20 mb-10">
          <div className="absolute inset-0 border-2 border-accent rounded-full animate-ping opacity-20" />
          <div className="relative w-20 h-20 border-2 border-accent rounded-full flex items-center justify-center bg-background z-10">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#e8ff00" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round" aria-hidden>
              <polyline points="20 6 9 17 4 12"/>
            </svg>
          </div>
        </div>

        <span className="font-display font-bold text-[10px] uppercase tracking-[0.3em] text-accent block mb-3">
          Order Confirmed
        </span>
        <h1 className="font-display font-black uppercase text-4xl md:text-6xl leading-[0.9] tracking-tighter mb-5">
          Thank You
        </h1>

        <p className="text-sm text-muted max-w-sm mb-12 leading-relaxed">
          A confirmation has been sent to your email. We'll notify you once your order ships.
        </p>

        <Suspense fallback={<p className="text-muted text-xs">Loading order details...</p>}>
          <OrderConfirmationClient />
        </Suspense>

        <div className="flex flex-col sm:flex-row gap-3 mt-12">
          <Link
            href="/"
            className="inline-flex items-center justify-center gap-3 bg-accent text-black px-8 py-4 font-display font-black text-xs uppercase tracking-[0.2em] hover:bg-accent/90 transition-colors"
          >
            Continue Shopping
          </Link>
          <Link
            href="/account"
            className="inline-flex items-center justify-center border border-border text-muted px-8 py-4 font-display font-bold text-xs uppercase tracking-[0.2em] hover:border-foreground hover:text-foreground transition-colors"
          >
            View Orders
          </Link>
        </div>
      </div>
    </div>
  );
}
