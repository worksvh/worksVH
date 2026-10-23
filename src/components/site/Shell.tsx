import type { ReactNode } from 'react'
import type { Content, Data } from '@/lib/content'
import { hasPlaceholder, telHref, phoneShort } from '@/lib/text'
import { mediaUrl } from '@/lib/storage'
import { imageAbsUrl } from './Img'

const appUrl = () => (process.env.APP_URL || '').replace(/\/$/, '')

function navActive(url: string, path: string): boolean {
  let u = url
  try { u = new URL(url, 'http://x').pathname } catch { /* nic */ }
  u = '/' + u.replace(/^\/+|\/+$/g, '')
  return u === path || (u !== '/' && path.startsWith(u + '/'))
}

/** Hlavička stránky (title, popis, sdílení, strukturovaná data) – React je přesune do <head>. */
function Head({ c, pageKey, path, seo, noindex }: { c: Content; pageKey: string; path: string; seo: Data; noindex: boolean }) {
  const company = c.setting('company')
  const seoDef = c.setting('seo')
  const title = String(seo.title ?? '').trim() || company.name
  const fullTitle = title + (pageKey === 'home' && title.includes(company.name) ? '' : String(seoDef.title_suffix))
  const desc = String(seo.description ?? '').trim() || String(seoDef.description)
  const og = imageAbsUrl(c, seo.image) ?? imageAbsUrl(c, seoDef.image)
  const canonical = appUrl() + (path === '/' ? '/' : path)

  const ld: Data = {
    '@context': 'https://schema.org',
    '@type': 'LocalBusiness',
    name: company.name,
    url: appUrl() + '/',
    telephone: String(company.phone).replace(/\s+/g, ''),
    address: {
      '@type': 'PostalAddress',
      streetAddress: company.street,
      postalCode: company.zip,
      addressLocality: company.city,
      addressCountry: 'CZ',
    },
  }
  const okVal = (v: unknown) => v !== '' && !hasPlaceholder(v)
  if (okVal(company.email)) ld.email = company.email
  if (okVal(company.hours_schema)) ld.openingHours = company.hours_schema
  if (okVal(company.ico)) ld.taxID = company.ico
  if (okVal(company.dic)) ld.vatID = company.dic
  if (okVal(company.operator)) ld.legalName = company.operator
  const json = JSON.stringify(ld).replace(/</g, '\\u003c').replace(/&/g, '\\u0026')

  return (
    <>
      <title>{c.ph(fullTitle)}</title>
      {desc !== '' && <meta name="description" content={c.ph(desc)} />}
      {noindex ? <meta name="robots" content="noindex" /> : <link rel="canonical" href={canonical} />}
      <meta property="og:type" content="website" />
      <meta property="og:locale" content="cs_CZ" />
      <meta property="og:site_name" content={company.name} />
      <meta property="og:title" content={c.ph(title)} />
      {desc !== '' && <meta property="og:description" content={c.ph(desc)} />}
      <meta property="og:url" content={canonical} />
      {og && <meta property="og:image" content={og} />}
      {og && <meta name="twitter:card" content="summary_large_image" />}
      {pageKey !== '404' && <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: json }} />}
    </>
  )
}

function Header({ c, path, pageKey }: { c: Content; path: string; pageKey: string }) {
  const menu = c.setting('menu')
  const company = c.setting('company')
  const logo = c.setting('display').logo
  const lm = c.media(logo?.media)
  const logoAlt = String(logo?.alt ?? '').trim() || 'VH Works – Tooling, Engineering, Precision'
  const isContact = pageKey === 'contact'
  return (
    <header className="site-header wrap">
      <a className="logo" href="/" aria-current={path === '/' ? 'page' : undefined}>
        {lm ? (
          // eslint-disable-next-line @next/next/no-img-element
          <img src={mediaUrl(lm.filename)} alt={logoAlt} height={46} width={lm.width && lm.height ? Math.round((46 * lm.width) / lm.height) : undefined} />
        ) : (
          // eslint-disable-next-line @next/next/no-img-element
          <img src="/assets/img/vh-works-logo.svg" alt={logoAlt} width={278} height={46} />
        )}
      </a>
      <nav className="main-nav" aria-label={c.ph(menu.aria || 'Hlavní menu')}>
        {(menu.items as Data[]).map((mi, i) => {
          const on = navActive(mi.url, path)
          return (
            <a key={i} href={mi.url} aria-current={on ? 'page' : undefined} className={on ? 'is-active' : undefined}>{c.ph(mi.label)}</a>
          )
        })}
        <a className="nav-phone" href={telHref(company.phone)}>{phoneShort(company.phone)}</a>
        <a className={`btn btn-dark btn-sm${isContact ? ' is-active' : ''}`} href={menu.button_url} aria-current={isContact ? 'page' : undefined}>{c.ph(menu.button)}</a>
      </nav>
    </header>
  )
}

