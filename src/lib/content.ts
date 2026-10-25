import { unstable_cache } from 'next/cache'
import { all } from './db'
import { schema, seed, pageSchema, collectionSchema, settingsSchema, type Field, type Fields, type PageSchema } from './schema'
import { stripPlaceholders } from './text'

/* ---------------------------------------------------------------- typy */

export type Data = Record<string, any>
export type ImageValue = { media: number | null; alt: string }
export type Variant = [number, number, string]

export interface Media {
  id: number
  filename: string
  original_name: string
  mime: string
  width: number | null
  height: number | null
  alt: string
  variants: Variant[]
  created_at: string
}

export interface Item {
  id: number
  collection: string
  slug: string
  sort: number
  visible: boolean
  data: Data
  updated_at: string
}

export interface Preview { type: 'page' | 'settings' | 'item'; key: string; data: Data; slug?: string | null }

/* ---------------------------------------------------------------- výchozí hodnoty */

export function fieldDefault(f: Field): unknown {
  if ('default' in f) return structuredClone(f.default)
  switch (f.type) {
    case 'tags': case 'repeater': return []
    case 'toggle': return false
    case 'image': return { media: null, alt: '' }
    case 'reference': case 'number': return null
    default: return ''
  }
}

/** Doplní chybějící klíče výchozími hodnotami podle polí. */
export function fillDefaults(fields: Fields, data: unknown): Data {
  const d = data && typeof data === 'object' && !Array.isArray(data) ? (data as Data) : {}
  const out: Data = {}
  for (const [name, f] of Object.entries(fields)) {
    let v = name in d ? d[name] : fieldDefault(f)
    if (f.type === 'repeater') {
      const rows = Array.isArray(v) ? v : v && typeof v === 'object' ? Object.values(v) : []
      v = rows.map((row) => fillDefaults(f.fields!, row))
    } else if (f.type === 'image') {
      v = v && typeof v === 'object'
        ? { media: v.media !== undefined && v.media !== null && v.media !== '' ? Number(v.media) : null, alt: String(v.alt ?? '') }
        : fieldDefault(f)
    } else if (f.type === 'tags') {
      v = Array.isArray(v) ? v : v && typeof v === 'object' ? Object.values(v) : []
    }
    out[name] = v
  }
  return out
}

export function fillPageDefaults(ps: Pick<PageSchema, 'groups'>, data: unknown): Data {
  const d = data && typeof data === 'object' ? (data as Data) : {}
  const out: Data = {}
  for (const [g, group] of Object.entries(ps.groups)) out[g] = fillDefaults(group.fields, d[g] ?? {})
  return out
}

/* ---------------------------------------------------------------- snímek obsahu (cache) */

interface Snapshot {
  pages: Record<string, string>
  settings: Record<string, string>
  items: { id: number; collection: string; slug: string | null; data: string; sort: number; visible: number; updated_at: string }[]
  media: (Omit<Media, 'variants'> & { variants: string })[]
  pageUpdated: Record<string, string | null>
}

async function loadSnapshot(): Promise<Snapshot> {
  const [pages, settings, items, media] = await Promise.all([
    all<{ key: string; data: string; updated_at: string | null }>('SELECT key, data, updated_at FROM pages'),
    all<{ key: string; data: string }>('SELECT key, data FROM settings'),
    all<Snapshot['items'][number]>('SELECT id, collection, slug, data, sort, visible, updated_at FROM items ORDER BY sort, id'),
    all<Snapshot['media'][number]>('SELECT * FROM media'),
  ])
  return {
    pages: Object.fromEntries(pages.map((r) => [r.key, r.data])),
    pageUpdated: Object.fromEntries(pages.map((r) => [r.key, r.updated_at])),
    settings: Object.fromEntries(settings.map((r) => [r.key, r.data])),
    items,
    media,
  }
}

/** Celý obsah webu jedním načtením; po uložení v adminu se zneplatní značkou „content“. */
export const getSnapshot = unstable_cache(loadSnapshot, ['content-v2'], { tags: ['content'], revalidate: 3600 })

const parse = (s: string | undefined | null): unknown => {
  if (!s) return null
  try { return JSON.parse(s) } catch { return null }
}

