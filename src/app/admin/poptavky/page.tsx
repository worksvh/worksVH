import { requireUser } from '@/lib/admin'
import { all } from '@/lib/db'
import { INQUIRY_STATUSES } from '@/lib/inquiries'
import { formatDate, telHref } from '@/lib/text'
import { AdminShell } from '@/components/admin/AdminShell'

type SP = Promise<Record<string, string | undefined>>

export default async function Inquiries({ searchParams }: { searchParams: SP }) {
  const user = await requireUser('/admin/poptavky')
  const sp = await searchParams
  const status = sp.stav && INQUIRY_STATUSES[sp.stav] ? sp.stav : ''
  const q = (sp.q ?? '').trim()
  let sql = 'SELECT i.*, (SELECT COUNT(*) FROM inquiry_files f WHERE f.inquiry_id = i.id) AS files FROM inquiries i WHERE 1'
  const args: string[] = []
  if (status) { sql += ' AND status = ?'; args.push(status) }
  if (q) { sql += ' AND (company LIKE ? OR name LIKE ? OR email LIKE ? OR phone LIKE ?)'; args.push(...Array(4).fill(`%${q}%`)) }
  sql += ' ORDER BY id DESC LIMIT 500'
  const [rows, countRows] = await Promise.all([
    all<{ id: number; created_at: string; company: string; name: string; email: string; phone: string; status: string; files: number }>(sql, args),
    all<{ status: string; n: number }>('SELECT status, COUNT(*) n FROM inquiries GROUP BY status'),
  ])
  const counts = Object.fromEntries(countRows.map((r) => [r.status, Number(r.n)]))
  const total = Object.values(counts).reduce((a, b) => a + b, 0)

  return (
    <AdminShell user={user} title="Poptávky" nav="inquiries">
      <div className="a-head"><h1>Poptávky</h1></div>
      <nav className="tabs" aria-label="Filtr podle stavu">
        <a href="/admin/poptavky" aria-current={status === '' ? 'page' : undefined}>Vše ({total})</a>
        {Object.entries(INQUIRY_STATUSES).map(([k, label]) => (
          <a key={k} href={`/admin/poptavky?stav=${k}`} aria-current={status === k ? 'page' : undefined}>{label} ({counts[k] ?? 0})</a>
        ))}
      </nav>
      <form method="get" action="/admin/poptavky" className="list-tools">
        {status !== '' && <input type="hidden" name="stav" value={status} />}
        <label htmlFor="iq" className="sr">Hledat</label>
        <input type="search" id="iq" name="q" defaultValue={q} placeholder="Firma, jméno, e-mail, telefon…" />
        <button type="submit" className="btn-small btn-ghost">Hledat</button>
      </form>
      {!rows.length ? (
        <p className="box">Žádné poptávky{status || q ? ' pro tento filtr' : ''}.</p>
      ) : (
        <div className="tbl-wrap">
          <table className="tbl">
            <thead><tr><th>Datum</th><th>Firma</th><th>Jméno</th><th>Kontakt</th><th>Přílohy</th><th>Stav</th></tr></thead>
            <tbody>
              {rows.map((r) => (
                <tr key={r.id} className={r.status === 'new' ? 'row-new' : ''}>
                  <td>{formatDate(r.created_at)}</td>
                  <td><a className="strong" href={`/admin/poptavky/${r.id}`}>{r.company}</a></td>
                  <td>{r.name}</td>
                  <td>
                    {r.email !== '' && <a href={`mailto:${r.email}`}>{r.email}</a>}
                    {r.phone !== '' && <><br /><a href={telHref(r.phone)}>{r.phone}</a></>}
                  </td>
                  <td>{Number(r.files) || '–'}</td>
                  <td><span className={`status status-${r.status}`}>{INQUIRY_STATUSES[r.status] ?? r.status}</span></td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </AdminShell>
  )
}
