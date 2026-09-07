'use client';

import React, { createContext, useContext, useEffect, useState, useCallback } from 'react';
import { getApiUrl, generateUUID } from '@/lib/api';

export interface CartItem {
  id: number;
  product_id: number;
  variant_id: number | null;
  title: string;
  variant_title?: string;
  image_url?: string;
  quantity: number;
  unit_price_minor: number;
  line_total_minor: number;
}

export interface Customer {
  id: number;
  first_name: string;
  last_name: string;
  email: string;
  phone?: string;
}

export interface SavedAddress {
  name: string;
  phone: string;
  email?: string;
  city: string;
  state?: string;
  line1: string;
  postal?: string;
  country?: string;
}

export interface DynamicSettings {
  storeName?: string;
  announcementText?: string;
  announcementSecondary?: string;
  salePromoTitle?: string;
  salePromoDiscount?: string;
  salePromoBgColor?: string;
  salePromoLink?: string;
  signatureHeroTitle?: string;
  signatureHeroSubtitle?: string;
  signatureHeroImage?: string;
  signatureHeroButtonText?: string;
  signatureHeroButtonLink?: string;
  homeHeroTitle?: string;
  homeHeroSubtitle?: string;
  homeHeroImage?: string;
  homeHeroCtaText?: string;
  homeHeroCtaLink?: string;
  homeBgColor?: string;
  homeBgImage?: string;
  homeBannerImage?: string;
  guaranteeTitle?: string;
  guaranteeSubtitle?: string;
  guaranteeImage?: string;
  logoUrl?: string;
  whiteLogoUrl?: string;
  supportEmail?: string;
  supportPhone?: string;
  currency?: string;
  taxRate?: string | number;
  shippingDomesticStandard?: string | number;
  shippingDomesticExpress?: string | number;
  shippingInternationalStandard?: string | number;
  shippingInternationalExpress?: string | number;
}

interface ToastMessage {
  id: string;
  text: string;
  type?: 'success' | 'info' | 'error';
}

interface StoreContextType {
  storeName: string;
  currency: string;
  isLoaded: boolean;
  settings: DynamicSettings;
  refreshSettings: () => Promise<void>;
  // Cart
  cartCount: number;
  cartItems: CartItem[];
  cartTotal: number;
  cartSessionId: string;
  isCartOpen: boolean;
  openCart: () => void;
  closeCart: () => void;
  toggleCart: () => void;
  addToCart: (productIdOrSlug: number | string, variantId?: number | null, quantity?: number) => Promise<void>;
  refreshCart: () => Promise<void>;
  // Customer auth
  customer: Customer | null;
  customerToken: string | null;
  loginCustomer: (email: string, password: string) => Promise<void>;
  registerCustomer: (data: { first_name: string; last_name: string; email: string; password: string; password_confirmation: string }) => Promise<void>;
  logoutCustomer: () => void;
  // Saved Address
  savedAddress: SavedAddress | null;
  saveAddress: (addr: SavedAddress) => void;
  isAddressModalOpen: boolean;
  openAddressModal: () => void;
  closeAddressModal: () => void;
  // Toast & Formatting
  toast: ToastMessage | null;
  showToast: (text: string, type?: 'success' | 'info' | 'error') => void;
  formatPrice: (amountMinor: number, customCurrency?: string) => string;
}

