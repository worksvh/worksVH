import { notFound } from 'next/navigation'
import { requireUser, fieldHasPlaceholder } from '@/lib/admin'
import { schema } from '@/lib/schema'
import { loadContent } from '@/lib/content'
import { mediaUrl } from '@/lib/storage'
import { AdminShell, Csrf } from '@/components/admin/AdminShell'

export default async function CollectionList({ params }: { params: Promise<{ col: string }> }) {
  const { col } = await params
  const c0 = Object.keys(schema.collections).find((k) => schema.collections[k].path === col)
  if (!c0) notFound()
  const user = await requireUser(`/admin/${col}`)
  const cs = schema.collections[c0]
  const content = await loadContent()
  const items = content.items(c0, false)
  const categories = c0 === 'works' ? content.itemsById('categories') : null
  const thumbField = cs.thumb

  return (
    <AdminShell user={user} title={cs.label} nav={`col:${c0}`}>
      <div className="a-head">
        <div>
          <h1>{cs.label}</h1>
          <p className="help">Pořadí na webu změníte přetažením řádku nebo šipkami. <a href={cs.web} target="_blank" rel="noopener">Zobrazit na webu ↗</a></p>
        </div>
        <a className="btn-primary" href={`/admin/${cs.path}/nova`}>+ Přidat {cs.singular}</a>
      </div>

      <div className="list-tools">
        <label htmlFor="list-search" className="sr">Hledat</label>
        <input type="search" id="list-search" placeholder="Hledat…" data-list-search />
        <span className="help" data-sort-state role="status"></span>
      </div>

      {!items.length ? (
        <p className="box">Zatím tu nic není. <a href={`/admin/${cs.path}/nova`}>Přidat první {cs.singular}</a>.</p>
      ) : (
        <table className="tbl tbl-list" data-sortable data-sort-url={`/admin/api/reorder/${cs.path}`}>
          <thead><tr>
            <th className="col-handle"><span className="sr">Pořadí</span></th>
            <th className="col-num">#</th>
            {thumbField && <th className="col-thumb">Náhled</th>}
            <th>Název</th>
            {c0 === 'works' && <><th>Kategorie</th><th>Označení</th></>}
            {cs.visibility && <th>Na webu</th>}
            <th className="col-actions"><span className="sr">Akce</span></th>
          </tr></thead>
          <tbody>
            {items.map((it, i) => {
              const d = it.data
              const title = String(d[cs.title] ?? '')
              const m = thumbField ? content.media(d[thumbField]?.media) : null
              const todo = Object.entries(cs.fields).some(([fn, fd]) => fieldHasPlaceholder(fd, d[fn]))
              return (
                <tr key={it.id} data-id={it.id} data-search={(title + ' ' + it.slug).toLowerCase()}>
                  <td className="col-handle"><span className="drag" data-row-handle aria-hidden="true" title="Přetáhnout">⠿</span></td>
                  <td className="col-num" data-row-num>{i + 1}</td>
                  {thumbField && (
                    <td className="col-thumb">
                      {/* eslint-disable-next-line @next/next/no-img-element */}
                      {m ? <img src={mediaUrl(m.variants.length ? m.variants[0][2] : m.filename)} alt="" width={64} height={48} /> : <span className="thumb-empty">bez fotky</span>}
                    </td>
                  )}
                  <td>
                    <a href={`/admin/${cs.path}/${it.id}`} className="strong">{title !== '' ? title : '(bez názvu)'}</a>
                    {todo && <> <span className="badge-todo">K doplnění</span></>}
                  </td>
                  {c0 === 'works' && (
                    <>
                      <td>{String(categories!.get(Number(d.category))?.data.name ?? '–')}</td>
                      <td>
                        {d.featured && <><span className="pill pill-soft">Vybraná</span> </>}
                        {d.on_home && <span className="pill pill-soft">Úvod</span>}
                      </td>
                    </>
                  )}
                  {cs.visibility && <td>{it.visible ? 'Ano' : <span className="muted">Skryto</span>}</td>}
                  <td className="col-actions">
                    <button type="button" className="icon-btn" data-row-move="-1" aria-label={`Posunout „${title}“ nahoru`}>↑</button>
                    <button type="button" className="icon-btn" data-row-move="1" aria-label={`Posunout „${title}“ dolů`}>↓</button>
                    <a className="btn-small btn-ghost" href={`/admin/${cs.path}/${it.id}`}>Upravit</a>
                    <form method="post" action={`/admin/api/delete/${cs.path}/${it.id}`} data-confirm={`Opravdu smazat „${title}“? Smazání nejde vrátit.`}>
                      <Csrf /><button type="submit" className="btn-small btn-danger">Smazat</button>
                    </form>
                  </td>
                </tr>
              )
            })}
          </tbody>
        </table>
      )}
    </AdminShell>
  )
}
