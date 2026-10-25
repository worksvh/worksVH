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

/** Název podstránky pro drobečkovou navigaci: popisek z menu, jinak titulek stránky. */
function crumbName(c: Content, path: string, title: string): string {
  const mi = (c.setting('menu').items as Data[]).find((m) => navActive(m.url, path))
  const label = mi ? c.ph(mi.label) : ''
  return label !== '' ? label : c.ph(title)
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

  const home = appUrl() + '/'
  const okVal = (v: unknown) => String(v ?? '').trim() !== '' && !hasPlaceholder(v)
  const biz: Data = {
    '@type': 'LocalBusiness',
    '@id': home + '#firma',
    name: company.name,
    url: home,
    telephone: String(company.phone).replace(/\s+/g, ''),
    address: {
      '@type': 'PostalAddress',
      streetAddress: company.street,
      postalCode: company.zip,
      addressLocality: company.city,
      addressCountry: 'CZ',
    },
  }
  if (okVal(seoDef.description)) biz.description = seoDef.description
  const logoUrl = imageAbsUrl(c, c.setting('display').logo) ?? appUrl() + '/assets/img/vh-works-logo.svg'
  biz.logo = logoUrl
  biz.image = imageAbsUrl(c, seoDef.image) ?? logoUrl
  if (okVal(company.email)) biz.email = company.email
  if (okVal(company.hours_schema)) biz.openingHours = company.hours_schema
  if (okVal(company.ico)) biz.taxID = company.ico
  if (okVal(company.dic)) biz.vatID = company.dic
  if (okVal(company.operator)) biz.legalName = company.operator
  const geo = String(company.geo ?? '').match(/^\s*(-?\d+(?:\.\d+)?)\s*[,;\s]\s*(-?\d+(?:\.\d+)?)\s*$/)
  if (geo) biz.geo = { '@type': 'GeoCoordinates', latitude: Number(geo[1]), longitude: Number(geo[2]) }
  if (okVal(company.map_url)) biz.hasMap = company.map_url
  const sameAs = String(company.profiles ?? '').split(/\s+/).filter((u) => /^https?:\/\/\S+$/.test(u))
  if (sameAs.length) biz.sameAs = sameAs

  const graph: Data[] = [
    biz,
    { '@type': 'WebSite', '@id': home + '#web', url: home, name: company.name, inLanguage: 'cs', publisher: { '@id': biz['@id'] } },
  ]
  if (path !== '/') {
    graph.push({
      '@type': 'BreadcrumbList',
      itemListElement: [
        { '@type': 'ListItem', position: 1, name: company.name, item: home },
        { '@type': 'ListItem', position: 2, name: crumbName(c, path, title), item: canonical },
      ],
    })
  }
  if (pageKey === 'services') {
    for (const s of c.items('services')) {
      const d = s.data as Data
      if (!okVal(d.title)) continue
      const text = String(d.description ?? '').replace(/<[^>]*>/g, ' ').replace(/&nbsp;/g, ' ').replace(/&amp;/g, '&').replace(/\s+/g, ' ').trim()
      graph.push({ '@type': 'Service', name: d.title, ...(okVal(text) ? { description: text } : {}), provider: { '@id': biz['@id'] }, areaServed: 'CZ' })
    }
  }
  const json = JSON.stringify({ '@context': 'https://schema.org', '@graph': graph }).replace(/</g, '\\u003c').replace(/&/g, '\\u0026')

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

function Footer({ c, path }: { c: Content; path: string }) {
  const co = c.setting('company')
  const menu = c.setting('menu')
  const logo = c.setting('display').logo
  const lm = c.media(logo?.media)
  const ph = c.ph
  const email = ph(co.email)
  const phone = ph(co.phone)
  const city = ph(`${co.zip} ${co.city}`.trim())
  const hours = ph(co.hours)
  const mapUrl = String(co.map_url ?? '').trim()
  const operator = ph(co.operator)
  const credit = ph(menu.credit_name)
  const pages = [...(menu.items as Data[]), { label: menu.button, url: menu.button_url }].filter((p) => ph(p.label) !== '')
  return (
    <footer className="site-footer">
      <div className="wrap footer-grid">
        <div className="footer-brand">
          <a href="/" className="footer-logo">
            {lm ? (
              // eslint-disable-next-line @next/next/no-img-element
              <img src={mediaUrl(lm.filename)} alt={ph(co.name)} height={40} className="is-custom" />
            ) : (
              // eslint-disable-next-line @next/next/no-img-element
              <img src="/assets/img/vh-works-logo-white.svg" alt={ph(co.name)} width={242} height={40} />
            )}
          </a>
          {ph(menu.footer_text) !== '' && <p>{ph(menu.footer_text)}</p>}
        </div>

        <div className="footer-col">
          {ph(menu.footer_h_contact) !== '' && <p className="footer-h">{ph(menu.footer_h_contact)}</p>}
          <ul>
            {phone !== '' && <li><a href={telHref(co.phone)} className="footer-phone">{phone}</a></li>}
            {email !== '' && <li>{hasPlaceholder(co.email) ? email : <a href={`mailto:${email}`}>{email}</a>}</li>}
            <li>{ph(co.street)}{city !== '' && <><br />{city}</>}</li>
            {hours !== '' && <li className="footer-pre">{hours}</li>}
            {mapUrl !== '' && ph(co.map_link) !== '' && <li><a href={mapUrl} target="_blank" rel="noopener">{ph(co.map_link)}</a></li>}
          </ul>
        </div>

        <nav className="footer-col" aria-label={ph(menu.footer_h_pages) || undefined}>
          {ph(menu.footer_h_pages) !== '' && <p className="footer-h">{ph(menu.footer_h_pages)}</p>}
          <ul>
            {pages.map((p, i) => (
              <li key={i}><a href={p.url} aria-current={navActive(p.url, path) ? 'page' : undefined}>{ph(p.label)}</a></li>
            ))}
          </ul>
        </nav>

        <div className="footer-col">
          {ph(menu.footer_h_billing) !== '' && <p className="footer-h">{ph(menu.footer_h_billing)}</p>}
          <ul>
            {operator !== '' && <li>{ph(co.operator_label) !== '' && <span className="footer-label">{ph(co.operator_label)}</span>}{operator}{ph(co.operator_address) !== '' && <><br />{ph(co.operator_address)}</>}</li>}
            {ph(co.ico) !== '' && <li>{ph(co.ico_label)} {ph(co.ico)}</li>}
            {ph(co.dic) !== '' && <li>{ph(co.dic_label)} {ph(co.dic)}</li>}
            {ph(co.register_note) !== '' && <li className="footer-note">{ph(co.register_note)}</li>}
          </ul>
        </div>
      </div>

      <div className="wrap"><div className="footer-bottom">
        <span>© {new Date().getFullYear()} {ph(co.name)}{ph(menu.copyright) !== '' && <>. {ph(menu.copyright)}</>}</span>
        <span className="footer-bottom-links">
          {ph(menu.privacy_link) !== '' && <a href="/ochrana-osobnich-udaju">{ph(menu.privacy_link)}</a>}
          {credit !== '' && (
            <span>
              {ph(menu.credit_label)}{' '}
              {String(menu.credit_url ?? '').trim() !== '' ? <a href={menu.credit_url} target="_blank" rel="noopener">{credit}</a> : credit}
            </span>
          )}
        </span>
      </div></div>
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
      <Footer c={c} path={path} />
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
