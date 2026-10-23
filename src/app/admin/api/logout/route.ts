import { cookies } from 'next/headers'
import { guard, see } from '@/lib/route'
import { SESSION_COOKIE } from '@/lib/auth'
import { flash } from '@/lib/admin'

export async function POST(req: Request) {
  const g = await guard(req, { auth: false })
  if (g instanceof Response) return g
  ;(await cookies()).delete(SESSION_COOKIE)
  await flash('ok', 'Byli jste odhlášeni.')
  return see('/admin/prihlaseni')
}
