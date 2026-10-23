import type { NextConfig } from 'next'

const security = [
  { key: 'X-Content-Type-Options', value: 'nosniff' },
  { key: 'Referrer-Policy', value: 'strict-origin-when-cross-origin' },
  { key: 'Permissions-Policy', value: 'camera=(), microphone=(), geolocation=(), payment=(), usb=()' },
  { key: 'X-Frame-Options', value: 'DENY' },
]

const config: NextConfig = {
  poweredByHeader: false,
  serverExternalPackages: ['sharp', '@libsql/client'],
  experimental: {
    // bez Blob tokenu (lokálně) jdou přílohy přímo ve formuláři
    serverActions: { bodySizeLimit: '55mb' },
  },
  async headers() {
    return [
      { source: '/:path*', headers: security },
      { source: '/admin/:path*', headers: [{ key: 'X-Robots-Tag', value: 'noindex, nofollow' }, { key: 'Cache-Control', value: 'no-store' }] },
    ]
  },
}

export default config
