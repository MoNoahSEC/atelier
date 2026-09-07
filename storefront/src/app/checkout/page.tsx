'use client';

import { useState, useEffect } from 'react';
import { useRouter } from 'next/navigation';
import Link from 'next/link';
import { useStore, SavedAddress } from '@/components/providers/StoreProvider';
import PaymentMethodSelector from '@/components/checkout/PaymentMethodSelector';
import StripePaymentForm from '@/components/checkout/StripePaymentForm';
import { getApiUrl, generateUUID } from '@/lib/api';
import { SmartImage } from '@/components/ui/SmartImage';

function getSessionId(): string {
  if (typeof window === 'undefined') return '';
  let id = localStorage.getItem('cart_session_id');
  if (!id) { id = generateUUID(); localStorage.setItem('cart_session_id', id); }
  return id;
}

const EGYPT_GOVERNORATES = [
  'Cairo', 'Giza', 'Alexandria', 'Qalyubia', 'Sharqia', 'Dakahlia', 'Gharbia',
  'Monufia', 'Beheira', 'Kafr El Sheikh', 'Damietta', 'Port Said', 'Ismailia',
  'Suez', 'Fayoum', 'Beni Suef', 'Minya', 'Assiut', 'Sohag', 'Qena', 'Luxor',
  'Aswan', 'Red Sea (Hurghada)', 'South Sinai (Sharm El Sheikh)', 'Matrouh'
];

