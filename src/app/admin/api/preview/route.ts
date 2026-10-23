import { randomBytes } from 'node:crypto'
import { cookies } from 'next/headers'
import { guard, field, see } from '@/lib/route'
import { adminEntity, entityParse } from '@/lib/admin'
import { parseForm } from '@/lib/form'
import { exec, now } from '@/lib/db'
import { PREVIEW_COOKIE } from '@/lib/site'

/* Náhled neuloženého obsahu: data se uloží k uživateli a web je s ?nahled=1 použije místo uložených. */
export async function POST(req: Request) {
  const g = await guard(req)
  if (g instanceof Response) return g
  const type = field(g.fd, '_type')
  const ent = await adminEntity(type, field(g.fd, '_key'))
  if (!ent) return see('/admin')
  const { data, slug } = await entityParse(ent, parseForm(g.fd), true)
  const key = type === 'item' ? `${ent.collection}:${ent.id ?? 'new'}` : ent.key
  const token = randomBytes(18).toString('base64url')
  await exec('DELETE FROM previews WHERE user_id = ? OR created_at < ?', [g.user!.id, now(-86400)])
  await exec('INSERT INTO previews (token, user_id, data, created_at) VALUES (?, ?, ?, ?)',
    [token, g.user!.id, JSON.stringify({ type, key, data, slug }), now()])
  ;(await cookies()).set(PREVIEW_COOKIE, token, { httpOnly: true, sameSite: 'lax', path: '/', maxAge: 3600, secure: process.env.NODE_ENV === 'production' })
  let [web, hash = ''] = ent.web.split('#')
  if (type === 'item' && ent.collection === 'services' && slug) hash = slug
  return see(`${web}?nahled=1${hash ? '#' + hash : ''}`)
}
