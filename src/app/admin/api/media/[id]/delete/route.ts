import { guard, see } from '@/lib/route'
import { flash, mediaUsage, contentChanged } from '@/lib/admin'
import { mediaDelete } from '@/lib/media'

export async function POST(req: Request, { params }: { params: Promise<{ id: string }> }) {
  const g = await guard(req)
  if (g instanceof Response) return g
  const id = Number((await params).id)
  if ((await mediaUsage(id)).length) {
    await flash('error', 'Obrázek je použitý na webu, nejde smazat. Nejdřív ho odeberte z míst, kde je použitý.')
  } else {
    await mediaDelete(id)
    contentChanged()
    await flash('ok', 'Obrázek smazán.')
  }
  return see('/admin/media')
}
