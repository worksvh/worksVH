import type { MetadataRoute } from 'next'

export const dynamic = 'force-dynamic'

// vyhledávače a AI asistenti (vyhledávání a citace, dotazy uživatelů) výslovně povolení; tréninkoví boti spadají pod '*'
const SEARCH_BOTS = [
  'Googlebot', 'Bingbot', 'SeznamBot',
  'OAI-SearchBot', 'ChatGPT-User', 'Claude-SearchBot', 'Claude-User', 'PerplexityBot', 'Perplexity-User',
]
const PRIVATE = ['/admin', '/api/']

export default function robots(): MetadataRoute.Robots {
  const base = (process.env.APP_URL || '').replace(/\/$/, '')
  return {
    rules: [
      { userAgent: '*', allow: '/', disallow: PRIVATE },
      { userAgent: SEARCH_BOTS, allow: '/', disallow: PRIVATE },
    ],
    sitemap: `${base}/sitemap.xml`,
  }
}
