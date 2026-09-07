'use client';

import { useState } from 'react';
import Link from 'next/link';
import { useStore } from '@/components/providers/StoreProvider';
import { getApiUrl } from '@/lib/api';

export default function TrackOrderPage() {
  const { storeName, formatPrice } = useStore();
  const [orderNumber, setOrderNumber] = useState('');
  const [contact, setContact] = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [order, setOrder] = useState<any>(null);

  const handleTrack = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!orderNumber.trim() || !contact.trim()) {
      setError('Please enter both your Order Number and Phone Number or Email.');
      return;
    }

    setLoading(true);
    setError('');
    setOrder(null);

    try {
      const res = await fetch(`${getApiUrl()}/public/orders/track`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({
          order_number: orderNumber.trim(),
          contact: contact.trim(),
        }),
      });

      const data = await res.json();
      if (!res.ok) {
        setError(data.message || 'Unable to find order. Please verify your details.');
      } else {
        setOrder(data.data);
      }
    } catch {
      setError('Network error. Please try again.');
    } finally {
      setLoading(false);
    }
  };

  const getStatusStep = (order: any) => {
    if (order.fulfillment_status === 'cancelled') return -1;
    if (order.shipping_status === 'delivered') return 4;
    if (order.shipping_status === 'shipped') return 3;
    if (order.payment_status === 'paid' || order.payment_method === 'cod') return 2;
    return 1;
  };

  const step = order ? getStatusStep(order) : 0;

  const STEPS = [
    { label: 'Order Placed', desc: 'Received & logged' },
    { label: 'Processing', desc: 'Packaged & verified' },
    { label: 'Out for Delivery', desc: 'Handed to courier' },
    { label: 'Delivered', desc: 'Successfully received' },
  ];

  return (
    <div className="bg-[#faf8f5] min-h-screen pt-20 md:pt-28 pb-20 px-4 sm:px-6 lg:px-16 text-stone-900">
      <div className="max-w-3xl mx-auto">

        {/* Header */}
        <div className="text-center mb-8">
          <div className="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-amber-100/80 border border-amber-300/60 text-[#c2410c] text-[11px] font-display font-bold uppercase tracking-wider mb-2">
            <span>Real-Time Tracking</span>
          </div>
          <h1 className="font-display font-black uppercase text-2xl sm:text-4xl text-stone-900 tracking-tight">
            Track Your Order
          </h1>
          <p className="text-xs sm:text-sm text-stone-500 mt-2 max-w-md mx-auto">
            Check live shipping status, carrier tracking codes, and item breakdown across all Egypt governorates.
          </p>
        </div>

        {/* Search Form */}
        <div className="bg-white border border-stone-200 rounded-2xl p-6 sm:p-8 shadow-2xs mb-8">
          <form onSubmit={handleTrack} className="space-y-4 text-xs">
            <div className="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
              <div>
                <label className="block font-display font-bold text-xs uppercase tracking-wider text-stone-700 mb-1.5">
                  Order Number *
                </label>
                <input
                  type="text"
                  required
                  placeholder="e.g. ORD-A1B2C3D4"
                  value={orderNumber}
                  onChange={e => setOrderNumber(e.target.value)}
                  className="w-full bg-[#faf8f5] border border-stone-200 rounded-xl px-4 py-3 text-sm text-stone-900 font-mono uppercase tracking-wider outline-none focus:border-[#c2410c] transition-colors"
                />
              </div>
              <div>
                <label className="block font-display font-bold text-xs uppercase tracking-wider text-stone-700 mb-1.5">
                  Phone Number or Email *
                </label>
                <input
                  type="text"
                  required
                  placeholder="01012345678 or email@..."
                  value={contact}
                  onChange={e => setContact(e.target.value)}
                  className="w-full bg-[#faf8f5] border border-stone-200 rounded-xl px-4 py-3 text-sm text-stone-900 outline-none focus:border-[#c2410c] transition-colors"
                />
              </div>
            </div>

            {error && (
              <div className="p-3 bg-rose-50 border border-rose-200 rounded-xl text-rose-800 text-xs font-display font-bold">
                {error}
              </div>
            )}

            <button
              type="submit"
              disabled={loading}
              className="w-full bg-[#c2410c] hover:bg-[#9a3412] text-amber-50 font-display font-bold text-xs uppercase tracking-wider py-3.5 rounded-xl transition-all duration-200 disabled:opacity-50 flex items-center justify-center gap-2 shadow-xs active:scale-95 cursor-pointer"
            >
              {loading ? (
                <>
                  <div className="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin" />
                  <span>Locating Order…</span>
                </>
              ) : (
                <span>Track Order Status →</span>
              )}
            </button>
          </form>
        </div>

        {/* Order Details Display */}
        {order && (
          <div className="bg-white border border-stone-200 rounded-2xl p-6 sm:p-8 shadow-md animate-fade-up space-y-6">
            
            {/* Top Status Bar */}
            <div className="flex flex-wrap items-center justify-between gap-4 border-b border-stone-200 pb-5">
              <div>
                <span className="font-display font-bold text-xs uppercase tracking-wider text-[#c2410c]">Order Found</span>
                <h2 className="font-display font-black text-xl sm:text-2xl text-stone-900 mt-0.5">{order.order_number}</h2>
                <p className="text-xs text-stone-500 mt-1">Placed on {new Date(order.created_at).toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' })}</p>
              </div>

              <div className="flex items-center gap-2">
                <span className={`px-3 py-1 font-display font-bold text-xs uppercase tracking-wider rounded-full border ${
                  order.payment_status === 'paid'
                    ? 'bg-emerald-50 text-emerald-800 border-emerald-200'
                    : 'bg-amber-50 text-amber-800 border-amber-200'
                }`}>
                  {order.payment_method === 'cod' ? 'Cash on Delivery' : `Payment: ${order.payment_status}`}
                </span>
                <span className="px-3 py-1 font-display font-bold text-xs uppercase tracking-wider bg-stone-100 text-stone-800 border border-stone-200 rounded-full">
                  {order.shipping_status || order.fulfillment_status}
                </span>
              </div>
            </div>

            {/* Visual Timeline */}
            <div>
              <p className="font-display font-bold text-xs uppercase tracking-wider text-stone-600 mb-4">Delivery Timeline</p>
              <div className="grid grid-cols-2 sm:grid-cols-4 gap-3">
                {STEPS.map((s, idx) => {
                  const isDone = step >= idx + 1;
                  const isCurrent = step === idx + 1;
                  return (
                    <div key={s.label} className={`p-4 rounded-xl border transition-all ${
                      isCurrent
                        ? 'border-[#c2410c] bg-amber-50/50 shadow-xs'
                        : isDone
                          ? 'border-emerald-200 bg-emerald-50/30'
                          : 'border-stone-200 bg-stone-50/50 opacity-60'
                    }`}>
                      <div className="flex items-center gap-2 mb-1.5">
                        <span className={`w-5 h-5 rounded-full flex items-center justify-center font-display font-black text-[10px] ${
                          isDone ? 'bg-[#c2410c] text-amber-50' : 'bg-stone-200 text-stone-600'
                        }`}>
                          {isDone ? '✓' : idx + 1}
                        </span>
                        <span className="font-display font-bold text-xs text-stone-900 uppercase tracking-tight">{s.label}</span>
                      </div>
                      <p className="text-[11px] text-stone-500">{s.desc}</p>
                    </div>
                  );
                })}
              </div>
            </div>

            {/* Carrier / Tracking Code if available */}
            {order.tracking_number && (
              <div className="p-4 bg-[#f4efe6] border border-amber-200 rounded-xl flex items-center justify-between">
                <div>
                  <p className="font-display font-bold text-xs uppercase tracking-wider text-[#c2410c]">Carrier Tracking Number</p>
                  <p className="font-mono text-sm text-stone-900 mt-0.5">{order.tracking_number}</p>
                </div>
                <span className="text-xs text-stone-500 font-display uppercase tracking-wider font-semibold">Courier Assigned</span>
              </div>
            )}

            {/* Items */}
            <div className="border-t border-stone-200 pt-5">
              <p className="font-display font-bold text-xs uppercase tracking-wider text-stone-600 mb-3">Ordered Items</p>
              <div className="divide-y divide-stone-100">
                {(order.items || []).map((item: any) => (
                  <div key={item.id} className="py-3 flex items-center justify-between gap-4">
                    <div>
                      <p className="text-xs font-bold text-stone-900">{item.product_title || item.title}</p>
                      <p className="text-[11px] text-stone-500 mt-0.5">Quantity: {item.quantity}</p>
                    </div>
                    <span className="font-display font-bold text-xs text-[#c2410c]">
                      {formatPrice(item.total_price_minor || (item.unit_price_minor * item.quantity))}
                    </span>
                  </div>
                ))}
              </div>
            </div>

            {/* Summary */}
            <div className="border-t border-stone-200 pt-4 flex justify-between items-center text-sm font-bold">
              <span className="text-stone-700">Total Order Amount</span>
              <span className="text-[#c2410c] font-black text-lg">{formatPrice(order.total_amount_minor)}</span>
            </div>
          </div>
        )}

        {/* Back Link */}
        <div className="text-center mt-8">
          <Link href="/collections/all" className="font-display font-bold text-xs uppercase tracking-wider text-stone-500 hover:text-[#c2410c] transition-colors">
            ← Continue Shopping at {storeName}
          </Link>
        </div>
      </div>
    </div>
  );
}
