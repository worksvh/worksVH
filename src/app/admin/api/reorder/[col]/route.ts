import { NextResponse } from 'next/server'
import { guard } from '@/lib/route'
import { batch } from '@/lib/db'
import { schema } from '@/lib/schema'
import { contentChanged } from '@/lib/admin'

export async function POST(req: Request, { params }: { params: Promise<{ col: string }> }) {
  const g = await guard(req, { json: true })
  if (g instanceof Response) return g
  const { col } = await params
  const c = Object.keys(schema.collections).find((k) => schema.collections[k].path === col)
  const ids = ((await req.json().catch(() => ({}))) as { ids?: unknown }).ids
  if (!c || !Array.isArray(ids)) return NextResponse.json({ ok: false }, { status: 400 })
  await batch(ids.map((id, i) => ['UPDATE items SET sort = ? WHERE id = ? AND collection = ?', [i + 1, Number(id), c]]))
  contentChanged()
  return NextResponse.json({ ok: true })
}
