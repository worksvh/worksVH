import { guard, field, see } from '@/lib/route'
import { one, exec } from '@/lib/db'
import { INQUIRY_STATUSES } from '@/lib/inquiries'
import { flash } from '@/lib/admin'

export async function POST(req: Request, { params }: { params: Promise<{ id: string }> }) {
  const g = await guard(req)
  if (g instanceof Response) return g
  const id = Number((await params).id)
  const q = await one<{ status: string }>('SELECT status FROM inquiries WHERE id = ?', [id])
  if (!q) return see('/admin/poptavky')
  const st = field(g.fd, 'status')
  await exec('UPDATE inquiries SET status = ?, note = ? WHERE id = ?',
    [INQUIRY_STATUSES[st] ? st : q.status, [...field(g.fd, 'note').trim()].slice(0, 10000).join(''), id])
  await flash('ok', 'Uloženo.')
  return see(`/admin/poptavky/${id}`)
}
