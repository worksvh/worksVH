import { guard, see } from '@/lib/route'
import { exec } from '@/lib/db'
import { inquiryDeleteFiles } from '@/lib/inquiries'
import { flash } from '@/lib/admin'

export async function POST(req: Request, { params }: { params: Promise<{ id: string }> }) {
  const g = await guard(req)
  if (g instanceof Response) return g
  const id = Number((await params).id)
  await inquiryDeleteFiles(id)
  await exec('DELETE FROM inquiries WHERE id = ?', [id])
  await flash('ok', `Poptávka č. ${id} je smazaná i s přílohami.`)
  return see('/admin/poptavky')
}
