import { NextResponse } from 'next/server'
import { handleUpload, type HandleUploadBody } from '@vercel/blob/client'
import { guard } from '@/lib/route'
import { MEDIA_MAX_BYTES, MEDIA_TYPES } from '@/lib/media'

/* Povolení pro nahrání obrázku z adminu přímo do Vercel Blob (do media/tmp, zpracuje se po nahrání). */
export async function POST(req: Request) {
  const g = await guard(req, { json: true })
  if (g instanceof Response) return g
  try {
    const result = await handleUpload({
      body: (await req.json()) as HandleUploadBody,
      request: req,
      onBeforeGenerateToken: async (pathname) => {
        const ext = (pathname.split('.').pop() || '').toLowerCase()
        if (!pathname.startsWith('media/tmp/') || pathname.includes('..') || !MEDIA_TYPES[ext]) throw new Error('Nepovolený soubor.')
        return { maximumSizeInBytes: MEDIA_MAX_BYTES, addRandomSuffix: true, validUntil: Date.now() + 15 * 60_000 }
      },
    })
    return NextResponse.json(result)
  } catch (e) {
    return NextResponse.json({ error: (e as Error).message }, { status: 400 })
  }
}
