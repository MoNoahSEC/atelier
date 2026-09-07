'use client';
import { useState, FormEvent } from 'react';
import { useRouter } from 'next/navigation';
import { useStore } from '@/components/providers/StoreProvider';
import { adminLogin } from '@/lib/admin-api';

export default function AdminLoginPage() {
  const router = useRouter();
  const { storeName } = useStore();
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);

  async function handleSubmit(e: FormEvent) {
    e.preventDefault();
    setLoading(true);
    setError('');
    try {
      const data = await adminLogin(email, password);
      localStorage.setItem('admin_token', data.token);
      localStorage.setItem('admin_user', JSON.stringify(data.user));
      router.push('/admin');
    } catch (err: any) {
      setError(err.message || 'Login failed. Please check your credentials.');
    } finally {
      setLoading(false);
    }
  }

  return (
    <div className="min-h-screen bg-[#faf8f5] flex items-center justify-center p-4">
      <div className="w-full max-w-md bg-white border border-stone-200/80 shadow-xl rounded-2xl p-8">
        {/* Logo */}
        <div className="text-center mb-8">
          <span className="font-display font-black uppercase text-3xl tracking-tight text-stone-900">{storeName}</span>
          <p className="text-[#c2410c] text-xs mt-1 uppercase tracking-wider font-display font-bold">Admin Control Panel</p>
        </div>

        <form onSubmit={handleSubmit} className="space-y-6">
          <h1 className="font-display font-black uppercase text-xl tracking-wider text-stone-900 border-b border-stone-100 pb-3">Sign In</h1>

          {error && (
            <div className="px-4 py-3 bg-rose-50 border border-rose-200 text-rose-700 text-xs font-semibold rounded-lg">
              {error}
            </div>
          )}

          <div className="space-y-4">
            <div>
              <label className="block font-display text-xs uppercase tracking-wider font-bold text-stone-700 mb-1.5">Email Address</label>
              <input
                type="email"
                value={email}
                onChange={e => setEmail(e.target.value)}
                required
                className="w-full bg-[#f4efe6]/50 border-2 border-stone-200 rounded-xl px-4 py-3 text-sm font-medium text-stone-900 outline-none focus:bg-white focus:border-[#c2410c] transition-all placeholder:text-stone-400"
                placeholder="admin@atelier.com"
              />
            </div>
            <div>
              <label className="block font-display text-xs uppercase tracking-wider font-bold text-stone-700 mb-1.5">Password</label>
              <input
                type="password"
                value={password}
                onChange={e => setPassword(e.target.value)}
                required
                className="w-full bg-[#f4efe6]/50 border-2 border-stone-200 rounded-xl px-4 py-3 text-sm font-medium text-stone-900 outline-none focus:bg-white focus:border-[#c2410c] transition-all placeholder:text-stone-400"
                placeholder="••••••••"
              />
            </div>
          </div>

          <button
            type="submit"
            disabled={loading}
            className="w-full bg-[#c2410c] text-amber-50 font-display font-black uppercase text-sm tracking-wider py-4 rounded-xl hover:bg-[#9a3412] active:scale-95 transition-all shadow-md disabled:opacity-50 cursor-pointer"
          >
            {loading ? 'Signing In...' : 'Sign In to Control Panel →'}
          </button>
        </form>

      </div>
    </div>
  );
}
