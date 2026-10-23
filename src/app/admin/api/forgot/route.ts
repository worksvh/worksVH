import { guard, field, see } from '@/lib/route'
import { clientIp, loginBlocked, passwordResetCreate } from '@/lib/auth'
import { one, exec, now } from '@/lib/db'
import { sendMail } from '@/lib/mail'
import { loadContent } from '@/lib/content'
import { log } from '@/lib/log'

export async function POST(req: Request) {
  const g = await guard(req, { auth: false })
  if (g instanceof Response) return g
  const email = field(g.fd, 'email').trim()
  const ip = await clientIp()
  if (!(await loginBlocked(ip))) {
    const u = await one<{ id: number; email: string }>('SELECT id, email FROM users WHERE email = ?', [email])
    if (u) {
      const token = await passwordResetCreate(u.id)
      const link = `${(process.env.APP_URL || '').replace(/\/$/, '')}/admin/obnova-hesla?token=${token}`
      const c = await loadContent()
      await sendMail({
        to: u.email,
        subject: 'Obnova hesla – administrace webu',
        text: `Dobrý den,\n\npro nastavení nového hesla otevřete tento odkaz (platí 1 hodinu a jde použít jen jednou):\n${link}\n\nPokud jste o obnovu nežádali, e-mail ignorujte.\n`,
        from: String(c.setting('mail').smtp_from ?? ''),
        fromName: String(c.setting('company').name ?? ''),
      })
      log('info', `Obnova hesla vyžádána pro uživatele ${u.id}`)
    } else {
      // stejné omezení jako u přihlášení proti zkoušení e-mailů
      await exec('INSERT INTO login_attempts (ip, attempted_at) VALUES (?, ?)', [ip, now()])
    }
  }
  return see('/admin/zapomenute-heslo?odeslano=1')
}
