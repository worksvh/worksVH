import type { ReactNode } from 'react'
import type { Field, Fields as FieldsT } from '@/lib/schema'
import { collectionSchema } from '@/lib/schema'
import { fillDefaults, fieldDefault, type Content, type Data } from '@/lib/content'
import { fieldHasPlaceholder } from '@/lib/admin'
import { mediaUrl } from '@/lib/storage'

/*
 * Generátor formulářů ze schématu. Cesta k poli = pole klíčů, např. ['hero', 'title'] nebo ['part', 'pins', 0, 'x'].
 *   name  = data[hero][title]
 *   id    = f-hero-title         (na tohle vedou odkazy „K doplnění“)
 *   error = hero.title
 */

type Path = (string | number)[]
const fName = (p: Path) => 'data[' + p.join('][') + ']'
export const fId = (p: Path) => 'f-' + p.join('-')

const Badge = ({ on }: { on: boolean }) => <span className="badge-todo" hidden={!on} data-todo-badge>K doplnění</span>

export function RenderFields({ c, fields, data, prefix, errors }: { c: Content; fields: FieldsT; data: Data; prefix: Path; errors: Record<string, string> }) {
  return (
    <>
      {Object.entries(fields).map(([name, f]) => (
        <RenderField key={name} c={c} f={f} v={data?.[name] ?? fieldDefault(f)} path={[...prefix, name]} errors={errors} />
      ))}
    </>
  )
}

