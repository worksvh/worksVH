/*
 * npm run upload-media – nahraje obrázky z public/media do Vercel Blob (jednorázově při přechodu na Vercel).
 * Potřebuje BLOB_READ_WRITE_TOKEN v .env. Už nahrané soubory přeskočí.
 */
import { readdirSync, readFileSync } from 'node:fs'
import path from 'node:path'
import { put, head } from '@vercel/blob'
import { loadEnv } from './env'

loadEnv()
if (!process.env.BLOB_READ_WRITE_TOKEN) {
  console.error('Chybí BLOB_READ_WRITE_TOKEN v .env.')
  process.exit(1)
}
const types: Record<string, string> = { jpg: 'image/jpeg', jpeg: 'image/jpeg', png: 'image/png', webp: 'image/webp', svg: 'image/svg+xml' }
const dir = path.join(process.cwd(), 'public', 'media')
let up = 0, skip = 0
for (const f of readdirSync(dir)) {
  const ext = f.split('.').pop()!.toLowerCase()
  if (!types[ext]) continue
  const pathname = `media/${f}`
  try {
    await head(pathname)
    skip++
    continue
  } catch { /* není nahraný */ }
  await put(pathname, readFileSync(path.join(dir, f)), { access: 'public', contentType: types[ext], addRandomSuffix: false, allowOverwrite: true })
  up++
}
console.log(`Nahráno ${up}, přeskočeno ${skip}.`)
