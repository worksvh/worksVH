import { randomBytes } from 'node:crypto'
import sharp from 'sharp'
import { exec, one, now } from './db'
import { storePut, storeDelete, mediaUrl } from './storage'
import type { Media, Variant } from './content'

export const MEDIA_MAX_BYTES = 10 * 1024 * 1024
export const MEDIA_TYPES: Record<string, string> = { jpg: 'image/jpeg', jpeg: 'image/jpeg', png: 'image/png', webp: 'image/webp', svg: 'image/svg+xml' }
const VARIANT_WIDTHS = [480, 960, 1600]

/** Bezpečné SVG: bez skriptů, událostí, vnějších odkazů a entit. */
export function svgSanitize(svg: string): string | null {
  if (/<!DOCTYPE|<!ENTITY/i.test(svg) || !/<svg[\s>]/i.test(svg)) return null
  let s = svg.replace(/<\?xml[^>]*>/gi, '')
  s = s.replace(/<(script|style|foreignObject|iframe|object|embed|image|a)\b[\s\S]*?<\/\1\s*>/gi, '')
  s = s.replace(/<(script|foreignObject|iframe|object|embed|image)\b[^>]*\/?>/gi, '')
  s = s.replace(/\s+on[a-z]+\s*=\s*("[^"]*"|'[^']*'|[^\s>]+)/gi, '')
  s = s.replace(/\s+(xlink:)?href\s*=\s*("(?!#)[^"]*"|'(?!#)[^']*')/gi, '')
  s = s.replace(/javascript:/gi, '')
  s = s.replace(/url\(\s*['"]?\s*(?!#)[^)]*\)/gi, 'none')
  return s.trim()
}

const magic = (b: Buffer, sig: number[], off = 0) => sig.every((x, i) => b[off + i] === x)
function sniff(b: Buffer): string | null {
  if (magic(b, [0xff, 0xd8, 0xff])) return 'image/jpeg'
  if (magic(b, [0x89, 0x50, 0x4e, 0x47])) return 'image/png'
  if (magic(b, [0x52, 0x49, 0x46, 0x46]) && magic(b, [0x57, 0x45, 0x42, 0x50], 8)) return 'image/webp'
  return null
}

/** Uloží obrázek do knihovny médií (originál + WebP varianty). Vrací [id, chyba]. */
export async function mediaUpload(name: string, buf: Buffer, alt = ''): Promise<[number | null, string | null]> {
  if (buf.length > MEDIA_MAX_BYTES) return [null, 'Obrázek je větší než 10 MB.']
  const original = name.split(/[\\/]/).pop() || 'obrazek'
  const ext = (original.split('.').pop() || '').toLowerCase()
  const expected = MEDIA_TYPES[ext]
  if (!expected) return [null, 'Povolené jsou jen obrázky JPG, PNG, WebP a SVG.']
  const base = `${new Date().getFullYear()}-${randomBytes(8).toString('hex')}`
  const filename = `${base}.${ext}`
  let w: number | null = null
  let h: number | null = null
  const variants: Variant[] = []

  if (expected === 'image/svg+xml') {
    const clean = svgSanitize(buf.toString('utf8'))
    if (clean === null) return [null, 'SVG se nepodařilo bezpečně zpracovat.']
    const vb = /viewBox\s*=\s*"[\d.\-]+[\s,]+[\d.\-]+[\s,]+([\d.]+)[\s,]+([\d.]+)"/i.exec(clean)
    if (vb) { w = Math.round(Number(vb[1])); h = Math.round(Number(vb[2])) }
    await storePut(`media/${filename}`, Buffer.from(clean), expected)
  } else {
    const got = sniff(buf)
    if (got !== expected && !(got === 'image/jpeg' && expected === 'image/jpeg')) return [null, `Obsah souboru neodpovídá příponě .${ext}.`]
    const img = sharp(buf, { failOn: 'none' }).rotate() // otočení podle EXIF
    const meta = await img.metadata()
    const rotated = (meta.orientation ?? 1) >= 5
    w = (rotated ? meta.height : meta.width) ?? null
    h = (rotated ? meta.width : meta.height) ?? null
    await storePut(`media/${filename}`, buf, expected)
    if (w && h) {
      const widths = VARIANT_WIDTHS.filter((vw) => vw < w!)
      widths.push(Math.min(w, 1600)) // největší varianta = originál (max. 1600 px)
      for (const vw of [...new Set(widths)].sort((a, b) => a - b)) {
        const out = await sharp(buf, { failOn: 'none' }).rotate().resize({ width: vw }).webp({ quality: 82 }).toBuffer({ resolveWithObject: true })
        const file = `${base}-${vw}.webp`
        await storePut(`media/${file}`, out.data, 'image/webp')
        variants.push([out.info.width, out.info.height, file])
      }
    }
  }
  const r = await exec('INSERT INTO media (filename, original_name, mime, width, height, alt, variants, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
    [filename, original, expected, w, h, alt.trim(), JSON.stringify(variants), now()])
  return [r.id, null]
}

export async function mediaGet(id: number): Promise<Media | null> {
  const row = await one<Media & { variants: string }>('SELECT * FROM media WHERE id = ?', [id])
  if (!row) return null
  return { ...row, variants: JSON.parse((row.variants as unknown as string) || '[]') }
}

export async function mediaDelete(id: number): Promise<void> {
  const m = await mediaGet(id)
  if (!m) return
  await storeDelete([m.filename, ...m.variants.map((v) => v[2])].map((f) => `media/${f}`))
  await exec('DELETE FROM media WHERE id = ?', [id])
}

/** Údaje pro výběr obrázku v adminu. */
export function mediaPublic(m: Media) {
  return {
    id: m.id,
    url: mediaUrl(m.filename),
    thumb: mediaUrl(m.variants.length ? m.variants[0][2] : m.filename),
    alt: m.alt,
    name: m.original_name,
    svg: m.mime === 'image/svg+xml',
    size: m.width ? `${m.width} × ${m.height} px` : '',
  }
}