function RenderField({ c, f, v, path, errors }: { c: Content; f: Field; v: any; path: Path; errors: Record<string, string> }) {
  const id = fId(path)
  const name = fName(path)
  const err = errors[path.join('.')]
  const req = Boolean(f.required)
  const describedBy = [f.help ? `${id}-help` : '', err ? `${id}-err` : ''].filter(Boolean).join(' ')
  const aria = {
    'aria-describedby': describedBy || undefined,
    'aria-invalid': err ? ('true' as const) : undefined,
    'aria-required': req ? ('true' as const) : undefined,
  }
  const label = (
    <>
      <span className="f-label-text">{f.label}{req && <> <span className="req" aria-hidden="true">*</span></>}</span>
      <Badge on={fieldHasPlaceholder(f, v)} />
    </>
  )
  const help = f.help ? <p className="help" id={`${id}-help`}>{f.help}</p> : null
  const errHtml = err ? <p className="err" id={`${id}-err`}>{err}</p> : null
  const max = f.maxLength !== undefined ? { maxLength: f.maxLength, 'data-count': '' } : {}
  const wrap = (children: ReactNode) => <div className={`f-field f-${f.type}${err ? ' has-error' : ''}`} data-field>{children}</div>

  switch (f.type) {
    case 'text':
    case 'tel':
    case 'email':
    case 'url': {
      const mode = f.type === 'email' ? { inputMode: 'email' as const, autoComplete: 'off' } : f.type === 'url' ? { inputMode: 'url' as const } : {}
      return wrap(<>
        <label htmlFor={id}>{label}</label>
        <input type={f.type === 'tel' ? 'tel' : 'text'} id={id} name={name} defaultValue={String(v ?? '')} {...max} {...mode} {...aria} data-ph-check="" />
        {help}{errHtml}
      </>)
    }
    case 'password':
      return wrap(<>
        <label htmlFor={id}>{label}</label>
        <input type="password" id={id} name={name} defaultValue="" autoComplete="new-password" placeholder={String(v ?? '') !== '' ? '•••••••• (uloženo)' : ''} {...aria} />
        {help}{errHtml}
      </>)
    case 'textarea': {
      const s = String(v ?? '')
      const rows = Math.max(3, Math.min(12, s.split('\n').length + 1, Math.ceil([...s].length / 80) + 1))
      return wrap(<>
        <label htmlFor={id}>{label}</label>
        <textarea id={id} name={name} rows={rows} defaultValue={s} {...max} {...aria} data-ph-check="" />
        {help}{errHtml}
      </>)
    }
    case 'number':
      return wrap(<>
        <label htmlFor={id}>{label}</label>
        <input type="number" step="any" id={id} name={name} defaultValue={v === null || v === undefined ? '' : String(v)} min={f.min} max={f.max} {...aria} className="input-short" />
        {help}{errHtml}
      </>)
    case 'select':
      return wrap(<>
        <label htmlFor={id}>{label}</label>
        <select id={id} name={name} defaultValue={String(v ?? '')} {...aria}>
          {Object.entries(f.options ?? {}).map(([ov, ol]) => <option key={ov} value={ov}>{ol}</option>)}
        </select>
        {help}{errHtml}
      </>)
    case 'reference': {
      const cs = collectionSchema(f.collection!)!
      return wrap(<>
        <label htmlFor={id}>{label}</label>
        <select id={id} name={name} defaultValue={v ? String(v) : ''} {...aria}>
          <option value="">– vyberte –</option>
          {c.items(f.collection!, false).map((it) => <option key={it.id} value={it.id}>{String(it.data[cs.title] ?? '')}</option>)}
        </select>
        {help}
        <p className="help"><a href={`/admin/${cs.path}`}>Upravit {cs.label.toLowerCase()} →</a></p>
        {errHtml}
      </>)
    }
    case 'toggle':
      return wrap(<>
        <input type="hidden" name={name} value="0" />
        <label className="switch" htmlFor={id}>
          <input type="checkbox" id={id} name={name} value="1" defaultChecked={Boolean(v)} {...aria} />
          <span className="switch-ui" aria-hidden="true"></span>{label}
        </label>
        {help}{errHtml}
      </>)
    case 'richtext':
      return wrap(<>
        <span className="f-label" id={`${id}-label`}>{label}</span>
        <div className="rte" data-rte>
          <div className="rte-bar" role="toolbar" aria-label="Formátování textu">
            <button type="button" data-cmd="bold" title="Tučně (Ctrl+B)" aria-label="Tučně"><b>B</b></button>
            <button type="button" data-cmd="link" title="Odkaz" aria-label="Vložit odkaz">Odkaz</button>
            <button type="button" data-cmd="unlink" title="Zrušit odkaz" aria-label="Zrušit odkaz">Zrušit odkaz</button>
            <button type="button" data-cmd="insertUnorderedList" title="Seznam s odrážkami" aria-label="Seznam s odrážkami">• Seznam</button>
            <button type="button" data-cmd="insertOrderedList" title="Číslovaný seznam" aria-label="Číslovaný seznam">1. Seznam</button>
            <button type="button" data-cmd="formatBlock" title="Obyčejný odstavec" aria-label="Obyčejný odstavec">Odstavec</button>
          </div>
          <div className="rte-area" id={id} contentEditable suppressContentEditableWarning role="textbox" aria-multiline="true" aria-labelledby={`${id}-label`} {...aria} data-ph-check=""
            dangerouslySetInnerHTML={{ __html: String(v ?? '') }} />
          <textarea name={name} hidden data-rte-value defaultValue={String(v ?? '')} />
        </div>
        {help}{errHtml}
      </>)
    case 'tags':
      return wrap(<>
        <span className="f-label" id={`${id}-label`}>{label}</span>
        <div className="tags-edit" role="group" aria-labelledby={`${id}-label`} data-tags id={id}>
          {(Array.isArray(v) ? v : []).map((t: string, i: number) => <TagRow key={i} name={name} val={String(t)} />)}
        </div>
        <template data-tag-template dangerouslySetInnerHTML={{ __html: tagRowHtml(name) }} />
        <button type="button" className="btn-small" data-tag-add>+ Přidat položku</button>
        {help}{errHtml}
      </>)
    case 'image': {
      const iv = v && typeof v === 'object' ? v : { media: null, alt: '' }
      const m = c.media(iv.media)
      const thumb = m ? mediaUrl(m.variants.length ? m.variants[0][2] : m.filename) : ''
      return wrap(<>
        <span className="f-label" id={`${id}-label`}>{label}</span>
        <div className="img-field" id={id} data-image-field data-svg={f.svg ? '1' : '0'}>
          {/* eslint-disable-next-line @next/next/no-img-element */}
          <div className="img-preview" data-img-preview>{m ? <img src={thumb} alt="" /> : <span>Bez obrázku</span>}</div>
          <div className="img-controls">
            <input type="hidden" name={`${name}[media]`} defaultValue={iv.media ? String(iv.media) : ''} data-img-id />
            <div className="img-buttons">
              <button type="button" className="btn-small" data-img-pick aria-describedby={`${id}-label`}>Vybrat nebo nahrát obrázek</button>
              <button type="button" className="btn-small btn-ghost" data-img-clear hidden={!m}>Odebrat obrázek</button>
            </div>
            <p className="help" data-img-name hidden={!m}>{m ? m.original_name + (m.width ? ` · ${m.width} × ${m.height} px` : '') : ''}</p>
            <label htmlFor={`${id}-alt`} className="sub-label">Popis obrázku (alt){m && <> <span className="req" aria-hidden="true">*</span></>}</label>
            <input type="text" id={`${id}-alt`} name={`${name}[alt]`} defaultValue={String(iv.alt ?? '')} {...aria} data-ph-check="" data-img-alt />
            <p className="help">Co je na obrázku – čte ho nevidomým čtečka a vidí ho Google. Dokud obrázek chybí, zobrazí se tento text v šedém místě na webu.</p>
          </div>
        </div>
        {help}{errHtml}
      </>)
    }
    case 'repeater': {
      const rows: Data[] = Array.isArray(v) ? v : []
      const min = f.min ?? 0
      const max = f.max ?? 999
      const fixed = min === max
      return wrap(<>
        <span className="f-label" id={`${id}-label`}>{label} <span className="rep-count">({fixed ? `přesně ${min}` : min ? `${min}–${max}` : `max. ${max}`})</span></span>
        {help}{errHtml}
        <div className="repeater" id={id} data-repeater data-min={min} data-max={max} role="list" aria-labelledby={`${id}-label`}>
          {rows.map((row, i) => <RepItem key={i} c={c} f={f} row={row ?? {}} path={[...path, i]} errors={errors} n={i + 1} />)}
        </div>
        <RepTemplate c={c} f={f} path={path} />
        {!fixed && <button type="button" className="btn-small" data-rep-add>+ Přidat položku</button>}
        <p className="help" data-rep-limit hidden></p>
      </>)
    }
  }
  return null
}

