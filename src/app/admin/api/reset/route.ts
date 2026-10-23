import { guard, field, see } from '@/lib/route'
import { passwordResetUser, passwordResetConsume, passwordProblem, hashPassword } from '@/lib/auth'
import { exec } from '@/lib/db'
import { flash } from '@/lib/admin'

export async function POST(req: Request) {
  const g = await guard(req, { auth: false })
  if (g instanceof Response) return g
  const token = field(g.fd, 'token')
  const u = await passwordResetUser(token)
  if (!u) return see('/admin/obnova-hesla?token=')
  const pw = field(g.fd, 'password')
  const err = passwordProblem(pw) ?? (pw !== field(g.fd, 'password2') ? 'Hesla se neshodují.' : null)
  if (err) return see(`/admin/obnova-hesla?token=${encodeURIComponent(token)}&chyba=${encodeURIComponent(err)}`)
  await exec('UPDATE users SET password_hash = ? WHERE id = ?', [await hashPassword(pw), u.id])
  await passwordResetConsume(token)
  await exec('DELETE FROM password_resets WHERE user_id = ?', [u.id])
  await flash('ok', 'Heslo je změněné. Přihlaste se novým heslem.')
  return see('/admin/prihlaseni')
}
