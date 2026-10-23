import { NextResponse } from 'next/server'
import { currentUser } from '@/lib/auth'
import { all } from '@/lib/db'
import { mediaPublic } from '@/lib/media'
import type { Media } from '@/lib/content'

export const dynamic = 'force-dynamic'

/* Seznam obrázků pro výběr v editoru. */
export async function GET() {
  if (!(await currentUser())) return NextResponse.json({ error: 'auth' }, { status: 401 })
  const rows = await all<Media & { variants: string }>('SELECT * FROM media ORDER BY id DESC')
  return NextResponse.json({ items: rows.map((m) => mediaPublic({ ...m, variants: JSON.parse((m.variants as unknown as string) || '[]') })) })
}
