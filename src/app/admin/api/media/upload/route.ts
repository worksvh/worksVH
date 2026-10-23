import { NextResponse } from 'next/server'
import { currentUser, csrfValid } from '@/lib/auth'
import { mediaUpload, mediaPublic, mediaGet } from '@/lib/media'
import { flash, contentChanged } from '@/lib/admin'
import { isOwnBlob, storeDelete, storeRead } from '@/lib/storage'
import { see } from '@/lib/route'

/*
 * Nahrání obrázků: buď přímo ve formuláři (lokálně, malé soubory), nebo JSON {url, name, alt}
 * s adresou souboru, který prohlížeč už nahrál do Vercel Blob.
 */
export async function POST(req: Request) {
  const wantsJson = (req.headers.get('accept') || '').includes('application/json')
  const isJson = (req.headers.get('content-type') || '').includes('application/json')
  const user = await currentUser()
  let list: { name: string; buf: Buffer | null; tmp?: string }[] = []
  let alt = ''
  let token: string | null = req.headers.get('x-csrf-token')
  if (isJson) {
    const b = (await req.json().catch(() => ({}))) as { url?: string; name?: string; alt?: string }
    alt = String(b.alt ?? '')
    if (b.url && isOwnBlob(b.url, 'media/tmp/')) list.push({ name: String(b.name ?? 'obrazek'), buf: await storeRead(b.url), tmp: b.url })
  } else {
    const fd = await req.formData().catch(() => null)
    token = (fd?.get('_csrf') as string | null) ?? token
    alt = String(fd?.get('alt') ?? '')
    for (const f of fd?.getAll('file') ?? []) {
      if (typeof f !== 'string' && f.size > 0) list.push({ name: f.name, buf: Buffer.from(await f.arrayBuffer()) })
    }
  }
  if (!user || !(await csrfValid(token))) return NextResponse.json({ items: [], errors: ['Přihlášení vypršelo, obnovte stránku.'] }, { status: 401 })

  const ok = []
  const errs: string[] = []
  for (const f of list) {
    const [id, err] = f.buf ? await mediaUpload(f.name, f.buf, alt) : [null, 'Soubor se nepodařilo načíst.']
    if (f.tmp) await storeDelete([f.tmp]).catch(() => undefined)
    if (id) ok.push(mediaPublic((await mediaGet(id))!))
    else errs.push(`${f.name}: ${err}`)
  }
  if (!list.length) errs.push('Nevybrali jste žádný soubor.')
  if (ok.length) contentChanged()
  if (wantsJson) return NextResponse.json({ items: ok, errors: errs }, { status: ok.length ? 200 : 422 })
  if (ok.length) await flash('ok', `Nahráno: ${ok.length}. Doplňte prosím popis (alt) u každého obrázku.`)
  for (const e of errs) await flash('error', e)
  return see('/admin/media')
}
