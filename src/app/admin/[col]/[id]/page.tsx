import { notFound } from 'next/navigation'
import { requireUser, adminEntity } from '@/lib/admin'
import { schema } from '@/lib/schema'
import { EditPage } from '@/components/admin/EditPage'

export default async function ItemEdit({ params }: { params: Promise<{ col: string; id: string }> }) {
  const { col, id } = await params
  const ck = Object.keys(schema.collections).find((k) => schema.collections[k].path === col)
  if (!ck) notFound()
  const user = await requireUser(`/admin/${col}/${id}`)
  const ent = await adminEntity('item', `${ck}:${id === 'nova' ? 'new' : id}`)
  if (!ent) notFound()
  return <EditPage user={user} ent={ent} />
}
