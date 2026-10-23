import { after } from 'next/server'
import { requireUser, placeholderReport } from '@/lib/admin'
import { loadContent } from '@/lib/content'
import { all, val } from '@/lib/db'
import { INQUIRY_STATUSES, inquiriesAnonymizeOld } from '@/lib/inquiries'
import { formatDate } from '@/lib/text'
import { AdminShell } from '@/components/admin/AdminShell'

export default async function Dashboard() {
  const user = await requireUser()
  const c = await loadContent()
  const [newCount, latest] = await Promise.all([
    val<number>("SELECT COUNT(*) FROM inquiries WHERE status = 'new'").then(Number),
    all<{ id: number; created_at: string; company: string; name: string; status: string }>('SELECT id, created_at, company, name, status FROM inquiries ORDER BY id DESC LIMIT 5'),
  ])
  const todo = placeholderReport(c)
  const hidden = Boolean(c.setting('display').hide_placeholders)
  // pojistka k cronu: staré poptávky se anonymizují i při otevření přehledu
  after(() => inquiriesAnonymizeOld(c).catch(() => undefined))
  let last: string | null = null

  return (
    <AdminShell user={user} title="Přehled" nav="dashboard">
      <div className="a-head"><h1>Přehled</h1></div>
      <div className="cards">
        <a className="stat" href="/admin/poptavky?stav=new">
          <span className="stat-num">{newCount}</span>
          <span className="stat-label">{newCount === 1 ? 'nová poptávka' : newCount >= 2 && newCount <= 4 ? 'nové poptávky' : 'nových poptávek'}</span>
        </a>
        <a className="stat" href="#k-doplneni">
          <span className="stat-num">{todo.length}</span>
          <span className="stat-label">polí k doplnění</span>
        </a>
        <div className="stat stat-links">
          <span className="stat-label">Rychlé odkazy</span>
          <a href="/admin/stranky/home">Upravit úvodní stránku</a>
          <a href="/admin/zakazky/nova">Přidat zakázku</a>
          <a href="/admin/media">Nahrát fotky</a>
          <a href="/admin/nastaveni/company">Kontakty firmy</a>
          <a href="/" target="_blank" rel="noopener">Zobrazit web ↗</a>
        </div>
      </div>

      {latest.length > 0 && (
        <section className="box">
          <h2>Poslední poptávky</h2>
          <table className="tbl">
            <thead><tr><th>Datum</th><th>Firma</th><th>Jméno</th><th>Stav</th></tr></thead>
            <tbody>
              {latest.map((q) => (
                <tr key={q.id}>
                  <td>{formatDate(q.created_at)}</td>
                  <td><a href={`/admin/poptavky/${q.id}`}>{q.company}</a></td>
                  <td>{q.name}</td>
                  <td><span className={`status status-${q.status}`}>{INQUIRY_STATUSES[q.status] ?? q.status}</span></td>
                </tr>
              ))}
            </tbody>
          </table>
        </section>
      )}

      <section className="box" id="k-doplneni">
        <h2>K doplnění <span className="badge-todo">{todo.length}</span></h2>
        <p className="help">
          Pole, ve kterých je text v hranatých závorkách [ … ]. Na webu se zatím{' '}
          {hidden
            ? <><strong>nezobrazují</strong> (zapnuté skrývání v Nastavení › Logo a zobrazení)</>
            : <><strong>zobrazují</strong>, aby šlo web ukazovat jako návrh. Skrýt je jde v Nastavení › Logo a zobrazení</>}.
        </p>
        {!todo.length ? <p>Vše je vyplněné.</p> : (
          <ul className="todo-list">
            {todo.flatMap((t, i) => {
              const rows = []
              if (t.where !== last) {
                last = t.where
                rows.push(<li key={`w${i}`} className="todo-where">{t.where}</li>)
              }
              rows.push(
                <li key={i}><a href={t.url}>{t.field}</a> <span className="todo-val">{[...t.value].length > 90 ? [...t.value].slice(0, 89).join('') + '…' : t.value}</span></li>,
              )
              return rows
            })}
          </ul>
        )}
      </section>
    </AdminShell>
  )
}
