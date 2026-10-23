import { guard, see } from '@/lib/route'
import { one } from '@/lib/db'
import { pageSave, settingSave, itemSave, uniqueSlug, flash, contentChanged } from '@/lib/admin'
import { collectionSchema } from '@/lib/schema'
import { formatDate } from '@/lib/text'

export async function POST(req: Request, { params }: { params: Promise<{ id: string }> }) {
  const g = await guard(req)
  if (g instanceof Response) return g
  const rev = await one<{ entity_type: string; entity_key: string; data: string; created_at: string }>('SELECT * FROM revisions WHERE id = ?', [Number((await params).id)])
  if (!rev) return see('/admin')
  const data = JSON.parse(rev.data || '{}')
  const uid = g.user!.id
  let url: string
  if (rev.entity_type === 'page') {
    await pageSave(rev.entity_key, data, uid)
    url = `/admin/stranky/${rev.entity_key}`
  } else if (rev.entity_type === 'settings') {
    await settingSave(rev.entity_key, data, uid)
    url = `/admin/nastaveni/${rev.entity_key}`
  } else {
    const row = await one<{ collection: string }>('SELECT collection FROM items WHERE id = ?', [Number(rev.entity_key)])
    if (!row) {
      await flash('error', 'Položka už neexistuje, verzi nejde obnovit.')
      return see('/admin')
    }
    const slug = data.slug ? await uniqueSlug(row.collection, data.slug, Number(rev.entity_key)) : null
    await itemSave(row.collection, Number(rev.entity_key), data.data ?? {}, slug, data.visible ?? true, uid)
    url = `/admin/${collectionSchema(row.collection)!.path}/${rev.entity_key}`
  }
  contentChanged()
  await flash('ok', `Obnovena verze z ${formatDate(rev.created_at)}.`)
  return see(url)
}
