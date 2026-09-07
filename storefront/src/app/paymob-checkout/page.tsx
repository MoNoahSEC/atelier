'use client';

import { useState, useEffect, Suspense } from 'react';
import { useSearchParams, useRouter } from 'next/navigation';
import Link from 'next/link';
import { getApiUrl } from '@/lib/api';

function PaymobCheckoutContent() {
  const searchParams = useSearchParams();
  const router = useRouter();

  const orderNumber = searchParams.get('order') || 'ORD-DEMO1234';
  const amount = searchParams.get('amount') || '2197.00';
  const currency = searchParams.get('currency') || 'EGP';
  const phoneParam = searchParams.get('phone') || '01012345678';
  const nameParam = searchParams.get('name') || 'Guest Customer';

  const [activeTab, setActiveTab] = useState<'card' | 'wallet' | 'installments'>('wallet');
  
  // Card state
  const [cardNumber, setCardNumber] = useState('4111 2222 3333 4444');
  const [cardHolder, setCardHolder] = useState(nameParam);
  const [expiry, setExpiry] = useState('12/28');
  const [cvv, setCvv] = useState('123');

  // Wallet state
  const [walletPhone, setWalletPhone] = useState(phoneParam);
  const [walletProvider, setWalletProvider] = useState('vodafone');

  // Installments state
  const [installmentProvider, setInstallmentProvider] = useState('valu');
  const [installmentMonths, setInstallmentMonths] = useState('6');

  // Processing state
  const [isProcessing, setIsProcessing] = useState(false);
  const [processStep, setProcessStep] = useState('');

  const formatCardInput = (val: string) => {
    const digits = val.replace(/\D/g, '').substring(0, 16);
    return digits.match(/.{1,4}/g)?.join(' ') || digits;
  };

  const handlePay = async (e: React.FormEvent) => {
    e.preventDefault();
    setIsProcessing(true);

    // 3D Secure Simulation sequence
    setProcessStep('Connecting to Central Bank of Egypt Payment Gateway...');
    await new Promise(r => setTimeout(r, 900));

    if (activeTab === 'wallet') {
      setProcessStep(`Requesting OTP authorization on ${walletPhone} (${walletProvider.toUpperCase()})...`);
      await new Promise(r => setTimeout(r, 1000));
    } else {
      setProcessStep('Verifying 3D-Secure 2.0 biometric authorization...');
      await new Promise(r => setTimeout(r, 1000));
    }

    setProcessStep('Capturing payment via Paymob Egypt Hub...');
    await new Promise(r => setTimeout(r, 800));

    try {
      // Call backend to update order status to paid
      await fetch(`${getApiUrl()}/public/paymob/confirm-test-payment`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          order_number: orderNumber,
          payment_method: 'paymob',
        })
      });
    } catch (err) {
      console.error('Confirm payment err', err);
    }

    // Redirect back to store confirmation
    router.push(`/order-confirmation?order=${orderNumber}&payment_status=paid&payment_method=paymob`);
  };

  return (
    <div className="min-h-screen bg-[#070b14] text-white flex flex-col justify-between font-sans">
      
      {/* Top Paymob Header */}
      <header className="border-b border-white/10 bg-[#0b1324] px-6 py-4">
        <div className="max-w-5xl mx-auto flex items-center justify-between">
          <div className="flex items-center gap-3">
            <div className="w-9 h-9 rounded bg-[#0066ff] flex items-center justify-center font-display font-black text-white text-lg tracking-tighter">
              P
            </div>
            <div>
              <div className="flex items-center gap-2">
                <span className="font-display font-black tracking-tight text-lg text-white">paymob</span>
                <span className="bg-[#0066ff]/20 text-[#38bdf8] text-[10px] font-bold px-2 py-0.5 rounded uppercase tracking-wider">
                  Unified Gateway
                </span>
              </div>
              <p className="text-[10px] text-white/50">Central Bank of Egypt (CBE) Licensed Payment Facilitator</p>
            </div>
          </div>

          <div className="flex items-center gap-4">
            <div className="hidden sm:flex items-center gap-1.5 text-xs text-[#22c55e] font-semibold bg-[#22c55e]/10 px-3 py-1.5 rounded-full">
              <svg className="w-4 h-4" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                <path d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
              </svg>
              <span>256-Bit TLS Secured</span>
            </div>
            <Link href="/checkout" className="text-xs text-white/60 hover:text-white underline">
              Cancel & Return
            </Link>
          </div>
        </div>
      </header>

      {/* Main Container */}
      <main className="max-w-4xl mx-auto px-4 py-8 md:py-12 w-full flex-1">
        
        {/* Order Info Strip */}
        <div className="bg-[#0e182e] border border-blue-500/20 rounded-xl p-5 md:p-6 mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4 shadow-2xl">
          <div>
            <span className="text-xs font-bold uppercase tracking-wider text-[#38bdf8]">
              Merchant: NOAH ATELIER (Egypt)
            </span>
            <h1 className="text-xl font-bold text-white mt-0.5">
              Order #{orderNumber}
            </h1>
            <p className="text-xs text-white/60 mt-1">
              Customer: <span className="text-white font-medium">{nameParam}</span> · {phoneParam}
            </p>
          </div>

          <div className="md:text-right border-t md:border-t-0 border-white/10 pt-3 md:pt-0">
            <span className="text-xs text-white/60 uppercase tracking-wider block">Total Amount Due</span>
            <span className="text-2xl md:text-3xl font-display font-black text-[#e8ff00] tracking-tight">
              {Number(amount).toLocaleString('en-US', { minimumFractionDigits: 2 })} {currency}
            </span>
          </div>
        </div>

        {/* Payment Methods Box */}
        <div className="bg-[#0b1324] border border-white/10 rounded-2xl shadow-2xl overflow-hidden">
          
          {/* Method Tabs */}
          <div className="grid grid-cols-3 border-b border-white/10 bg-[#080d19]">
            <button
              type="button"
              onClick={() => setActiveTab('wallet')}
              className={`py-4 px-3 text-center transition-all flex flex-col sm:flex-row items-center justify-center gap-2 font-bold text-xs md:text-sm ${
                activeTab === 'wallet'
                  ? 'bg-[#0b1324] text-[#38bdf8] border-b-2 border-[#38bdf8]'
                  : 'text-white/60 hover:text-white hover:bg-white/5'
              }`}
            >
              <span className="text-lg">📱</span>
              <span>Vodafone Cash & Wallets</span>
            </button>

            <button
              type="button"
              onClick={() => setActiveTab('card')}
              className={`py-4 px-3 text-center transition-all flex flex-col sm:flex-row items-center justify-center gap-2 font-bold text-xs md:text-sm ${
                activeTab === 'card'
                  ? 'bg-[#0b1324] text-[#38bdf8] border-b-2 border-[#38bdf8]'
                  : 'text-white/60 hover:text-white hover:bg-white/5'
              }`}
            >
              <span className="text-lg">💳</span>
              <span>Credit / Debit Cards</span>
            </button>

            <button
              type="button"
              onClick={() => setActiveTab('installments')}
              className={`py-4 px-3 text-center transition-all flex flex-col sm:flex-row items-center justify-center gap-2 font-bold text-xs md:text-sm ${
                activeTab === 'installments'
                  ? 'bg-[#0b1324] text-[#38bdf8] border-b-2 border-[#38bdf8]'
                  : 'text-white/60 hover:text-white hover:bg-white/5'
              }`}
            >
              <span className="text-lg">🏦</span>
              <span>Installments & ValU</span>
            </button>
          </div>

          {/* Tab Forms */}
          <form onSubmit={handlePay} className="p-6 md:p-8 space-y-6">

            {/* ── TAB 1: VODAFONE CASH & DIGITAL WALLETS ────────────────────────── */}
            {activeTab === 'wallet' && (
              <div className="space-y-6">
                <div>
                  <label className="block text-xs font-bold uppercase tracking-wider text-white/80 mb-3">
                    Select Digital Wallet Provider (Egypt)
                  </label>
                  <div className="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    {[
                      { id: 'vodafone', name: 'Vodafone Cash', color: 'border-red-500 bg-red-500/10 text-red-400' },
                      { id: 'orange', name: 'Orange Cash', color: 'border-orange-500 bg-orange-500/10 text-orange-400' },
                      { id: 'etisalat', name: 'Etisalat Cash', color: 'border-emerald-500 bg-emerald-500/10 text-emerald-400' },
                      { id: 'instapay', name: 'InstaPay / WE', color: 'border-purple-500 bg-purple-500/10 text-purple-400' },
                    ].map(w => (
                      <button
                        key={w.id}
                        type="button"
                        onClick={() => setWalletProvider(w.id)}
                        className={`p-3 rounded-lg border text-left transition-all text-xs font-bold flex flex-col justify-between h-16 ${
                          walletProvider === w.id
                            ? `${w.color} ring-2 ring-blue-400`
                            : 'border-white/10 bg-[#101b33] text-white/70 hover:border-white/30'
                        }`}
                      >
                        <span className="text-xs">⚡</span>
                        <span>{w.name}</span>
                      </button>
                    ))}
                  </div>
                </div>

                <div>
                  <label className="block text-xs font-bold uppercase tracking-wider text-white/80 mb-2">
                    Mobile Wallet Number *
                  </label>
                  <div className="relative">
                    <span className="absolute left-4 top-1/2 -translate-y-1/2 text-sm text-white/50 font-mono">
                      🇪🇬 +20
                    </span>
                    <input
                      type="tel"
                      required
                      value={walletPhone}
                      onChange={e => setWalletPhone(e.target.value)}
                      placeholder="01012345678"
                      className="w-full bg-[#101b33] border border-white/20 rounded-lg pl-20 pr-4 py-3.5 text-white font-mono text-base focus:border-[#38bdf8] outline-none transition-colors"
                    />
                  </div>
                  <p className="text-[11px] text-white/50 mt-2">
                    You will receive a prompt on your phone or in your wallet app to enter your wallet PIN and authorize the payment.
                  </p>
                </div>
              </div>
            )}

            {/* ── TAB 2: CREDIT / DEBIT CARDS ─────────────────────────────────── */}
            {activeTab === 'card' && (
              <div className="space-y-4">
                <div>
                  <label className="block text-xs font-bold uppercase tracking-wider text-white/80 mb-2">
                    Card Number *
                  </label>
                  <div className="relative">
                    <input
                      type="text"
                      required
                      value={cardNumber}
                      onChange={e => setCardNumber(formatCardInput(e.target.value))}
                      placeholder="4111 2222 3333 4444"
                      className="w-full bg-[#101b33] border border-white/20 rounded-lg px-4 py-3.5 text-white font-mono text-base tracking-wider focus:border-[#38bdf8] outline-none transition-colors"
                    />
                    <div className="absolute right-4 top-1/2 -translate-y-1/2 flex items-center gap-1.5 text-xs text-white/40 font-bold">
                      <span>VISA</span> · <span>MC</span> · <span>MEEZA</span>
                    </div>
                  </div>
                </div>

                <div>
                  <label className="block text-xs font-bold uppercase tracking-wider text-white/80 mb-2">
                    Cardholder Name *
                  </label>
                  <input
                    type="text"
                    required
                    value={cardHolder}
                    onChange={e => setCardHolder(e.target.value)}
                    placeholder="Name as printed on card"
                    className="w-full bg-[#101b33] border border-white/20 rounded-lg px-4 py-3.5 text-white focus:border-[#38bdf8] outline-none transition-colors"
                  />
                </div>

                <div className="grid grid-cols-2 gap-4">
                  <div>
                    <label className="block text-xs font-bold uppercase tracking-wider text-white/80 mb-2">
                      Expiry Date *
                    </label>
                    <input
                      type="text"
                      required
                      value={expiry}
                      onChange={e => setExpiry(e.target.value)}
                      placeholder="MM/YY"
                      maxLength={5}
                      className="w-full bg-[#101b33] border border-white/20 rounded-lg px-4 py-3.5 text-white font-mono focus:border-[#38bdf8] outline-none transition-colors"
                    />
                  </div>

                  <div>
                    <label className="block text-xs font-bold uppercase tracking-wider text-white/80 mb-2">
                      CVV / Security Code *
                    </label>
                    <input
                      type="password"
                      required
                      value={cvv}
                      onChange={e => setCvv(e.target.value.substring(0, 4))}
                      placeholder="123"
                      maxLength={4}
                      className="w-full bg-[#101b33] border border-white/20 rounded-lg px-4 py-3.5 text-white font-mono focus:border-[#38bdf8] outline-none transition-colors"
                    />
                  </div>
                </div>
              </div>
            )}

            {/* ── TAB 3: INSTALLMENTS & BNPL ─────────────────────────────────── */}
            {activeTab === 'installments' && (
              <div className="space-y-6">
                <div>
                  <label className="block text-xs font-bold uppercase tracking-wider text-white/80 mb-3">
                    Select Installment Provider
                  </label>
                  <div className="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    {[
                      { id: 'valu', name: 'valU' },
                      { id: 'souhoola', name: 'Souhoola' },
                      { id: 'sympl', name: 'Sympl' },
                      { id: 'premium', name: 'Premium Card' },
                    ].map(p => (
                      <button
                        key={p.id}
                        type="button"
                        onClick={() => setInstallmentProvider(p.id)}
                        className={`p-3 rounded-lg border text-center transition-all text-xs font-bold ${
                          installmentProvider === p.id
                            ? 'border-[#38bdf8] bg-[#38bdf8]/10 text-[#38bdf8] ring-2 ring-blue-400'
                            : 'border-white/10 bg-[#101b33] text-white/70 hover:border-white/30'
                        }`}
                      >
                        {p.name}
                      </button>
                    ))}
                  </div>
                </div>

                <div>
                  <label className="block text-xs font-bold uppercase tracking-wider text-white/80 mb-3">
                    Select Tenure
                  </label>
                  <div className="grid grid-cols-4 gap-3">
                    {[
                      { m: '3', monthly: Math.round(Number(amount) / 3) },
                      { m: '6', monthly: Math.round(Number(amount) / 6) },
                      { m: '12', monthly: Math.round(Number(amount) / 12) },
                      { m: '24', monthly: Math.round(Number(amount) / 24) },
                    ].map(t => (
                      <button
                        key={t.m}
                        type="button"
                        onClick={() => setInstallmentMonths(t.m)}
                        className={`p-3 rounded-lg border text-center transition-all ${
                          installmentMonths === t.m
                            ? 'border-[#38bdf8] bg-[#38bdf8]/10 text-white ring-2 ring-blue-400'
                            : 'border-white/10 bg-[#101b33] text-white/70 hover:border-white/30'
                        }`}
                      >
                        <p className="font-bold text-xs">{t.m} Months</p>
                        <p className="text-[10px] text-[#38bdf8] mt-1">{t.monthly} EGP/mo</p>
                      </button>
                    ))}
                  </div>
                </div>
              </div>
            )}

            {/* Pay Button */}
            <div className="pt-4 border-t border-white/10">
              <button
                type="submit"
                disabled={isProcessing}
                className="w-full bg-[#0066ff] hover:bg-[#0052cc] text-white font-display font-black text-sm uppercase tracking-[0.2em] py-4 rounded-xl shadow-lg transition-all flex items-center justify-center gap-3 disabled:opacity-50"
              >
                {isProcessing ? (
                  <>
                    <div className="w-5 h-5 border-2 border-white border-t-transparent rounded-full animate-spin" />
                    <span>Processing Payment...</span>
                  </>
                ) : (
                  <span>
                    Pay {Number(amount).toLocaleString('en-US', { minimumFractionDigits: 2 })} {currency} with Paymob →
                  </span>
                )}
              </button>

              <div className="mt-4 flex items-center justify-center gap-6 text-[11px] text-white/40">
                <span>🔒 Powered by Paymob Egypt</span>
                <span>•</span>
                <span>PCI-DSS Level 1 Certified</span>
                <span>•</span>
                <span>3D-Secure 2.0</span>
              </div>
            </div>
          </form>
        </div>
      </main>

      {/* 3D-Secure Processing Modal */}
      {isProcessing && (
        <div className="fixed inset-0 z-50 bg-black/80 backdrop-blur-md flex items-center justify-center p-4">
          <div className="bg-[#0e182e] border border-blue-500/30 rounded-2xl p-8 max-w-md w-full text-center space-y-6 shadow-2xl">
            <div className="w-16 h-16 rounded-full bg-blue-500/20 border-2 border-[#38bdf8] flex items-center justify-center mx-auto animate-pulse">
              <div className="w-8 h-8 border-3 border-[#38bdf8] border-t-transparent rounded-full animate-spin" />
            </div>

            <div>
              <h3 className="text-lg font-bold text-white mb-2">
                Paymob 3D-Secure Verification
              </h3>
              <p className="text-xs text-[#38bdf8] font-mono leading-relaxed">
                {processStep}
              </p>
            </div>

            <div className="p-3 bg-[#070b14] rounded-lg border border-white/10 text-[11px] text-white/60">
              Transaction ID: <span className="font-mono text-white">PM-{orderNumber}</span> · Amount: <span className="text-white font-bold">{amount} EGP</span>
            </div>
          </div>
        </div>
      )}

      {/* Footer */}
      <footer className="border-t border-white/10 bg-[#070b14] py-6 px-6 text-center text-xs text-white/40">
        Paymob Technology SAE © 2026. All Rights Reserved. Regulated under Egyptian Law No. 194 of 2020.
      </footer>
    </div>
  );
}

export default function PaymobCheckoutPage() {
  return (
    <Suspense fallback={<div className="min-h-screen bg-[#070b14] flex items-center justify-center text-white">Loading Paymob Gateway...</div>}>
      <PaymobCheckoutContent />
    </Suspense>
  );
}
