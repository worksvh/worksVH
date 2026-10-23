import { Fragment, type ReactNode } from 'react'
import type { Content } from '@/lib/content'
import { telHref, phoneShort } from '@/lib/text'

/** Text s konci řádků jako <br>. */
export function Lines({ text }: { text: string }) {
  const parts = text.split(/\r?\n/)
  return <>{parts.map((p, i) => <Fragment key={i}>{i > 0 && <br />}{p}</Fragment>)}</>
}

/** Nahradí {telefon} odkazem na telefon. */
export function WithPhone({ c, text }: { c: Content; text: string }) {
  const phone = String(c.setting('company').phone ?? '')
  const parts = text.split('{telefon}')
  return (
    <>
      {parts.map((p, i) => (
        <Fragment key={i}>
          {i > 0 && <a href={telHref(phone)} className="strong">{phoneShort(phone)}</a>}
          {p}
        </Fragment>
      ))}
    </>
  )
}

/** Bezpečné HTML z editoru (sanitizované při uložení). */
export function Rich({ html, className, as = 'div' }: { html: string; className?: string; as?: 'div' }) {
  const Tag = as
  return <Tag className={className} dangerouslySetInnerHTML={{ __html: html }} />
}

export function Eyebrow({ c, text }: { c: Content; text: unknown }): ReactNode {
  const t = c.ph(text)
  return t !== '' ? <div className="mono mono-blue eyebrow">{t}</div> : null
}
