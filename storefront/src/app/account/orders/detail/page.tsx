'use client';

import { useEffect, useState, use } from 'react';
import Link from 'next/link';
import { useStore } from '@/components/providers/StoreProvider';
import { useRouter, useSearchParams } from 'next/navigation';
import { getApiUrl } from '@/lib/api';

const formatPrice = (minor: number) => {
  return new Intl.NumberFormat('en-EG', { style: 'currency', currency: 'EGP' }).format(minor / 100);
};

import { Suspense } from 'react';

function OrderDetailContent() {
  const searchParams = useSearchParams();
  const id = searchParams.get('id');
  const router = useRouter();
  const { storeName } = useStore();
  const [order, setOrder] = useState<any>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  useEffect(() => {
    async function fetchOrder() {
      const token = localStorage.getItem('customer_token');
      if (!token) {
        router.push('/account');
        return;
      }

      try {
        const res = await fetch(`${getApiUrl()}/public/customer/orders/${id}`, {
          headers: {
            'Authorization': `Bearer ${token}`,
            'Accept': 'application/json'
          }
        });
        
        if (res.ok) {
          const data = await res.json();
          setOrder(data);
        } else {
          setError('Order not found or access denied.');
        }
      } catch (e) {
        console.error('Failed to fetch order', e);
        setError('Network error loading order.');
      } finally {
        setLoading(false);
      }
    }
    
    if (id) fetchOrder();
  }, [id, router]);

  if (loading) {
    return (
      <main className="min-h-screen bg-[#0a0a0a] flex items-center justify-center">
        <div className="text-[#e8ff47] animate-pulse font-mono text-sm uppercase tracking-widest">Loading Order...</div>
      </main>
    );
  }

  if (error || !order) {
    return (
      <main className="min-h-screen bg-[#0a0a0a] pt-24 pb-12 px-6 md:px-12">
        <div className="max-w-4xl mx-auto text-center">
          <p className="text-red-400 mb-6 font-mono text-sm uppercase tracking-widest">{error || 'Order not found'}</p>
          <Link href="/account" className="inline-block bg-[#e8ff47] text-black px-6 py-3 text-xs uppercase font-bold tracking-widest hover:bg-white transition-colors">
            Back to Account
          </Link>
        </div>
      </main>
    );
  }

  return (
    <main className="min-h-screen bg-[#0a0a0a] pt-24 pb-12 px-6 md:px-12">
      <div className="max-w-4xl mx-auto">
        <div className="mb-8">
          <Link href="/account" className="text-white/40 hover:text-white transition-colors text-xs uppercase tracking-widest font-mono inline-flex items-center gap-2">
            <span>←</span> Back to Account
          </Link>
        </div>
        
        <div className="flex flex-col md:flex-row justify-between items-start md:items-end mb-12 border-b border-white/10 pb-6 gap-4">
          <div>
            <h1 className="font-black uppercase tracking-[0.3em] text-white text-3xl mb-2">Order {order.order_number}</h1>
            <p className="text-white/40 text-xs tracking-widest uppercase font-mono">
              Placed on {new Date(order.created_at).toLocaleString()}
            </p>
          </div>
          <div className="flex gap-2">
            <span className="px-3 py-1.5 bg-white/5 border border-white/10 text-xs uppercase tracking-widest font-mono text-white/70">
              {order.payment_method}
            </span>
            <span className={`px-3 py-1.5 border text-xs uppercase tracking-widest font-mono ${
              order.payment_status === 'paid' ? 'bg-[#e8ff47]/10 border-[#e8ff47]/30 text-[#e8ff47]' : 'bg-white/5 border-white/10 text-white/70'
            }`}>
              {order.payment_status?.replace('_', ' ')}
            </span>
            <span className="px-3 py-1.5 bg-white/5 border border-white/10 text-xs uppercase tracking-widest font-mono text-white/70">
              {order.fulfillment_status?.replace('_', ' ')}
            </span>
          </div>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-3 gap-12">
          <div className="md:col-span-2 space-y-6">
            <h2 className="font-display text-xl uppercase tracking-widest border-b border-white/10 pb-4">Items</h2>
            <div className="space-y-4">
              {order.items?.map((item: any) => (
                <div key={item.id} className="flex gap-6 py-4 border-b border-white/5">
                  <div className="flex-1">
                    <h3 className="font-bold text-white text-sm mb-1">{item.title}</h3>
                    {item.variant_title && <p className="text-white/40 text-xs mb-2">{item.variant_title}</p>}
                    <p className="text-white/30 text-xs font-mono uppercase tracking-widest">Qty: {item.quantity}</p>
                  </div>
                  <div className="text-right">
                    <div className="font-mono text-sm text-white">{formatPrice(item.line_total_minor)}</div>
                    {item.quantity > 1 && (
                      <div className="text-white/30 text-xs mt-1">{formatPrice(item.unit_price_minor)} each</div>
                    )}
                  </div>
                </div>
              ))}
            </div>
          </div>

          <div className="space-y-8">
            <div>
              <h2 className="font-display text-xl uppercase tracking-widest border-b border-white/10 pb-4 mb-4">Summary</h2>
              <div className="space-y-3 font-mono text-sm">
                <div className="flex justify-between text-white/60">
                  <span>Subtotal</span>
                  <span>{formatPrice(order.subtotal_minor)}</span>
                </div>
                <div className="flex justify-between text-white/60">
                  <span>Shipping</span>
                  <span>{formatPrice(order.shipping_total_minor)}</span>
                </div>
                <div className="flex justify-between text-white/60">
                  <span>Tax</span>
                  <span>{formatPrice(order.tax_total_minor)}</span>
                </div>
                {order.payment_method === 'cod' && (
                  <div className="flex justify-between text-white/60">
                    <span>COD Fee</span>
                    <span>{formatPrice(2000)}</span>
                  </div>
                )}
                <div className="flex justify-between pt-4 border-t border-white/10 mt-4 text-[#e8ff47] font-bold">
                  <span>Total</span>
                  <span>{formatPrice(order.total_minor)}</span>
                </div>
              </div>
            </div>

            <div>
              <h2 className="font-display text-xl uppercase tracking-widest border-b border-white/10 pb-4 mb-4">Shipping Info</h2>
              <div className="bg-white/5 p-4 border border-white/10 text-sm text-white/70 space-y-1">
                <p className="font-bold text-white">{order.shipping_address?.name}</p>
                <p>{order.shipping_address?.line1}</p>
                <p>{order.shipping_address?.city}, {order.shipping_address?.state}</p>
                <p>{order.shipping_address?.country} {order.shipping_address?.postal}</p>
              </div>
            </div>

            {order.tracking_number && (
              <div>
                <h2 className="font-display text-xl uppercase tracking-widest border-b border-white/10 pb-4 mb-4">Tracking</h2>
                <div className="bg-[#e8ff47]/10 p-4 border border-[#e8ff47]/20 text-sm">
                  <p className="text-white/70 mb-2 text-xs uppercase tracking-widest font-mono">Tracking Number</p>
                  <p className="font-mono text-[#e8ff47] font-bold">{order.tracking_number}</p>
                </div>
              </div>
            )}
          </div>
        </div>
      </div>
    </main>
  );
}

export default function OrderDetailPage() {
  return (
    <Suspense fallback={<div className="min-h-screen bg-[#0a0a0a] flex items-center justify-center"><div className="text-[#e8ff47] animate-pulse font-mono text-sm uppercase tracking-widest">Loading...</div></div>}>
      <OrderDetailContent />
    </Suspense>
  );
}

