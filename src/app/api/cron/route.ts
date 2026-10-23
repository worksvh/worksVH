import { NextResponse } from 'next/server'
import { list, del } from '@vercel/blob'
import { loadContent } from '@/lib/content'
import { inquiriesAnonymizeOld } from '@/lib/inquiries'
import { exec, now } from '@/lib/db'
import { blobEnabled } from '@/lib/storage'
import { log } from '@/lib/log'

export const dynamic = 'force-dynamic'
export const maxDuration = 60

/*
 * Denní údržba (Vercel Cron, viz vercel.json): anonymizace starých poptávek, úklid nedokončených nahrávek
 * (přílohy z formuláře, který se nakonec neodeslal), starých náhledů a záznamů o pokusech.
 */
export async function GET(req: Request) {
  const secret = process.env.CRON_SECRET
  if (!secret || req.headers.get('authorization') !== `Bearer ${secret}`) return new NextResponse('Unauthorized', { status: 401 })
  const anonymized = await inquiriesAnonymizeOld(await loadContent())
  let removed = 0
  if (blobEnabled()) {
    const cutoff = Date.now() - 86400_000
    for (const prefix of ['poptavky/tmp/', 'media/tmp/']) {
      let cursor: string | undefined
      do {
        const r = await list({ prefix, cursor, limit: 1000 })
        const old = r.blobs.filter((b) => new Date(b.uploadedAt).getTime() < cutoff).map((b) => b.url)
        if (old.length) await del(old)
        removed += old.length
        cursor = r.hasMore ? r.cursor : undefined
      } while (cursor)
    }
  }
  await exec('DELETE FROM previews WHERE created_at < ?', [now(-86400)])
  await exec('DELETE FROM rate_hits WHERE at < ?', [now(-86400)])
  await exec('DELETE FROM login_attempts WHERE attempted_at < ?', [now(-86400)])
  log('info', `Cron: anonymizováno ${anonymized}, smazáno nedokončených nahrávek ${removed}`)
  return NextResponse.json({ anonymized, removed })
}
