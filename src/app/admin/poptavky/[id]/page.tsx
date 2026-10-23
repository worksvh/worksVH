import { notFound } from 'next/navigation'
import { requireUser } from '@/lib/admin'
import { one } from '@/lib/db'
import { loadContent, type Data } from '@/lib/content'
import { INQUIRY_STATUSES, inquiryFiles } from '@/lib/inquiries'
import { formatDate, formatBytes, telHref } from '@/lib/text'
import { AdminShell, Csrf } from '@/components/admin/AdminShell'

export default async function Inquiry({ params }: { params: Promise<{ id: string }> }) {
  const id = Number((await params).id)
  const user = await requireUser(`/admin/poptavky/${id}`)
  const q = await one<Data>('SELECT * FROM inquiries WHERE id = ?', [id])
  if (!q) notFound()
  const files = await inquiryFiles(id)
  const months = Number((await loadContent()).setting('mail').retention_months) || 24

  return (
    <AdminShell user={user} title={`Poptávka č. ${id}`} nav="inquiries">
      <div className="a-head">
        <div>
          <p className="crumb"><a href="/admin/poptavky">← Poptávky</a></p>
          <h1>Poptávka č. {q.id} – {q.company}</h1>
          <p className="help">
            Přijato {formatDate(q.created_at)}. Souhlas se zpracováním udělen {formatDate(q.consent_at)}.{' '}
            {q.anonymized_at ? `Anonymizováno ${formatDate(q.anonymized_at)}.` : `Po ${months} měsících se poptávka automaticky anonymizuje a přílohy smažou.`}
          </p>
        </div>
      </div>
      <div className="edit-layout">
        <div>
          <section className="box">
            <dl className="dl">
              <div><dt>Firma</dt><dd>{q.company}</dd></div>
              <div><dt>Jméno</dt><dd>{q.name}</dd></div>
              <div><dt>E-mail</dt><dd>{q.email !== '' ? <a href={`mailto:${q.email}`}>{q.email}</a> : '–'}</dd></div>
              <div><dt>Telefon</dt><dd>{q.phone !== '' ? <a href={telHref(q.phone)}>{q.phone}</a> : '–'}</dd></div>
              <div><dt>Počet kusů</dt><dd>{q.quantity || '–'}</dd></div>
              <div><dt>Potřebný termín</dt><dd>{q.deadline || '–'}</dd></div>
              <div className="dl-wide"><dt>Popis</dt><dd className="pre">{q.message}</dd></div>
            </dl>
          </section>
          <section className="box">
            <h2>Přílohy</h2>
            {!files.length ? <p className="help">Bez příloh.</p> : (
              <ul className="file-dl">
                {files.map((f) => (
                  <li key={f.id}><a href={`/admin/api/inquiry/${q.id}/file/${f.id}`}>{f.original_name}</a> <span className="help">{formatBytes(Number(f.size))}</span></li>
                ))}
              </ul>
            )}
          </section>
        </div>
        <aside className="edit-side">
          <form method="post" action={`/admin/api/inquiry/${q.id}`} className="box" data-edit-form data-plain>
            <Csrf />
            <div className="f-field"><label htmlFor="i-status">Stav</label>
              <select id="i-status" name="status" defaultValue={q.status}>
                {Object.entries(INQUIRY_STATUSES).map(([k, label]) => <option key={k} value={k}>{label}</option>)}
              </select>
            </div>
            <div className="f-field"><label htmlFor="i-note">Interní poznámka</label><textarea id="i-note" name="note" rows={6} defaultValue={q.note} /><p className="help">Vidí ji jen správci webu.</p></div>
            <button type="submit" className="btn-primary">Uložit</button>
          </form>
          <form method="post" action={`/admin/api/inquiry/${q.id}/delete`} data-confirm="Opravdu smazat poptávku i s přílohami? Nejde to vrátit.">
            <Csrf /><button type="submit" className="btn-small btn-danger">Smazat poptávku</button>
          </form>
        </aside>
      </div>
    </AdminShell>
  )
}
