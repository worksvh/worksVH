import type { ReactNode } from 'react'
import { schema } from '@/lib/schema'
import { val } from '@/lib/db'
import { csrfToken, type User } from '@/lib/auth'
import { takeFlash } from '@/lib/admin'

function NavLink({ href, label, on, extra }: { href: string; label: string; on: boolean; extra?: ReactNode }) {
  return <a href={href} aria-current={on ? 'page' : undefined} className={on ? 'is-active' : undefined}>{label}{extra}</a>
}

export async function Csrf() {
  return <input type="hidden" name="_csrf" value={await csrfToken()} />
}

export async function AdminShell({ user, title, nav, children }: { user: User; title: string; nav: string; children: ReactNode }) {
  const [newCount, flashes] = await Promise.all([
    val<number>("SELECT COUNT(*) FROM inquiries WHERE status = 'new'").then(Number),
    takeFlash(),
  ])
  return (
    <>
      <title>{`${title} – Administrace VH Works`}</title>
      <a className="skip" href="#a-main">Přeskočit na obsah</a>
      <div className="a-shell">
        <aside className="a-side">
          <div className="a-brand">
            {/* eslint-disable-next-line @next/next/no-img-element */}
            <a href="/admin"><img src="/assets/img/vh-works-logo.svg" alt="VH Works" width={169} height={28} /></a>
            <span className="a-brand-sub">Administrace</span>
          </div>
          <nav className="a-nav" aria-label="Administrace">
            <NavLink href="/admin" label="Přehled" on={nav === 'dashboard'} />
            <details className="a-nav-group" open={nav.startsWith('page:')}>
              <summary>Stránky</summary>
              {Object.entries(schema.pages).map(([k, ps]) => <NavLink key={k} href={`/admin/stranky/${k}`} label={ps.label} on={nav === `page:${k}`} />)}
            </details>
            {['services', 'works', 'categories', 'machines', 'people'].map((ck) => {
              const cs = schema.collections[ck]
              return <NavLink key={ck} href={`/admin/${cs.path}`} label={cs.label} on={nav === `col:${ck}`} />
            })}
            <NavLink href="/admin/poptavky" label="Poptávky" on={nav === 'inquiries'}
              extra={newCount ? <> <span className="pill">{newCount}<span className="sr"> nových</span></span></> : null} />
            <NavLink href="/admin/media" label="Média" on={nav === 'media'} />
            <NavLink href="/admin/nastaveni" label="Nastavení" on={nav === 'settings'} />
          </nav>
          <div className="a-side-foot">
            <a href="/" target="_blank" rel="noopener">Otevřít web ↗</a>
            <span className="a-user">{user.email}</span>
            <form method="post" action="/admin/api/logout"><Csrf /><button type="submit" className="linkish">Odhlásit</button></form>
          </div>
        </aside>
        <main className="a-main" id="a-main" tabIndex={-1}>
          {flashes.map((f, i) => (
            <div key={i} className={`toast toast-${f.type}`} role={f.type === 'error' ? 'alert' : 'status'} data-toast>{f.msg}</div>
          ))}
          {children}
        </main>
      </div>
      <dialog className="picker" id="media-picker" aria-labelledby="picker-title">
        <div className="picker-head">
          <h2 id="picker-title">Vyberte obrázek</h2>
          <button type="button" className="icon-btn" data-picker-close aria-label="Zavřít">×</button>
        </div>
        <div className="picker-upload">
          <label htmlFor="picker-file" className="btn-small">Nahrát nový obrázek<input type="file" id="picker-file" accept=".jpg,.jpeg,.png,.webp,.svg" className="sr" data-picker-file /></label>
          <span className="help">JPG, PNG nebo WebP do 10 MB<span data-picker-svg-note hidden>, u loga i SVG</span>.</span>
          <input type="search" placeholder="Hledat…" aria-label="Hledat v obrázcích" data-picker-search />
        </div>
        <p className="err" data-picker-error hidden></p>
        <div className="picker-grid" data-picker-grid role="list"></div>
      </dialog>
    </>
  )
}

export async function AuthBox({ title, children }: { title: string; children: ReactNode }) {
  const flashes = await takeFlash()
  return (
    <div className="auth-page">
      <title>{`${title} – Administrace VH Works`}</title>
      <main className="auth-box">
        {/* eslint-disable-next-line @next/next/no-img-element */}
        <img src="/assets/img/vh-works-logo.svg" alt="VH Works" width={217} height={36} className="auth-logo" />
        {flashes.map((f, i) => <div key={i} className={`toast toast-${f.type}`} role="status">{f.msg}</div>)}
        {children}
      </main>
    </div>
  )
}

export function SettingsTabs({ tab }: { tab: string }) {
  return (
    <nav className="tabs" aria-label="Části nastavení">
      {Object.entries(schema.settings).map(([k, ss]) => (
        <a key={k} href={`/admin/nastaveni/${k}`} aria-current={tab === k ? 'page' : undefined}>{ss.label}</a>
      ))}
      <a href="/admin/nastaveni/uzivatele" aria-current={tab === 'uzivatele' ? 'page' : undefined}>Uživatelé</a>
      <a href="/admin/nastaveni/zaloha" aria-current={tab === 'zaloha' ? 'page' : undefined}>Záloha</a>
    </nav>
  )
}