function Footer({ c }: { c: Content }) {
  const co = c.setting('company')
  const menu = c.setting('menu')
  const ph = c.ph
  const addr = [ph(co.street), ph(`${co.zip} ${co.city}`.trim())].filter(Boolean).join(', ')
  const left = [ph(co.name), addr].filter(Boolean).join(' · ')
  const email = ph(co.email)
  const operator = [ph(co.operator), ph(co.operator_address)].filter(Boolean).join(', ')
  const legal = [
    operator !== '' ? (co.operator_label !== '' ? `${ph(co.operator_label)}: ` : '') + operator : '',
    ph(co.ico) !== '' ? `${ph(co.ico_label)} ${ph(co.ico)}` : '',
    ph(co.dic) !== '' ? `${ph(co.dic_label)} ${ph(co.dic)}` : '',
  ].filter(Boolean)
  const note = ph(co.register_note)
  const credit = ph(menu.credit_name)
  return (
    <footer className="site-footer wrap">
      <div className="footer-row">
        <span>{left}</span>
        <span className="footer-right">
          {email !== '' && (hasPlaceholder(co.email) ? <span className="mono">{email}</span> : <a href={`mailto:${email}`}>{email}</a>)}
          <a href="/ochrana-osobnich-udaju">{ph(menu.privacy_link)}</a>
        </span>
      </div>
      {(legal.length > 0 || note !== '' || credit !== '') && (
        <div className="footer-row footer-legal">
          <span>{legal.join(' · ')}{note !== '' && <>{legal.length ? '. ' : ''}{note}</>}</span>
          {credit !== '' && (
            <span>
              {ph(menu.credit_label)}{' '}
              {String(menu.credit_url ?? '').trim() !== '' ? <a href={menu.credit_url} target="_blank" rel="noopener">{credit}</a> : credit}
            </span>
          )}
        </div>
      )}
    </footer>
  )
}

export function Shell({ c, pageKey, path, seo, noindex = false, children }: {
  c: Content
  pageKey: string
  path: string
  seo: Data
  noindex?: boolean
  children: ReactNode
}) {
  const display = c.setting('display')
  return (
    <div className={`page-${pageKey}`}>
      <Head c={c} pageKey={pageKey} path={path} seo={seo} noindex={noindex || pageKey === 'thanks' || c.isPreview} />
      <a className="skip" href="#obsah">{c.ph(display.skip_link || 'Přeskočit na obsah')}</a>
      {c.isPreview && <div className="preview-bar" role="status">Náhled neuloženého obsahu. Změny se uloží až v administraci tlačítkem Uložit.</div>}
      <Header c={c} path={path} pageKey={pageKey} />
      <main id="obsah" tabIndex={-1}>{children}</main>
      <Footer c={c} />
      {display.cookie_bar && c.ph(display.cookie_text) !== '' && (
        <div className="cookie-bar" role="region" aria-label="Cookies" data-cookie-bar hidden>
          <p>
            {c.ph(display.cookie_text)}
            {c.ph(display.cookie_link) !== '' && <> <a href="/ochrana-osobnich-udaju">{c.ph(display.cookie_link)}</a></>}
          </p>
          <button type="button" className="btn btn-dark" data-cookie-ok>{c.ph(display.cookie_button || 'OK')}</button>
        </div>
      )}
    </div>
  )
}
