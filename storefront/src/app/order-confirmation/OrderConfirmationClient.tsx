'use client';

import { useSearchParams } from 'next/navigation';
import OrderDetails from './OrderDetails';

export default function OrderConfirmationClient() {
  const searchParams = useSearchParams();
  const orderNumber = searchParams.get('order');

  if (orderNumber) {
    return <OrderDetails orderNumber={orderNumber} />;
  }

  return (
    <p className="font-display text-xs uppercase tracking-[0.2em] text-muted mb-8">
      Order processing...
    </p>
  );
}
