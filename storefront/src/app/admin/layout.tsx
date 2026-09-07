'use client';
import { useEffect, useState, ReactNode } from 'react';
import { useRouter, usePathname } from 'next/navigation';
import Link from 'next/link';
import { adminLogout, adminMe, getUnreadNotificationCount } from '@/lib/admin-api';

const NAV = [
  {
    href: '/admin',
    label: 'Dashboard',
    icon: (
      <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
      </svg>
    ),
  },
  {
    href: '/admin/calendar',
    label: 'Calendar',
    icon: (
      <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
      </svg>
    ),
  },
  {
    href: '/admin/products',
    label: 'Products',
    icon: (
      <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
      </svg>
    ),
  },
  {
    href: '/admin/collections',
    label: 'Collections',
    icon: (
      <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
      </svg>
    ),
  },
  {
    href: '/admin/orders',
    label: 'Orders',
    icon: (
      <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
      </svg>
    ),
  },
  {
    href: '/admin/customers',
    label: 'Customers',
    icon: (
      <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
      </svg>
    ),
  },
  {
    href: '/admin/reviews',
    label: 'Reviews',
    icon: (
      <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
      </svg>
    ),
  },
  {
    href: '/admin/coupons',
    label: 'Coupons',
    icon: (
      <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z" />
      </svg>
    ),
  },
  {
    href: '/admin/media',
    label: 'Media',
    icon: (
      <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
      </svg>
    ),
  },
  {
    href: '/admin/pages',
    label: 'CMS Pages',
    icon: (
      <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
      </svg>
    ),
  },
  {
    href: '/admin/activity',
    label: 'Activity Log',
    icon: (
      <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
      </svg>
    ),
  },
  {
    href: '/admin/notifications',
    label: 'Notifications',
    icon: (
      <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
      </svg>
    ),
  },
  {
    href: '/admin/settings',
    label: 'Settings',
    icon: (
      <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
      </svg>
    ),
  },
];

