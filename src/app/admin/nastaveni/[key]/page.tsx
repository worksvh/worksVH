import { notFound } from 'next/navigation'
import { requireUser, adminEntity } from '@/lib/admin'
import { EditPage } from '@/components/admin/EditPage'

export default async function SettingsEdit({ params }: { params: Promise<{ key: string }> }) {
  const { key } = await params
  const user = await requireUser(`/admin/nastaveni/${key}`)
  const ent = await adminEntity('settings', key)
  if (!ent) notFound()
  return <EditPage user={user} ent={ent} />
}
