import { promises as fs } from 'node:fs'
import path from 'node:path'
import { put, del, head, copy } from '@vercel/blob'

/*
 * Úložiště souborů. Na Vercelu Vercel Blob (BLOB_READ_WRITE_TOKEN), lokálně složky na disku:
 *   media/…      → public/media (veřejné obrázky)
 *   poptavky/…   → storage/poptavky (přílohy poptávek, ven se nedostanou)
 */

export const blobEnabled = () => Boolean(process.env.BLOB_READ_WRITE_TOKEN)

/** Veřejná adresa úložiště Blob odvozená z tokenu (vercel_blob_rw_<store>_…). */
export function blobBase(): string {
  if (process.env.MEDIA_BASE_URL) return process.env.MEDIA_BASE_URL.replace(/\/$/, '')
  const m = /^vercel_blob_rw_([a-z0-9]+)_/i.exec(process.env.BLOB_READ_WRITE_TOKEN || '')
  return m ? `https://${m[1].toLowerCase()}.public.blob.vercel-storage.com` : ''
}

/** URL obrázku z knihovny médií (v DB je jen název souboru). */
export function mediaUrl(file: string): string {
  if (/^https?:\/\//.test(file)) return file
  return (blobEnabled() ? blobBase() : '') + '/media/' + file
}

const localPath = (pathname: string) =>
  pathname.startsWith('media/')
    ? path.join(process.cwd(), 'public', pathname)
    : path.join(process.cwd(), 'storage', pathname)

/** Uloží soubor pod pevným jménem. Vrací URL (Blob) nebo pathname (lokálně). */
export async function storePut(pathname: string, body: Buffer, contentType: string): Promise<string> {
  if (blobEnabled()) {
    const r = await put(pathname, body, { access: 'public', contentType, addRandomSuffix: false, allowOverwrite: true })
    return r.url
  }
  const p = localPath(pathname)
  await fs.mkdir(path.dirname(p), { recursive: true })
  await fs.writeFile(p, body)
  return pathname
}

export async function storeDelete(urlsOrPaths: string[]): Promise<void> {
  const list = urlsOrPaths.filter(Boolean)
  if (!list.length) return
  if (blobEnabled()) {
    await del(list.map((u) => (/^https?:\/\//.test(u) ? u : blobBase() + '/' + u.replace(/^\//, ''))))
    return
  }
  await Promise.all(list.map((p) => fs.unlink(localPath(p)).catch(() => undefined)))
}

/** Načte soubor (pro přílohy e-mailů a stahování v adminu). */
export async function storeRead(urlOrPath: string): Promise<Buffer | null> {
  try {
    if (/^https?:\/\//.test(urlOrPath)) {
      const r = await fetch(urlOrPath, { cache: 'no-store' })
      return r.ok ? Buffer.from(await r.arrayBuffer()) : null
    }
    return await fs.readFile(localPath(urlOrPath))
  } catch {
    return null
  }
}

export async function storeHead(url: string): Promise<{ size: number; contentType: string; pathname: string } | null> {
  try {
    const h = await head(url)
    return { size: h.size, contentType: h.contentType, pathname: h.pathname }
  } catch {
    return null
  }
}

export async function storeMove(url: string, toPathname: string): Promise<string> {
  const r = await copy(url, toPathname, { access: 'public', addRandomSuffix: false, allowOverwrite: true })
  await del(url)
  return r.url
}

/** Patří URL do našeho úložiště Blob? */
export function isOwnBlob(url: string, prefix: string): boolean {
  const base = blobBase()
  return Boolean(base) && url.startsWith(base + '/' + prefix)
}
