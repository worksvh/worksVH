import { guard, see } from '@/lib/route'
import { one, val, batch } from '@/lib/db'
import { schema } from '@/lib/schema'
import { flash, revisionStmts, contentChanged } from '@/lib/admin'

export async function POST(req: Request, { params }: { params: Promise<{ col: string; id: string }> }) {
  const g = await guard(req)
  if (g instanceof Response) return g
  const p = await params
  const c = Object.keys(schema.collections).find((k) => schema.collections[k].path === p.col)
  if (!c) return see('/admin')
  const id = Number(p.id)
  const back = `/admin/${p.col}`
  if (c === 'categories' && (await val("SELECT 1 FROM items WHERE collection = 'works' AND json_extract(data, '$.category') = ?", [id]))) {
    await flash('error', 'Kategorii nejde smazat, protože ji používají zakázky. Nejdřív jim nastavte jinou kategorii.')
    return see(back)
  }
  const row = await one<{ data: string }>('SELECT data FROM items WHERE id = ? AND collection = ?', [id, c])
  if (row) {
    await batch([
      ...revisionStmts('item', String(id), { data: JSON.parse(row.data), deleted: true }, g.user!.id),
      ['DELETE FROM items WHERE id = ? AND collection = ?', [id, c]],
    ])
    contentChanged()
    await flash('ok', 'Smazáno.')
  }
  return see(back)
}
