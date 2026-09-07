'use client';
import { useEffect, useState } from 'react';
import { useParams } from 'next/navigation';
import Link from 'next/link';
import { getCustomer, banCustomer, unbanCustomer } from '@/lib/admin-api';

const Badge = ({ s }: { s: string }) => {
  const map: Record<string,string> = { 
    paid: 'text-emerald-800 bg-emerald-50 border-emerald-200', 
    pending: 'text-amber-800 bg-amber-50 border-amber-200', 
    fulfilled: 'text-emerald-800 bg-emerald-50 border-emerald-200', 
    unfulfilled: 'text-[#c2410c] bg-amber-50 border-amber-200' 
  };
  return <span className={`inline-block px-2.5 py-0.5 text-[10px] font-display uppercase tracking-wider font-bold rounded border ${map[s]||'text-stone-600 bg-stone-100 border-stone-200'}`}>{s}</span>;
};

export default function CustomerDetailPage() {
  const { id } = useParams() as { id: string };
  const [customer, setCustomer] = useState<any>(null);
  const [loading, setLoading] = useState(true);
  const [banning, setBanning] = useState(false);

  const fetchCustomer = () => {
    getCustomer(id).then(r => { setCustomer(r); setLoading(false); }).catch(() => setLoading(false));
  };

  useEffect(() => {
    fetchCustomer();
  }, [id]);

  const toggleBan = async () => {
    if (banning) return;
    try {
      setBanning(true);
      if (customer.is_banned) {
        if (!confirm('Are you sure you want to unban this customer?')) return;
        await unbanCustomer(id);
      } else {
        const reason = prompt('Enter a reason for banning this customer (optional):');
        if (reason === null) return; // cancelled
        await banCustomer(id, reason);
      }
      fetchCustomer(); // Refresh data
    } catch (err: any) {
      alert(err.message || 'Failed to update ban status');
    } finally {
      setBanning(false);
    }
  };

  if (loading) return <div className="flex items-center justify-center h-64 text-xs text-stone-400 font-display uppercase tracking-widest animate-pulse font-semibold">Loading customer profile...</div>;
  if (!customer) return <div className="text-center py-20 text-stone-400 font-display uppercase tracking-wider text-xs font-semibold">Customer not found</div>;

  const fmt = (v: number, cur = 'EGP') => new Intl.NumberFormat('en-US', { style: 'currency', currency: cur, minimumFractionDigits: 0 }).format((v || 0) / 100);
  const date = (d: string) => new Date(d).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
  const totalSpent = customer.orders?.filter((o: any) => o.payment_status === 'paid').reduce((s: number, o: any) => s + o.total_amount_minor, 0) || 0;

  return (
    <div className="max-w-4xl space-y-6">
      <div className="flex items-center gap-4">
        <Link href="/admin/customers" className="text-stone-500 hover:text-stone-900 transition-colors text-xs font-display uppercase tracking-wider font-bold">← Back to Customers</Link>
        <h1 className="font-display font-black uppercase text-2xl tracking-tight text-stone-900">
          {customer.first_name} {customer.last_name}
          {customer.is_banned && <span className="ml-3 text-xs bg-rose-100 text-rose-800 px-2.5 py-1 rounded-md uppercase tracking-wider border border-rose-300 font-bold">Banned</span>}
        </h1>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-4">
        {/* Profile */}
        <div className="border border-stone-200 bg-white rounded-2xl p-5 space-y-4 shadow-xs relative">
          <p className="font-display font-bold text-xs uppercase tracking-wider text-stone-700">Profile</p>
          {[['Email', customer.email], ['Phone', customer.phone || '—'], ['Status', customer.status || 'active'], ['Joined', date(customer.created_at)]].map(([l, v]) => (
            <div key={l}><p className="text-[10px] text-stone-400 font-display uppercase tracking-wider mb-0.5 font-bold">{l}</p><p className="text-sm font-semibold text-stone-900">{v}</p></div>
          ))}
          {customer.is_banned && customer.ban_reason && (
             <div className="mt-2 p-2.5 bg-rose-50 border border-rose-200 rounded-xl text-xs text-rose-900">
               <strong>Ban Reason:</strong> {customer.ban_reason}
             </div>
          )}
          <button
            onClick={toggleBan}
            disabled={banning}
            className={`mt-4 w-full py-2.5 text-xs font-display uppercase tracking-wider font-bold rounded-xl border transition-all cursor-pointer ${
              customer.is_banned 
                ? 'border-stone-300 text-stone-700 hover:bg-stone-100' 
                : 'border-rose-300 text-rose-700 bg-rose-50 hover:bg-rose-100'
            }`}
          >
            {banning ? 'Processing...' : customer.is_banned ? 'Unban User' : 'Ban User'}
          </button>
        </div>

        {/* Stats */}
        <div className="border border-stone-200 bg-white rounded-2xl p-5 space-y-4 shadow-xs">
          <p className="font-display font-bold text-xs uppercase tracking-wider text-stone-700">Stats</p>
          <div><p className="text-[10px] text-stone-400 font-display uppercase tracking-wider mb-0.5 font-bold">Total Orders</p><p className="text-3xl font-display font-black text-stone-900">{customer.orders?.length || 0}</p></div>
          <div><p className="text-[10px] text-stone-400 font-display uppercase tracking-wider mb-0.5 font-bold">Lifetime Value</p><p className="text-3xl font-display font-black text-[#c2410c]">{fmt(totalSpent)}</p></div>
        </div>

        {/* Addresses */}
        <div className="border border-stone-200 bg-white rounded-2xl p-5 shadow-xs">
          <p className="font-display font-bold text-xs uppercase tracking-wider text-stone-700 mb-4">Addresses</p>
          {customer.addresses?.length ? customer.addresses.map((a: any) => (
            <div key={a.id} className="text-xs text-stone-600 space-y-0.5 mb-4 pb-4 border-b border-stone-100 last:border-0 last:mb-0">
              <p className="text-stone-900 font-bold">{a.first_name} {a.last_name}</p>
              <p>{a.address_line_1}</p>
              {a.address_line_2 && <p>{a.address_line_2}</p>}
              <p>{a.city}, {a.country_code} {a.postal_code}</p>
              <span className="inline-block mt-1 px-2 py-0.5 text-[10px] border border-stone-200 rounded text-stone-500 uppercase tracking-wider font-semibold">{a.type}</span>
            </div>
          )) : <p className="text-xs text-stone-400 font-medium">No addresses on file</p>}
        </div>
      </div>

      {/* Order History */}
      <div className="border border-stone-200 bg-white rounded-xl shadow-xs overflow-hidden">
        <div className="px-5 py-4 border-b border-stone-100 bg-stone-50">
          <p className="font-display font-bold text-xs uppercase tracking-wider text-stone-700">Order History</p>
        </div>
        <div className="overflow-x-auto">
          <table className="w-full text-xs">
            <thead><tr className="border-b border-stone-200 bg-stone-50 text-stone-600 font-semibold">
              <th className="text-left px-5 py-3 font-display uppercase tracking-wider">Order</th>
              <th className="text-right px-3 py-3 font-display uppercase tracking-wider">Total</th>
              <th className="text-left px-3 py-3 font-display uppercase tracking-wider">Payment</th>
              <th className="text-left px-3 py-3 font-display uppercase tracking-wider">Fulfillment</th>
              <th className="text-left px-3 py-3 font-display uppercase tracking-wider">Date</th>
              <th className="text-right px-5 py-3 font-display uppercase tracking-wider">Actions</th>
            </tr></thead>
            <tbody className="divide-y divide-stone-100">
              {(customer.orders || []).map((o: any) => (
                <tr key={o.id} className="hover:bg-stone-50 transition-colors">
                  <td className="px-5 py-3 font-mono font-bold text-stone-900">{o.order_number}</td>
                  <td className="px-3 py-3 text-right font-bold text-stone-900">{fmt(o.total_amount_minor, o.currency)}</td>
                  <td className="px-3 py-3"><Badge s={o.payment_status} /></td>
                  <td className="px-3 py-3"><Badge s={o.fulfillment_status} /></td>
                  <td className="px-3 py-3 text-stone-500 font-mono text-[11px]">{date(o.created_at)}</td>
                  <td className="px-5 py-3 text-right"><Link href={`/admin/orders/${o.id}`} className="font-display text-xs uppercase tracking-wider font-bold text-[#c2410c] hover:underline">View →</Link></td>
                </tr>
              ))}
              {!customer.orders?.length && <tr><td colSpan={6} className="px-5 py-12 text-center text-stone-400 font-display uppercase tracking-widest text-xs font-semibold">No orders yet</td></tr>}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
}



