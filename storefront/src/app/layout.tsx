import type { Metadata } from "next";
import "./globals.css";
import SiteHeader from "@/components/layout/SiteHeader";
import Link from "next/link";
import { fetchStoreSettings, getApiUrl } from "@/lib/api";
import Spotlight from "@/components/ui/Spotlight";
import { StoreProvider } from "@/components/providers/StoreProvider";
import PwaRegistration from "@/components/pwa/PwaRegistration";

export const metadata: Metadata = {
  title: {
    default: "ATELIER — Premium Accessories",
    template: "%s | ATELIER",
  },
  description:
    "Premium, editorial accessories for the modern wardrobe. Precision crafted for everyday excellence.",
  openGraph: {
    siteName: "ATELIER",
    type: "website",
  },
};

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

export default async function RootLayout({
  children,
}: Readonly<{ children: React.ReactNode }>) {
  const [collections, settings] = await Promise.all([
    getCollections(),
    fetchStoreSettings(),
  ]);

  const storeName = settings.storeName || "ATELIER";

  return (
    <html lang="en" data-scroll-behavior="smooth">
      <body className="antialiased min-h-screen flex flex-col bg-background text-foreground relative">
        <StoreProvider storeName={storeName}>
          <PwaRegistration />
          <Spotlight />
          <SiteHeader collections={collections} storeName={storeName} />

          <main id="main-content" className="flex-1 flex flex-col">
            {children}
          </main>

          {/* ── Footer (ATELIER Luxury Obsidian & Burnt Terracotta) ── */}
          <footer className="border-t border-stone-800 bg-[#1c1917] text-stone-200 mt-0">
            {/* Newsletter Subscription Strip */}
            <div className="border-b border-stone-800 bg-stone-950 py-10 px-6 lg:px-16">
              <div className="container mx-auto flex flex-col md:flex-row items-center justify-between gap-6">
                <div>
                  <h3 className="font-display font-black uppercase text-xl md:text-2xl text-white tracking-tight">
                    Join The {storeName} Circle
                  </h3>
                  <p className="text-xs text-stone-400 font-medium mt-1">
                    Subscribe for secret drops, 10% off your first order & VIP early access.
                  </p>
                </div>
                <div className="flex w-full md:w-auto max-w-md gap-2">
                  <input
                    type="email"
                    placeholder="Enter your email address..."
                    className="flex-1 bg-stone-900 border border-stone-700 rounded-xl px-4 py-3 text-xs text-white placeholder:text-stone-500 outline-none focus:border-[#c2410c] transition-all"
                  />
                  <button className="bg-[#c2410c] hover:bg-[#9a3412] text-amber-50 font-display font-black uppercase text-xs tracking-wider px-6 py-3 rounded-xl transition-all shadow-md shrink-0 cursor-pointer active:scale-95">
                    Subscribe →
                  </button>
                </div>
              </div>
            </div>

            {/* Main Footer 4 Columns */}
            <div className="container mx-auto px-6 lg:px-16 py-16 grid grid-cols-1 md:grid-cols-4 gap-10">
              {/* Brand Column */}
              <div className="space-y-4">
                <div className="font-display font-black uppercase text-3xl tracking-tight text-white">
                  <span className="text-[#c2410c]">A</span>TELIER
                </div>
                <p className="text-xs text-stone-400 leading-relaxed font-medium">
                  Precision-crafted luxury tech accessories, leather cases & EDC gear engineered in Egypt for everyday motion.
                </p>
                <div className="flex gap-2 pt-2">
                  {['IG', 'FB', 'TK', 'WA'].map(s => (
                    <a key={s} href="#" className="w-9 h-9 border border-stone-700 hover:border-[#c2410c] hover:text-[#c2410c] text-stone-300 font-display font-black text-xs rounded-xl flex items-center justify-center transition-all bg-stone-900">
                      {s}
                    </a>
                  ))}
                </div>
              </div>

              {/* Shop Links */}
              <div>
                <h4 className="font-display font-bold uppercase text-xs tracking-wider text-[#c2410c] mb-4">
                  Shop E-Wallets
                </h4>
                <ul className="space-y-2.5">
                  {[
                    { l: 'All E-Wallets', h: '/collections/all' },
                    { l: 'MagSafe Smart Wallets', h: '/collections/magsafe-wallets' },
                    { l: 'Pop-Up RFID Cardholders', h: '/collections/rfid-cardholders' },
                    { l: 'Slim Leather Bifolds', h: '/collections/leather-wallets' },
                    { l: 'Minimalist Money Clips', h: '/collections/money-clips' },
                    { l: 'Passport & Travel Wallets', h: '/collections/travel-wallets' },
                    { l: 'MagSafe Battery Wallets', h: '/collections/powerbank-wallets' },
                    { l: 'Executive Gift Sets', h: '/collections/bundles-gift-sets' },
                  ].map(link => (
                    <li key={link.l}>
                      <Link href={link.h} className="text-xs text-stone-400 hover:text-white font-medium transition-colors">
                        {link.l}
                      </Link>
                    </li>
                  ))}
                </ul>
              </div>

              {/* Customer Care */}
              <div>
                <h4 className="font-display font-bold uppercase text-xs tracking-wider text-[#c2410c] mb-4">
                  Customer Care
                </h4>
                <ul className="space-y-2.5">
                  {[
                    { l: 'Track Order', h: '/track-order' },
                    { l: 'Delivery Address', h: '/account' },
                    { l: 'Shipping Policy', h: '/pages/shipping' },
                    { l: 'Returns & Exchange', h: '/pages/returns' },
                    { l: 'Help & FAQ', h: '/pages/faq' },
                  ].map(link => (
                    <li key={link.l}>
                      <Link href={link.h} className="text-xs text-stone-400 hover:text-white font-medium transition-colors">
                        {link.l}
                      </Link>
                    </li>
                  ))}
                </ul>
              </div>

              {/* Secure Payment Column */}
              <div className="space-y-4">
                <h4 className="font-display font-bold uppercase text-xs tracking-wider text-[#c2410c]">
                  Accepted Payments
                </h4>
                <p className="text-xs text-stone-400 font-medium">
                  We accept Cash on Delivery (COD), Paymob, Visa, Mastercard, and ValU Installments across Egypt.
                </p>
                <div className="flex flex-wrap gap-2 pt-1">
                  {['💵 COD', '💳 Paymob', '💳 Visa', '💳 Mastercard', '⚡ ValU'].map(pay => (
                    <span key={pay} className="px-2.5 py-1 text-[11px] font-display font-bold uppercase tracking-wider bg-stone-900 border border-stone-700 text-stone-300 rounded-lg">
                      {pay}
                    </span>
                  ))}
                </div>
              </div>
            </div>

            {/* Bottom Bar */}
            <div className="border-t border-stone-800 bg-stone-950 py-6 px-6 lg:px-16">
              <div className="container mx-auto flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-stone-500 font-medium">
                <p>© {new Date().getFullYear()} {storeName}. All rights reserved. Precision Crafted in Egypt.</p>
                <div className="flex gap-6">
                  <Link href="/pages/privacy" className="hover:text-stone-300 transition-colors">Privacy Policy</Link>
                  <Link href="/pages/terms" className="hover:text-stone-300 transition-colors">Terms of Service</Link>
                </div>
              </div>
            </div>
          </footer>
        </StoreProvider>
      </body>
    </html>
  );
}
