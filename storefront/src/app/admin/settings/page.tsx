'use client';
import { useEffect, useRef, useState } from 'react';
import { getSettings, updateSettings } from '@/lib/admin-api';

const DEFAULTS = {
  storeName: 'ATELIER',
  announcementText: 'FAST DELIVERY · CASH ON DELIVERY · EASY EXCHANGES',
  announcementSecondary: 'EGYPTIAN LUXURY CRAFTSMANSHIP · 24H EXPRESS DISPATCH · CASH ON DELIVERY & PAYMOB',
  freeShippingThreshold: '2500',
  salePromoTitle: 'END OF SEASON SALE',
  salePromoDiscount: '40% OFF',
  salePromoBgColor: '#991b1b',
  salePromoLink: '/collections/all',
  signatureHeroTitle: 'SIGNATURE LEATHER COLLECTION',
  signatureHeroSubtitle: 'Designed to Match',
  signatureHeroImage: 'https://images.unsplash.com/photo-1620138290379-3d12234551d0?w=1800&q=85',
  signatureHeroButtonText: 'SHOP ALL →',
  signatureHeroButtonLink: '/collections/all',
  homeHeroTitle: 'PRECISION CRAFTED SMART WALLETS',
  homeHeroSubtitle: 'Handcrafted full-grain leather smart wallets, aerospace titanium cardholders, and minimalist everyday carry.',
  homeHeroImage: 'https://images.unsplash.com/photo-1620138290379-3d12234551d0?w=1800&q=85',
  homeHeroCtaText: 'Explore Catalog',
  homeHeroCtaLink: '/collections/all',
  homeBgColor: '#faf8f5',
  homeBgImage: '',
  homeBannerImage: 'https://images.unsplash.com/photo-1548036328-c9fa89d128fa?w=1600&q=80',
  guaranteeTitle: 'The ATELIER Guarantee',
  guaranteeSubtitle: '100% Genuine Italian & Egyptian Leather · 2-Year Craftsmanship Warranty · Fast Door-to-Door Delivery across Egypt',
  guaranteeImage: 'https://images.unsplash.com/photo-1548036328-c9fa89d128fa?w=1600&q=80',
  logoUrl: '',
  whiteLogoUrl: '',
  supportEmail: 'concierge@atelier.eg',
  supportPhone: '+20 100 000 0000',
  currency: 'EGP',
  taxRate: '14.0',
  shippingDomestic: '50.00',
  shippingInternational: '250.00',
};

