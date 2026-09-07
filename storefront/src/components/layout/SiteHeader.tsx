'use client';

import { useState, useEffect, useRef } from 'react';
import Link from 'next/link';
import CartDrawer from '@/components/cart/CartDrawer';
import { AddressModal } from '@/components/ui/AddressModal';
import { SmartImage } from '@/components/ui/SmartImage';
import { useStore } from '@/components/providers/StoreProvider';
import { getApiUrl } from '@/lib/api';
import AppInstallButton from '@/components/pwa/AppInstallButton';

export default function SiteHeader({ collections = [], storeName: propStoreName = 'ATELIER' }: { collections?: any[]; storeName?: string }) {
  const {
    storeName: contextStoreName,
    settings,
    cartCount,
    isCartOpen,
    openCart,
    closeCart,
    customer,
    logoutCustomer,
    openAddressModal,
    savedAddress,
    formatPrice
  } = useStore();
  const storeName = settings?.storeName || contextStoreName || propStoreName;

  const [scrolled, setScrolled] = useState(false);
  const [hideHeader, setHideHeader] = useState(false);
  const lastScrollY = useRef(0);
  const [menuOpen, setMenuOpen] = useState(false);
  const [accountDropdown, setAccountDropdown] = useState(false);
  const dropdownRef = useRef<HTMLDivElement>(null);
  
  const [searchOpen, setSearchOpen] = useState(false);
  const [searchQuery, setSearchQuery] = useState('');
  const [searchResults, setSearchResults] = useState<any[]>([]);
  const [searchLoading, setSearchLoading] = useState(false);

  const [showAppBanner, setShowAppBanner] = useState(false);

  const announcement = settings?.announcementText || 'FAST DELIVERY · CASH ON DELIVERY · EASY EXCHANGES';
  const secondaryAnnouncement = settings?.announcementSecondary || 'EGYPTIAN LUXURY CRAFTSMANSHIP · 24H EXPRESS DISPATCH · CASH ON DELIVERY & PAYMOB';

  useEffect(() => {
    const isCapacitor = typeof window !== 'undefined' && (window as any).Capacitor?.isNative;
    const isMobile = typeof navigator !== 'undefined' && /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
    
    if (isMobile && !isCapacitor) {
      const dismissed = localStorage.getItem('appBannerDismissed');
      if (!dismissed) {
        setShowAppBanner(true);
      }
    }
  }, []);

  const dismissAppBanner = () => {
    localStorage.setItem('appBannerDismissed', 'true');
    setShowAppBanner(false);
  };

  useEffect(() => {
    const onScroll = () => {
      const currentScrollY = window.scrollY;
      setScrolled(currentScrollY > 30);
      
      if (currentScrollY > lastScrollY.current && currentScrollY > 100) {
        setHideHeader(true);
      } else if (currentScrollY < lastScrollY.current || currentScrollY <= 100) {
        setHideHeader(false);
      }
      lastScrollY.current = currentScrollY;
    };
    window.addEventListener('scroll', onScroll, { passive: true });
    return () => window.removeEventListener('scroll', onScroll);
  }, []);

  useEffect(() => {
    const handler = (e: MouseEvent) => {
      if (dropdownRef.current && !dropdownRef.current.contains(e.target as Node)) {
        setAccountDropdown(false);
      }
    };
    document.addEventListener('mousedown', handler);
    return () => document.removeEventListener('mousedown', handler);
  }, []);

  useEffect(() => {
    const handler = (e: KeyboardEvent) => {
      if (e.key === 'Escape') {
        setSearchOpen(false);
        setAccountDropdown(false);
      }
    };
    document.addEventListener('keydown', handler);
    return () => document.removeEventListener('keydown', handler);
  }, []);

  useEffect(() => {
    if (!searchQuery.trim()) {
      setSearchResults([]);
      return;
    }
    const delay = setTimeout(async () => {
      setSearchLoading(true);
      try {
        const res = await fetch(`${getApiUrl()}/public/catalog?search=${encodeURIComponent(searchQuery)}`);
        const json = await res.json();
        setSearchResults(json.data || []);
      } catch (err) {
        console.error(err);
      } finally {
        setSearchLoading(false);
      }
    }, 200);
    return () => clearTimeout(delay);
  }, [searchQuery]);

  const defaultNavItems = [
    { label: 'All Accessories', href: '/collections/all', tag: 'New' },
    { label: 'iPhone Cases', href: '/collections/iphone-cases', tag: null },
    { label: 'Watch Straps', href: '/collections/apple-watch-straps', tag: null },
    { label: 'AirPods Cases', href: '/collections/airpods-cases', tag: null },
    { label: 'Wallets', href: '/collections/wallets-cardholders', tag: null },
    { label: 'Chargers', href: '/collections/chargers-docks', tag: null },
    { label: 'Gift Sets', href: '/collections/bundles-gift-sets', tag: 'Hot' },
  ];

  const navItems = collections && collections.length > 0
    ? [
        { label: 'All Accessories', href: '/collections/all', tag: 'New' },
        ...collections.slice(0, 6).map((c: any) => ({ label: c.title, href: `/collections/${c.slug}`, tag: null })),
      ]
    : defaultNavItems;

  return (
    <>
      {/* Top Announcement Bar */}
      <div className="bg-stone-950 text-stone-200 border-b border-stone-800 text-[11px] font-display font-bold uppercase tracking-wider py-1.5 px-4 overflow-hidden relative z-[51]">
        <div className="flex whitespace-nowrap animate-marquee gap-8 items-center">
          {Array.from({ length: 6 }).map((_, i) => (
            <span key={i} className="flex items-center gap-6 shrink-0">
              <span className="text-amber-400 font-extrabold">{announcement}</span>
              <span className="text-stone-500">✦</span>
              <span className="text-stone-300">{secondaryAnnouncement}</span>
              <span className="text-stone-500">✦</span>
            </span>
          ))}
        </div>
      </div>

      {showAppBanner && (
        <div className="bg-[#c2410c] text-amber-50 px-4 py-2.5 flex items-center justify-between z-[60] relative shadow-xs">
          <div className="flex items-center gap-3">
            <svg className="w-5 h-5 text-amber-100" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
            </svg>
            <div className="flex flex-col">
              <span className="font-display font-black text-xs uppercase tracking-tight leading-tight text-white">Get the {storeName} App</span>
              <span className="text-[11px] font-semibold text-amber-100/90 uppercase">Faster checkout & exclusive drops</span>
            </div>
          </div>
          <div className="flex items-center gap-3">
            <AppInstallButton className="bg-amber-50 text-[#c2410c] px-3.5 py-1.5 font-display font-bold text-xs uppercase tracking-wider rounded-md shadow-sm hover:bg-white transition-colors">
              Install
            </AppInstallButton>
            <button onClick={dismissAppBanner} className="text-amber-100/80 hover:text-white p-1">
              ✕
            </button>
          </div>
        </div>
      )}
      <header
        className={`fixed inset-x-0 z-50 pt-safe transition-all duration-300 ${
          showAppBanner ? 'top-[76px]' : 'top-[29px]'
        } ${
          hideHeader ? '-translate-y-[calc(100%+env(safe-area-inset-top)+35px)]' : 'translate-y-0'
        } ${
          scrolled
            ? 'bg-[#faf8f5]/95 backdrop-blur-xl border-b border-stone-200 shadow-sm pb-3 pt-[calc(0.75rem+env(safe-area-inset-top))]'
            : 'bg-[#faf8f5]/90 backdrop-blur-md border-b border-stone-200/60 pb-3.5 pt-[calc(0.875rem+env(safe-area-inset-top))]'
        }`}
      >
        <div className="mx-auto px-4 md:px-6 lg:px-12 flex items-center justify-between gap-4 md:gap-6">

          {/* Logo */}
          <Link
            href="/"
            className="group font-display font-black text-2xl md:text-3xl uppercase tracking-tight text-stone-900 shrink-0 flex items-center gap-2"
          >
            <span className="text-stone-900 group-hover:text-[#c2410c] transition-colors duration-200">
              {storeName}
            </span>
          </Link>

          {/* Desktop Centered Navigation */}
          <nav className="hidden md:flex items-center justify-center gap-6 lg:gap-8 flex-1" aria-label="Primary">
            <Link
              href="/"
              className="font-display font-bold text-sm uppercase tracking-wider text-stone-900 hover:text-[#c2410c] transition-colors"
            >
              Home
            </Link>
            
            <div className="relative group">
              <Link
                href="/collections/all"
                className="font-display font-bold text-sm uppercase tracking-wider text-stone-900 hover:text-[#c2410c] transition-colors flex items-center gap-1 py-2"
              >
                <span>Collections</span>
                <svg className="w-3.5 h-3.5 text-stone-500 group-hover:text-[#c2410c] group-hover:rotate-180 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2.5} d="M19 9l-7 7-7-7" />
                </svg>
              </Link>
              <div className="absolute top-full left-1/2 -translate-x-1/2 pt-1 w-56 opacity-0 pointer-events-none group-hover:opacity-100 group-hover:pointer-events-auto transition-all duration-200 z-50">
                <div className="bg-white border border-stone-200 shadow-xl rounded-2xl p-2.5 space-y-1">
                  <Link href="/collections/magsafe-wallets" className="block px-3 py-2 text-xs font-display font-bold uppercase tracking-wider text-stone-800 hover:text-[#c2410c] hover:bg-[#faf8f5] rounded-xl transition-colors">
                    MagSafe Smart Wallets
                  </Link>
                  <Link href="/collections/rfid-cardholders" className="block px-3 py-2 text-xs font-display font-bold uppercase tracking-wider text-stone-800 hover:text-[#c2410c] hover:bg-[#faf8f5] rounded-xl transition-colors">
                    Pop-Up RFID Cardholders
                  </Link>
                  <Link href="/collections/leather-wallets" className="block px-3 py-2 text-xs font-display font-bold uppercase tracking-wider text-stone-800 hover:text-[#c2410c] hover:bg-[#faf8f5] rounded-xl transition-colors">
                    Slim Leather Bifolds
                  </Link>
                  <Link href="/collections/money-clips" className="block px-3 py-2 text-xs font-display font-bold uppercase tracking-wider text-stone-800 hover:text-[#c2410c] hover:bg-[#faf8f5] rounded-xl transition-colors">
                    Minimalist Money Clips
                  </Link>
                  <Link href="/collections/travel-wallets" className="block px-3 py-2 text-xs font-display font-bold uppercase tracking-wider text-stone-800 hover:text-[#c2410c] hover:bg-[#faf8f5] rounded-xl transition-colors">
                    Passport & Travel Wallets
                  </Link>
                  <Link href="/collections/powerbank-wallets" className="block px-3 py-2 text-xs font-display font-bold uppercase tracking-wider text-stone-800 hover:text-[#c2410c] hover:bg-[#faf8f5] rounded-xl transition-colors">
                    MagSafe Battery Wallets
                  </Link>
                  <Link href="/collections/bundles-gift-sets" className="block px-3 py-2 text-xs font-display font-bold uppercase tracking-wider text-stone-800 hover:text-[#c2410c] hover:bg-[#faf8f5] rounded-xl transition-colors">
                    Executive Gift Sets
                  </Link>
                  <div className="border-t border-stone-100 pt-1 mt-1">
                    <Link href="/collections/all" className="block px-3 py-2 text-xs font-display font-black uppercase tracking-wider text-[#c2410c] hover:bg-amber-50 rounded-xl transition-colors">
                      View All E-Wallets →
                    </Link>
                  </div>
                </div>
              </div>
            </div>

            <Link
              href="/collections/bundles-gift-sets"
              className="font-display font-bold text-sm uppercase tracking-wider text-stone-900 hover:text-[#c2410c] transition-colors"
            >
              Bundles
            </Link>

            <Link
              href="/collections/bundles-gift-sets"
              className="font-display font-black text-sm uppercase tracking-wider text-stone-900 hover:text-[#c2410c] transition-colors"
            >
              GIFT SETS
            </Link>
          </nav>

          {/* Right Action Icons (Large & Clearly Visible) */}
          <div className="flex items-center gap-3 md:gap-4 shrink-0">

            {/* Live Search Icon */}
            <button
              onClick={() => setSearchOpen(true)}
              aria-label="Search Catalog"
              className="w-10 h-10 flex items-center justify-center text-stone-800 hover:text-[#c2410c] hover:bg-stone-100 rounded-full transition-colors cursor-pointer"
            >
              <svg className="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                <circle cx="11" cy="11" r="8" /><path d="m21 21-4.3-4.3" />
              </svg>
            </button>

            {/* Account / User Icon */}
            <div className="relative" ref={dropdownRef}>
              <button
                onClick={() => setAccountDropdown(!accountDropdown)}
                aria-label="Account"
                className="w-10 h-10 flex items-center justify-center text-stone-800 hover:text-[#c2410c] hover:bg-stone-100 rounded-full transition-colors cursor-pointer"
              >
                <svg className="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                  <path strokeLinecap="round" strokeLinejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
              </button>

              {accountDropdown && (
                <div className="absolute right-0 mt-2 w-56 bg-white border border-stone-200 shadow-xl rounded-2xl p-2 z-50 animate-fade-in divide-y divide-stone-100">
                  <div className="p-3">
                    {customer ? (
                      <>
                        <p className="font-display font-bold text-sm text-stone-900">{customer.first_name} {customer.last_name}</p>
                        <p className="text-xs text-stone-500 truncate font-mono mt-0.5">{customer.email}</p>
                      </>
                    ) : (
                      <>
                        <p className="font-display font-bold text-xs uppercase tracking-wider text-[#c2410c]">Welcome to {storeName}</p>
                        <p className="text-xs text-stone-500 mt-1">Sign in for saved orders & faster checkout</p>
                      </>
                    )}
                  </div>

                  <div className="py-1">
                    <Link
                      href="/account"
                      onClick={() => setAccountDropdown(false)}
                      className="flex items-center gap-2.5 px-3 py-2 text-xs font-display font-bold uppercase tracking-wider text-stone-700 hover:text-[#c2410c] hover:bg-amber-50 rounded-xl transition-colors"
                    >
                      <svg className="w-4 h-4 text-stone-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                      </svg>
                      {customer ? 'My Account' : 'Sign In / Register'}
                    </Link>
                    <Link
                      href="/track-order"
                      onClick={() => setAccountDropdown(false)}
                      className="flex items-center gap-2.5 px-3 py-2 text-xs font-display font-bold uppercase tracking-wider text-stone-700 hover:text-[#c2410c] hover:bg-amber-50 rounded-xl transition-colors"
                    >
                      <svg className="w-4 h-4 text-stone-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                      </svg>
                      Track Order
                    </Link>
                    <button
                      onClick={() => { setAccountDropdown(false); openAddressModal(); }}
                      className="w-full text-left flex items-center gap-2.5 px-3 py-2 text-xs font-display font-bold uppercase tracking-wider text-stone-700 hover:text-[#c2410c] hover:bg-amber-50 rounded-xl transition-colors cursor-pointer"
                    >
                      <svg className="w-4 h-4 text-stone-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                      </svg>
                      Delivery Address
                    </button>
                  </div>

                  {customer && (
                    <div className="pt-1">
                      <button
                        onClick={() => { logoutCustomer(); setAccountDropdown(false); }}
                        className="w-full text-left px-3 py-2 text-xs font-display font-bold uppercase tracking-wider text-rose-600 hover:bg-rose-50 rounded-xl transition-colors cursor-pointer"
                      >
                        Sign Out
                      </button>
                    </div>
                  )}
                </div>
              )}
            </div>

            {/* Shopping Bag Icon with Badge */}
            <button
              onClick={openCart}
              aria-label="Shopping Cart"
              className="relative w-10 h-10 flex items-center justify-center text-stone-800 hover:text-[#c2410c] hover:bg-stone-100 rounded-full transition-colors cursor-pointer"
            >
              <svg className="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                <path strokeLinecap="round" strokeLinejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
              </svg>
              {cartCount > 0 && (
                <span className="absolute 0 top-0.5 right-0.5 min-w-[18px] h-[18px] px-1 rounded-full bg-[#c2410c] text-amber-50 font-display font-black text-[10px] flex items-center justify-center shadow-xs">
                  {cartCount > 99 ? '99+' : cartCount}
                </span>
              )}
            </button>

            {/* Mobile Hamburger Menu Toggle */}
            <button
              onClick={() => setMenuOpen(!menuOpen)}
              className="md:hidden w-10 h-10 flex items-center justify-center text-stone-800 hover:text-[#c2410c] rounded-lg transition-colors cursor-pointer"
              aria-label="Toggle menu"
            >
              <svg className="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                {menuOpen ? (
                  <path strokeLinecap="round" strokeLinejoin="round" d="M6 18L18 6M6 6l12 12" />
                ) : (
                  <path strokeLinecap="round" strokeLinejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                )}
              </svg>
            </button>
          </div>
        </div>
      </header>

      {/* SEARCH OVERLAY */}
      {searchOpen && (
        <div className="fixed inset-0 z-[60] bg-white/95 backdrop-blur-xl flex flex-col p-6 lg:p-12 animate-fade-in pt-safe pb-safe">
          <div className="flex justify-between items-center mb-8 max-w-4xl mx-auto w-full">
            <h2 className="font-display font-bold text-xs uppercase tracking-wider text-slate-600">Catalog Search</h2>
            <button 
              onClick={() => setSearchOpen(false)} 
              className="w-10 h-10 flex items-center justify-center text-slate-500 hover:text-slate-900 border border-slate-300 hover:border-slate-500 transition-colors rounded-full"
            >
              ✕
            </button>
          </div>
          <div className="w-full max-w-4xl mx-auto">
            <input 
              type="text" 
              autoFocus 
              placeholder="Search shoes, bags, accessories..." 
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              className="w-full bg-slate-50 border-2 border-slate-300 rounded-lg text-xl md:text-2xl font-display font-bold text-slate-900 placeholder-slate-400 p-4 outline-none focus:border-slate-900 transition-colors"
            />
            
            <div className="mt-6 max-h-[55vh] overflow-y-auto pr-2 space-y-2">
              {searchLoading ? (
                <div className="p-8 text-center text-slate-400 font-display uppercase tracking-widest text-xs font-semibold">Searching catalog...</div>
              ) : searchResults.length > 0 ? (
                searchResults.map((product) => (
                  <Link 
                    key={product.id} 
                    href={`/products/${product.slug}`}
                    onClick={() => setSearchOpen(false)}
                    className="group flex items-center justify-between p-4 bg-white border border-slate-200 rounded-lg hover:border-slate-400 hover:shadow-xs transition-all"
                  >
                    <div className="flex items-center gap-4 min-w-0">
                      <div className="w-14 h-14 relative bg-slate-100 shrink-0 overflow-hidden border border-slate-200 rounded">
                        {product.image_url && (
                          <SmartImage
                            src={product.image_url}
                            alt={product.title}
                            fill
                            cropMode="cover"
                            objectPosition="center"
                          />
                        )}
                      </div>
                      <div className="min-w-0">
                        <p className="font-display font-bold text-sm text-slate-900 uppercase truncate group-hover:text-slate-700 transition-colors">
                          {product.title}
                        </p>
                        <p className="text-xs text-slate-500 mt-0.5">{storeName}</p>
                      </div>
                    </div>
                    <span className="font-display font-bold text-sm text-slate-900 shrink-0 ml-4">
                      {formatPrice(product.price_minor || product.retail_price_minor, product.currency)}
                    </span>
                  </Link>
                ))
              ) : searchQuery.trim().length > 0 ? (
                <div className="p-8 text-center border border-slate-200 bg-white rounded-lg text-slate-500 font-display uppercase tracking-widest text-xs font-semibold">
                  No products matching "{searchQuery}"
                </div>
              ) : null}
            </div>
          </div>
        </div>
      )}

      {/* MOBILE MENU */}
      {menuOpen && (
        <div className="fixed inset-0 z-50 lg:hidden bg-[#faf8f5] p-6 pt-safe pb-safe flex flex-col animate-fade-in">
          <div className="flex justify-between items-center mb-6 border-b border-stone-200 pb-4 pt-2">
            <span className="font-display font-black text-2xl tracking-tight text-stone-900">{storeName}</span>
            <button 
              onClick={() => setMenuOpen(false)} 
              className="text-stone-800 text-xl w-10 h-10 flex items-center justify-center border-2 border-stone-200 rounded-xl bg-white cursor-pointer active:scale-95"
            >✕</button>
          </div>
          <nav className="flex-1 overflow-y-auto space-y-3">
            {navItems.map((item) => (
              <Link
                key={item.label}
                href={item.href}
                onClick={() => setMenuOpen(false)}
                className="block font-display font-black text-2xl uppercase tracking-tight text-stone-900 hover:text-[#c2410c] transition-colors py-3 border-b border-stone-200/80"
              >
                {item.label}
              </Link>
            ))}
            
            <div className="pt-4 mt-2">
              <AppInstallButton onOpen={() => setMenuOpen(false)} className="w-full flex text-left items-center justify-between bg-white border-2 border-stone-200 rounded-xl p-4 hover:border-[#c2410c] transition-colors shadow-xs">
                <div>
                  <p className="font-display font-bold text-xs uppercase text-[#c2410c]">Get the {storeName} App</p>
                  <p className="text-xs text-stone-500 mt-0.5">Faster checkout & exclusive drops</p>
                </div>
                <svg className="w-6 h-6 text-[#c2410c]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                </svg>
              </AppInstallButton>
            </div>

            <div className="pt-4 mt-4 border-t border-stone-200 flex flex-col gap-1 pb-8">
              <Link
                href="/track-order"
                onClick={() => setMenuOpen(false)}
                className="font-display font-bold text-base uppercase tracking-wider text-stone-800 hover:text-[#c2410c] py-3.5 flex items-center gap-3"
              >
                <svg className="w-5 h-5 text-[#c2410c]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                </svg>
                Track Order
              </Link>
              <button
                onClick={() => { setMenuOpen(false); openAddressModal(); }}
                className="text-left font-display font-bold text-base uppercase tracking-wider text-stone-800 hover:text-[#c2410c] py-3.5 flex items-center gap-3 cursor-pointer"
              >
                <svg className="w-5 h-5 text-[#c2410c]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                Delivery Address
              </button>
              <Link
                href="/account"
                onClick={() => setMenuOpen(false)}
                className="font-display font-bold text-base uppercase tracking-wider text-stone-800 hover:text-[#c2410c] py-3.5 flex items-center gap-3"
              >
                <svg className="w-5 h-5 text-[#c2410c]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                {customer ? 'My Account' : 'Sign In / Register'}
              </Link>
            </div>
          </nav>
        </div>
      )}

      <AddressModal />
      <CartDrawer isOpen={isCartOpen} onClose={closeCart} />
    </>
  );
}
