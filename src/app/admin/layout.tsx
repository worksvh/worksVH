import type { ReactNode } from 'react'
import type { Metadata, Viewport } from 'next'
import { csrfToken } from '@/lib/auth'
import { blobEnabled } from '@/lib/storage'
import { SiteScripts } from '@/components/site/SiteScripts'
import { BlobBridge } from '@/components/admin/BlobBridge'

export const viewport: Viewport = { width: 'device-width', initialScale: 1 }
export const metadata: Metadata = { robots: { index: false, follow: false } }
export const dynamic = 'force-dynamic'

const v = process.env.VERCEL_GIT_COMMIT_SHA?.slice(0, 8) || 'dev'

export default async function AdminLayout({ children }: { children: ReactNode }) {
  return (
    <html lang="cs">
      <head>
        <link rel="icon" href="/assets/img/favicon.svg" type="image/svg+xml" />
        <link rel="stylesheet" href={`/assets/css/admin.css?v=${v}`} />
      </head>
      <body className="admin" data-csrf={await csrfToken()}>
        {children}
        {blobEnabled() && <BlobBridge />}
        <SiteScripts src={`/assets/js/admin.js?v=${v}`} />
      </body>
    </html>
  )
}
