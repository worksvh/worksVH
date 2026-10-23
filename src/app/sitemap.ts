import type { MetadataRoute } from 'next'
import { loadContent } from '@/lib/content'

export const dynamic = 'force-dynamic'

const ROUTES: [string, string][] = [
  ['/', 'home'], ['/sluzby', 'services'], ['/prace', 'work'], ['/dilna', 'workshop'],
  ['/kontakt', 'contact'], ['/ochrana-osobnich-udaju', 'privacy'],
]

export default async function sitemap(): Promise<MetadataRoute.Sitemap> {
  const base = (process.env.APP_URL || '').replace(/\/$/, '')
  const c = await loadContent()
  return ROUTES.map(([path, key]) => {
    const updated = c.pageUpdated(key)
    return { url: base + path, ...(updated ? { lastModified: updated.slice(0, 10) } : {}) }
  })
}
