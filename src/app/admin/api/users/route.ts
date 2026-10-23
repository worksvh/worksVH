import { guard, field, see } from '@/lib/route'
import { one, val, exec } from '@/lib/db'
import { passwordProblem, verifyPassword, hashPassword, userCreate } from '@/lib/auth'
import { flash } from '@/lib/admin'
import { EMAIL_RE } from '@/lib/inquiries'

const back = '/admin/nastaveni/uzivatele'

export async function POST(req: Request) {
  const g = await guard(req)
  if (g instanceof Response) return g
  const me = g.user!
  const fd = g.fd
  const action = field(fd, 'action')
  const fail = (k: string, msg: string) => see(`${back}?${k}=${encodeURIComponent(msg)}`)
  if (action === 'add') {
    const email = field(fd, 'email').trim()
    const pw = field(fd, 'password')
    if (!EMAIL_RE.test(email)) return fail('add', 'Zadejte platný e-mail.')
    if (await val('SELECT 1 FROM users WHERE email = ?', [email])) return fail('add', 'Uživatel s tímto e-mailem už existuje.')
    const p = passwordProblem(pw)
    if (p) return fail('add', p)
    await userCreate(email, pw, field(fd, 'name'))
    await flash('ok', `Správce ${email} přidán. Heslo mu předejte bezpečnou cestou.`)
  } else if (action === 'password') {
    const u = await one<{ password_hash: string }>('SELECT password_hash FROM users WHERE id = ?', [me.id])
    const pw = field(fd, 'new')
    if (!u || !(await verifyPassword(field(fd, 'current'), u.password_hash))) return fail('password', 'Současné heslo nesedí.')
    const p = passwordProblem(pw)
    if (p) return fail('password', p)
    if (pw !== field(fd, 'new2')) return fail('password', 'Nová hesla se neshodují.')
    await exec('UPDATE users SET password_hash = ? WHERE id = ?', [await hashPassword(pw), me.id])
    await flash('ok', 'Heslo změněno.')
  } else if (action === 'delete') {
    const uid = Number(field(fd, 'id'))
    if (uid === me.id) await flash('error', 'Sami sebe smazat nemůžete.')
    else {
      await exec('DELETE FROM users WHERE id = ?', [uid])
      await flash('ok', 'Uživatel smazán.')
    }
  }
  return see(back)
}
