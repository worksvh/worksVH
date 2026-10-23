import { requireUser } from '@/lib/admin'
import { all } from '@/lib/db'
import { formatDate } from '@/lib/text'
import { AdminShell, Csrf, SettingsTabs } from '@/components/admin/AdminShell'

type SP = Promise<Record<string, string | undefined>>

export default async function Users({ searchParams }: { searchParams: SP }) {
  const me = await requireUser('/admin/nastaveni/uzivatele')
  const sp = await searchParams
  const users = await all<{ id: number; email: string; name: string; last_login_at: string | null }>('SELECT id, email, name, created_at, last_login_at FROM users ORDER BY id')
  return (
    <AdminShell user={me} title="Nastavení: Uživatelé" nav="settings">
      <div className="a-head"><h1>Nastavení</h1></div>
      <SettingsTabs tab="uzivatele" />
      <section className="box">
        <h2>Správci webu</h2>
        <table className="tbl">
          <thead><tr><th>E-mail</th><th>Jméno</th><th>Poslední přihlášení</th><th><span className="sr">Akce</span></th></tr></thead>
          <tbody>
            {users.map((u) => (
              <tr key={u.id}>
                <td>{u.email}{u.id === me.id && <> <span className="pill pill-soft">vy</span></>}</td>
                <td>{u.name}</td>
                <td>{formatDate(u.last_login_at) || '–'}</td>
                <td>{u.id !== me.id && (
                  <form method="post" action="/admin/api/users" data-confirm={`Opravdu odebrat správce ${u.email}?`}>
                    <Csrf /><input type="hidden" name="action" value="delete" /><input type="hidden" name="id" value={u.id} />
                    <button type="submit" className="btn-small btn-danger">Odebrat</button>
                  </form>
                )}</td>
              </tr>
            ))}
          </tbody>
        </table>
      </section>
      <div className="two-col">
        <form method="post" action="/admin/api/users" className="box">
          <h2>Přidat správce</h2>
          {sp.add && <p className="err-box" role="alert">{sp.add}</p>}
          <Csrf /><input type="hidden" name="action" value="add" />
          <div className="f-field"><label htmlFor="u-email">E-mail</label><input type="email" id="u-email" name="email" required autoComplete="off" /></div>
          <div className="f-field"><label htmlFor="u-name">Jméno</label><input type="text" id="u-name" name="name" /></div>
          <div className="f-field"><label htmlFor="u-pass">Heslo (min. 10 znaků)</label><input type="password" id="u-pass" name="password" minLength={10} required autoComplete="new-password" /></div>
          <button type="submit" className="btn-primary">Přidat</button>
        </form>
        <form method="post" action="/admin/api/users" className="box">
          <h2>Změnit moje heslo</h2>
          {sp.password && <p className="err-box" role="alert">{sp.password}</p>}
          <Csrf /><input type="hidden" name="action" value="password" />
          <div className="f-field"><label htmlFor="p-cur">Současné heslo</label><input type="password" id="p-cur" name="current" required autoComplete="current-password" /></div>
          <div className="f-field"><label htmlFor="p-new">Nové heslo (min. 10 znaků)</label><input type="password" id="p-new" name="new" minLength={10} required autoComplete="new-password" /></div>
          <div className="f-field"><label htmlFor="p-new2">Nové heslo znovu</label><input type="password" id="p-new2" name="new2" minLength={10} required autoComplete="new-password" /></div>
          <button type="submit" className="btn-primary">Změnit heslo</button>
        </form>
      </div>
    </AdminShell>
  )
}