/* ---------------------------------------------------------------- obsah pro jeden požadavek */

export class Content {
  private pageCache = new Map<string, Data>()
  private settingCache = new Map<string, Data>()
  private itemCache = new Map<string, Item[]>()
  private mediaMap: Map<number, Media>
  readonly hide: boolean

  constructor(private snap: Snapshot, private preview: Preview | null = null) {
    this.mediaMap = new Map(snap.media.map((m) => [Number(m.id), { ...m, id: Number(m.id), variants: (parse(m.variants) as Variant[]) || [] }]))
    this.hide = Boolean(this.setting('display').hide_placeholders)
  }

  get isPreview() { return this.preview !== null }

  page(key: string): Data {
    let p = this.pageCache.get(key)
    if (!p) {
      const ps = pageSchema(key)
      if (!ps) throw new Error(`Neznámá stránka ${key}`)
      const pv = this.preview?.type === 'page' && this.preview.key === key ? this.preview.data : null
      const raw = pv ?? (key in this.snap.pages ? parse(this.snap.pages[key]) : seed.pages[key] ?? {})
      p = fillPageDefaults(ps, raw)
      this.pageCache.set(key, p)
    }
    return p
  }

  pageUpdated(key: string) { return this.snap.pageUpdated[key] ?? null }

  setting(key: string): Data {
    let s = this.settingCache.get(key)
    if (!s) {
      const ss = settingsSchema(key)
      if (!ss) throw new Error(`Neznámé nastavení ${key}`)
      const pv = this.preview?.type === 'settings' && this.preview.key === key ? this.preview.data : null
      const raw = pv ?? (key in this.snap.settings ? parse(this.snap.settings[key]) : seed.settings[key] ?? {})
      s = fillDefaults(ss.fields, raw)
      this.settingCache.set(key, s)
    }
    return s
  }

  /** Položky sbírky seřazené podle pořadí. */
  items(collection: string, onlyVisible = true): Item[] {
    const ck = collection + (onlyVisible ? ':v' : ':a')
    let list = this.itemCache.get(ck)
    if (list) return list
    const cs = collectionSchema(collection)!
    list = this.snap.items
      .filter((r) => r.collection === collection && (!onlyVisible || Number(r.visible) === 1))
      .map((r) => ({
        id: Number(r.id), collection, slug: r.slug ?? '', sort: Number(r.sort), visible: Number(r.visible) === 1,
        data: fillDefaults(cs.fields, parse(r.data)), updated_at: r.updated_at,
      }))
    // náhled jedné položky s neuloženými změnami
    const p = this.preview
    if (p?.type === 'item') {
      const [pc, pid] = p.key.split(':')
      if (pc === collection) {
        let found = false
        list = list.map((it) => {
          if (String(it.id) !== pid) return it
          found = true
          return { ...it, data: fillDefaults(cs.fields, p.data), slug: p.slug ?? it.slug }
        })
        if (!found && pid === 'new') {
          list.push({ id: 0, collection, slug: p.slug || 'nahled', sort: Number.MAX_SAFE_INTEGER, visible: true, data: fillDefaults(cs.fields, p.data), updated_at: '' })
        }
      }
    }
    this.itemCache.set(ck, list)
    return list
  }

  itemsById(collection: string): Map<number, Item> {
    return new Map(this.items(collection, false).map((it) => [it.id, it]))
  }

  media(id: number | null | undefined): Media | null {
    return id ? this.mediaMap.get(Number(id)) ?? null : null
  }

  /** Hodnota pro veřejný web: když je zapnuté skrývání, zmizí texty v hranatých závorkách. */
  ph = (v: unknown): string => {
    const s = v == null ? '' : String(v)
    return s !== '' && this.hide ? stripPlaceholders(s) : s
  }

  /** CTA texty stránky s doplněním výchozích z Nastavení. */
  ctaFor(pageCta: Data): Data {
    const out = { ...this.setting('cta') }
    for (const k of ['label', 'title', 'button']) {
      if (String(pageCta?.[k] ?? '').trim() !== '') out[k] = pageCta[k]
    }
    return out
  }
}

export async function loadContent(preview: Preview | null = null): Promise<Content> {
  return new Content(await getSnapshot(), preview)
}

export { schema }
