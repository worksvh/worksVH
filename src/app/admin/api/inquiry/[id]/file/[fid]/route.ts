import { NextResponse } from 'next/server'
import { currentUser } from '@/lib/auth'
import { one } from '@/lib/db'
import { storeRead } from '@/lib/storage'
import type { InquiryFile } from '@/lib/inquiries'

export const dynamic = 'force-dynamic'

/* Stažení přílohy poptávky – jen pro přihlášené. */
export async function GET(_req: Request, { params }: { params: Promise<{ id: string; fid: string }> }) {
  if (!(await currentUser())) return new NextResponse('Nepřihlášeno', { status: 401 })
  const p = await params
  const f = await one<InquiryFile>('SELECT * FROM inquiry_files WHERE id = ? AND inquiry_id = ?', [Number(p.fid), Number(p.id)])
  const buf = f ? await storeRead(f.stored_name) : null
  if (!f || !buf) return new NextResponse('Soubor nenalezen', { status: 404 })
  return new NextResponse(new Uint8Array(buf), {
    headers: {
      'Content-Type': 'application/octet-stream',
      'Content-Length': String(buf.length),
      'Content-Disposition': `attachment; filename*=UTF-8''${encodeURIComponent(f.original_name)}`,
      'X-Content-Type-Options': 'nosniff',
      'Cache-Control': 'no-store',
    },
  })
}