export default function AdminLayout({ children }: { children: ReactNode }) {
  const router = useRouter();
  const pathname = usePathname();
  const [user, setUser] = useState<any>(null);
  const [collapsed, setCollapsed] = useState(false);
  const [unreadCount, setUnreadCount] = useState(0);
  const [storeName, setStoreName] = useState('ATELIER');
  const [authReady, setAuthReady] = useState(false);

  useEffect(() => {
    const loadSettings = () => {
      if (typeof window !== 'undefined') {
        try {
          const cached = localStorage.getItem('cached_store_settings');
          if (cached) {
            const parsed = JSON.parse(cached);
            if (parsed.storeName) setStoreName(parsed.storeName);
          }
        } catch {}
      }
      import('@/lib/admin-api').then(({ getSettings }) => {
        getSettings().then(s => {
          if (s?.storeName) setStoreName(s.storeName);
        }).catch(() => {});
      });
    };

    loadSettings();

    const handleSettingsUpdated = (e: any) => {
      if (e.detail?.storeName) {
        setStoreName(e.detail.storeName);
      }
    };
    window.addEventListener('store_settings_updated', handleSettingsUpdated);
    return () => window.removeEventListener('store_settings_updated', handleSettingsUpdated);
  }, []);

  useEffect(() => {
    if (pathname === '/admin/login') {
      setAuthReady(true);
      return;
    }

    const token = localStorage.getItem('admin_token');
    if (!token) {
      router.replace('/admin/login');
      setAuthReady(true);
      return;
    }

    adminMe()
      .then((result) => setUser(result?.data ?? result))
      .catch(() => {
        localStorage.removeItem('admin_token');
        localStorage.removeItem('admin_user');
        router.replace('/admin/login');
      })
      .finally(() => setAuthReady(true));
  }, [pathname, router]);

  useEffect(() => {
    if (!user || pathname === '/admin/login') return;
    const fetchCount = () => {
      getUnreadNotificationCount().then(c => setUnreadCount(c)).catch(() => {});
    };
    fetchCount();
    const interval = setInterval(fetchCount, 30000);
    return () => clearInterval(interval);
  }, [user, pathname]);

  async function logout() {
    await adminLogout().catch(() => undefined);
    localStorage.removeItem('admin_token');
    localStorage.removeItem('admin_user');
    router.replace('/admin/login');
  }

  const [mobileNavOpen, setMobileNavOpen] = useState(false);

  if (!authReady) {
    return <div className="min-h-screen grid place-items-center bg-stone-950"><span className="font-display text-xs font-black uppercase tracking-[.25em] text-white/70 animate-pulse">Securing workspace</span></div>;
  }

  if (pathname === '/admin/login') return <>{children}</>;

  return (
    <div className="min-h-screen bg-[radial-gradient(circle_at_10%_0%,rgba(255,255,255,.95),transparent_35%),linear-gradient(135deg,#f4f1ec,#e9e5de)] flex text-stone-900" style={{ fontFamily: 'Inter, sans-serif' }}>
      {/* Mobile Drawer Overlay */}
      {mobileNavOpen && (
        <div
          className="fixed inset-0 bg-stone-900/60 backdrop-blur-xs z-40 lg:hidden"
          onClick={() => setMobileNavOpen(false)}
        />
      )}

      {/* Sidebar */}
      <aside className={`fixed inset-y-0 left-0 z-50 lg:static flex flex-col bg-stone-950/95 text-stone-200 border-r border-white/10 backdrop-blur-2xl transition-all duration-300 shrink-0 ${
        mobileNavOpen ? 'translate-x-0 w-64' : '-translate-x-full lg:translate-x-0'
      } ${collapsed ? 'lg:w-16' : 'lg:w-56'}`}>
        {/* Logo / Store Name */}
        <div className={`flex items-center justify-between px-4 py-4 border-b border-stone-800`}>
          <div className="flex items-center gap-2.5 min-w-0">
            <div className="w-8 h-8 rounded-lg bg-[#c2410c] text-white flex items-center justify-center font-display font-black text-base uppercase shrink-0 shadow-xs">
              {storeName.charAt(0)}
            </div>
            {(!collapsed || mobileNavOpen) && (
              <span className="font-display font-black uppercase text-base tracking-tight text-white truncate" title={storeName}>
                {storeName}
              </span>
            )}
          </div>
          <button
            onClick={() => setMobileNavOpen(false)}
            className="lg:hidden text-stone-400 hover:text-white p-1 cursor-pointer"
          >
            ✕
          </button>
        </div>

        {/* Nav */}
        <nav className="flex-1 py-3 overflow-y-auto space-y-1 px-2">
          {NAV.map(item => {
            const active = pathname === item.href || (item.href !== '/admin' && pathname.startsWith(item.href));
            return (
              <Link key={item.href} href={item.href}
                onClick={() => setMobileNavOpen(false)}
                className={`flex items-center gap-3 px-3.5 py-2.5 text-xs font-display font-bold uppercase tracking-wider rounded-lg transition-all duration-150 ${
                  active ? 'bg-white text-stone-950 shadow-md font-black' : 'text-stone-400 hover:text-white hover:bg-stone-800/80'
                } ${collapsed && !mobileNavOpen ? 'justify-center px-0' : ''}`}>
                <span className="shrink-0">{item.icon}</span>
                {(!collapsed || mobileNavOpen) && item.label}
              </Link>
            );
          })}
        </nav>

        {/* Collapse toggle + user */}
        <div className="border-t border-stone-800 p-3 space-y-2">
          {(!collapsed || mobileNavOpen) && user && (
            <div className="px-1 pb-1">
              <p className="text-[11px] text-stone-400 uppercase tracking-wider truncate font-semibold">{user.email}</p>
            </div>
          )}
          <div className={`flex gap-2 ${collapsed && !mobileNavOpen ? 'flex-col items-center' : 'items-center justify-between'}`}>
            <button onClick={logout} className="text-xs font-display uppercase tracking-wider text-rose-400 hover:text-rose-300 font-bold transition-colors flex items-center gap-1 cursor-pointer">
              {collapsed && !mobileNavOpen ? '✕' : 'Logout'}
            </button>
            <button onClick={() => setCollapsed(!collapsed)} className="hidden lg:block text-stone-400 hover:text-white transition-colors text-sm p-1 cursor-pointer">
              {collapsed ? '→' : '←'}
            </button>
          </div>
        </div>
      </aside>

      {/* Main */}
      <div className="flex-1 flex flex-col min-w-0 overflow-hidden">
        {/* Topbar */}
        <header className="sticky top-0 z-30 h-16 border-b border-white/70 bg-white/72 backdrop-blur-2xl flex items-center px-4 sm:px-6 shrink-0 shadow-sm gap-3">
          {/* Mobile hamburger */}
          <button
            onClick={() => setMobileNavOpen(true)}
            className="lg:hidden p-2.5 rounded-xl border border-white/80 bg-white/70 text-stone-600 shadow-sm hover:text-stone-900"
            aria-label="Open navigation"
          >
            <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 6h16M4 12h16M4 18h16" />
            </svg>
          </button>

          <h2 className="font-display font-bold uppercase text-xs tracking-wider text-stone-600 truncate">
            {NAV.find(n => n.href !== '/admin' ? pathname.startsWith(n.href) : pathname === '/admin')?.label || 'Dashboard'}
          </h2>
          <div className="ml-auto flex items-center gap-3 sm:gap-4">
            <Link href="/admin/notifications" className="relative rounded-xl p-2.5 text-stone-600 hover:bg-white hover:text-[#c2410c] transition-colors">
              <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
              </svg>
              {unreadCount > 0 && (
                <span className="absolute top-1 right-1 bg-[#c2410c] text-amber-50 text-[10px] font-bold rounded-full w-4 h-4 flex items-center justify-center shadow-xs">
                  {unreadCount > 9 ? '9+' : unreadCount}
                </span>
              )}
            </Link>
            <Link href="/" target="_blank" className="hidden sm:inline-flex text-xs font-display font-bold uppercase tracking-wider text-stone-800 hover:text-[#c2410c] border border-stone-200 bg-white/70 hover:border-[#c2410c] px-3 py-2 rounded-xl transition-all shadow-sm">
              Store ↗
            </Link>
          </div>
        </header>

        {/* Page content */}
        <main className="flex-1 overflow-auto p-4 sm:p-6 lg:p-8">
          {children}
        </main>
      </div>
    </div>
  );
}
