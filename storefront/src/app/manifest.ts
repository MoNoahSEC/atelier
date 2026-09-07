import type { MetadataRoute } from 'next';

export default function manifest(): MetadataRoute.Manifest {
  return { name: 'ATELIER — Premium Accessories', short_name: 'ATELIER', start_url: '/', display: 'standalone', background_color: '#faf8f5', theme_color: '#171717', icons: [{ src: '/favicon.ico', sizes: 'any', type: 'image/x-icon' }] };
}
