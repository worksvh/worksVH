import { NextResponse } from 'next/server'
import { handleUpload, type HandleUploadBody } from '@vercel/blob/client'
import { clientIp } from '@/lib/auth'
import { ipHash, INQ_FILE_TYPES, INQ_MAX_FILE_BYTES } from '@/lib/inquiries'
import { val, exec, now } from '@/lib/db'

/* Povolení pro nahrání přílohy poptávky z prohlížeče přímo do Vercel Blob. */

const MAX_PER_HOUR = 20

export async function POST(request: Request) {
  const body = (await request.json()) as HandleUploadBody
  try {
    const result = await handleUpload({
      body,
      request,
      onBeforeGenerateToken: async (pathname) => {
        const ext = (pathname.split('.').pop() || '').toLowerCase()
        if (!pathname.startsWith('poptavky/tmp/') || pathname.includes('..') || !INQ_FILE_TYPES.includes(ext)) {
          throw new Error('Nepovolený soubor.')
        }
        const key = 'upl:' + ipHash(await clientIp())
        const n = await val<number>('SELECT COUNT(*) FROM rate_hits WHERE key = ? AND at > ?', [key, now(-3600)])
        if (Number(n) >= MAX_PER_HOUR) throw new Error('Příliš mnoho souborů, zkuste to později.')
        await exec('INSERT INTO rate_hits (key, at) VALUES (?, ?)', [key, now()])
        return { maximumSizeInBytes: INQ_MAX_FILE_BYTES, addRandomSuffix: true, validUntil: Date.now() + 15 * 60_000 }
      },
    })
    return NextResponse.json(result)
  } catch (e) {
    return NextResponse.json({ error: (e as Error).message }, { status: 400 })
  }
}
