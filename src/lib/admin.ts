import { cookies, headers } from 'next/headers'
import { redirect } from 'next/navigation'
import { revalidateTag } from 'next/cache'
import { currentUser, type User } from './auth'
import { all, one, val, exec, batch, now, type Params } from './db'
import { schema, pageSchema, collectionSchema, settingsSchema, type Field, type Fields, type CollectionSchema, type Group } from './schema'
import { fillDefaults, fillPageDefaults, fieldDefault, type Data, type Content } from './content'
import { FormNode, node, str, type FormValue } from './form'
import { richtextSanitize } from './richtext'
import { hasPlaceholder, slugify } from './text'
import { EMAIL_RE } from './inquiries'

/* ---------------------------------------------------------------- přístup a hlášky */

export async function requireUser(back?: string): Promise<User> {
  const u = await currentUser()
  if (!u) redirect('/admin/prihlaseni' + (back ? '?zpet=' + encodeURIComponent(back) : ''))
  return u
}

export const FLASH_COOKIE = 'vhw_flash'
export type Flash = { type: 'ok' | 'error'; msg: string }

/** Hláška pro příští stránku (cookie; proxy ji předá stránce a smaže). */
export async function flash(type: Flash['type'], msg: string): Promise<void> {
  const jar = await cookies()
  let list: Flash[] = []
  try { list = JSON.parse(jar.get(FLASH_COOKIE)?.value || '[]') } catch { /* nic */ }
  list.push({ type, msg })
  jar.set(FLASH_COOKIE, JSON.stringify(list).slice(0, 3000), { path: '/admin', httpOnly: true, sameSite: 'lax', maxAge: 60 })
}

export async function takeFlash(): Promise<Flash[]> {
  const raw = (await headers()).get('x-flash')
  if (!raw) return []
  try { return JSON.parse(decodeURIComponent(raw)) as Flash[] } catch { return [] }
}

/** Po každé změně obsahu: web se načte znovu z databáze. */
export function contentChanged(): void {
  revalidateTag('content', { expire: 0 })
}

/** Bezpečný návrat jen na adresu v adminu. */
export function safeBack(url: unknown, fallback = '/admin'): string {
  return typeof url === 'string' && /^\/admin(\/[\w\-/.]*)?(\?[\w=&%\-]*)?$/.test(url) ? url : fallback
}

/* ---------------------------------------------------------------- normalizace a validace */

type Errors = Record<string, string>

export async function normalizeFields(fields: Fields, input: FormValue | undefined, path: string, errors: Errors, old: Data = {}): Promise<Data> {
  const n = node(input)
  const out: Data = {}
  for (const [name, f] of Object.entries(fields)) {
    const p = path === '' ? name : `${path}.${name}`
    out[name] = await normalizeValue(f, n.get(name), p, errors, old?.[name])
  }
  return out
}

