'use client';

import { useRouter } from 'next/navigation';

const SORT_OPTIONS = [
  { label: 'Newest', value: 'newest' },
  { label: 'Price: Low\u2013High', value: 'price_asc' },
  { label: 'Price: High\u2013Low', value: 'price_desc' },
  { label: 'Best Selling', value: 'best' },
];

export function SortDropdown({ activeSort }: { activeSort: string }) {
  const router = useRouter();

  return (
    <select
      name="sort"
      defaultValue={activeSort}
      onChange={(e) => {
        const url = new URL(window.location.href);
        url.searchParams.set('sort', e.target.value);
        router.push(url.pathname + url.search);
      }}
      className="bg-surface border border-border text-foreground font-display font-bold text-[10px] uppercase tracking-[0.15em] px-3 py-2 outline-none focus:border-accent transition-colors cursor-pointer appearance-none"
    >
      {SORT_OPTIONS.map((o) => (
        <option key={o.value} value={o.value}>
          {o.label}
        </option>
      ))}
    </select>
  );
}
