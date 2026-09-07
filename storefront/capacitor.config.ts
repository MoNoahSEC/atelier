import type { CapacitorConfig } from '@capacitor/cli';

const config: CapacitorConfig = {
  appId: 'com.atelier.storefront',
  appName: 'ATELIER',
  webDir: 'out',
  server: {
    // Native builds load the production storefront; localhost is never shipped to customers.
    url: process.env.CAPACITOR_SERVER_URL || 'https://atelier404.store',
    cleartext: false,
    allowNavigation: ['atelier404.store', '*.atelier404.store'],
  },
};

export default config;
