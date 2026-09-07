import type { NextConfig } from "next";

const apiUrl = process.env.NEXT_PUBLIC_API_URL || 'http://127.0.0.1:8000';
let apiHostname = '127.0.0.1';
let apiProtocol = 'http';
try {
  const parsed = new URL(apiUrl);
  apiHostname = parsed.hostname;
  apiProtocol = parsed.protocol.replace(':', '');
} catch (e) {
  console.warn('Invalid NEXT_PUBLIC_API_URL, defaulting images to localhost');
}

const nextConfig: NextConfig = {
  allowedDevOrigins: [
    'localhost:3000',
    'localhost',
    '127.0.0.1:3000',
    '127.0.0.1',
    '192.168.*',
    '192.168.*:3000',
    '10.*',
    '10.*:3000',
    '172.*',
    '*.local',
    '*.local:3000',
  ],
  async rewrites() {
    return [
      {
        source: '/api/v1/:path*',
        destination: 'http://127.0.0.1:8000/api/v1/:path*',
      },
      {
        source: '/storage/:path*',
        destination: 'http://127.0.0.1:8000/storage/:path*',
      },
    ];
  },
  ...(process.env.CAPACITOR_BUILD === 'true' && { output: 'export' }),
  images: {
    dangerouslyAllowSVG: true,
    remotePatterns: [
      { protocol: apiProtocol as 'http' | 'https', hostname: apiHostname },
      { protocol: 'https', hostname: 'images.unsplash.com' },
      { protocol: 'https', hostname: '**.unsplash.com' },
      { protocol: 'https', hostname: 'storage.googleapis.com' },
      { protocol: 'https', hostname: 'modelviewer.dev' },
      { protocol: 'https', hostname: 'cdn.shopify.com' },
      { protocol: 'https', hostname: '**' },
      { protocol: 'http', hostname: '**' },
    ],
    ...(process.env.NODE_ENV === 'development' ? { dangerouslyAllowLocalIP: true } : {}),
  },
  async headers() {
    return [
      {
        source: '/(.*)',
        headers: [
          { key: 'X-Content-Type-Options', value: 'nosniff' },
          { key: 'X-Frame-Options', value: 'DENY' },
          { key: 'Referrer-Policy', value: 'strict-origin-when-cross-origin' },
          { key: 'Permissions-Policy', value: 'camera=(), microphone=(), geolocation=()' },
        ],
      },
    ];
  },
};

export default nextConfig;
