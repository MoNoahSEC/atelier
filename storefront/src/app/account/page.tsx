'use client';

import { useState, useEffect } from 'react';
import { useRouter } from 'next/navigation';
import { useStore, SavedAddress } from '@/components/providers/StoreProvider';
import Link from 'next/link';
import { getApiUrl } from '@/lib/api';

const EGYPT_GOVERNORATES = [
  'Cairo', 'Giza', 'Alexandria', 'Qalyubia', 'Sharqia', 'Dakahlia', 'Gharbia',
  'Monufia', 'Beheira', 'Kafr El Sheikh', 'Damietta', 'Port Said', 'Ismailia',
  'Suez', 'Fayoum', 'Beni Suef', 'Minya', 'Assiut', 'Sohag', 'Qena', 'Luxor',
  'Aswan', 'Red Sea (Hurghada)', 'South Sinai (Sharm El Sheikh)', 'Matrouh'
];

export default function AccountPage() {
  const router = useRouter();
  const { 
    storeName, 
    customer, 
    customerToken, 
    loginCustomer, 
    registerCustomer, 
    logoutCustomer, 
    savedAddress, 
    saveAddress, 
    formatPrice 
  } = useStore();
  
  // Unauth tabs: 'login' | 'register'
  const [unauthTab, setUnauthTab] = useState<'login' | 'register'>('login');
  
  // Auth tabs: 'orders' | 'address' | 'profile' | 'security'
  const [authTab, setAuthTab] = useState<'orders' | 'address' | 'profile' | 'security'>('orders');

  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');

  // Login form
  const [loginEmail, setLoginEmail] = useState('');
  const [loginPassword, setLoginPassword] = useState('');

  // Register form
  const [regFirstName, setRegFirstName] = useState('');
  const [regLastName, setRegLastName] = useState('');
  const [regEmail, setRegEmail] = useState('');
  const [regPassword, setRegPassword] = useState('');
  const [regConfirm, setRegConfirm] = useState('');

  // Profile form
  const [profileFirstName, setProfileFirstName] = useState('');
  const [profileLastName, setProfileLastName] = useState('');
  const [profilePhone, setProfilePhone] = useState('');

  // Security form
  const [currentPassword, setCurrentPassword] = useState('');
  const [newPassword, setNewPassword] = useState('');
  const [newPasswordConfirm, setNewPasswordConfirm] = useState('');

  // Address form
  const [addressForm, setAddressForm] = useState<SavedAddress>({
    name: '',
    phone: '',
    email: '',
    city: 'Cairo',
    state: '',
    line1: '',
    postal: '',
    country: 'EG',
  });

  // Orders data
  const [orders, setOrders] = useState<any[]>([]);
  const [ordersLoading, setOrdersLoading] = useState(false);

  useEffect(() => {
    if (customer) {
      setProfileFirstName(customer.first_name || '');
      setProfileLastName(customer.last_name || '');
      setProfilePhone(customer.phone || '');
    }
    if (savedAddress) {
      setAddressForm(savedAddress);
    } else if (customer) {
      setAddressForm(prev => ({
        ...prev,
        name: `${customer.first_name} ${customer.last_name}`.trim(),
        email: customer.email,
        phone: customer.phone || '',
      }));
    }
  }, [customer, savedAddress]);

  useEffect(() => {
    if (customer && customerToken && authTab === 'orders') {
      fetchOrders();
    }
  }, [customer, customerToken, authTab]);

  async function fetchOrders() {
    setOrdersLoading(true);
    try {
      const res = await fetch(`${getApiUrl()}/public/customer/orders`, {
        headers: { 'Authorization': `Bearer ${customerToken}`, 'Accept': 'application/json' }
      });
      if (res.ok) {
        const data = await res.json();
        setOrders(data.data || data);
      }
    } catch (e) {
      console.error('Failed to fetch customer orders', e);
    } finally {
      setOrdersLoading(false);
    }
  }

  async function handleLogin(e: React.FormEvent) {
    e.preventDefault();
    setLoading(true); setError(''); setSuccess('');
    try {
      await loginCustomer(loginEmail, loginPassword);
    } catch (err: any) {
      setError(err.message || 'Login failed.');
    } finally {
      setLoading(false);
    }
  }

  async function handleRegister(e: React.FormEvent) {
    e.preventDefault();
    if (regPassword !== regConfirm) {
      setError('Passwords do not match.');
      return;
    }
    setLoading(true); setError(''); setSuccess('');
    try {
      await registerCustomer({
        first_name: regFirstName,
        last_name: regLastName,
        email: regEmail,
        password: regPassword,
        password_confirmation: regConfirm,
      });
    } catch (err: any) {
      setError(err.message || 'Registration failed.');
    } finally {
      setLoading(false);
    }
  }

  async function handleSaveAddress(e: React.FormEvent) {
    e.preventDefault();
    if (!addressForm.name || !addressForm.phone || !addressForm.line1) {
      setError('Please fill in Name, Phone, and Street Address.');
      return;
    }
    saveAddress(addressForm);
    setSuccess('Delivery address saved successfully!');
  }

  async function handleUpdateProfile(e: React.FormEvent) {
    e.preventDefault();
    setLoading(true); setError(''); setSuccess('');
    try {
      const res = await fetch(`${getApiUrl()}/public/auth/profile`, {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${customerToken}`,
          'Accept': 'application/json',
        },
        body: JSON.stringify({
          first_name: profileFirstName,
          last_name: profileLastName,
          phone: profilePhone,
        }),
      });
      const data = await res.json();
      if (!res.ok) throw new Error(data.message || 'Failed to update profile');
      setSuccess('Profile updated successfully.');
    } catch (err: any) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  }

  async function handleUpdatePassword(e: React.FormEvent) {
    e.preventDefault();
    if (newPassword !== newPasswordConfirm) {
      setError('New passwords do not match.');
      return;
    }
    setLoading(true); setError(''); setSuccess('');
    try {
      const res = await fetch(`${getApiUrl()}/public/auth/password`, {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${customerToken}`,
          'Accept': 'application/json',
        },
        body: JSON.stringify({
          current_password: currentPassword,
          password: newPassword,
          password_confirmation: newPasswordConfirm,
        }),
      });
      const data = await res.json();
      if (!res.ok) throw new Error(data.message || 'Failed to change password');
      setSuccess('Password updated successfully.');
      setCurrentPassword('');
      setNewPassword('');
      setNewPasswordConfirm('');
    } catch (err: any) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  }

  const inputCls = 'w-full bg-[#faf8f5] border border-stone-200 rounded-xl px-4 py-3 text-sm text-stone-900 font-medium outline-none focus:border-[#c2410c] transition-colors placeholder:text-stone-400';
  const labelCls = 'block font-display font-bold text-xs uppercase tracking-wider text-stone-700 mb-1.5';

  if (customer) {
    return (
      <main className="min-h-screen bg-[#faf8f5] pt-24 pb-16 px-4 sm:px-6 md:px-12 text-stone-900">
        <div className="max-w-6xl mx-auto">
          
          {/* Header */}
          <div className="flex flex-wrap justify-between items-end gap-4 mb-8 border-b border-stone-200 pb-6">
            <div>
              <h1 className="font-display font-black uppercase tracking-tight text-stone-900 text-2xl sm:text-3xl md:text-4xl">
                Customer Portal
              </h1>
              <p className="text-stone-500 text-xs sm:text-sm mt-1">
                Welcome back, <strong className="text-[#c2410c]">{customer.first_name} {customer.last_name}</strong> ({customer.email})
              </p>
            </div>
            
            <div className="flex items-center gap-3">
              <Link
                href="/track-order"
                className="px-4 py-2 border border-stone-300 hover:border-[#c2410c] rounded-xl text-xs font-display font-bold uppercase tracking-wider text-stone-800 hover:text-[#c2410c] transition-colors bg-white shadow-2xs"
              >
                Track Order
              </Link>
              <button
                onClick={logoutCustomer}
                className="px-4 py-2 bg-rose-50 border border-rose-200 text-rose-700 rounded-xl text-xs font-display font-bold uppercase tracking-wider hover:bg-rose-100 transition-colors cursor-pointer"
              >
                Sign Out
              </button>
            </div>
          </div>

          <div className="flex flex-col md:flex-row gap-6 lg:gap-8">
            
            {/* Sidebar Tabs */}
            <div className="w-full md:w-60 shrink-0 space-y-1.5">
              {[
                { id: 'orders', label: 'My Orders', icon: (
                  <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                  </svg>
                )},
                { id: 'address', label: 'Saved Address', icon: (
                  <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                  </svg>
                )},
                { id: 'profile', label: 'Profile Info', icon: (
                  <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                  </svg>
                )},
                { id: 'security', label: 'Security & Password', icon: (
                  <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                  </svg>
                )},
              ].map(t => (
                <button
                  key={t.id}
                  onClick={() => { setAuthTab(t.id as any); setError(''); setSuccess(''); }}
                  className={`w-full text-left px-4 py-3.5 text-xs uppercase tracking-wider font-display font-bold rounded-xl transition-all flex items-center gap-3 cursor-pointer ${
                    authTab === t.id 
                      ? 'bg-[#c2410c] text-amber-50 shadow-xs' 
                      : 'bg-white text-stone-700 hover:text-stone-900 hover:bg-stone-50 border border-stone-200'
                  }`}
                >
                  <span className="shrink-0">{t.icon}</span>
                  <span>{t.label}</span>
                </button>
              ))}
            </div>

            {/* Main Panel Content */}
            <div className="flex-1 bg-white border border-stone-200 rounded-2xl p-6 sm:p-8 shadow-2xs min-h-[400px]">
              {error && <div className="mb-6 p-3.5 bg-rose-50 border border-rose-200 rounded-xl text-rose-800 text-xs font-display font-bold uppercase tracking-wider">{error}</div>}
              {success && <div className="mb-6 p-3.5 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-800 text-xs font-display font-bold uppercase tracking-wider">{success}</div>}
              
              {/* ORDERS TAB */}
              {authTab === 'orders' && (
                <div>
                  <h2 className="font-display font-black text-lg uppercase tracking-wider text-stone-900 mb-6">
                    Order History
                  </h2>
                  {ordersLoading ? (
                    <div className="flex items-center justify-center py-16">
                      <div className="w-8 h-8 border-2 border-stone-200 border-t-[#c2410c] rounded-full animate-spin" />
                    </div>
                  ) : orders.length === 0 ? (
                    <div className="bg-[#faf8f5] p-8 border border-stone-200 rounded-2xl text-center space-y-4">
                      <p className="text-stone-500 text-xs">You have not placed any orders yet.</p>
                      <Link href="/collections/all" className="inline-block bg-[#c2410c] hover:bg-[#9a3412] text-amber-50 px-6 py-3 rounded-xl font-display font-bold text-xs uppercase tracking-wider transition-colors shadow-xs">
                        Browse Products →
                      </Link>
                    </div>
                  ) : (
                    <div className="overflow-x-auto">
                      <table className="w-full text-left text-xs border-collapse">
                        <thead>
                          <tr className="border-b border-stone-200 text-stone-500 font-display uppercase tracking-wider">
                            <th className="py-3 font-bold">Order #</th>
                            <th className="py-3 font-bold">Date</th>
                            <th className="py-3 font-bold">Payment</th>
                            <th className="py-3 font-bold">Status</th>
                            <th className="py-3 font-bold text-right">Total</th>
                          </tr>
                        </thead>
                        <tbody className="divide-y divide-stone-100">
                          {orders.map((order: any) => (
                            <tr key={order.id} className="hover:bg-stone-50 transition-colors">
                              <td className="py-4 font-mono font-bold text-[#c2410c]">
                                <Link href={`/account/orders/detail?id=${order.id}`} className="hover:underline">
                                  {order.order_number}
                                </Link>
                              </td>
                              <td className="py-4 text-stone-600">{new Date(order.created_at).toLocaleDateString()}</td>
                              <td className="py-4 uppercase text-[11px] font-mono text-stone-500">{order.payment_method}</td>
                              <td className="py-4">
                                <span className={`px-2 py-0.5 text-[10px] font-display font-bold uppercase tracking-wider rounded-full border ${
                                  order.payment_status === 'paid'
                                    ? 'bg-emerald-50 text-emerald-800 border-emerald-200'
                                    : 'bg-amber-50 text-amber-800 border-amber-200'
                                }`}>
                                  {order.payment_status}
                                </span>
                              </td>
                              <td className="py-4 text-right font-display font-bold text-stone-900">
                                {formatPrice(order.total_amount_minor || order.total_minor)}
                              </td>
                            </tr>
                          ))}
                        </tbody>
                      </table>
                    </div>
                  )}
                </div>
              )}

              {/* SAVED ADDRESS TAB */}
              {authTab === 'address' && (
                <div className="max-w-xl">
                  <h2 className="font-display font-black text-lg uppercase tracking-wider text-stone-900 mb-1.5">
                    Default Delivery Address
                  </h2>
                  <p className="text-xs text-stone-500 mb-6">
                    This address will be automatically selected during 1-click checkout.
                  </p>
                  <form onSubmit={handleSaveAddress} className="space-y-4 text-xs">
                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                      <div>
                        <label className={labelCls}>Recipient Name *</label>
                        <input
                          type="text"
                          required
                          value={addressForm.name}
                          onChange={e => setAddressForm({ ...addressForm, name: e.target.value })}
                          className={inputCls}
                        />
                      </div>
                      <div>
                        <label className={labelCls}>Phone Number *</label>
                        <input
                          type="tel"
                          required
                          value={addressForm.phone}
                          onChange={e => setAddressForm({ ...addressForm, phone: e.target.value })}
                          className={inputCls}
                        />
                      </div>
                    </div>

                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                      <div>
                        <label className={labelCls}>Governorate / City *</label>
                        <select
                          value={addressForm.city}
                          onChange={e => setAddressForm({ ...addressForm, city: e.target.value })}
                          className={`${inputCls} cursor-pointer`}
                        >
                          {EGYPT_GOVERNORATES.map(gov => (
                            <option key={gov} value={gov}>{gov}</option>
                          ))}
                        </select>
                      </div>
                      <div>
                        <label className={labelCls}>District / Area</label>
                        <input
                          type="text"
                          placeholder="e.g. Maadi"
                          value={addressForm.state || ''}
                          onChange={e => setAddressForm({ ...addressForm, state: e.target.value })}
                          className={inputCls}
                        />
                      </div>
                    </div>

                    <div>
                      <label className={labelCls}>Street Address, Building & Apt *</label>
                      <input
                        type="text"
                        required
                        value={addressForm.line1}
                        onChange={e => setAddressForm({ ...addressForm, line1: e.target.value })}
                        className={inputCls}
                      />
                    </div>

                    <button
                      type="submit"
                      className="bg-[#c2410c] hover:bg-[#9a3412] text-amber-50 font-display font-bold uppercase text-xs tracking-wider px-6 py-3.5 rounded-xl transition-colors shadow-xs mt-4 cursor-pointer"
                    >
                      Save Delivery Address
                    </button>
                  </form>
                </div>
              )}

              {/* PROFILE TAB */}
              {authTab === 'profile' && (
                <div className="max-w-md text-xs">
                  <h2 className="font-display font-black text-lg uppercase tracking-wider text-stone-900 mb-6">
                    Personal Information
                  </h2>
                  <form onSubmit={handleUpdateProfile} className="space-y-3.5">
                    <div>
                      <label className={labelCls}>Email Address</label>
                      <input type="email" value={customer.email} readOnly className={`${inputCls} opacity-60 cursor-not-allowed`} />
                    </div>
                    <div className="grid grid-cols-2 gap-3">
                      <div>
                        <label className={labelCls}>First Name</label>
                        <input type="text" value={profileFirstName} onChange={e => setProfileFirstName(e.target.value)} required className={inputCls} />
                      </div>
                      <div>
                        <label className={labelCls}>Last Name</label>
                        <input type="text" value={profileLastName} onChange={e => setProfileLastName(e.target.value)} required className={inputCls} />
                      </div>
                    </div>
                    <div>
                      <label className={labelCls}>Contact Phone</label>
                      <input type="tel" value={profilePhone} onChange={e => setProfilePhone(e.target.value)} className={inputCls} />
                    </div>
                    <button type="submit" disabled={loading} className="w-full bg-[#c2410c] hover:bg-[#9a3412] text-amber-50 font-display font-bold uppercase text-xs tracking-wider py-3.5 rounded-xl transition-colors disabled:opacity-50 mt-4 cursor-pointer">
                      {loading ? 'Saving...' : 'Save Profile Changes'}
                    </button>
                  </form>
                </div>
              )}

              {/* SECURITY TAB */}
              {authTab === 'security' && (
                <div className="max-w-md text-xs">
                  <h2 className="font-display font-black text-lg uppercase tracking-wider text-stone-900 mb-6">
                    Change Account Password
                  </h2>
                  <form onSubmit={handleUpdatePassword} className="space-y-3.5">
                    <div>
                      <label className={labelCls}>Current Password</label>
                      <input type="password" value={currentPassword} onChange={e => setCurrentPassword(e.target.value)} required className={inputCls} />
                    </div>
                    <div>
                      <label className={labelCls}>New Password</label>
                      <input type="password" minLength={8} value={newPassword} onChange={e => setNewPassword(e.target.value)} required className={inputCls} />
                    </div>
                    <div>
                      <label className={labelCls}>Confirm New Password</label>
                      <input type="password" minLength={8} value={newPasswordConfirm} onChange={e => setNewPasswordConfirm(e.target.value)} required className={inputCls} />
                    </div>
                    <button type="submit" disabled={loading} className="w-full bg-[#c2410c] hover:bg-[#9a3412] text-amber-50 font-display font-bold uppercase text-xs tracking-wider py-3.5 rounded-xl transition-colors disabled:opacity-50 mt-4 cursor-pointer">
                      {loading ? 'Updating...' : 'Update Password'}
                    </button>
                  </form>
                </div>
              )}
            </div>
          </div>
        </div>
      </main>
    );
  }

  // Unauthenticated view
  return (
    <main className="min-h-screen bg-[#faf8f5] flex items-center justify-center px-4 py-24 text-stone-900">
      <div className="w-full max-w-md bg-white border border-stone-200 rounded-2xl p-6 sm:p-8 shadow-md">
        {/* Logo */}
        <div className="text-center mb-6">
          <Link href="/">
            <span className="font-display font-black uppercase tracking-tight text-[#c2410c] text-3xl hover:opacity-90 transition-opacity">
              {storeName}
            </span>
          </Link>
          <p className="text-stone-500 text-xs mt-1 font-display uppercase tracking-wider font-semibold">
            Customer Account
          </p>
        </div>

        {/* Quick guest note */}
        <div className="mb-6 p-3 bg-amber-50 border border-amber-200/80 rounded-xl text-xs text-stone-700 text-center">
          Purchasing without an account? You can <Link href="/checkout" className="text-[#c2410c] underline font-bold">Checkout as Guest</Link> anytime.
        </div>

        {/* Tabs */}
        <div className="flex mb-6 border-b border-stone-200">
          {(['login', 'register'] as const).map(t => (
            <button
              key={t}
              onClick={() => { setUnauthTab(t); setError(''); setSuccess(''); }}
              className={`flex-1 py-3 text-xs uppercase tracking-wider font-display font-bold transition-colors cursor-pointer ${
                unauthTab === t 
                  ? 'text-[#c2410c] border-b-2 border-[#c2410c] -mb-px' 
                  : 'text-stone-400 hover:text-stone-700'
              }`}
            >
              {t === 'login' ? 'Sign In' : 'Create Account'}
            </button>
          ))}
        </div>

        {error && <div className="mb-5 p-3 bg-rose-50 border border-rose-200 rounded-xl text-rose-800 text-xs font-display font-bold">{error}</div>}
        {success && <div className="mb-5 p-3 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-800 text-xs font-display font-bold">{success}</div>}

        {/* Login */}
        {unauthTab === 'login' && (
          <form onSubmit={handleLogin} className="space-y-3.5 text-xs">
            <div>
              <label className={labelCls}>Email Address</label>
              <input 
                type="email" 
                required 
                value={loginEmail} 
                onChange={e => setLoginEmail(e.target.value)}
                placeholder="your@email.com" 
                className={inputCls} 
                autoComplete="email" 
              />
            </div>
            <div>
              <label className={labelCls}>Password</label>
              <input 
                type="password" 
                required 
                value={loginPassword} 
                onChange={e => setLoginPassword(e.target.value)}
                placeholder="••••••••" 
                className={inputCls} 
                autoComplete="current-password" 
              />
            </div>
            <button 
              type="submit" 
              disabled={loading}
              className="w-full bg-[#c2410c] hover:bg-[#9a3412] text-amber-50 font-display font-bold uppercase text-xs tracking-wider py-3.5 rounded-xl transition-all disabled:opacity-50 mt-4 shadow-xs active:scale-95 cursor-pointer"
            >
              {loading ? 'Signing In...' : 'Sign In'}
            </button>
          </form>
        )}

        {/* Register */}
        {unauthTab === 'register' && (
          <form onSubmit={handleRegister} className="space-y-3.5 text-xs">
            <div className="grid grid-cols-2 gap-3">
              <div>
                <label className={labelCls}>First Name</label>
                <input 
                  type="text" 
                  required 
                  value={regFirstName} 
                  onChange={e => setRegFirstName(e.target.value)}
                  placeholder="Ahmed" 
                  className={inputCls} 
                />
              </div>
              <div>
                <label className={labelCls}>Last Name</label>
                <input 
                  type="text" 
                  required 
                  value={regLastName} 
                  onChange={e => setRegLastName(e.target.value)}
                  placeholder="Ali" 
                  className={inputCls} 
                />
              </div>
            </div>
            <div>
              <label className={labelCls}>Email Address</label>
              <input 
                type="email" 
                required 
                value={regEmail} 
                onChange={e => setRegEmail(e.target.value)}
                placeholder="your@email.com" 
                className={inputCls} 
              />
            </div>
            <div>
              <label className={labelCls}>Password</label>
              <input 
                type="password" 
                required 
                minLength={8} 
                value={regPassword} 
                onChange={e => setRegPassword(e.target.value)}
                placeholder="Min 8 characters" 
                className={inputCls} 
              />
            </div>
            <div>
              <label className={labelCls}>Confirm Password</label>
              <input 
                type="password" 
                required 
                value={regConfirm} 
                onChange={e => setRegConfirm(e.target.value)}
                placeholder="••••••••" 
                className={inputCls} 
              />
            </div>
            <button 
              type="submit" 
              disabled={loading}
              className="w-full bg-[#c2410c] hover:bg-[#9a3412] text-amber-50 font-display font-bold uppercase text-xs tracking-wider py-3.5 rounded-xl transition-all disabled:opacity-50 mt-4 shadow-xs active:scale-95 cursor-pointer"
            >
              {loading ? 'Creating Account...' : 'Create Account'}
            </button>
          </form>
        )}
      </div>
    </main>
  );
}
