import type { Content, Data } from '@/lib/content'
import { telHref } from '@/lib/text'

/** Modrý blok s výzvou: nadpis a telefon vlevo, karta pro přiložení výkresu vpravo. */
export function Cta({ c, cta, large = false }: { c: Content; cta: Data; large?: boolean }) {
  const phone = String(c.setting('company').phone)

  // štítek „PDF · STEP · DWG · NEBO FOTKA NÁČRTU“ → „PDF, STEP, DWG nebo fotka náčrtu“
  let formats = ''
  const segs = c.ph(cta.label).split(/\s*·\s*/u).filter((s) => s !== '')
  segs.forEach((seg, i) => {
    const m = /^(nebo|or)\s+(.+)$/iu.exec(seg)
    if (m) formats += ' ' + m[1].toLowerCase() + ' ' + m[2].toLowerCase()
    else formats += (i > 0 ? ', ' : '') + ([...seg].length <= 5 ? seg : seg.toLowerCase())
  })
  formats = formats.trim()

  return (
    <section id="kontakt" className={`wrap cta-section${large ? ' cta-section--large' : ''}`}>
      <div className={`cta${large ? ' cta--large' : ''}`}>
        <div className="cta-text">
          <h2>{c.ph(cta.title)}</h2>
          <p className="cta-call">
            {c.ph(cta.phone_label) !== '' && <>{c.ph(cta.phone_label)} </>}
            <a className="cta-phone" href={telHref(phone)}>{phone}</a>
          </p>
        </div>
        <a className="cta-drop" href={cta.button_url}>
          <span className="cta-drop-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" width="22" height="22"><path d="M12 15V4m0 0L7.5 8.5M12 4l4.5 4.5M5 14v4.5A1.5 1.5 0 0 0 6.5 20h11a1.5 1.5 0 0 0 1.5-1.5V14" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" /></svg>
          </span>
          {c.ph(cta.drop_title) !== '' && <span className="cta-drop-title">{c.ph(cta.drop_title)}</span>}
          {formats !== '' && <span className="cta-drop-formats">{formats}</span>}
          <span className="btn cta-drop-btn">{c.ph(cta.button)} <span aria-hidden="true">→</span></span>
        </a>
      </div>
    </section>
  )
}