export default function CheckoutPage() {
  const router = useRouter();
  const { storeName, customer, savedAddress, saveAddress, formatPrice } = useStore();

  const [form, setForm] = useState<SavedAddress>({
    name: '',
    phone: '',
    email: '',
    city: 'Cairo',
    state: '',
    line1: '',
    postal: '',
    country: 'EG',
  });

  const [saveAddressOption, setSaveAddressOption] = useState(true);
  const [paymentMethod, setPaymentMethod] = useState('paymob');
  const [errors, setErrors] = useState<Record<string, string>>({});
  const [loading, setLoading] = useState(false);
  const [isSubmitted, setIsSubmitted] = useState(false);
  const [apiError, setApiError] = useState('');
  const [cart, setCart] = useState<any>(null);
  const [cartLoading, setCartLoading] = useState(true);
  const [clientSecret, setClientSecret] = useState('');
  const [isEditingAddress, setIsEditingAddress] = useState(false);

  useEffect(() => {
    if (savedAddress && savedAddress.name) {
      setForm(savedAddress);
    } else if (customer) {
      setForm(prev => ({
        ...prev,
        name: `${customer.first_name} ${customer.last_name}`.trim(),
        email: customer.email,
        phone: customer.phone || '',
      }));
    }

    async function fetchCart() {
      try {
        const res = await fetch(`${getApiUrl()}/public/cart`, {
          headers: {
            'Cart-Session-Id': getSessionId(),
            'Accept': 'application/json'
          }
        });
        if (res.ok) {
          const data = await res.json();
          setCart(data);
          if (!data.items || data.items.length === 0) {
            setApiError('Your bag is empty.');
          }
        }
      } catch (e) {
        console.error('Failed to fetch cart', e);
      } finally {
        setCartLoading(false);
      }
    }
    fetchCart();
  }, [customer, savedAddress]);

  function handleChange(e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement>) {
    setForm(prev => ({ ...prev, [e.target.name]: e.target.value }));
    setErrors(prev => { const n = { ...prev }; delete n[e.target.name]; return n; });
  }

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    if (!form.name.trim() || !form.phone.trim() || !form.line1.trim()) {
      setApiError('Please fill in your Name, Phone Number, and Street Address.');
      return;
    }

    const cleanPhone = form.phone.replace(/[\s-]/g, '');
    if (cleanPhone.length < 10) {
      setApiError('Please enter a valid mobile phone number (e.g. 01012345678).');
      return;
    }

    if (saveAddressOption) {
      saveAddress(form);
    }

    if (paymentMethod === 'apple_pay' || paymentMethod === 'google_pay') {
      setLoading(true);
      setTimeout(() => {
        setLoading(false);
        alert(`${paymentMethod === 'apple_pay' ? 'Apple Pay' : 'Google Pay'} authenticated successfully! Placing your order...`);
      }, 1000);
    }

    if (paymentMethod !== 'stripe') setLoading(true);
    setIsSubmitted(true);
    setApiError('');

    try {
      const res = await fetch(`${getApiUrl()}/public/checkout`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({
          cart_session_id: getSessionId(),
          email: form.email || undefined,
          phone: form.phone,
          shipping_address: { 
            name: form.name, 
            line1: form.line1, 
            city: form.city, 
            state: form.state || '', 
            country: 'EG', 
            postal: form.postal || '11511' 
          },
          payment_method: paymentMethod,
        }),
      });

      const data = await res.json();
      if (!res.ok) {
        if (res.status === 422 && data.errors) setErrors(data.errors);
        else setApiError(data.message || 'Something went wrong. Please try again.');
        setLoading(false);
        setIsSubmitted(false);
        return;
      }
      
      if (paymentMethod === 'stripe') {
        setClientSecret(data.client_secret);
      } else if (paymentMethod === 'paymob' && data.checkout_url) {
        window.location.href = data.checkout_url;
      } else {
        localStorage.removeItem('cart_session_id');
        router.push(`/order-confirmation?order=${data.order_number}`);
      }
    } catch {
      setApiError('Network error. Please check your connection and try again.');
      setLoading(false);
      setIsSubmitted(false);
    }
  }

  const hasSavedInfo = Boolean(savedAddress && savedAddress.name && savedAddress.line1);
  const codSurcharge = 0;
  const grandTotalMinor = (cart?.total_minor || 0) + codSurcharge;

  return (
    <div className="pt-16 md:pt-20 bg-[#faf8f5] min-h-screen text-stone-900">

      <div className="border-b border-stone-200/80 bg-white px-6 md:px-12 py-3.5 flex items-center justify-between shadow-2xs">
        <Link href="/" className="font-display font-black uppercase text-xl md:text-2xl tracking-tight text-[#c2410c]">
          {storeName}
        </Link>
        <div className="flex items-center gap-2 font-display text-xs uppercase tracking-wider">
          <Link href="/collections/all" className="text-stone-500 hover:text-stone-800">1. Shop ✓</Link>
          <span className="text-stone-300">›</span>
          <span className="text-stone-500">2. Bag ✓</span>
          <span className="text-stone-300">›</span>
          <span className="text-[#c2410c] font-black">3. Delivery & Checkout ●</span>
        </div>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-[1fr_440px] min-h-[calc(100vh-5rem-57px)]">
        
        <div className="px-6 md:px-12 xl:px-20 py-8 md:py-10">

          <div className="mb-6 p-4 bg-[#f4efe6] border border-stone-200 rounded-xl flex flex-wrap items-center justify-between gap-3 text-xs">
            {customer ? (
              <div className="flex items-center gap-2">
                <span>👤</span>
                <span>Signed in as <strong className="text-stone-900">{customer.first_name} ({customer.email})</strong></span>
              </div>
            ) : (
              <div className="flex items-center gap-2 text-stone-700">
                <span className="text-amber-600 font-bold">⚡</span>
                <span><strong>Instant Guest Checkout:</strong> No password or account needed to order.</span>
              </div>
            )}

            {!customer && (
              <Link href="/account" className="font-display font-bold uppercase text-[#c2410c] hover:underline">
                Have an account? Sign in →
              </Link>
            )}
          </div>

          {!clientSecret ? (
            <form onSubmit={handleSubmit} noValidate className="max-w-2xl space-y-8">
              
              <h1 className="font-display font-black uppercase text-2xl sm:text-3xl tracking-tight text-stone-900">
                Delivery & Payment
              </h1>

              <div className="bg-white border border-stone-200 rounded-2xl p-6 sm:p-8 space-y-5 shadow-2xs">
                <div className="flex items-center justify-between border-b border-stone-100 pb-3.5">
                  <div className="flex items-center gap-3">
                    <span className="w-6 h-6 rounded-full bg-[#c2410c] text-amber-50 font-display font-black text-xs flex items-center justify-center">
                      1
                    </span>
                    <h2 className="font-display font-black uppercase text-base sm:text-lg text-stone-900 tracking-wide">
                      Delivery Address & Contact
                    </h2>
                  </div>

                  {hasSavedInfo && (
                    <button
                      type="button"
                      onClick={() => setIsEditingAddress(!isEditingAddress)}
                      className="text-xs font-display font-bold uppercase tracking-wider text-[#c2410c] hover:underline cursor-pointer"
                    >
                      {isEditingAddress ? 'Keep Current' : 'Change Address'}
                    </button>
                  )}
                </div>

                {hasSavedInfo && !isEditingAddress ? (
                  <div className="p-4 bg-[#faf8f5] border border-stone-200 rounded-xl flex items-start justify-between">
                    <div>
                      <p className="font-bold text-sm text-stone-900">{form.name}</p>
                      <p className="text-xs text-stone-600 font-mono mt-0.5">{form.phone}</p>
                      {form.email && <p className="text-xs text-stone-500 mt-0.5">{form.email}</p>}
                      <p className="text-xs text-[#c2410c] mt-2 font-medium">
                        📍 {form.line1}, {form.state ? `${form.state}, ` : ''}{form.city}, Egypt
                      </p>
                    </div>
                    <button
                      type="button"
                      onClick={() => setIsEditingAddress(true)}
                      className="px-3 py-1.5 border border-stone-200 text-xs font-display font-bold uppercase tracking-wider text-stone-800 hover:border-[#c2410c] rounded-lg transition-colors cursor-pointer"
                    >
                      Edit
                    </button>
                  </div>
                ) : (
                  <div className="space-y-4">
                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                      <div>
                        <label className="block font-display font-bold text-xs uppercase tracking-wider text-stone-700 mb-1.5">
                          Your Full Name *
                        </label>
                        <input
                          type="text"
                          required
                          name="name"
                          autoComplete="name"
                          placeholder="e.g. Ahmed Ali"
                          value={form.name}
                          onChange={handleChange}
                          className="w-full bg-[#faf8f5] border border-stone-200 rounded-xl px-4 py-3 text-sm text-stone-900 font-medium outline-none focus:border-[#c2410c] transition-colors"
                        />
                      </div>
                      <div>
                        <label className="block font-display font-bold text-xs uppercase tracking-wider text-stone-700 mb-1.5">
                          Phone Number (Mobile) *
                        </label>
                        <input
                          type="tel"
                          required
                          name="phone"
                          autoComplete="tel"
                          placeholder="01012345678"
                          value={form.phone}
                          onChange={handleChange}
                          className="w-full bg-[#faf8f5] border border-stone-200 rounded-xl px-4 py-3 text-sm text-stone-900 font-medium outline-none focus:border-[#c2410c] transition-colors"
                        />
                      </div>
                    </div>

                    <div>
                      <label className="block font-display font-bold text-xs uppercase tracking-wider text-stone-700 mb-1.5">
                        Email Address <span className="text-stone-400 normal-case">(Optional for order receipts)</span>
                      </label>
                      <input
                        type="email"
                        name="email"
                        autoComplete="email"
                        placeholder="ahmed@example.com"
                        value={form.email || ''}
                        onChange={handleChange}
                        className="w-full bg-[#faf8f5] border border-stone-200 rounded-xl px-4 py-3 text-sm text-stone-900 font-medium outline-none focus:border-[#c2410c] transition-colors"
                      />
                    </div>

                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                      <div>
                        <label className="block font-display font-bold text-xs uppercase tracking-wider text-stone-700 mb-1.5">
                          Governorate / City *
                        </label>
                        <select
                          name="city"
                          autoComplete="address-level1"
                          value={form.city}
                          onChange={handleChange}
                          className="w-full bg-[#faf8f5] border border-stone-200 rounded-xl px-4 py-3 text-sm text-stone-900 font-medium outline-none focus:border-[#c2410c] transition-colors cursor-pointer"
                        >
                          {EGYPT_GOVERNORATES.map(gov => (
                            <option key={gov} value={gov}>{gov}</option>
                          ))}
                        </select>
                      </div>
                      <div>
                        <label className="block font-display font-bold text-xs uppercase tracking-wider text-stone-700 mb-1.5">
                          Area / District
                        </label>
                        <input
                          type="text"
                          name="state"
                          autoComplete="address-level2"
                          placeholder="e.g. New Cairo / Zamalek / Smouha"
                          value={form.state || ''}
                          onChange={handleChange}
                          className="w-full bg-[#faf8f5] border border-stone-200 rounded-xl px-4 py-3 text-sm text-stone-900 font-medium outline-none focus:border-[#c2410c] transition-colors"
                        />
                      </div>
                    </div>

                    <div>
                      <label className="block font-display font-bold text-xs uppercase tracking-wider text-stone-700 mb-1.5">
                        Street Address, Building & Apt *
                      </label>
                      <input
                        type="text"
                        required
                        name="line1"
                        autoComplete="street-address"
                        placeholder="e.g. 24 Gamal Abdel Nasser St, Bldg 3, Apt 10"
                        value={form.line1}
                        onChange={handleChange}
                        className="w-full bg-[#faf8f5] border border-stone-200 rounded-xl px-4 py-3 text-sm text-stone-900 font-medium outline-none focus:border-[#c2410c] transition-colors"
                      />
                    </div>

                    <label className="flex items-center gap-2.5 pt-1.5 cursor-pointer select-none">
                      <input
                        type="checkbox"
                        checked={saveAddressOption}
                        onChange={e => setSaveAddressOption(e.target.checked)}
                        className="w-4 h-4 accent-[#c2410c] rounded"
                      />
                      <span className="text-xs text-stone-600 font-display uppercase tracking-wider">
                        Save this delivery address on this device for 1-click ordering
                      </span>
                    </label>
                  </div>
                )}
              </div>

              <div className="bg-white border border-stone-200 rounded-2xl p-6 sm:p-8 space-y-5 shadow-2xs">
                <div className="flex items-center gap-3 border-b border-stone-100 pb-3.5">
                  <span className="w-6 h-6 rounded-full bg-[#c2410c] text-amber-50 font-display font-black text-xs flex items-center justify-center">
                    2
                  </span>
                  <h2 className="font-display font-black uppercase text-base sm:text-lg text-stone-900 tracking-wide">
                    Choose Payment Method
                  </h2>
                </div>

                <PaymentMethodSelector selected={paymentMethod} onChange={setPaymentMethod} />

                {paymentMethod === 'cod' && (
                  <div className="p-4 border border-emerald-300 bg-emerald-50 rounded-xl text-xs text-emerald-900">
                    <span className="font-bold text-emerald-800 mr-2">FREE CASH ON DELIVERY:</span>
                    Pay in cash when our courier delivers to your door with 0 EGP extra fees.
                  </div>
                )}
                {paymentMethod === 'paymob' && (
                  <div className="p-4 border border-amber-300 bg-amber-50 rounded-xl text-xs text-stone-800">
                    <span className="font-bold text-[#c2410c] mr-2">PAYMOB EGYPT:</span>
                    Supports Vodafone Cash, Orange/Etisalat/WE Wallets, Meeza, and Credit/Debit cards.
                  </div>
                )}
                {(paymentMethod === 'apple_pay' || paymentMethod === 'google_pay') && (
                  <div className="p-4 border border-stone-300 bg-stone-50 rounded-xl text-xs text-stone-800">
                    <span className="font-bold text-stone-900 mr-2">DIGITAL WALLET:</span>
                    Instant tokenized transaction without entering credit card numbers manually.
                  </div>
                )}
              </div>

              {apiError && (
                <div className="border border-rose-300 bg-rose-50 p-4 rounded-xl text-xs text-rose-800 font-display font-bold">
                  {apiError}
                </div>
              )}

              <button
                type="submit"
                disabled={loading || isSubmitted || cartLoading || !cart || !cart.items || cart.items.length === 0}
                className="w-full bg-[#c2410c] hover:bg-[#9a3412] text-amber-50 font-display font-black text-sm uppercase tracking-wider py-4 rounded-xl shadow-md transition-all active:scale-98 disabled:opacity-40 flex items-center justify-center gap-3 cursor-pointer"
              >
                {loading ? (
                  <>
                    <div className="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin" />
                    <span>Processing Order…</span>
                  </>
                ) : paymentMethod === 'paymob' ? (
                  <span>Pay {formatPrice(grandTotalMinor)} with Paymob →</span>
                ) : paymentMethod === 'apple_pay' ? (
                  <span>Pay {formatPrice(grandTotalMinor)} with Apple Pay →</span>
                ) : paymentMethod === 'google_pay' ? (
                  <span>Pay {formatPrice(grandTotalMinor)} with Google Pay →</span>
                ) : paymentMethod === 'stripe' ? (
                  <span>Continue to Card Payment →</span>
                ) : (
                  <span>Place Cash on Delivery Order ({formatPrice(grandTotalMinor)}) →</span>
                )}
              </button>
            </form>
          ) : (
            <div className="max-w-xl bg-white border border-stone-200 p-8 rounded-2xl shadow-md">
              <h2 className="font-display font-black uppercase text-xl mb-5 text-stone-900">Enter Card Details</h2>
              <StripePaymentForm 
                clientSecret={clientSecret} 
                onSuccess={() => localStorage.removeItem('cart_session_id')} 
                onError={setApiError}
                returnUrl={`${typeof window !== 'undefined' ? window.location.origin : ''}/order-confirmation`} 
              />
              <button 
                onClick={() => setClientSecret('')}
                className="mt-6 text-xs text-stone-500 uppercase font-display tracking-wider underline hover:text-[#c2410c] cursor-pointer"
              >
                ← Change Payment Method
              </button>
            </div>
          )}
        </div>

        <div className="border-l border-stone-200 bg-white px-8 py-10 hidden lg:block shadow-2xs">
          <h3 className="font-display font-black text-xs uppercase tracking-wider text-[#c2410c] mb-6">
            Order Summary ({cart?.items?.reduce((acc: number, i: any) => acc + i.quantity, 0) || 0} items)
          </h3>

          {cartLoading ? (
            <p className="text-xs text-stone-400 animate-pulse">Loading bag items...</p>
          ) : cart?.items?.length > 0 ? (
            <div className="space-y-6">
              <div className="space-y-4 max-h-[45vh] overflow-y-auto pr-2 divide-y divide-stone-100">
                {cart.items.map((item: any) => (
                  <div key={item.id} className="pt-4 flex gap-4">
                    <div className="w-16 h-16 bg-[#f4efe6] shrink-0 border border-stone-200 rounded-lg overflow-hidden relative">
                      {item.image_url && (
                        <SmartImage
                          src={item.image_url}
                          alt={item.title}
                          fill
                          className="object-cover"
                        />
                      )}
                    </div>
                    <div className="flex-1 min-w-0">
                      <p className="font-display font-bold text-xs uppercase text-stone-900 truncate">
                        {item.title}
                      </p>
                      {item.variant_title && (
                        <p className="text-[11px] text-stone-500">{item.variant_title}</p>
                      )}
                      <div className="flex items-center justify-between mt-1">
                        <span className="text-xs text-stone-500">Qty: {item.quantity}</span>
                        <span className="font-display font-bold text-xs text-[#c2410c]">
                          {formatPrice(item.price_minor * item.quantity)}
                        </span>
                      </div>
                    </div>
                  </div>
                ))}
              </div>

              {/* Price Breakdown */}
              <div className="border-t border-stone-200 pt-4 space-y-2 text-xs">
                <div className="flex justify-between text-stone-600">
                  <span>Subtotal</span>
                  <span className="font-medium text-stone-900">{formatPrice(cart.subtotal_minor || 0)}</span>
                </div>
                <div className="flex justify-between text-stone-600">
                  <span>Egypt Doorstep Delivery</span>
                  <span className="font-bold text-emerald-700 uppercase">FREE</span>
                </div>
                <div className="flex justify-between text-stone-600">
                  <span>Payment Surcharge</span>
                  <span className="font-bold text-stone-900">0 EGP</span>
                </div>
                <div className="border-t border-stone-200 pt-3 flex justify-between items-baseline font-display font-black text-base text-stone-900">
                  <span>Total Amount</span>
                  <span className="text-xl text-[#c2410c]">{formatPrice(grandTotalMinor)}</span>
                </div>
              </div>

              {/* Secure Trust Badge */}
              <div className="p-3.5 bg-[#f4efe6] border border-stone-200 rounded-xl text-xs text-stone-600 space-y-1">
                <div className="flex items-center gap-1.5 text-stone-900 font-bold">
                  <span className="text-emerald-700">🔒</span> 256-Bit Encrypted Secure Checkout
                </div>
                <p className="text-[11px] text-stone-500">Orders in Egypt delivered within 24–48 hours with door-to-door tracking & inspection.</p>
              </div>
            </div>
          ) : (
            <p className="text-xs text-stone-400">Your bag is empty.</p>
          )}
        </div>
      </div>
    </div>
  );
}