function TagRow({ name, val }: { name: string; val: string }) {
  return (
    <div className="tag-row" data-tag-row>
      <input type="text" name={`${name}[]`} defaultValue={val} aria-label="Položka" data-ph-check="" />
      <button type="button" className="icon-btn" data-move="-1" aria-label="Posunout nahoru">↑</button>
      <button type="button" className="icon-btn" data-move="1" aria-label="Posunout dolů">↓</button>
      <button type="button" className="icon-btn icon-danger" data-tag-remove aria-label="Odebrat položku">×</button>
    </div>
  )
}

const esc = (s: string) => s.replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;')
const tagRowHtml = (name: string) =>
  `<div class="tag-row" data-tag-row><input type="text" name="${esc(name)}[]" value="" aria-label="Položka" data-ph-check><button type="button" class="icon-btn" data-move="-1" aria-label="Posunout nahoru">↑</button><button type="button" class="icon-btn" data-move="1" aria-label="Posunout dolů">↓</button><button type="button" class="icon-btn icon-danger" data-tag-remove aria-label="Odebrat položku">×</button></div>`

function RepItem({ c, f, row, path, errors, n }: { c: Content; f: Field; row: Data; path: Path; errors: Record<string, string>; n: number }) {
  const title = f.item_label ? String(row[f.item_label] ?? '') : ''
  const fixed = (f.min ?? -1) === (f.max ?? -2)
  return (
    <fieldset className="rep-item" role="listitem" data-rep-item draggable={false}>
      <legend className="rep-head">
        <span className="rep-drag" data-drag-handle title="Přetáhněte pro změnu pořadí" aria-hidden="true">⠿</span>
        <span className="rep-num" data-rep-num>{n || ''}</span>
        <span className="rep-title" data-rep-title>{title}</span>
      </legend>
      <div className="rep-actions">
        <button type="button" className="icon-btn" data-move="-1" aria-label="Posunout nahoru">↑</button>
        <button type="button" className="icon-btn" data-move="1" aria-label="Posunout dolů">↓</button>
        {!fixed && <button type="button" className="icon-btn icon-danger" data-rep-remove aria-label="Smazat položku">×</button>}
      </div>
      <div className="rep-body"><RenderFields c={c} fields={f.fields!} data={row} prefix={path} errors={errors} /></div>
    </fieldset>
  )
}

/* Šablona nové položky opakovače: admin.js ji naklonuje a nahradí __i__ jedinečným klíčem. */
async function RepTemplate({ c, f, path }: { c: Content; f: Field; path: Path }) {
  const { renderToStaticMarkup } = await import('react-dom/server')
  const html = renderToStaticMarkup(<RepItem c={c} f={f} row={fillDefaults(f.fields!, {})} path={[...path, '__i__']} errors={{}} n={0} />)
  return <template data-rep-template dangerouslySetInnerHTML={{ __html: html }} />
}
