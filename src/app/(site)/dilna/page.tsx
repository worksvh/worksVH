import { siteContent, isPreview, type SearchParams } from '@/lib/site'
import type { Data } from '@/lib/content'
import { Shell } from '@/components/site/Shell'
import { Img } from '@/components/site/Img'
import { MapBox } from '@/components/site/MapBox'
import { Eyebrow, Lines } from '@/components/site/bits'

export default async function Workshop({ searchParams }: { searchParams: SearchParams }) {
  const c = await siteContent(await isPreview(searchParams))
  const page = c.page('workshop')
  const { hero, machines: m, people: pp, visit: v } = page
  const co = c.setting('company')
  const machines = c.items('machines')
  const people = c.items('people')
  const team = pp.team as Data[]

  return (
    <Shell c={c} pageKey="workshop" path="/dilna" seo={page.seo}>
      <section className="wrap page-hero page-hero--workshop">
        <div className="workshop-title">
          <Eyebrow c={c} text={hero.label} />
          <h1 className="h1 h1--short">{c.ph(hero.title)}</h1>
        </div>
        {c.ph(hero.story) !== '' && <p className="workshop-story"><Lines text={c.ph(hero.story)} /></p>}
      </section>

      <section id="fotka" className="wrap">
        <div className="photo photo--wide"><Img c={c} value={page.photo.image} sizes="(min-width: 1360px) 1280px, 100vw" lazy={false} /></div>
      </section>

      <section id="stroje" className="wrap section-pad">
        <div className="section-head section-head--tight">
          <h2 className="h2">{c.ph(m.title)}</h2>
          {c.ph(m.status) !== '' && <span className="mono">{c.ph(m.status)}</span>}
        </div>
        <div className="table-frame" tabIndex={0} role="region" aria-label={c.ph(m.title)}>
          <table className="machines">
            <thead><tr>
              <th scope="col" className="mono-sm">{c.ph(m.col_name)}</th>
              <th scope="col" className="mono-sm">{c.ph(m.col_type)}</th>
              <th scope="col" className="mono-sm">{c.ph(m.col_range)}</th>
              <th scope="col" className="mono-sm">{c.ph(m.col_use)}</th>
            </tr></thead>
            <tbody>
              {machines.map((row) => (
                <tr key={row.id}>
                  <th scope="row">{c.ph(row.data.name)}</th>
                  <td>{c.ph(row.data.type)}</td>
                  <td className="mono-td">{c.ph(row.data.range)}</td>
                  <td className="muted">{c.ph(row.data.use)}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </section>

      <section id="lide" className="wrap section-pad people">
        <div className="people-intro">
          <h2 className="h2">{c.ph(pp.title)}</h2>
          {c.ph(pp.text) !== '' && <p className="text-17"><Lines text={c.ph(pp.text)} /></p>}
        </div>
        {people.length > 0 ? (
          <div className="people-cards">
            {people.map((p) => (
              <figure key={p.id} className="person">
                <div className="photo photo--4x5"><Img c={c} value={p.data.portrait} sizes="(min-width: 900px) 25vw, 100vw" /></div>
                <figcaption>
                  <span className="person-name">{c.ph(p.data.name)}</span>
                  {c.ph(p.data.role) !== '' && <span className="mono">{c.ph(p.data.role)}</span>}
                </figcaption>
              </figure>
            ))}
          </div>
        ) : team.length > 0 ? (
          <ul className="team">
            {team.map((t, i) => (
              <li key={i} className="team-item"><span className="team-count">{c.ph(t.count)}</span><span className="team-role">{c.ph(t.role)}</span></li>
            ))}
          </ul>
        ) : null}
      </section>

      <section id="navsteva" className="wrap section-pad section-pad--bottom">
        <div className="panel visit">
          <div className="visit-body">
            <h2 className="h2-detail">{c.ph(v.title)}</h2>
            <p className="text-17">
              {c.ph(co.street)}, {c.ph(`${co.zip} ${co.city}`.trim())}
              {c.ph(co.hours) !== '' && <><br /><Lines text={c.ph(co.hours)} /></>}
              {c.ph(v.parking) !== '' && <><br /><Lines text={c.ph(v.parking)} /></>}
            </p>
            {c.ph(v.button) !== '' && <a className="btn btn-blue" href="/kontakt">{c.ph(v.button)}</a>}
          </div>
          <MapBox c={c} className="map--visit" />
        </div>
      </section>
    </Shell>
  )
}
