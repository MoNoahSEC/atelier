'use client';

import React from 'react';

export interface PaymentMethod {
  id: string;
  label: string;
  badge?: string;
  description: string;
  icon?: React.ReactNode;
}

interface PaymentMethodSelectorProps {
  selected: string;
  onChange: (method: string) => void;
  methods?: PaymentMethod[];
}

const defaultMethods: PaymentMethod[] = [
  {
    id: 'apple_pay',
    label: 'Apple Pay',
    badge: 'Instant & Secure',
    description: 'Pay instantly with Touch ID / Face ID & Apple Wallet',
    icon: (
      <svg className="w-6 h-6 text-stone-900 fill-current" viewBox="0 0 24 24">
        <path d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.81-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83M15.97 6.37c.61-.75 1.04-1.8 0.92-2.87-.9.04-1.97.6-2.6 1.34-.56.64-.99 1.7-0.85 2.73.99.08 1.98-.51 2.53-1.2" />
      </svg>
    ),
  },
  {
    id: 'google_pay',
    label: 'Google Pay',
    badge: 'Instant Wallet',
    description: 'Pay securely using saved payment methods in Google Account',
    icon: (
      <svg className="w-6 h-6" viewBox="0 0 24 24">
        <path fill="#4285F4" d="M23.745 12.27c0-.7-.06-1.4-.19-2.07H12v4.51h6.6c-.29 1.52-1.14 2.82-2.4 3.68v3.05h3.88c2.27-2.09 3.665-5.17 3.665-9.17z"/>
        <path fill="#34A853" d="M12 24c3.24 0 5.95-1.08 7.93-2.91l-3.88-3.05c-1.08.72-2.45 1.16-4.05 1.16-3.12 0-5.77-2.1-6.72-4.93H1.25v3.15C3.26 21.36 7.33 24 12 24z"/>
        <path fill="#FBBC05" d="M5.28 14.27c-.25-.72-.38-1.49-.38-2.27s.13-1.55.38-2.27V6.58H1.25C.45 8.18 0 9.98 0 12s.45 3.82 1.25 5.42l4.03-3.15z"/>
        <path fill="#EA4335" d="M12 4.75c1.77 0 3.35.61 4.6 1.8l3.42-3.42C17.95 1.19 15.24 0 12 0 7.33 0 3.26 2.64 1.25 6.58l4.03 3.15c.95-2.83 3.6-4.98 6.72-4.98z"/>
      </svg>
    ),
  },
  {
    id: 'paymob',
    label: 'Paymob / Cards & Mobile Wallets',
    badge: 'Vodafone Cash & Cards',
    description: 'Visa, Mastercard, Meeza, Vodafone Cash, Orange, Etisalat, InstaPay',
    icon: (
      <svg className="w-6 h-6 text-[#c2410c]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 10h18M7 15h1m4 0h1m-7 4h12a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
      </svg>
    ),
  },
  {
    id: 'cod',
    label: 'Cash on Delivery (COD)',
    badge: '0 EGP Extra Fee',
    description: 'Inspect your package and pay cash directly upon doorstep delivery',
    icon: (
      <svg className="w-6 h-6 text-emerald-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
      </svg>
    ),
  },
];

export default function PaymentMethodSelector({
  selected,
  onChange,
  methods = defaultMethods,
}: PaymentMethodSelectorProps) {
  return (
    <div className="space-y-3">
      {methods.map((method) => {
        const isSelected = selected === method.id;

        return (
          <label
            key={method.id}
            className={`flex items-start gap-3.5 p-4 rounded-xl cursor-pointer transition-all duration-200 border ${
              isSelected
                ? 'border-[#c2410c] bg-amber-50/50 shadow-xs'
                : 'border-stone-200 bg-white hover:border-stone-300 hover:bg-stone-50/50'
            }`}
          >
            <input
              type="radio"
              name="payment_method"
              value={method.id}
              checked={isSelected}
              onChange={() => onChange(method.id)}
              className="sr-only"
            />

            <div className="shrink-0 mt-0.5 w-10 h-10 rounded-lg bg-stone-100/80 border border-stone-200 flex items-center justify-center">
              {method.icon}
            </div>

            <div className="flex-1 min-w-0">
              <div className="flex items-center justify-between gap-2">
                <div className="flex items-center gap-2">
                  <span className={`font-display font-bold uppercase tracking-wider text-xs md:text-sm ${
                    isSelected ? 'text-[#c2410c]' : 'text-stone-900'
                  }`}>
                    {method.label}
                  </span>
                  {method.badge && (
                    <span className={`text-[10px] font-display font-bold uppercase tracking-wider px-2 py-0.5 rounded-full ${
                      method.id === 'cod' 
                        ? 'bg-emerald-100 text-emerald-800' 
                        : 'bg-amber-100 text-[#c2410c]'
                    }`}>
                      {method.badge}
                    </span>
                  )}
                </div>

                <div className={`w-4 h-4 rounded-full border flex items-center justify-center transition-all shrink-0 ${
                  isSelected ? 'border-[#c2410c]' : 'border-stone-300'
                }`}>
                  {isSelected && <div className="w-2 h-2 rounded-full bg-[#c2410c]" />}
                </div>
              </div>
              <p className="text-xs text-stone-500 mt-1 font-normal leading-relaxed">
                {method.description}
              </p>
            </div>
          </label>
        );
      })}
    </div>
  );
}
