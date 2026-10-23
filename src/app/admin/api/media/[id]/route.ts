import { guard, field, see } from '@/lib/route'
import { exec } from '@/lib/db'
import { flash, contentChanged } from '@/lib/admin'

/* Uložení popisu (alt) obrázku. */
export async function POST(req: Request, { params }: { params: Promise<{ id: string }> }) {
  const g = await guard(req)
  if (g instanceof Response) return g
  const id = Number((await params).id)
  await exec('UPDATE media SET alt = ? WHERE id = ?', [[...field(g.fd, 'alt').trim()].slice(0, 300).join(''), id])
  contentChanged()
  await flash('ok', 'Popis uložen.')
  return see(`/admin/media#m-${id}`)
}
