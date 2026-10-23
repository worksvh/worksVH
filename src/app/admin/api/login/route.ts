import { cookies } from 'next/headers'
import { guard, field, see } from '@/lib/route'
import { clientIp, loginAttempt, loginBlocked, encodeSession, sessionCookieOptions, SESSION_COOKIE } from '@/lib/auth'
import { safeBack } from '@/lib/admin'

export async function POST(req: Request) {
  const g = await guard(req, { auth: false })
  if (g instanceof Response) return g
  const email = field(g.fd, 'email').trim()
  const back = safeBack(field(g.fd, 'zpet'))
  const ip = await clientIp()
  const again = (code: string) => see(`/admin/prihlaseni?chyba=${code}&email=${encodeURIComponent(email)}&zpet=${encodeURIComponent(back)}`)
  if (await loginBlocked(ip)) return again('blok')
  const u = await loginAttempt(email, field(g.fd, 'password'), ip)
  if (!u) return again((await loginBlocked(ip)) ? 'blok' : '1')
  ;(await cookies()).set(SESSION_COOKIE, encodeSession(u.id), sessionCookieOptions())
  return see(back)
}