const URL_RE = /^(https?:\/\/[^\s]+|\/[^\s]*|#[\w-]*|mailto:[^\s]+|tel:[+\d\s]+)$/i

async function normalizeValue(f: Field, raw: FormValue | undefined, p: string, errors: Errors, old: unknown): Promise<unknown> {
  const req = Boolean(f.required)
  let v: string
  switch (f.type) {
    case 'text':
    case 'tel':
    case 'select':
      v = str(raw).replace(/[\r\n]/g, ' ').trim()
      if (f.type === 'select' && !(v in (f.options ?? {}))) v = Object.keys(f.options ?? { '': '' })[0]
      break
    case 'textarea':
      v = str(raw).replace(/\r\n/g, '\n').trim()
      break
    case 'password':
      v = str(raw)
      return v === '' ? String(old ?? '') : v // prázdné = beze změny
    case 'richtext': {
      const html = richtextSanitize(str(raw))
      if (req && html.replace(/<[^>]*>/g, '').trim() === '') errors[p] = 'Vyplňte prosím toto pole.'
      return html
    }
    case 'url':
      v = str(raw).trim()
      if (v !== '' && !hasPlaceholder(v) && !URL_RE.test(v)) errors[p] = 'Zadejte odkaz začínající https://, nebo cestu na webu začínající lomítkem (např. /kontakt).'
      break
    case 'email':
      v = str(raw).trim()
      if (v !== '' && !hasPlaceholder(v) && !EMAIL_RE.test(v)) errors[p] = 'Zadejte platný e-mail.'
      break
    case 'number': {
      const s = str(raw).replace(',', '.').trim()
      let num: number | null = null
      if (s === '') num = null
      else if (!/^-?\d+(\.\d+)?$/.test(s)) errors[p] = 'Zadejte číslo.'
      else {
        num = Number(s)
        if ((f.min !== undefined && num < f.min) || (f.max !== undefined && num > f.max)) errors[p] = `Číslo musí být mezi ${f.min} a ${f.max}.`
      }
      if (req && num === null && !errors[p]) errors[p] = 'Vyplňte prosím toto pole.'
      return num
    }
    case 'toggle':
      return str(raw) !== '' && str(raw) !== '0'
    case 'tags': {
      const list = raw instanceof FormNode ? raw.list().map(str) : str(raw).split(/\r?\n/)
      const tags = list.map((t) => t.trim()).filter((t) => t !== '')
      if (req && !tags.length) errors[p] = 'Přidejte aspoň jednu položku.'
      return tags
    }
    case 'image': {
      const r = node(raw)
      const idRaw = str(r.get('media'))
      let mid: number | null = /^\d+$/.test(idRaw) ? Number(idRaw) : null
      const alt = str(r.get('alt')).trim()
      if (mid !== null) {
        const m = await one<{ mime: string }>('SELECT mime FROM media WHERE id = ?', [mid])
        if (!m) mid = null
        else if (m.mime === 'image/svg+xml' && !f.svg) errors[p] = 'SVG jde použít jen pro logo. Vyberte JPG, PNG nebo WebP.'
        else if (alt === '') errors[p] = 'Doplňte popis obrázku (alt). Popisuje obrázek nevidomým a vyhledávačům.'
      }
      if (req && mid === null) errors[p] = 'Vyberte obrázek.'
      return { media: mid, alt }
    }
    case 'reference': {
      const s = str(raw)
      let id: number | null = /^\d+$/.test(s) ? Number(s) : null
      if (id !== null && !(await val('SELECT 1 FROM items WHERE id = ? AND collection = ?', [id, f.collection!]))) id = null
      if (req && id === null) errors[p] = 'Vyberte položku.'
      return id
    }
    case 'repeater': {
      const rows = raw instanceof FormNode ? raw.list() : []
      const oldRows = Array.isArray(old) ? old : []
      const out: Data[] = []
      for (let i = 0; i < rows.length; i++) out.push(await normalizeFields(f.fields!, rows[i], `${p}.${i}`, errors, oldRows[i] ?? {}))
      const n = out.length
      if (f.min !== undefined && f.max !== undefined && f.min === f.max && n !== f.min) errors[p] = `Musí tu být přesně ${f.min} položky.`
      else if (f.min !== undefined && n < f.min) errors[p] = `Přidejte aspoň ${f.min} položky.`
      else if (f.max !== undefined && n > f.max) errors[p] = `Položek může být nejvýše ${f.max}.`
      return out
    }
    default:
      v = str(raw)
  }
  if (f.maxLength !== undefined && [...v].length > f.maxLength) errors[p] = `Text je moc dlouhý (max. ${f.maxLength} znaků, teď ${[...v].length}).`
  if (req && v === '' && !errors[p]) errors[p] = 'Vyplňte prosím toto pole.'
  return v
}

/* ---------------------------------------------------------------- editované entity */

export interface Entity {
  type: 'page' | 'settings' | 'item'
  key: string
  title: string
  label: string
  groups: Record<string, Group>
  flat: boolean
  data: Data
  updated_at: string | null
  url: string
  web: string
  nav: string
  rev: [string, string] | null
  settingsTab?: string
  collection?: string
  cs?: CollectionSchema
  id?: number | null
  slug?: string
  visible?: boolean
}

const parseJson = (s: unknown): Data => {
  try { return JSON.parse(String(s ?? '{}')) || {} } catch { return {} }
}

/** Popis editované entity: odkud se berou pole, data a kam se ukládá. Null = neexistuje. */
export async function adminEntity(type: string, key: string): Promise<Entity | null> {
  if (type === 'page') {
    const ps = pageSchema(key)
    if (!ps) return null
    const row = await one<{ data: string; updated_at: string | null }>('SELECT data, updated_at FROM pages WHERE key = ?', [key])
    return {
      type: 'page', key, title: 'Stránka: ' + ps.label, label: ps.label, groups: ps.groups, flat: false,
      data: fillPageDefaults(ps, row ? parseJson(row.data) : {}), updated_at: row?.updated_at ?? null,
      url: `/admin/stranky/${key}`, web: ps.path, nav: `page:${key}`, rev: ['page', key],
    }
  }
  if (type === 'settings') {
    const ss = settingsSchema(key)
    if (!ss) return null
    const row = await one<{ data: string; updated_at: string | null }>('SELECT data, updated_at FROM settings WHERE key = ?', [key])
    const web = key === 'notfound' ? '/neexistuje' : key === 'form_msgs' ? '/kontakt' : '/'
    return {
      type: 'settings', key, title: 'Nastavení: ' + ss.label, label: ss.label,
      groups: { _: { label: ss.label, help: ss.help, fields: ss.fields } }, flat: true,
      data: fillDefaults(ss.fields, row ? parseJson(row.data) : {}), updated_at: row?.updated_at ?? null,
      url: `/admin/nastaveni/${key}`, web, nav: 'settings', rev: ['settings', key], settingsTab: key,
    }
  }
  if (type !== 'item') return null
  const [c, id] = key.split(':')
  const cs = collectionSchema(c)
  if (!cs) return null
  let item: { id: number; slug: string | null; data: string; visible: number; updated_at: string } | null = null
  if (id !== 'new') {
    if (!/^\d+$/.test(id ?? '')) return null
    item = await one('SELECT id, slug, data, visible, updated_at FROM items WHERE id = ? AND collection = ?', [Number(id), c])
    if (!item) return null
  }
  const data = fillDefaults(cs.fields, item ? parseJson(item.data) : {})
  const title = item ? String(data[cs.title] ?? '') : ''
  return {
    type: 'item', key, collection: c, cs, id: item?.id ?? null,
    title: item ? `${cs.label}: ${title !== '' ? title : '#' + item.id}` : 'Nová ' + cs.singular,
    label: cs.label, groups: { _: { label: 'Obsah', fields: cs.fields } }, flat: true,
    data, slug: item?.slug ?? '', visible: item ? Number(item.visible) === 1 : true, updated_at: item?.updated_at ?? null,
    url: item ? `/admin/${cs.path}/${item.id}` : `/admin/${cs.path}/nova`,
    web: cs.web + (c === 'services' && item ? '#' + item.slug : ''),
    nav: `col:${c}`, rev: item ? ['item', String(item.id)] : null,
  }
}

/** Přečte a ověří odeslaný formulář entity. */
export async function entityParse(ent: Entity, form: FormNode, forPreview = false): Promise<{ data: Data; errors: Errors; slug: string | null; visible: boolean }> {
  const input = form.get('data')
  const errors: Errors = {}
  let data: Data
  if (ent.flat) data = await normalizeFields(ent.groups._.fields, input, '', errors, ent.data)
  else {
    data = {}
    const n = node(input)
    for (const [g, group] of Object.entries(ent.groups)) data[g] = await normalizeFields(group.fields, n.get(g), g, errors, ent.data[g] ?? {})
  }
  let slug: string | null = null
  let visible = true
  if (ent.type === 'item') {
    const cs = ent.cs!
    visible = !cs.visibility || (str(form.get('visible')) !== '' && str(form.get('visible')) !== '0')
    if (cs.slug) {
      let raw = slugify(str(form.get('slug')))
      if (raw === '') raw = slugify(String(data[cs.slug] ?? ''))
      slug = forPreview ? raw : await uniqueSlug(ent.collection!, raw, ent.id ?? null)
    }
    if (ent.collection === 'works' && data.on_home && !forPreview) {
      const rows = await all<{ id: number; data: string }>("SELECT id, data FROM items WHERE collection = 'works'")
      const others = rows.filter((r) => r.id !== ent.id && parseJson(r.data).on_home).length
      if (others >= 3) errors.on_home = 'Na úvodní stránce už jsou 3 zakázky. Nejdřív u jiné zakázky vypněte „Na úvodní stránce“.'
    }
  }
  return { data, errors, slug, visible }
}

export async function uniqueSlug(collection: string, slug: string, exceptId: number | null): Promise<string> {
  const base = slug !== '' ? slug : 'polozka'
  let s = base
  let i = 2
  while (await val('SELECT 1 FROM items WHERE collection = ? AND slug = ? AND id <> ?', [collection, s, exceptId ?? 0])) s = `${base}-${i++}`
  return s
}

/* ---------------------------------------------------------------- ukládání a historie */

export const REVISIONS_KEEP = 10

export function revisionStmts(type: string, key: string, data: unknown, userId: number | null): [string, Params][] {
  return [
    ['INSERT INTO revisions (entity_type, entity_key, data, created_at, user_id) VALUES (?, ?, ?, ?, ?)', [type, key, JSON.stringify(data), now(), userId]],
    [`DELETE FROM revisions WHERE entity_type = ? AND entity_key = ? AND id NOT IN (
      SELECT id FROM revisions WHERE entity_type = ? AND entity_key = ? ORDER BY id DESC LIMIT ${REVISIONS_KEEP})`, [type, key, type, key]],
  ]
}

export interface Revision { id: number; created_at: string; name: string | null; email: string | null }

export const revisionsFor = (type: string, key: string) =>
  all<Revision>(`SELECT r.id, r.created_at, u.name, u.email FROM revisions r LEFT JOIN users u ON u.id = r.user_id
    WHERE r.entity_type = ? AND r.entity_key = ? ORDER BY r.id DESC`, [type, key])

export async function pageSave(key: string, data: Data, uid: number | null): Promise<void> {
  await batch([
    [`INSERT INTO pages (key, data, updated_at, updated_by) VALUES (?, ?, ?, ?)
      ON CONFLICT(key) DO UPDATE SET data = excluded.data, updated_at = excluded.updated_at, updated_by = excluded.updated_by`,
      [key, JSON.stringify(data), now(), uid]],
    ...revisionStmts('page', key, data, uid),
  ])
}

export async function settingSave(key: string, data: Data, uid: number | null): Promise<void> {
  await batch([
    [`INSERT INTO settings (key, data, updated_at) VALUES (?, ?, ?)
      ON CONFLICT(key) DO UPDATE SET data = excluded.data, updated_at = excluded.updated_at`, [key, JSON.stringify(data), now()]],
    ...revisionStmts('settings', key, data, uid),
  ])
}

export async function itemSave(collection: string, id: number | null, data: Data, slug: string | null, visible: boolean, uid: number | null): Promise<number> {
  const json = JSON.stringify(data)
  const rev = { data, slug, visible }
  if (id === null) {
    const sort = Number(await val('SELECT COALESCE(MAX(sort), 0) + 1 FROM items WHERE collection = ?', [collection]))
    const r = await exec('INSERT INTO items (collection, slug, data, sort, visible, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?)',
      [collection, slug, json, sort, visible ? 1 : 0, now(), now()])
    await batch(revisionStmts('item', String(r.id), rev, uid))
    return r.id
  }
  const extra: [string, Params][] = []
  if (collection === 'works' && data.featured) {
    // vybraná zakázka může být jen jedna
    extra.push(["UPDATE items SET data = json_set(data, '$.featured', json('false')) WHERE collection = 'works' AND id <> ?", [id]])
  }
  await batch([
    ['UPDATE items SET slug = ?, data = ?, visible = ?, updated_at = ? WHERE id = ? AND collection = ?', [slug, json, visible ? 1 : 0, now(), id, collection]],
    ...extra,
    ...revisionStmts('item', String(id), rev, uid),
  ])
  return id
}

/** Uloží entitu; vrací adresu, kam se vrátit. */
export async function entitySave(ent: Entity, data: Data, slug: string | null, visible: boolean, uid: number): Promise<string> {
  // první úprava: původní (výchozí) obsah uložit do historie, ať jde vrátit
  if (ent.rev && !(await val('SELECT 1 FROM revisions WHERE entity_type = ? AND entity_key = ?', ent.rev))) {
    const orig = ent.type === 'item' ? { data: ent.data, slug: ent.slug ?? null, visible: ent.visible ?? true } : ent.data
    await batch(revisionStmts(ent.rev[0], ent.rev[1], orig, uid))
  }
  if (ent.type === 'page') {
    await pageSave(ent.key, data, uid)
    return ent.url
  }
  if (ent.type === 'settings') {
    await settingSave(ent.key, data, uid)
    return ent.url
  }
  const id = await itemSave(ent.collection!, ent.id ?? null, data, slug, visible, uid)
  if (ent.id == null && ent.collection === 'works' && data.featured) {
    await exec("UPDATE items SET data = json_set(data, '$.featured', json('false')) WHERE collection = 'works' AND id <> ?", [id])
  }
  return `/admin/${ent.cs!.path}/${id}`
}

/* ---------------------------------------------------------------- „K doplnění“ */

export function fieldHasPlaceholder(f: Field, v: unknown): boolean {
  switch (f.type) {
    case 'tags': return Array.isArray(v) && v.some(hasPlaceholder)
    case 'image': return !!v && typeof v === 'object' && !(v as Data).media && hasPlaceholder((v as Data).alt ?? '')
    case 'richtext': return hasPlaceholder(String(v ?? '').replace(/<[^>]*>/g, ''))
    case 'repeater': case 'toggle': case 'number': case 'reference': case 'password': return false
    default: return hasPlaceholder(v)
  }
}

export interface TodoItem { where: string; field: string; value: string; url: string }

/** Projde všechen obsah a vrátí pole s textem v [hranatých závorkách]. */
export function placeholderReport(c: Content): TodoItem[] {
  const out: TodoItem[] = []
  const walk = (fields: Fields, data: Data, idPrefix: string, where: string, url: string, labels: string[]) => {
    for (const [name, f] of Object.entries(fields)) {
      const v = data?.[name]
      const id = `${idPrefix}-${name}`
      const label = [...labels, f.label]
      if (f.type === 'repeater' && Array.isArray(v)) {
        v.forEach((row, i) => walk(f.fields!, row ?? {}, `${id}-${i}`, where, url, [...label, String(i + 1)]))
        continue
      }
      const vals = f.type === 'tags' ? (Array.isArray(v) ? v : [])
        : f.type === 'image' ? (v && !v.media ? [v.alt ?? ''] : [])
        : f.type === 'richtext' ? [String(v ?? '').replace(/<[^>]*>/g, '')]
        : typeof v === 'string' || typeof v === 'number' ? [String(v)] : []
      const hit = vals.find((s: unknown) => hasPlaceholder(s))
      if (hit !== undefined) out.push({ where, field: label.join(' › '), value: String(hit), url: `${url}#f${id}` })
    }
  }
  for (const [key, ps] of Object.entries(schema.pages)) {
    const data = c.page(key)
    for (const [g, group] of Object.entries(ps.groups)) walk(group.fields, data[g], `-${g}`, 'Stránka ' + ps.label, `/admin/stranky/${key}`, [group.label])
  }
  for (const [ck, cs] of Object.entries(schema.collections)) {
    for (const it of c.items(ck, false)) {
      const title = String(it.data[cs.title] ?? '')
      walk(cs.fields, it.data, '', `${cs.label} › ${title !== '' ? title : '#' + it.id}`, `/admin/${cs.path}/${it.id}`, [])
    }
  }
  for (const [key, ss] of Object.entries(schema.settings)) walk(ss.fields, c.setting(key), '', 'Nastavení › ' + ss.label, `/admin/nastaveni/${key}`, [])
  return out
}

/* ---------------------------------------------------------------- použití médií */

function usesMedia(data: Data, fields: Fields, mediaId: number): boolean {
  for (const [name, f] of Object.entries(fields)) {
    const v = data?.[name]
    if (f.type === 'image' && v && Number(v.media) === mediaId) return true
    if (f.type === 'repeater' && Array.isArray(v) && v.some((row) => usesMedia(row, f.fields!, mediaId))) return true
  }
  return false
}

/** Kde je obrázek použitý: seznam [popis, odkaz]. Bere data přímo z DB (bez cache). */
export async function mediaUsage(mediaId: number, rows?: UsageRows): Promise<[string, string][]> {
  const r = rows ?? (await loadUsageRows())
  const uses: [string, string][] = []
  for (const [key, ps] of Object.entries(schema.pages)) {
    const data = r.pages.get(key) ?? {}
    for (const [g, group] of Object.entries(ps.groups)) {
      if (usesMedia(data[g] ?? {}, group.fields, mediaId)) uses.push([`Stránka ${ps.label} › ${group.label}`, `/admin/stranky/${key}`])
    }
  }
  for (const it of r.items) {
    const cs = schema.collections[it.collection]
    if (cs && usesMedia(it.data, cs.fields, mediaId)) uses.push([`${cs.label} › ${it.data[cs.title] ?? '#' + it.id}`, `/admin/${cs.path}/${it.id}`])
  }
  for (const [key, ss] of Object.entries(schema.settings)) {
    if (usesMedia(r.settings.get(key) ?? {}, ss.fields, mediaId)) uses.push([`Nastavení › ${ss.label}`, `/admin/nastaveni/${key}`])
  }
  return uses
}

export interface UsageRows { pages: Map<string, Data>; settings: Map<string, Data>; items: { id: number; collection: string; data: Data }[] }

export async function loadUsageRows(): Promise<UsageRows> {
  const [p, s, i] = await Promise.all([
    all<{ key: string; data: string }>('SELECT key, data FROM pages'),
    all<{ key: string; data: string }>('SELECT key, data FROM settings'),
    all<{ id: number; collection: string; data: string }>('SELECT id, collection, data FROM items'),
  ])
  return {
    pages: new Map(p.map((x) => [x.key, parseJson(x.data)])),
    settings: new Map(s.map((x) => [x.key, parseJson(x.data)])),
    items: i.map((x) => ({ id: x.id, collection: x.collection, data: parseJson(x.data) })),
  }
}

export { fieldDefault }
