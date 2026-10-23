/* Drobné pomocníky pro texty. */

export const PLACEHOLDER_RE = /\[[^\[\]]+\]/u
const PLACEHOLDER_RE_G = /\[[^\[\]]+\]/gu

/** Obsahuje hodnota text „k doplnění“ v hranatých závorkách? */
export function hasPlaceholder(v: unknown): boolean {
  return typeof v === 'string' && PLACEHOLDER_RE.test(v)
}

/** Odstraní texty v hranatých závorkách (a zbylé oddělovače na krajích). */
export function stripPlaceholders(v: unknown): string {
  let s = v == null ? '' : String(v)
  if (s === '') return s
  s = s.replace(PLACEHOLDER_RE_G, '').trim()
  return s.replace(/^[\s·–,-]+|[\s·–,-]+$/gu, '')
}

export function slugify(s: string): string {
  return s
    .toLowerCase()
    .trim()
    .normalize('NFD')
    .replace(/[̀-ͯ]/g, '')
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '')
}

export const telHref = (phone: string) => 'tel:' + String(phone).replace(/[^0-9+]/g, '')
export const phoneShort = (phone: string) => String(phone).replace(/^\+420\s*/, '').trim()

export function formatBytes(b: number): string {
  if (b >= 1048576) return (b / 1048576).toFixed(1).replace('.', ',') + ' MB'
  return Math.max(1, Math.round(b / 1024)).toLocaleString('cs-CZ') + ' kB'
}

/** „2026-10-23 12:05:00“ → „23. 10. 2026 12:05“ */
export function formatDate(d: string | null | undefined, time = true): string {
  if (!d) return ''
  const m = /^(\d{4})-(\d{2})-(\d{2})(?:[ T](\d{2}):(\d{2}))?/.exec(d)
  if (!m) return d
  const date = `${+m[3]}. ${+m[2]}. ${m[1]}`
  return time && m[4] ? `${date} ${m[4]}:${m[5]}` : date
}

export function escapeHtml(s: unknown): string {
  return String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' })[c]!)
}
