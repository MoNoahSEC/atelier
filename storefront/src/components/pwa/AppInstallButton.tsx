'use client';

import { useEffect, useRef, useState } from 'react';

type PromptEvent = Event & { prompt: () => Promise<void>; userChoice: Promise<{ outcome: string }> };

export default function AppInstallButton({ className, children, onOpen }: { className?: string; children: React.ReactNode; onOpen?: () => void }) {
  const promptRef = useRef<PromptEvent | null>(null);
  const [open, setOpen] = useState(false);

  useEffect(() => {
    const capture = (event: Event) => { event.preventDefault(); promptRef.current = event as PromptEvent; };
    window.addEventListener('beforeinstallprompt', capture);
    return () => window.removeEventListener('beforeinstallprompt', capture);
  }, []);

  async function install() {
    onOpen?.();
    if (promptRef.current) {
      await promptRef.current.prompt();
      promptRef.current = null;
      return;
    }
    setOpen(true);
  }

  return <>
    <button type="button" onClick={install} className={className}>{children}</button>
    {open && <div className="fixed inset-0 z-[100] grid place-items-end sm:place-items-center bg-stone-950/45 p-4 backdrop-blur-sm" role="dialog" aria-modal="true">
      <div className="w-full max-w-sm rounded-[1.5rem] border border-white/70 bg-white/95 p-6 shadow-2xl animate-fade-up">
        <div className="flex items-start justify-between gap-4"><div><p className="font-display text-[10px] font-black uppercase tracking-[.2em] text-[#c2410c]">ATELIER ON YOUR PHONE</p><h2 className="mt-1 font-display text-2xl font-black uppercase text-stone-900">ثبّت المتجر</h2></div><button type="button" onClick={() => setOpen(false)} className="grid h-9 w-9 place-items-center rounded-full bg-stone-100 text-lg">×</button></div>
        <div className="mt-5 space-y-3 rounded-2xl bg-stone-100/80 p-4 text-sm leading-6 text-stone-700"><p><strong>Android:</strong> من قائمة المتصفح اختر <strong>Install app</strong> أو <strong>Add to Home screen</strong>.</p><p><strong>iPhone:</strong> من Safari اضغط مشاركة ثم <strong>Add to Home Screen</strong>.</p></div>
      </div>
    </div>}
  </>;
}
