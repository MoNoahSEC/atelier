'use client';

import React, { useState } from 'react';
import { loadStripe } from '@stripe/stripe-js';
import {
  Elements,
  PaymentElement,
  useStripe,
  useElements
} from '@stripe/react-stripe-js';

const stripePromise = loadStripe(process.env.NEXT_PUBLIC_STRIPE_PUBLISHABLE_KEY || '');

interface StripePaymentFormProps {
  clientSecret: string;
  onSuccess: () => void;
  onError: (message: string) => void;
  returnUrl: string;
}

function CheckoutForm({ returnUrl, onSuccess, onError }: { returnUrl: string, onSuccess: () => void, onError: (message: string) => void }) {
  const stripe = useStripe();
  const elements = useElements();
  const [isLoading, setIsLoading] = useState(false);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();

    if (!stripe || !elements) {
      return;
    }

    setIsLoading(true);

    const { error } = await stripe.confirmPayment({
      elements,
      confirmParams: {
        return_url: returnUrl,
      },
    });

    if (error) {
      onError(error.message ?? 'An unknown error occurred');
    } else {
      onSuccess();
    }
    
    setIsLoading(false);
  };

  return (
    <form onSubmit={handleSubmit} className="space-y-6">
      <div className="p-4 bg-white/[0.02] border border-white/10">
        <PaymentElement options={{ layout: 'tabs' }} />
      </div>
      
      <button
        type="submit"
        disabled={isLoading || !stripe || !elements}
        className="w-full bg-[#e8ff00] text-black font-display font-bold uppercase tracking-[0.25em] text-sm py-4 px-6 hover:bg-white transition-all disabled:opacity-50 disabled:cursor-not-allowed"
      >
        {isLoading ? 'Processing...' : 'Pay Now'}
      </button>
    </form>
  );
}

export default function StripePaymentForm({
  clientSecret,
  onSuccess,
  onError,
  returnUrl
}: StripePaymentFormProps) {
  if (!clientSecret) return null;

  return (
    <Elements
      stripe={stripePromise}
      options={{
        clientSecret,
        appearance: {
          theme: 'night',
          variables: {
            colorPrimary: '#e8ff00',
            colorBackground: '#0a0a0a',
            colorText: '#ffffff',
            colorDanger: '#ff4444',
            fontFamily: 'Inter, system-ui, sans-serif',
          }
        }
      }}
    >
      <CheckoutForm returnUrl={returnUrl} onSuccess={onSuccess} onError={onError} />
    </Elements>
  );
}
