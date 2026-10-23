'use client'

import { useActionState, useEffect, useRef, useState, startTransition, type FormEvent, type DragEvent } from 'react'
import { submitInquiry, type InquiryState } from './actions'

export interface InquiryTexts {
  head_left: string; head_right: string; title: string
  f_company: string; f_name: string; f_email: string; f_phone: string; f_quantity: string; f_deadline: string; f_message: string
  files_title: string; files_formats: string; files_button: string; files_limits: string
  consent: string; consent_link: string; note: string; submit: string
  msgCount: string; msgSize: string; msgTotal: string; msgType: string; msgRemove: string; msgUpload: string; uploading: string
}

const fmt = (b: number) => (b >= 1048576 ? (b / 1048576).toFixed(1).replace('.', ',') + ' MB' : Math.max(1, Math.round(b / 1024)) + ' kB')

export function InquiryForm({ t, started, blob, limits }: {
  t: InquiryTexts
  started: string
  blob: boolean
  limits: { maxFiles: number; maxFile: number; maxTotal: number; types: string[] }
}) {
  const [state, action, pending] = useActionState<InquiryState, FormData>(submitInquiry, { values: {}, errors: {}, formError: null, started })
  const [files, setFiles] = useState<File[]>([])
  const [fileError, setFileError] = useState<string>('')
  const [uploading, setUploading] = useState(false)
  const [over, setOver] = useState(false)
  const formRef = useRef<HTMLFormElement>(null)
  const inputRef = useRef<HTMLInputElement>(null)
  const alertRef = useRef<HTMLDivElement>(null)
  const err = { ...state.errors }
  const v = state.values

  const problem = (list: File[]): string => {
    if (list.length > limits.maxFiles) return t.msgCount
    let total = 0
    for (const f of list) {
      const ext = (f.name.split('.').pop() || '').toLowerCase()
      if (!f.name.includes('.') || !limits.types.includes(ext)) return t.msgType.replace('{soubor}', f.name)
      if (f.size > limits.maxFile) return t.msgSize.replace('{soubor}', f.name)
      total += f.size
    }
    return total > limits.maxTotal ? t.msgTotal : ''
  }

  const update = (list: File[]) => {
    const seen = new Set<string>()
    const next = list.filter((f) => {
      const k = f.name + '|' + f.size
      if (seen.has(k)) return false
      seen.add(k)
      return true
    })
    setFiles(next)
    setFileError(problem(next))
  }

  // po odpovědi serveru fokus na první chybu
  useEffect(() => {
    if (!state.formError) return
    const bad = formRef.current?.querySelector<HTMLElement>('[aria-invalid="true"]')
    if (bad) bad.focus()
    else alertRef.current?.focus()
  }, [state])

  const onSubmit = async (e: FormEvent<HTMLFormElement>) => {
    e.preventDefault()
    const form = e.currentTarget
    const p = problem(files)
    if (p) {
      setFileError(p)
      inputRef.current?.focus()
      return
    }
    const bad = form.querySelector<HTMLInputElement>(':invalid')
    if (bad) {
      bad.setAttribute('aria-invalid', 'true')
      bad.focus()
      bad.reportValidity?.()
      return
    }
    const fd = new FormData(form)
    fd.delete('soubory')
    if (files.length && blob) {
      setUploading(true)
      try {
        const { upload } = await import('@vercel/blob/client')
        for (const f of files) {
          const safe = f.name.replace(/[^\w.\-]+/g, '_').slice(-120)
          const r = await upload(`poptavky/tmp/${safe}`, f, { access: 'public', handleUploadUrl: '/api/poptavka/upload' })
          fd.append('blob', JSON.stringify({ url: r.url, name: f.name }))
        }
      } catch {
        setUploading(false)
        setFileError(t.msgUpload)
        inputRef.current?.focus()
        return
      }
      setUploading(false)
    } else {
      for (const f of files) fd.append('soubory', f)
    }
    startTransition(() => action(fd))
  }

  const fileErr = fileError || err.files || ''
  const busy = pending || uploading

  const field = (name: keyof InquiryState['values'], id: string, label: string, type: string, o: { required?: boolean; autocomplete?: string; wide?: boolean } = {}) => {
    const e = err[name]
    const common = {
      id, name, required: o.required, 'aria-required': o.required ? ('true' as const) : undefined, autoComplete: o.autocomplete,
      'aria-invalid': e ? ('true' as const) : undefined, 'aria-describedby': e ? `${id}-err` : undefined,
      defaultValue: String(v[name] ?? ''),
    }
    return (
      <div className={`field${o.wide ? ' field--wide' : ''}`}>
        <label htmlFor={id} className="mono-sm">{label}{o.required && <> <span className="req" aria-hidden="true">*</span></>}</label>
        {type === 'textarea' ? <textarea {...common} rows={5} maxLength={10000} key={`${id}-${state.started}`} /> : <input type={type} {...common} maxLength={200} key={`${id}-${state.started}`} />}
        {e && <p className="field-error" id={`${id}-err`}>{e}</p>}
      </div>
    )
  }

  return (
    <section id="poptavka" className="panel inquiry" aria-labelledby="poptavka-nadpis">
      <div className="panel-head mono-sm"><span>{t.head_left}</span><span>{t.head_right}</span></div>
      <form ref={formRef} className="inquiry-form" action={action} onSubmit={onSubmit} noValidate data-inquiry>
        <h2 id="poptavka-nadpis" className="h2-detail field--wide">{t.title}</h2>
        {state.formError && <div ref={alertRef} className="form-alert field--wide" role="alert" tabIndex={-1}>{state.formError}</div>}
        <input type="hidden" name="started" value={state.started} />
        <div className="hp" aria-hidden="true">
          <label htmlFor="k-web">Nevyplňujte</label>
          <input type="text" id="k-web" name="web" tabIndex={-1} autoComplete="off" />
        </div>
        {field('company', 'k-firma', t.f_company, 'text', { required: true, autocomplete: 'organization' })}
        {field('name', 'k-jmeno', t.f_name, 'text', { required: true, autocomplete: 'name' })}
        {field('email', 'k-email', t.f_email, 'email', { required: true, autocomplete: 'email' })}
        {field('phone', 'k-tel', t.f_phone, 'tel', { autocomplete: 'tel' })}
        {field('quantity', 'k-ks', t.f_quantity, 'text')}
        {field('deadline', 'k-termin', t.f_deadline, 'text')}
        {field('message', 'k-popis', t.f_message, 'textarea', { required: true, wide: true })}

        <div className="field--wide">
          <label htmlFor="k-soubor" className={`dropzone${over ? ' is-over' : ''}`} data-dropzone
            onDragEnter={(e: DragEvent) => { e.preventDefault(); setOver(true) }}
            onDragOver={(e: DragEvent) => { e.preventDefault(); setOver(true) }}
            onDragLeave={() => setOver(false)}
            onDrop={(e: DragEvent) => { e.preventDefault(); setOver(false); if (e.dataTransfer?.files.length) update(files.concat(Array.from(e.dataTransfer.files))) }}>
            <span className="dropzone-main">
              <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#141517" strokeWidth="1.6" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true" focusable="false"><path d="M12 16V4" /><path d="M7 9l5-5 5 5" /><path d="M4 16v3a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-3" /></svg>
              <span className="dropzone-text">
                <span className="strong">{t.files_title}</span>
                <span className="mono-sm">{t.files_formats}</span>
              </span>
            </span>
            <span className="mono dropzone-btn">{t.files_button}</span>
            <input ref={inputRef} id="k-soubor" className="file-input" type="file" name="soubory" multiple accept={'.' + limits.types.join(',.')}
              aria-describedby={'k-soubor-limits' + (fileErr ? ' k-soubor-err' : '')} aria-invalid={fileErr ? 'true' : undefined}
              onChange={(e) => { const picked = Array.from(e.target.files ?? []); e.target.value = ''; update(files.concat(picked)) }} />
          </label>
          <p className="files-limits" id="k-soubor-limits">{t.files_limits}</p>
          <ul className="file-list" aria-live="polite">
            {files.map((f, i) => (
              <li key={f.name + f.size}>
                <span className="file-name">{f.name} <span className="file-size">{fmt(f.size)}</span></span>
                <button type="button" aria-label={`${t.msgRemove}: ${f.name}`} onClick={() => { update(files.filter((_, j) => j !== i)); inputRef.current?.focus() }}>{t.msgRemove}</button>
              </li>
            ))}
          </ul>
          <p className="field-error" id="k-soubor-err" hidden={!fileErr}>{fileErr}</p>
        </div>

        <div className="field--wide consent">
          <input type="checkbox" id="k-souhlas" name="consent" value="1" required aria-required="true" defaultChecked={Boolean(v.consent)} key={`consent-${state.started}`}
            aria-invalid={err.consent ? 'true' : undefined} aria-describedby={err.consent ? 'k-souhlas-err' : undefined} />
          <label htmlFor="k-souhlas">{t.consent} <a href="/ochrana-osobnich-udaju" target="_blank">{t.consent_link}</a>. <span className="req" aria-hidden="true">*</span></label>
          {err.consent && <p className="field-error" id="k-souhlas-err">{err.consent}</p>}
        </div>

        <div className="field--wide form-foot">
          {t.note !== '' && <span className="form-note">{t.note.split('\n').map((l, i) => <span key={i}>{i > 0 && <br />}{l}</span>)}</span>}
          <button type="submit" className="btn btn-dark btn-lg" disabled={busy} aria-busy={busy || undefined}>{uploading ? t.uploading : t.submit}</button>
        </div>
      </form>
    </section>
  )
}