export default function AdminSettingsPage() {
  const [settings, setSettings] = useState(DEFAULTS);
  const [activeTab, setActiveTab] = useState<'brand' | 'visuals' | 'finance' | 'app'>('visuals');
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [success, setSuccess] = useState(false);
  const [error, setError] = useState('');

  // PWA Install prompt
  const deferredPrompt = useRef<any>(null);
  const [pwaInstallable, setPwaInstallable] = useState(false);
  const [pwaInstalled, setPwaInstalled] = useState(false);

  useEffect(() => {
    const handler = (e: Event) => {
      e.preventDefault();
      deferredPrompt.current = e;
      setPwaInstallable(true);
    };
    window.addEventListener('beforeinstallprompt', handler);
    window.addEventListener('appinstalled', () => setPwaInstalled(true));
    return () => window.removeEventListener('beforeinstallprompt', handler);
  }, []);

  async function handleInstallPwa() {
    if (!deferredPrompt.current) return;
    deferredPrompt.current.prompt();
    const { outcome } = await deferredPrompt.current.userChoice;
    if (outcome === 'accepted') {
      setPwaInstalled(true);
      setPwaInstallable(false);
    }
    deferredPrompt.current = null;
  }

  useEffect(() => {
    getSettings()
      .then(data => {
        setSettings(s => ({ ...s, ...data }));
        setLoading(false);
      })
      .catch(() => setLoading(false));
  }, []);

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    setSaving(true);
    setSuccess(false);
    setError('');
    try {
      await updateSettings(settings);
      setSuccess(true);
      setTimeout(() => setSuccess(false), 3000);
    } catch (err: any) {
      setError(err.message || 'Failed to save settings.');
    } finally {
      setSaving(false);
    }
  }

  const F = ({ label, name, type = 'text', hint, ...rest }: any) => (
    <div>
      <label className="block font-display text-[11px] uppercase tracking-wider text-stone-700 font-bold mb-1.5">{label}</label>
      <input
        type={type}
        value={(settings as any)[name] ?? ''}
        onChange={e => setSettings(s => ({ ...s, [name]: e.target.value }))}
        className="w-full bg-[#faf8f5] border border-stone-200 rounded-xl px-4 py-3 text-sm text-stone-900 outline-none focus:border-stone-900 transition-colors placeholder:text-stone-400 font-medium"
        {...rest}
      />
      {hint && <p className="text-[11px] text-stone-500 mt-1">{hint}</p>}
    </div>
  );

  const TArea = ({ label, name, hint, rows = 3 }: any) => (
    <div>
      <label className="block font-display text-[11px] uppercase tracking-wider text-stone-700 font-bold mb-1.5">{label}</label>
      <textarea
        rows={rows}
        value={(settings as any)[name] ?? ''}
        onChange={e => setSettings(s => ({ ...s, [name]: e.target.value }))}
        className="w-full bg-[#faf8f5] border border-stone-200 rounded-xl px-4 py-3 text-sm text-stone-900 outline-none focus:border-stone-900 transition-colors placeholder:text-stone-400 font-medium"
      />
      {hint && <p className="text-[11px] text-stone-500 mt-1">{hint}</p>}
    </div>
  );

  return (
    <div className="max-w-4xl space-y-6">
      <div>
        <h1 className="font-display font-black uppercase text-2xl tracking-tight text-stone-900">Store Settings & Customization</h1>
        <p className="text-xs text-stone-500 mt-1">Manage store brand, background photos, announcement tickers, and checkout options.</p>
      </div>

      {/* Tabs */}
      <div className="flex flex-wrap gap-2 border-b border-stone-200 pb-3">
        <button
          type="button"
          onClick={() => setActiveTab('visuals')}
          className={`px-4 py-2 rounded-xl text-xs font-display font-bold uppercase tracking-wider transition-all cursor-pointer ${
            activeTab === 'visuals'
              ? 'bg-stone-900 text-white shadow-xs'
              : 'bg-white text-stone-700 hover:bg-stone-100 border border-stone-200'
          }`}
        >
          🎨 Visuals
        </button>
        <button
          type="button"
          onClick={() => setActiveTab('brand')}
          className={`px-4 py-2 rounded-xl text-xs font-display font-bold uppercase tracking-wider transition-all cursor-pointer ${
            activeTab === 'brand'
              ? 'bg-stone-900 text-white shadow-xs'
              : 'bg-white text-stone-700 hover:bg-stone-100 border border-stone-200'
          }`}
        >
          📢 Brand
        </button>
        <button
          type="button"
          onClick={() => setActiveTab('finance')}
          className={`px-4 py-2 rounded-xl text-xs font-display font-bold uppercase tracking-wider transition-all cursor-pointer ${
            activeTab === 'finance'
              ? 'bg-stone-900 text-white shadow-xs'
              : 'bg-white text-stone-700 hover:bg-stone-100 border border-stone-200'
          }`}
        >
          💳 Finance
        </button>
        <button
          type="button"
          onClick={() => setActiveTab('app')}
          className={`px-4 py-2 rounded-xl text-xs font-display font-bold uppercase tracking-wider transition-all cursor-pointer ${
            activeTab === 'app'
              ? 'bg-[#c2410c] text-white shadow-xs'
              : 'bg-white text-stone-700 hover:bg-stone-100 border border-stone-200'
          }`}
        >
          📱 Mobile App
        </button>
      </div>

      {loading && (
        <div className="font-display text-xs uppercase tracking-widest text-stone-400 animate-pulse font-semibold">Loading settings...</div>
      )}

      {error && <div className="px-4 py-3 bg-rose-50 border border-rose-200 rounded-xl text-rose-800 text-xs font-display font-bold uppercase tracking-wider">{error}</div>}
      {success && <div className="px-4 py-3 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-800 text-xs font-display font-bold uppercase tracking-wider">Settings saved and updated live across the storefront!</div>}

      <form onSubmit={handleSubmit} className="space-y-6">

        {/* Tab 1: Visuals & Backgrounds */}
        {activeTab === 'visuals' && (
          <div className="space-y-6">
            {/* Banner 1: End of Season Promo Banner (Screenshot 3) */}
            <div className="border border-stone-200 bg-white rounded-2xl p-6 shadow-xs space-y-4">
              <p className="font-display font-bold text-xs uppercase tracking-wider text-stone-700 flex items-center gap-2">
                <span>🏷️</span> End of Season Sale Banner (Screenshot 3)
              </p>
              <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <F
                  label="Banner Headline"
                  name="salePromoTitle"
                  placeholder="END OF SEASON SALE"
                  hint="Upper badge label"
                />
                <F
                  label="Discount Text / Typography"
                  name="salePromoDiscount"
                  placeholder="40% OFF"
                  hint="Massive bold text"
                />
                <F
                  label="Banner Background Color"
                  name="salePromoBgColor"
                  placeholder="#991b1b"
                  hint="Hex code (e.g. #991b1b for Crimson, #1c1917 for Obsidian)"
                />
              </div>
            </div>

            {/* Banner 2: Signature Leather Collection Hero (Screenshot 2) */}
            <div className="border border-stone-200 bg-white rounded-2xl p-6 shadow-xs space-y-4">
              <p className="font-display font-bold text-xs uppercase tracking-wider text-stone-700 flex items-center gap-2">
                <span>🖼️</span> Signature Collection Lifestyle Banner (Screenshot 2)
              </p>
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <F
                  label="Lifestyle Background Photo URL"
                  name="signatureHeroImage"
                  hint="High-resolution wallpaper image for the signature collection hero"
                  placeholder="https://..."
                />
                <F
                  label="Hero Button Text"
                  name="signatureHeroButtonText"
                  placeholder="SHOP ALL →"
                />
              </div>

              {settings.signatureHeroImage && (
                <div className="p-3 bg-stone-50 rounded-xl border border-stone-200">
                  <p className="text-[11px] font-display font-bold uppercase text-stone-500 mb-2">Live Background Photo Preview:</p>
                  <div className="h-36 rounded-lg overflow-hidden relative border border-stone-300">
                    <img src={settings.signatureHeroImage} alt="Hero preview" className="w-full h-full object-cover" />
                  </div>
                </div>
              )}

              <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-1">
                <F label="Collection Headline Title" name="signatureHeroTitle" placeholder="SIGNATURE LEATHER COLLECTION" />
                <F label="Collection Subtitle Text" name="signatureHeroSubtitle" placeholder="Designed to Match" />
              </div>
            </div>

            {/* Background Customization */}
            <div className="border border-stone-200 bg-white rounded-2xl p-6 shadow-xs space-y-4">
              <p className="font-display font-bold text-xs uppercase tracking-wider text-stone-700 flex items-center gap-2">
                <span>🎨</span> Page Backgrounds &amp; Guarantee Banner
              </p>
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <F
                  label="Homepage Background Color"
                  name="homeBgColor"
                  placeholder="#faf8f5"
                  hint="Hex color code (e.g. #faf8f5 for light luxury or #0c0a09 for stealth dark)"
                />
                <F
                  label="Guarantee Banner Photo URL"
                  name="guaranteeImage"
                  placeholder="https://..."
                  hint="Background image for the bottom Guarantee & Craftsmanship banner"
                />
              </div>
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                <F label="Guarantee Banner Title" name="guaranteeTitle" placeholder="The ATELIER Guarantee" />
                <F label="Guarantee Banner Subtitle" name="guaranteeSubtitle" placeholder="100% Genuine Italian & Egyptian Leather..." />
              </div>
            </div>
          </div>
        )}

        {/* Tab 2: Brand & Announcements */}
        {activeTab === 'brand' && (
          <div className="space-y-6">
            <div className="border border-stone-200 bg-white rounded-2xl p-6 shadow-xs space-y-4">
              <p className="font-display font-bold text-xs uppercase tracking-wider text-stone-700">Brand Identity</p>
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <F label="Store Name" name="storeName" />
                <F label="Support Email" name="supportEmail" type="email" />
                <F label="Concierge Phone / WhatsApp" name="supportPhone" placeholder="+20 100 000 0000" />
              </div>
            </div>

            <div className="border border-stone-200 bg-white rounded-2xl p-6 shadow-xs space-y-4">
              <p className="font-display font-bold text-xs uppercase tracking-wider text-stone-700">Top Announcement Marquee</p>
              <TArea
                label="Primary Announcement Message"
                name="announcementText"
                hint="Displays in the top infinite scrolling marquee header"
                placeholder="FAST DELIVERY · CASH ON DELIVERY · EASY EXCHANGES"
              />
              <TArea
                label="Secondary Ticker Message"
                name="announcementSecondary"
                hint="Alternating ticker highlight"
                placeholder="EGYPTIAN LUXURY CRAFTSMANSHIP · 24H EXPRESS DISPATCH · CASH ON DELIVERY & PAYMOB"
              />
            </div>
          </div>
        )}

        {/* Tab 3: Finance & Shipping */}
        {activeTab === 'finance' && (
          <div className="space-y-6">
            <div className="border border-stone-200 bg-white rounded-2xl p-6 shadow-xs space-y-4">
              <p className="font-display font-bold text-xs uppercase tracking-wider text-stone-700">Currencies &amp; Free Shipping Threshold</p>
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label className="block font-display text-[11px] uppercase tracking-wider text-stone-700 font-bold mb-1.5">Store Currency</label>
                  <select
                    value={settings.currency}
                    onChange={e => setSettings(s => ({ ...s, currency: e.target.value }))}
                    className="w-full bg-[#faf8f5] border border-stone-200 rounded-xl px-4 py-3 text-sm text-stone-900 outline-none focus:border-[#c2410c] transition-colors cursor-pointer font-medium"
                  >
                    <option value="EGP">EGP (Egyptian Pound)</option>
                    <option value="USD">USD ($)</option>
                    <option value="EUR">EUR (€)</option>
                    <option value="SAR">SAR (Saudi Riyal)</option>
                    <option value="AED">AED (UAE Dirham)</option>
                  </select>
                </div>
                <F
                  label="Free Shipping Threshold (EGP)"
                  name="freeShippingThreshold"
                  type="number"
                  hint="Order subtotal required for customer to unlock Free Express Shipping (default: 2500)"
                />
                <F label="Tax Rate (%)" name="taxRate" type="number" step="0.1" />
                <F label="Standard Domestic Delivery (EGP)" name="shippingDomestic" type="number" step="1" />
                <F label="International Delivery (EGP)" name="shippingInternational" type="number" step="1" />
              </div>
            </div>
          </div>
        )}

        {/* Mobile App PWA Tab */}
        {activeTab === 'app' && (
          <div className="space-y-6">
            <div className="border border-stone-200 bg-white rounded-2xl p-6 shadow-xs space-y-5">
              <div>
                <p className="font-display font-bold text-xs uppercase tracking-wider text-stone-700">📱 Install Store as Mobile App (PWA)</p>
                <p className="text-xs text-stone-500 mt-1">Install the storefront as a native-like Progressive Web App on your phone — no App Store needed. Works on Android Chrome and iOS Safari.</p>
              </div>

              <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                {/* Install Section */}
                <div className="bg-gradient-to-br from-stone-900 to-stone-800 rounded-xl p-5 text-white space-y-3">
                  <div className="flex items-center gap-3">
                    <div className="w-12 h-12 bg-[#c2410c] rounded-xl flex items-center justify-center text-2xl shadow-md">
                      🛍️
                    </div>
                    <div>
                      <p className="font-display font-black text-sm uppercase tracking-wider">Storefront App</p>
                      <p className="text-stone-400 text-[11px]">Add to Home Screen</p>
                    </div>
                  </div>

                  {pwaInstalled ? (
                    <div className="bg-emerald-600/20 border border-emerald-500/40 rounded-lg px-4 py-3 text-center">
                      <span className="text-emerald-300 font-display font-bold text-xs uppercase tracking-wider">✓ App Installed Successfully!</span>
                    </div>
                  ) : pwaInstallable ? (
                    <button
                      type="button"
                      onClick={handleInstallPwa}
                      className="w-full bg-[#c2410c] hover:bg-[#9a3412] text-white font-display font-black text-xs uppercase tracking-wider py-3 rounded-lg transition-all active:scale-95 cursor-pointer shadow-md"
                    >
                      ⬇️ Install App Now
                    </button>
                  ) : (
                    <div className="bg-stone-700/50 border border-stone-600 rounded-lg px-4 py-3 text-center space-y-1">
                      <p className="text-stone-300 text-[11px] font-medium">Open this page in Chrome / Safari on your phone, then:</p>
                      <p className="text-amber-300 font-display font-bold text-[11px] uppercase tracking-wider">Android: Menu → Add to Home Screen</p>
                      <p className="text-amber-300 font-display font-bold text-[11px] uppercase tracking-wider">iPhone: Share → Add to Home Screen</p>
                    </div>
                  )}
                </div>

                {/* Instructions */}
                <div className="bg-stone-50 border border-stone-200 rounded-xl p-5 space-y-3">
                  <p className="font-display font-bold text-xs uppercase tracking-wider text-stone-700">How to Install on Phone</p>
                  <ol className="space-y-2 text-xs text-stone-600">
                    <li className="flex items-start gap-2">
                      <span className="bg-stone-900 text-white rounded-full w-5 h-5 flex items-center justify-center text-[10px] font-bold shrink-0 mt-0.5">1</span>
                      <span>Open the storefront URL on your phone browser: <code className="bg-stone-200 px-1.5 py-0.5 rounded text-stone-800 font-mono text-[10px]">http://YOUR-IP:3000</code></span>
                    </li>
                    <li className="flex items-start gap-2">
                      <span className="bg-stone-900 text-white rounded-full w-5 h-5 flex items-center justify-center text-[10px] font-bold shrink-0 mt-0.5">2</span>
                      <span><strong>Android Chrome:</strong> Tap the ⋮ menu → "Add to Home Screen" → Install</span>
                    </li>
                    <li className="flex items-start gap-2">
                      <span className="bg-stone-900 text-white rounded-full w-5 h-5 flex items-center justify-center text-[10px] font-bold shrink-0 mt-0.5">3</span>
                      <span><strong>iPhone Safari:</strong> Tap the Share icon □↑ → "Add to Home Screen" → Add</span>
                    </li>
                    <li className="flex items-start gap-2">
                      <span className="bg-emerald-600 text-white rounded-full w-5 h-5 flex items-center justify-center text-[10px] font-bold shrink-0 mt-0.5">✓</span>
                      <span>The app icon will appear on your home screen — opens full-screen like a native app!</span>
                    </li>
                  </ol>
                </div>
              </div>
            </div>
          </div>
        )}

        {/* Action Button — only show on non-app tabs */}
        {activeTab !== 'app' && (
          <div className="pt-2">
            <button
              type="submit"
              disabled={saving || loading}
              className="bg-stone-900 text-white font-display font-black uppercase text-xs tracking-wider px-8 py-4 rounded-xl hover:bg-black shadow-md active:scale-95 transition-all disabled:opacity-50 cursor-pointer"
            >
              {saving ? 'Saving...' : 'Save Settings'}
            </button>
          </div>
        )}
      </form>
    </div>
  );
}
