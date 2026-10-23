import type { MetadataRoute } from 'next'

export const dynamic = 'force-dynamic'

export default function robots(): MetadataRoute.Robots {
  const base = (process.env.APP_URL || '').replace(/\/$/, '')
  return { rules: { userAgent: '*', disallow: '/admin' }, sitemap: `${base}/sitemap.xml` }
}
