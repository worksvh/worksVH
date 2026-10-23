import JSZip from 'jszip'
import { NextResponse } from 'next/server'
import { guard } from '@/lib/route'
import { all } from '@/lib/db'
import { storeRead } from '@/lib/storage'
import { log } from '@/lib/log'

export const maxDuration = 60

const TABLES = ['pages', 'settings', 'items', 'media', 'revisions', 'users', 'inquiries', 'inquiry_files', 'migrations']

/* ZIP s obsahem databáze (JSON po tabulkách, včetně SQL pro obnovu) a obrázky. */
export async function POST(req: Request) {
  const g = await guard(req)
  if (g instanceof Response) return g
  const zip = new JSZip()
  const stamp = new Date().toISOString().slice(0, 16).replace(/[T:]/g, '-')
  let sql = 'PRAGMA foreign_keys = OFF;\nBEGIN;\n'
  const q = (v: unknown) => (v === null || v === undefined ? 'NULL' : typeof v === 'number' ? String(v) : `'${String(v).replace(/'/g, "''")}'`)
  for (const t of TABLES) {
    const rows = await all(`SELECT * FROM "${t}"`).catch(() => [])
    zip.file(`databaze/${t}.json`, JSON.stringify(rows, null, 1))
    sql += `DELETE FROM "${t}";\n`
    for (const r of rows) sql += `INSERT INTO "${t}" (${Object.keys(r).map((k) => `"${k}"`).join(',')}) VALUES (${Object.values(r).map(q).join(',')});\n`
  }
  zip.file('databaze/obnova.sql', sql + 'COMMIT;\n')
  const media = await all<{ filename: string; variants: string }>('SELECT filename, variants FROM media')
  for (const m of media) {
    const files = [m.filename, ...(JSON.parse(m.variants || '[]') as [number, number, string][]).map((v) => v[2])]
    for (const f of files) {
      const buf = await storeRead(`media/${f}`).catch(() => null)
      if (buf) zip.file(`media/${f}`, buf)
      else log('error', `Záloha: obrázek ${f} se nepodařilo načíst`)
    }
  }
  zip.file('README.txt', `Záloha webu VH Works z ${new Date().toLocaleString('cs-CZ', { timeZone: 'Europe/Prague' })}.\n`
    + 'databaze/obnova.sql obnoví obsah databáze (spusťte nad prázdnou databází po migraci), media/ jsou obrázky.\n'
    + 'Přílohy poptávek záloha záměrně neobsahuje.\n')
  const buf = await zip.generateAsync({ type: 'uint8array', compression: 'DEFLATE' })
  return new NextResponse(buf as unknown as BodyInit, {
    headers: {
      'Content-Type': 'application/zip',
      'Content-Disposition': `attachment; filename="vhworks-zaloha-${stamp}.zip"`,
      'Cache-Control': 'no-store',
    },
  })
}
