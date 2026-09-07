'use client';

import { useState, useEffect } from 'react';
import Link from 'next/link';
import { SmartImage } from '@/components/ui/SmartImage';
import { useStore } from '@/components/providers/StoreProvider';
import { getApiUrl } from '@/lib/api';

export default function CartDrawer({
  isOpen,
  onClose,
  onCartUpdate,
}: {
  isOpen: boolean;
  onClose: () => void;
  onCartUpdate?: () => void;
}) {
  const {
    cartItems,
    cartTotal,
    cartSessionId,
    currency,
    refreshCart,
    formatPrice,
    savedAddress,
    openAddressModal,
    showToast
  } = useStore();

  const [updatingId, setUpdatingId] = useState<number | null>(null);

  // Handle escape key
  useEffect(() => {
    const handler = (e: KeyboardEvent) => { if (e.key === 'Escape') onClose(); };
    if (isOpen) window.addEventListener('keydown', handler);
    return () => window.removeEventListener('keydown', handler);
  }, [isOpen, onClose]);

  async function updateQuantity(itemId: number, quantity: number) {
    setUpdatingId(itemId);
    try {
      const sid = cartSessionId || (typeof window !== 'undefined' ? localStorage.getItem('cart_session_id') : '') || '';
      const res = await fetch(`${getApiUrl()}/public/cart/items/${itemId}`, {
        method: 'PATCH',
        headers: {
          'Content-Type': 'application/json',
          'Cart-Session-Id': sid,
        },
        body: JSON.stringify({ quantity }),
      });
      if (res.ok) {
        await refreshCart();
        if (onCartUpdate) onCartUpdate();
      } else {
        showToast('Failed to update item quantity', 'error');
      }
    } catch (err) {
      console.error('Quantity update failed', err);
    } finally {
      setUpdatingId(null);
    }
  }

  const isEmpty = !cartItems || cartItems.length === 0;
  const totalItemCount = cartItems.reduce((acc, i) => acc + i.quantity, 0);

  return (
    <>
      {/* Backdrop */}
      <div
        className={`fixed inset-0 z-50 bg-stone-950/70 backdrop-blur-xs transition-opacity duration-300 ${
          isOpen ? 'opacity-100' : 'opacity-0 pointer-events-none'
        }`}
        onClick={onClose}
        aria-hidden="true"
      />

      {/* Drawer Panel */}
      <div
        role="dialog"
        aria-modal="true"
        aria-label="Shopping Cart"
        className={`fixed inset-y-0 right-0 z-50 w-full max-w-md border-l border-white/60 bg-white/86 backdrop-blur-2xl flex flex-col shadow-[-22px_0_65px_rgba(20,18,16,.26)] transition-transform duration-500 ease-out ${
          isOpen ? 'translate-x-0' : 'translate-x-full'
        }`}
      >
        {/* Header */}
        <div className="px-5 sm:px-6 py-4 border-b border-stone-200/70 shrink-0 bg-white/55">
          <div className="flex items-center justify-between">
            <div className="flex items-center gap-2">
              <h2 className="font-display font-black uppercase text-lg tracking-wider text-stone-900">
                Shopping Bag
              </h2>
              {!isEmpty && (
                <span className="bg-[#c2410c] text-amber-50 font-display font-bold text-xs px-2.5 py-0.5 rounded-full shadow-xs">
                  {totalItemCount} {totalItemCount === 1 ? 'item' : 'items'}
                </span>
              )}
            </div>
            <button
              onClick={onClose}
              className="w-8 h-8 flex items-center justify-center text-stone-500 hover:text-stone-900 border border-stone-300 hover:border-stone-500 rounded-lg transition-colors cursor-pointer active:scale-95"
              aria-label="Close cart"
            >
              ✕
            </button>
          </div>

        </div>

        {/* Address Delivery Banner */}
        <div className="px-5 sm:px-6 py-3 bg-stone-950 text-white flex items-center justify-between text-xs">
          <div className="flex items-center gap-2 text-white/85 truncate font-medium">
            <svg className="w-4 h-4 text-[#f97316] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
            </svg>
            <span className="truncate">
              {savedAddress ? `Deliver to: ${savedAddress.name} (${savedAddress.city})` : 'Set your address for an accurate delivery price'}
            </span>
          </div>
          <button
            onClick={() => { onClose(); openAddressModal(); }}
            className="text-[#fbbf24] font-display font-black uppercase text-[11px] tracking-wider hover:text-white shrink-0 ml-2 cursor-pointer"
          >
            {savedAddress ? 'Edit' : '+ Set Address'}
          </button>
        </div>

        {/* Content List */}
        <div className="flex-1 overflow-y-auto p-4 sm:p-5 bg-[linear-gradient(135deg,rgba(250,248,245,.8),rgba(238,232,224,.55))]">
          {isEmpty ? (
            <div className="flex flex-col items-center justify-center py-20 text-center gap-4">
              <div className="w-16 h-16 rounded-full bg-stone-100 flex items-center justify-center text-stone-400 border border-stone-200">
                <svg className="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                </svg>
              </div>
              <div>
                <p className="font-display font-bold text-base uppercase tracking-wider text-stone-800">
                  Your bag is empty
                </p>
                <p className="text-xs text-stone-500 mt-1">
                  Discover precision-crafted leather cases, watch straps & accessories.
                </p>
              </div>
              <button
                onClick={onClose}
                className="mt-2 bg-[#c2410c] text-amber-50 font-display font-black text-xs uppercase tracking-wider px-6 py-3 rounded-xl shadow-sm hover:bg-[#9a3412] transition-colors cursor-pointer active:scale-95"
              >
                Start Shopping →
              </button>
            </div>
          ) : (
            <ul className="space-y-3">
              {cartItems.map((item) => (
                <li key={item.id} className="flex gap-3 rounded-2xl border border-white/80 bg-white/80 p-3 shadow-[0_12px_24px_-20px_rgba(28,25,23,.5)]">
                  {/* Thumbnail */}
                  <div className="w-24 h-24 bg-stone-100 shrink-0 flex items-center justify-center overflow-hidden border border-stone-200 rounded-xl relative shadow-inner">
                    {item.image_url ? (
                      <SmartImage
                        src={item.image_url}
                        alt={item.title}
                        fill
                        cropMode="cover"
                        objectPosition="center"
                      />
                    ) : (
                      <div className="w-6 h-6 text-stone-400 text-xl font-bold">A</div>
                    )}
                  </div>

                  {/* Details */}
                  <div className="flex-1 min-w-0 flex flex-col justify-between">
                    <div className="pr-1">
                      <p className="font-display text-sm font-black uppercase text-stone-900 leading-tight line-clamp-2">{item.title}</p>
                      {item.variant_title && (
                        <p className="font-display text-xs text-stone-500 uppercase tracking-wider mt-0.5">
                          {item.variant_title}
                        </p>
                      )}
                    </div>

                    <div className="flex items-end justify-between mt-3">
                      <div className="flex flex-col gap-1.5">
                        {/* Quantity Controls */}
                        <div className="flex items-center border border-stone-300 bg-white rounded-lg overflow-hidden w-fit shadow-2xs">
                          <button
                            onClick={() => updateQuantity(item.id, item.quantity - 1)}
                            disabled={updatingId === item.id}
                            className="w-8 h-8 flex items-center justify-center font-bold text-sm text-stone-700 hover:bg-stone-100 transition-colors cursor-pointer disabled:opacity-50"
                            aria-label="Decrease quantity"
                          >
                            −
                          </button>
                          <span className="w-8 h-8 flex items-center justify-center font-bold text-xs text-stone-900 border-x border-stone-200">
                            {item.quantity}
                          </span>
                          <button
                            onClick={() => updateQuantity(item.id, item.quantity + 1)}
                            disabled={updatingId === item.id}
                            className="w-8 h-8 flex items-center justify-center font-bold text-sm text-stone-700 hover:bg-stone-100 transition-colors cursor-pointer disabled:opacity-50"
                            aria-label="Increase quantity"
                          >
                            +
                          </button>
                        </div>
                        
                        {/* Remove Action */}
                        <button 
                          onClick={() => updateQuantity(item.id, 0)}
                          disabled={updatingId === item.id}
                          className="text-[10px] text-rose-600 hover:text-rose-800 font-display font-semibold uppercase tracking-wider text-left py-0.5 cursor-pointer"
                        >
                          Remove Item
                        </button>
                      </div>

                      {/* Line Total */}
                      <span className="font-display font-black text-sm text-[#c2410c] mb-6 text-right">
                        {formatPrice(item.line_total_minor || (item.unit_price_minor * item.quantity), currency)}
                      </span>
                    </div>
                  </div>
                </li>
              ))}
            </ul>
          )}
        </div>

        {/* Footer */}
        {!isEmpty && (
          <div className="border-t border-white/70 p-5 sm:p-6 space-y-4 shrink-0 bg-white/88 backdrop-blur-xl shadow-[0_-14px_36px_-28px_rgba(28,25,23,.6)]">
            <div className="space-y-2">
              <div className="flex justify-between text-sm">
                <span className="text-stone-600">Subtotal</span>
                <span className="font-display font-bold text-base text-stone-900">{formatPrice(cartTotal, currency)}</span>
              </div>
              <div className="border-t border-stone-200 pt-2 flex justify-between text-sm">
                <span className="font-display font-black text-stone-900 uppercase tracking-wide">Items total</span>
                <span className="font-display font-black text-lg text-[#c2410c]">
                  {formatPrice(cartTotal, currency)}
                </span>
              </div>
              <p className="text-[10px] text-stone-500 font-display uppercase tracking-wider pt-0.5">
                Delivery and payment fees are shown clearly before you place the order.
              </p>
            </div>

            <Link
              href="/checkout"
              onClick={onClose}
              className="flex items-center justify-center gap-3 w-full py-4 bg-[#c2410c] text-amber-50 font-display font-black text-sm uppercase tracking-wider rounded-xl hover:bg-[#9a3412] active:scale-95 transition-all shadow-lg cursor-pointer"
            >
              <span>Proceed to Checkout</span>
              <span className="text-lg">→</span>
            </Link>
          </div>
        )}
      </div>
    </>
  );
}
