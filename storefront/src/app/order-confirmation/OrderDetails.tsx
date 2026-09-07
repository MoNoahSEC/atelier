'use client';

import { useEffect, useState } from 'react';
import { getApiUrl } from '@/lib/api';

const formatPrice = (minor: number) => {
  return new Intl.NumberFormat('en-EG', { style: 'currency', currency: 'EGP' }).format(minor / 100);
};

export default function OrderDetails({ orderNumber }: { orderNumber: string }) {
  const [order, setOrder] = useState<any>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    async function fetchOrder() {
      const token = localStorage.getItem('customer_token');
      if (!token) {
        setLoading(false);
        return;
      }

      try {
        const res = await fetch(`${getApiUrl()}/public/customer/orders/${orderNumber}`, {
          headers: {
            'Authorization': `Bearer ${token}`,
            'Accept': 'application/json'
          }
        });
        
        if (res.ok) {
          const data = await res.json();
          setOrder(data);
        }
      } catch (e) {
        console.error('Failed to fetch order', e);
      } finally {
        setLoading(false);
      }
    }
    
    fetchOrder();
  }, [orderNumber]);

  let paymentMessage = '';
  if (order?.payment_method === 'cod') {
    paymentMessage = 'Payment will be collected on delivery';
  } else if (order?.payment_method === 'paymob') {
    paymentMessage = order?.payment_status === 'paid' ? 'Payment confirmed via Paymob' : 'Payment pending via Paymob';
  } else if (order?.payment_method === 'stripe') {
    paymentMessage = 'Payment confirmed';
  }

  if (loading) {
    return <div className="text-sm text-muted animate-pulse">Loading order details...</div>;
  }

  if (!order) {
    return (
      <div className="font-display text-xs uppercase tracking-[0.2em] text-muted mb-2">
        Order: <span className="text-foreground font-bold">{orderNumber}</span>
      </div>
    );
  }

  return (
    <div className="w-full max-w-2xl mx-auto bg-surface border border-border p-6 text-left">
      <div className="flex flex-wrap justify-between items-end border-b border-border pb-4 mb-6">
        <div>
          <h2 className="font-display font-bold text-[10px] uppercase tracking-[0.3em] text-muted mb-1">Order Number</h2>
          <div className="font-display font-black text-xl text-foreground">{order.order_number}</div>
        </div>
        <div className="text-right mt-4 sm:mt-0">
          <div className="inline-block px-2 py-1 text-[10px] font-display uppercase tracking-widest bg-accent/10 text-accent border border-accent/20 mb-2">
            {order.payment_method}
          </div>
          <div className="text-xs text-muted">
            {paymentMessage}
          </div>
        </div>
      </div>
      
      <div className="space-y-4 mb-6 max-h-[300px] overflow-y-auto pr-2">
        {order.items?.map((item: any) => (
          <div key={item.id} className="flex justify-between items-center text-sm">
            <div>
              <span className="font-bold">{item.title}</span>
              {item.variant_title && <span className="text-muted ml-2">({item.variant_title})</span>}
              <div className="text-xs text-muted mt-1">Qty: {item.quantity}</div>
            </div>
            <div className="font-display font-bold">
              {formatPrice(item.line_total_minor)}
            </div>
          </div>
        ))}
      </div>
      
      <div className="border-t border-border pt-4 space-y-2 text-sm">
        <div className="flex justify-between text-muted">
          <span>Subtotal</span>
          <span className="font-display font-bold text-foreground">{formatPrice(order.subtotal_minor)}</span>
        </div>
        <div className="flex justify-between text-muted">
          <span>Shipping</span>
          <span className="font-display font-bold text-foreground">{formatPrice(order.shipping_total_minor)}</span>
        </div>
        <div className="flex justify-between text-muted">
          <span>Tax</span>
          <span className="font-display font-bold text-foreground">{formatPrice(order.tax_total_minor)}</span>
        </div>
        {order.payment_method === 'cod' && (
          <div className="flex justify-between text-muted">
            <span>COD Fee</span>
            <span className="font-display font-bold text-foreground">{formatPrice(2000)}</span>
          </div>
        )}
        <div className="flex justify-between pt-2 border-t border-border mt-2">
          <span className="font-display font-bold text-xs uppercase tracking-widest">Total</span>
          <span className="font-display font-black text-xl text-accent">{formatPrice(order.total_minor)}</span>
        </div>
      </div>
    </div>
  );
}
