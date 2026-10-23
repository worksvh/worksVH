import type { User } from '@/lib/auth'
import type { Entity } from '@/lib/admin'
import { revisionsFor, REVISIONS_KEEP } from '@/lib/admin'
import { loadContent } from '@/lib/content'
import { formatDate } from '@/lib/text'
import { AdminShell, Csrf, SettingsTabs } from './AdminShell'
import { RenderFields } from './Fields'

const PREVIEWABLE_SETTINGS = ['company', 'display', 'menu', 'cta', 'notfound', 'form_msgs']

export async function EditPage({ user, ent }: { user: User; ent: Entity }) {
  const c = await loadContent()
  const revisions = ent.rev ? await revisionsFor(...ent.rev) : []
  const isItem = ent.type === 'item'
  const cs = ent.cs
  const webBase = ent.web.split('#')[0]
  const errors: Record<string, string> = {}

  return (
    <AdminShell user={user} title={ent.title} nav={ent.nav}>
      <div className="a-head">
        <div>
          {isItem && <p className="crumb"><a href={`/admin/${cs!.path}`}>← {cs!.label}</a></p>}
          <h1>{ent.title}</h1>
          {ent.updated_at && <p className="help">Naposledy uloženo {formatDate(ent.updated_at)}</p>}
        </div>
        {(!isItem || ent.id) && <a className="btn-small btn-ghost" href={ent.web} target="_blank" rel="noopener">Zobrazit na webu ↗</a>}
      </div>

      {ent.settingsTab && <SettingsTabs tab={ent.settingsTab} />}

      <div className="err-box" role="alert" tabIndex={-1} hidden data-err-box></div>

      <div className="edit-layout">
        <form method="post" action="/admin/api/save" className="edit-form" data-edit-form noValidate>
          <Csrf />
          <input type="hidden" name="_type" value={ent.type} />
          <input type="hidden" name="_key" value={ent.key} />

          {isItem && (cs!.slug || cs!.visibility) && (
            <section className="group">
              <h2>Zobrazení</h2>
              {cs!.visibility && (
                <div className="f-field f-toggle">
                  <input type="hidden" name="visible" value="0" />
                  <label className="switch" htmlFor="f-visible">
                    <input type="checkbox" id="f-visible" name="visible" value="1" defaultChecked={ent.visible} />
                    <span className="switch-ui" aria-hidden="true"></span><span className="f-label-text">Zobrazit na webu</span>
                  </label>
                  <p className="help">Vypnuté položky na webu nejsou vidět, ale zůstanou uložené.</p>
                </div>
              )}
              {cs!.slug && (
                <div className="f-field" data-field>
                  <label htmlFor="f-slug"><span className="f-label-text">Adresa (slug)</span></label>
                  <input type="text" id="f-slug" name="slug" defaultValue={ent.slug} data-slug-from={`f-${cs!.slug}`} data-slug-auto={ent.slug === '' ? '1' : '0'} aria-describedby="f-slug-help" pattern="[a-z0-9-]*" />
                  <p className="help" id="f-slug-help">
                    {ent.collection === 'services' ? 'Kotva na stránce Služby, např. /sluzby#konstrukce.' : 'Používá se v adrese filtru, např. /prace?kategorie=cnc.'}
                    {' '}Vyplní se samo z názvu, jen malá písmena bez diakritiky a pomlčky.
                  </p>
                </div>
              )}
            </section>
          )}

          {Object.entries(ent.groups).map(([g, group]) => (
            <section key={g} className="group" id={`g-${g}`}>
              <div className="group-head">
                <h2>{group.label}</h2>
                {group.anchor && <a className="small-link" href={`${webBase}#${group.anchor}`} target="_blank" rel="noopener">Zobrazit na webu ↗</a>}
              </div>
              {group.help && <p className="help group-help">{group.help}</p>}
              {ent.flat
                ? <RenderFields c={c} fields={group.fields} data={ent.data} prefix={[]} errors={errors} />
                : <RenderFields c={c} fields={group.fields} data={ent.data[g] ?? {}} prefix={[g]} errors={errors} />}
            </section>
          ))}

          <div className="save-bar">
            <button type="submit" className="btn-primary">Uložit</button>
            {(ent.type !== 'settings' || PREVIEWABLE_SETTINGS.includes(ent.key)) && (
              <button type="submit" className="btn-small btn-ghost" formAction="/admin/api/preview" formTarget="_blank" data-preview>Náhled</button>
            )}
            <span className="save-state" data-dirty-note hidden>Máte neuložené změny</span>
          </div>
        </form>

        <aside className="edit-side">
          {isItem && ent.id && (
            <form method="post" action={`/admin/api/delete/${cs!.path}/${ent.id}`} data-confirm="Opravdu smazat tuto položku? Smazání nejde vrátit.">
              <Csrf />
              <button type="submit" className="btn-small btn-danger">Smazat {cs!.singular}</button>
            </form>
          )}
          <section className="box history">
            <h2>Historie</h2>
            {!revisions.length ? (
              <p className="help">Zatím žádné uložené verze.</p>
            ) : (
              <>
                <p className="help">Posledních {REVISIONS_KEEP} uložení. Obnovením se vybraná verze uloží jako aktuální.</p>
                <ol className="rev-list">
                  {revisions.map((r, i) => (
                    <li key={r.id}>
                      <span>
                        {formatDate(r.created_at)}{i === 0 && <> <span className="pill pill-soft">aktuální</span></>}<br />
                        <span className="help">{r.name || r.email || 'systém'}</span>
                      </span>
                      {i > 0 && (
                        <form method="post" action={`/admin/api/restore/${r.id}`} data-confirm={`Obnovit verzi z ${formatDate(r.created_at)}? Neuložené změny ve formuláři se ztratí.`}>
                          <Csrf /><button type="submit" className="btn-small btn-ghost">Obnovit</button>
                        </form>
                      )}
                    </li>
                  ))}
                </ol>
              </>
            )}
          </section>
        </aside>
      </div>
    </AdminShell>
  )
}
