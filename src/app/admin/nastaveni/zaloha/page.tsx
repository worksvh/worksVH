import { requireUser } from '@/lib/admin'
import { AdminShell, Csrf, SettingsTabs } from '@/components/admin/AdminShell'

export default async function Backup() {
  const user = await requireUser('/admin/nastaveni/zaloha')
  return (
    <AdminShell user={user} title="Nastavení: Záloha" nav="settings">
      <div className="a-head"><h1>Nastavení</h1></div>
      <SettingsTabs tab="zaloha" />
      <section className="box">
        <h2>Záloha webu</h2>
        <p>Stáhne ZIP s databází (všechny texty, nastavení, sbírky a poptávky) a s obrázky. Přílohy poptávek záloha záměrně neobsahuje.</p>
        <form method="post" action="/admin/api/backup"><Csrf /><button type="submit" className="btn-primary">Stáhnout zálohu</button></form>
        <p className="help">Databáze v Turso má navíc vlastní automatické zálohy (obnova k libovolnému okamžiku), nastavují se v účtu Turso.</p>
      </section>
    </AdminShell>
  )
}