const DEFAULT_SETTINGS: DynamicSettings = {
  storeName: 'ATELIER',
  announcementText: 'FAST DELIVERY ACROSS EGYPT · CASH ON DELIVERY & PAYMOB · EASY EXCHANGES',
  announcementSecondary: 'EGYPTIAN LUXURY CRAFTSMANSHIP · 24H EXPRESS DISPATCH · CASH ON DELIVERY & PAYMOB',
  homeHeroTitle: 'PRECISION CRAFTED ACCESSORIES',
  homeHeroSubtitle: 'Handcrafted full-grain leather cases, aerospace titanium hardware, and minimalist everyday carry.',
  homeHeroImage: 'https://images.unsplash.com/photo-1620138290379-3d12234551d0?w=1800&q=85',
  homeHeroCtaText: 'Explore Catalog',
  homeHeroCtaLink: '/collections/all',
  homeBgColor: '#0c0a09',
  homeBgImage: '',
  homeBannerImage: 'https://images.unsplash.com/photo-1548036328-c9fa89d128fa?w=1600&q=80',
  guaranteeTitle: 'The ATELIER Guarantee',
  guaranteeSubtitle: '100% Genuine Italian & Egyptian Leather · 2-Year Craftsmanship Warranty · Fast Door-to-Door Delivery across Egypt',
  guaranteeImage: 'https://images.unsplash.com/photo-1548036328-c9fa89d128fa?w=1600&q=80',
  currency: 'EGP',
  taxRate: '14.0',
  shippingDomesticStandard: '10.00',
  shippingDomesticExpress: '25.00',
  shippingInternationalStandard: '25.00',
  shippingInternationalExpress: '40.00',
};

const StoreContext = createContext<StoreContextType | undefined>(undefined);

