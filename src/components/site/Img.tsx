import type { Content, ImageValue } from '@/lib/content'
import { mediaUrl } from '@/lib/storage'

/**
 * <img> pro hodnotu pole typu image. Když obrázek chybí, vrátí šedé zástupné místo
 * s textem z alt (popis toho, co má být na fotce).
 */
export function Img({ c, value, sizes = '100vw', className = '', lazy = true }: {
  c: Content
  value: ImageValue
  sizes?: string
  className?: string
  lazy?: boolean
}) {
  const m = c.media(value?.media)
  const alt = String(value?.alt ?? '')
  if (!m) {
    const label = c.ph(alt)
    return (
      <div className={`photo-ph ${className}`} role="img" aria-label={label !== '' ? label : 'Místo pro fotografii'}>
        <span>{label}</span>
      </div>
    )
  }
  const common = {
    alt: c.ph(alt !== '' ? alt : m.alt),
    decoding: 'async' as const,
    className: className || undefined,
    ...(lazy ? { loading: 'lazy' as const } : { fetchPriority: 'high' as const }),
  }
  if (m.variants.length) {
    const largest = m.variants[m.variants.length - 1]
    return (
      // eslint-disable-next-line @next/next/no-img-element
      <img
        src={mediaUrl(largest[2])}
        srcSet={m.variants.map((v) => `${mediaUrl(v[2])} ${v[0]}w`).join(', ')}
        sizes={sizes}
        width={largest[0]}
        height={largest[1]}
        {...common}
      />
    )
  }
  // eslint-disable-next-line @next/next/no-img-element
  return <img src={mediaUrl(m.filename)} width={m.width ?? undefined} height={m.height ?? undefined} {...common} />
}

/** Absolutní URL obrázku (pro og:image). */
export function imageAbsUrl(c: Content, value: ImageValue | undefined): string | null {
  const m = c.media(value?.media)
  if (!m) return null
  const file = m.variants.length ? m.variants[m.variants.length - 1][2] : m.filename
  const u = mediaUrl(file)
  return /^https?:/.test(u) ? u : (process.env.APP_URL || '').replace(/\/$/, '') + u
}
