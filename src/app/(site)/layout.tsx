import type { ReactNode } from 'react'
import type { Viewport } from 'next'
import { SiteScripts } from '@/components/site/SiteScripts'

// obsah z databáze se načítá při každém požadavku (z cache), nic se nepředgeneruje při buildu
export const dynamic = 'force-dynamic'

export const viewport: Viewport = { width: 'device-width', initialScale: 1 }

const v = process.env.VERCEL_GIT_COMMIT_SHA?.slice(0, 8) || 'dev'

export default function SiteLayout({ children }: { children: ReactNode }) {
  return (
    <html lang="cs">
      <head>
        <link rel="icon" href="/assets/img/favicon.svg" type="image/svg+xml" />
        <link rel="preload" href="/assets/fonts/exo2-latin.woff2" as="font" type="font/woff2" crossOrigin="" />
        <link rel="preload" href="/assets/fonts/exo2-latin-ext.woff2" as="font" type="font/woff2" crossOrigin="" />
        <link rel="preload" href="/assets/fonts/montserrat-latin.woff2" as="font" type="font/woff2" crossOrigin="" />
        <link rel="preload" href="/assets/fonts/montserrat-latin-ext.woff2" as="font" type="font/woff2" crossOrigin="" />
        <link rel="stylesheet" href={`/assets/css/site.css?v=${v}`} />
        <noscript><link rel="stylesheet" href="/assets/css/nojs.css" /></noscript>
      </head>
      <body>
        {children}
        <SiteScripts src={`/assets/js/site.js?v=${v}`} />
      </body>
    </html>
  )
}