export function StoreProvider({ children, storeName: initialStoreName }: { children: React.ReactNode; storeName?: string }) {
  const [storeName, setStoreName] = useState(initialStoreName || 'ATELIER');
  const [currency, setCurrency] = useState('EGP');
  const [settings, setSettings] = useState<DynamicSettings>(DEFAULT_SETTINGS);
  const [isLoaded, setIsLoaded] = useState(false);
  const [cartSessionId, setCartSessionId] = useState('');
  const [cartItems, setCartItems] = useState<CartItem[]>([]);
  const [cartTotal, setCartTotal] = useState(0);
  const [isCartOpen, setIsCartOpen] = useState(false);
  const [customer, setCustomer] = useState<Customer | null>(null);
  const [customerToken, setCustomerToken] = useState<string | null>(null);
  const [savedAddress, setSavedAddress] = useState<SavedAddress | null>(null);
  const [isAddressModalOpen, setIsAddressModalOpen] = useState(false);
  const [toast, setToast] = useState<ToastMessage | null>(null);

  const cartCount = cartItems.reduce((acc, item) => acc + item.quantity, 0);

  const refreshSettings = useCallback(async () => {
    // 1. Restore from cache immediately for zero flicker
    if (typeof window !== 'undefined') {
      const cached = localStorage.getItem('cached_store_settings');
      if (cached) {
        try {
          const parsed = JSON.parse(cached);
          setSettings(s => ({ ...s, ...parsed }));
          if (parsed.storeName) setStoreName(parsed.storeName);
          if (parsed.currency) setCurrency(parsed.currency);
        } catch {}
      }
    }

    // 2. Fetch fresh settings from server safely
    try {
      const res = await fetch(`${getApiUrl()}/public/settings`, { cache: 'no-store' });
      if (res.ok) {
        const json = await res.json();
        if (json.data) {
          setSettings(s => ({ ...s, ...json.data }));
          if (json.data.storeName) setStoreName(json.data.storeName);
          if (json.data.currency) setCurrency(json.data.currency);
          if (typeof window !== 'undefined') {
            localStorage.setItem('cached_store_settings', JSON.stringify(json.data));
          }
        }
      }
    } catch (err) {
      // Gracefully silent fallback
    }
  }, []);

  const showToast = useCallback((text: string, type: 'success' | 'info' | 'error' = 'success') => {
    const id = generateUUID();
    setToast({ id, text, type });
    setTimeout(() => {
      setToast(prev => prev?.id === id ? null : prev);
    }, 3500);
  }, []);

  const formatPrice = useCallback((amountMinor: number, customCurrency?: string) => {
    const curr = customCurrency || currency || 'EGP';
    const major = (amountMinor || 0) / 100;
    if (curr === 'EGP') {
      return `${major.toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 2 })} EGP`;
    }
    return new Intl.NumberFormat('en-US', {
      style: 'currency',
      currency: curr,
      minimumFractionDigits: 0,
      maximumFractionDigits: 2
    }).format(major);
  }, [currency]);

  const refreshCart = useCallback(async (sessionId?: string) => {
    let sid = sessionId;
    if (!sid && typeof window !== 'undefined') {
      sid = localStorage.getItem('cart_session_id') || '';
    }
    if (!sid) return;
    try {
      const res = await fetch(`${getApiUrl()}/public/cart`, {
        headers: { 'Cart-Session-Id': sid },
        cache: 'no-store'
      });
      if (res.ok) {
        const data = await res.json();
        setCartItems(data.items || []);
        setCartTotal(data.total_minor || 0);
      }
    } catch (err) {
      // Gracefully handle offline or network delay without uncaught exception
    }
  }, []);

  useEffect(() => {
    let sessionId = localStorage.getItem('cart_session_id');
    if (!sessionId) {
      sessionId = generateUUID();
      localStorage.setItem('cart_session_id', sessionId);
    }
    setCartSessionId(sessionId);
    refreshCart(sessionId);

    // Restore customer session
    const token = localStorage.getItem('customer_token');
    const custData = localStorage.getItem('customer_data');
    if (token && custData) {
      setCustomerToken(token);
      try {
        setCustomer(JSON.parse(custData));
      } catch {}
    }

    // Restore saved address (for both logged-in and guest users)
    const addrData = localStorage.getItem('saved_shipping_address');
    if (addrData) {
      try {
        setSavedAddress(JSON.parse(addrData));
      } catch {}
    }

    // Fetch dynamic store settings once on mount
    refreshSettings().finally(() => setIsLoaded(true));

    // Listen for live admin setting updates
    const onSettingsUpdate = () => {
      refreshSettings();
    };
    window.addEventListener('store_settings_updated', onSettingsUpdate);
    window.addEventListener('storage', onSettingsUpdate);
    return () => {
      window.removeEventListener('store_settings_updated', onSettingsUpdate);
      window.removeEventListener('storage', onSettingsUpdate);
    };
  }, [refreshCart, refreshSettings]);

  const saveAddress = (addr: SavedAddress) => {
    setSavedAddress(addr);
    localStorage.setItem('saved_shipping_address', JSON.stringify(addr));
    showToast('Delivery address saved successfully!', 'success');
  };

  const addToCart = async (productIdOrSlug: number | string, variantId: number | null = null, quantity: number = 1) => {
    let sid = cartSessionId;
    if (!sid && typeof window !== 'undefined') {
      sid = localStorage.getItem('cart_session_id') || '';
      if (!sid) {
        sid = generateUUID();
        localStorage.setItem('cart_session_id', sid);
      }
      setCartSessionId(sid);
    }
    if (!sid) return;

    const payload: any = {
      quantity,
      product_variant_id: variantId,
    };
    if (typeof productIdOrSlug === 'number') {
      payload.product_id = productIdOrSlug;
    } else if (!isNaN(Number(productIdOrSlug)) && String(productIdOrSlug).trim() !== '') {
      payload.product_id = Number(productIdOrSlug);
    } else {
      payload.product_slug = String(productIdOrSlug);
    }

    try {
      const res = await fetch(`${getApiUrl()}/public/cart/items`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Cart-Session-Id': sid
        },
        body: JSON.stringify(payload)
      });

      if (!res.ok) {
        const errJson = await res.json().catch(() => ({}));
        showToast(errJson.message || 'Failed to add item to cart', 'error');
        throw new Error(errJson.message || 'Failed to add to cart');
      }

      const updatedData = await res.json();
      if (updatedData.items) {
        setCartItems(updatedData.items);
        setCartTotal(updatedData.total_minor || 0);
      } else {
        await refreshCart(sid);
      }

      showToast('Added to bag!', 'success');
      setIsCartOpen(true);
    } catch (err: any) {
      console.error(err);
      throw err;
    }
  };

  const loginCustomer = async (email: string, password: string) => {
    const res = await fetch(`${getApiUrl()}/public/auth/login`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ email, password })
    });
    if (!res.ok) {
      const err = await res.json().catch(() => ({}));
      throw new Error(err.message || 'Login failed');
    }
    const data = await res.json();
    localStorage.setItem('customer_token', data.token);
    localStorage.setItem('customer_data', JSON.stringify(data.customer));
    setCustomerToken(data.token);
    setCustomer(data.customer);

    // Auto-update saved address name/phone if empty
    setSavedAddress(prev => {
      const updated = {
        name: prev?.name || `${data.customer.first_name} ${data.customer.last_name}`.trim(),
        phone: prev?.phone || data.customer.phone || '',
        email: data.customer.email,
        city: prev?.city || 'Cairo',
        state: prev?.state || '',
        line1: prev?.line1 || '',
        postal: prev?.postal || '',
        country: 'EG',
      };
      localStorage.setItem('saved_shipping_address', JSON.stringify(updated));
      return updated;
    });

    showToast(`Welcome back, ${data.customer.first_name}!`);
  };

  const registerCustomer = async (data: { first_name: string; last_name: string; email: string; password: string; password_confirmation: string }) => {
    const res = await fetch(`${getApiUrl()}/public/auth/register`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(data)
    });
    if (!res.ok) {
      const err = await res.json().catch(() => ({}));
      throw new Error(err.message || 'Registration failed');
    }
    const resData = await res.json();
    localStorage.setItem('customer_token', resData.token);
    localStorage.setItem('customer_data', JSON.stringify(resData.customer));
    setCustomerToken(resData.token);
    setCustomer(resData.customer);

    const updated = {
      name: `${resData.customer.first_name} ${resData.customer.last_name}`.trim(),
      phone: '',
      email: resData.customer.email,
      city: 'Cairo',
      state: '',
      line1: '',
      postal: '',
      country: 'EG',
    };
    setSavedAddress(updated);
    localStorage.setItem('saved_shipping_address', JSON.stringify(updated));

    showToast(`Account created! Welcome, ${resData.customer.first_name}!`);
  };

  const logoutCustomer = async () => {
    if (customerToken) {
      await fetch(`${getApiUrl()}/public/auth/logout`, {
        method: 'POST',
        headers: { 'Authorization': `Bearer ${customerToken}` }
      }).catch(() => {});
    }
    localStorage.removeItem('customer_token');
    localStorage.removeItem('customer_data');
    setCustomerToken(null);
    setCustomer(null);
    showToast('Signed out successfully.');
  };

  return (
    <StoreContext.Provider value={{
      storeName, currency, isLoaded, settings, refreshSettings,
      cartCount, cartItems, cartTotal, cartSessionId,
      isCartOpen, openCart: () => setIsCartOpen(true), closeCart: () => setIsCartOpen(false), toggleCart: () => setIsCartOpen(p => !p),
      addToCart, refreshCart: () => refreshCart(cartSessionId),
      customer, customerToken, loginCustomer, registerCustomer, logoutCustomer,
      savedAddress, saveAddress,
      isAddressModalOpen, openAddressModal: () => setIsAddressModalOpen(true), closeAddressModal: () => setIsAddressModalOpen(false),
      toast, showToast, formatPrice
    }}>
      {children}
      {/* Global Toast Notification */}
      {toast && (
        <div className="fixed bottom-6 right-6 z-50 animate-fade-up">
          <div className={`flex items-center gap-3 px-5 py-4 border shadow-2xl backdrop-blur-xl ${
            toast.type === 'error'
              ? 'bg-red-950/95 border-red-500/50 text-red-200'
              : 'bg-[#111111]/95 border-[#e8ff00]/50 text-white'
          }`}>
            <span className={`w-2.5 h-2.5 rounded-full ${toast.type === 'error' ? 'bg-red-400' : 'bg-[#e8ff00] shadow-[0_0_10px_#e8ff00]'}`} />
            <span className="font-display font-bold text-sm tracking-wide">{toast.text}</span>
            {toast.type !== 'error' && (
              <button
                onClick={() => setIsCartOpen(true)}
                className="ml-3 font-display font-black text-xs uppercase tracking-widest text-[#e8ff00] underline hover:text-white transition-colors"
              >
                View Cart →
              </button>
            )}
          </div>
        </div>
      )}
    </StoreContext.Provider>
  );
}

export function useStore() {
  const context = useContext(StoreContext);
  if (context === undefined) {
    throw new Error('useStore must be used within a StoreProvider');
  }
  return context;
}
