'use client';
import { useEffect, useState } from 'react';
import { useParams } from 'next/navigation';
import Link from 'next/link';
import { getOrder, updateOrderStatus } from '@/lib/admin-api';

const Badge = ({ s }: { s: string }) => {
  const map: Record<string,string> = { 
    paid: 'text-emerald-800 bg-emerald-50 border-emerald-200', 
    pending: 'text-amber-800 bg-amber-50 border-amber-200', 
    failed: 'text-rose-800 bg-rose-50 border-rose-200', 
    fulfilled: 'text-emerald-800 bg-emerald-50 border-emerald-200', 
    unfulfilled: 'text-[#c2410c] bg-amber-50 border-amber-200', 
    fulfilling: 'text-sky-800 bg-sky-50 border-sky-200', 
    cancelled: 'text-rose-800 bg-rose-50 border-rose-200', 
    shipped: 'text-sky-800 bg-sky-50 border-sky-200', 
    delivered: 'text-emerald-800 bg-emerald-50 border-emerald-200' 
  };
  return <span className={`inline-block px-2.5 py-0.5 text-[10px] font-display uppercase tracking-wider font-bold rounded border ${map[s]||'text-stone-600 bg-stone-100 border-stone-200'}`}>{s}</span>;
};

export default function OrderDetailPage() {
  const { id } = useParams() as { id: string };
  const [order, setOrder] = useState<any>(null);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [success, setSuccess] = useState('');
  const [updates, setUpdates] = useState<Record<string,string>>({});

  useEffect(() => {
    getOrder(id).then(r => { setOrder(r); setLoading(false); }).catch(() => setLoading(false));
  }, [id]);

  async function handleStatusUpdate() {
    if (!Object.keys(updates).length) return;
    setSaving(true); setSuccess('');
    try {
      const r = await updateOrderStatus(id, updates);
      setOrder(r); setUpdates({}); setSuccess('Order updated successfully!');
    } catch (e: any) { alert(e.message); }
    finally { setSaving(false); }
  }

  if (loading) return <div className="flex items-center justify-center h-64 text-xs text-stone-400 font-display uppercase tracking-widest animate-pulse font-semibold">Loading order details...</div>;
  if (!order) return <div className="text-center py-20 text-stone-400 font-display uppercase tracking-wider text-xs font-semibold">Order not found</div>;

  const fmt = (v: number) => `${((v || 0) / 100).toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 2 })} ${order.currency || 'EGP'}`;
  const date = (d: string) => new Date(d).toLocaleString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' });
  
  const Sel = ({ label, field, options }: { label: string; field: string; options: string[] }) => (
    <div>
      <label className="block font-display text-[11px] uppercase tracking-wider text-stone-700 font-bold mb-1.5">{label}</label>
      <select value={updates[field] ?? order[field] ?? ''} onChange={e => setUpdates(u => ({ ...u, [field]: e.target.value }))}
        className="w-full bg-[#faf8f5] border border-stone-200 rounded-xl px-3.5 py-2.5 text-sm text-stone-800 outline-none focus:border-[#c2410c] cursor-pointer font-medium">
        {options.map(o => <option key={o} value={o}>{o}</option>)}
      </select>
    </div>
  );

  const getMethodBadge = (m: string) => {
    if (m === 'paymob') return 'Paymob (Egypt Wallet / Cards)';
    if (m === 'stripe') return 'Stripe (Credit / Debit Card)';
    if (m === 'cod') return 'Cash on Delivery (COD)';
    return m || 'Standard';
  };

  return (
    <div className="max-w-4xl space-y-6">
      <div className="flex flex-wrap items-center gap-3">
        <Link href="/admin/orders" className="text-stone-500 hover:text-stone-900 transition-colors text-xs font-display uppercase tracking-wider font-bold">← Back to Orders</Link>
        <h1 className="font-display font-black uppercase text-2xl tracking-tight text-stone-900">{order.order_number}</h1>
        <Badge s={order.payment_status} />
        <Badge s={order.fulfillment_status} />
      </div>
      {success && <div className="px-4 py-3 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-800 text-xs font-display font-bold uppercase tracking-wider">{success}</div>}

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-4">
        {/* Order Summary */}
        <div className="border border-stone-200 bg-white rounded-2xl p-5 space-y-4 shadow-xs">
          <p className="font-display font-bold text-xs uppercase tracking-wider text-stone-700">Order Information</p>
          <div className="space-y-2">
            <div className="flex justify-between text-xs"><span className="text-stone-500 font-medium">Date</span><span className="text-stone-900 font-medium">{date(order.created_at)}</span></div>
            <div className="flex justify-between text-xs"><span className="text-stone-500 font-medium">Customer Email</span><span className="text-stone-900 font-mono">{order.customer_email}</span></div>
            <div className="flex justify-between text-xs"><span className="text-stone-500 font-medium">Payment Gateway</span><span className="text-[#c2410c] font-bold">{getMethodBadge(order.payment_method)}</span></div>
            {order.provider_ref && (
              <div className="flex justify-between text-xs"><span className="text-stone-500 font-medium">Gateway Ref</span><span className="text-stone-800 font-mono">{order.provider_ref}</span></div>
            )}
            <div className="flex justify-between text-xs"><span className="text-stone-500 font-medium">Currency</span><span className="text-stone-900 font-bold">{order.currency || 'EGP'}</span></div>
          </div>

          <div className="border-t border-stone-100 pt-4 space-y-1.5">
            <div className="flex justify-between text-xs"><span className="text-stone-500">Subtotal</span><span className="text-stone-900 font-semibold">{fmt(order.subtotal_minor)}</span></div>
            <div className="flex justify-between text-xs"><span className="text-stone-500">Shipping</span><span className="text-stone-900 font-semibold">{fmt(order.shipping_minor)}</span></div>
            <div className="flex justify-between text-xs"><span className="text-stone-500">Tax</span><span className="text-stone-900 font-semibold">{fmt(order.tax_minor)}</span></div>
            {order.discount_minor > 0 && (
              <div className="flex justify-between text-xs"><span className="text-stone-500">Discount</span><span className="text-emerald-700 font-bold">-{fmt(order.discount_minor)}</span></div>
            )}
            <div className="flex justify-between text-base font-bold border-t border-stone-200 pt-3 mt-2">
              <span className="text-stone-900">Total Amount</span>
              <span className="text-[#c2410c] font-black">{fmt(order.total_amount_minor)}</span>
            </div>
          </div>
        </div>

        {/* Update Status */}
        <div className="border border-stone-200 bg-white rounded-2xl p-5 space-y-4 shadow-xs">
          <p className="font-display font-bold text-xs uppercase tracking-wider text-stone-700">Update Status & Tracking</p>
          <Sel label="Payment Status" field="payment_status" options={['pending','paid','failed','refunded']} />
          <Sel label="Fulfillment Status" field="fulfillment_status" options={['unfulfilled','fulfilling','fulfilled','cancelled']} />
          <Sel label="Shipping Status" field="shipping_status" options={['pending','shipped','delivered','returned']} />
          <div>
            <label className="block font-display text-[11px] uppercase tracking-wider text-stone-700 font-bold mb-1.5">Carrier Tracking Number</label>
            <input 
              type="text" 
              value={updates.tracking_number ?? order.tracking_number ?? ''} 
              onChange={e => setUpdates(u => ({ ...u, tracking_number: e.target.value }))}
              className="w-full bg-[#faf8f5] border border-stone-200 rounded-xl px-3.5 py-2.5 text-sm text-stone-900 outline-none focus:border-[#c2410c] placeholder:text-stone-400 font-mono font-medium" 
              placeholder="e.g. BOSTA-1029384 or AWB-88992" 
            />
          </div>
          <button 
            onClick={handleStatusUpdate} 
            disabled={saving}
            className="w-full bg-[#c2410c] text-amber-50 font-display font-bold uppercase text-xs tracking-wider py-3.5 rounded-xl hover:bg-[#9a3412] shadow-xs active:scale-95 transition-all disabled:opacity-50 cursor-pointer"
          >
            {saving ? 'Saving...' : 'Save Order Changes'}
          </button>
        </div>
      </div>

      {/* Order Items */}
      <div className="border border-stone-200 bg-white rounded-xl shadow-xs overflow-hidden">
        <div className="px-5 py-4 border-b border-stone-100 bg-stone-50 flex items-center justify-between">
          <p className="font-display font-bold text-xs uppercase tracking-wider text-stone-700">Ordered Items ({order.items?.length ?? 0})</p>
        </div>
        <div className="overflow-x-auto">
          <table className="w-full text-xs">
            <thead><tr className="border-b border-stone-200 bg-stone-50 text-stone-600 font-semibold uppercase tracking-wider">
              <th className="text-left px-5 py-3">Product</th>
              <th className="text-right px-3 py-3">Qty</th>
              <th className="text-right px-3 py-3">Unit Price</th>
              <th className="text-right px-5 py-3">Total</th>
            </tr></thead>
            <tbody className="divide-y divide-stone-100">
              {(order.items || []).map((item: any) => (
                <tr key={item.id} className="hover:bg-stone-50 transition-colors">
                  <td className="px-5 py-3.5">
                    <div className="text-stone-900 font-bold">{item.product_title || item.title}</div>
                    <div className="text-stone-500 font-mono text-[10px]">{item.sku || '–'}</div>
                  </td>
                  <td className="px-3 py-3.5 text-right text-stone-900 font-semibold">{item.quantity}</td>
                  <td className="px-3 py-3.5 text-right text-stone-900">{fmt(item.unit_price_minor)}</td>
                  <td className="px-5 py-3.5 text-right font-bold text-[#c2410c]">{fmt(item.total_price_minor || (item.unit_price_minor * item.quantity))}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
}



