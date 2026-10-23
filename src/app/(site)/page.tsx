import { Fragment } from 'react'
import { siteContent, isPreview, type SearchParams } from '@/lib/site'
import type { Data } from '@/lib/content'
import { Shell } from '@/components/site/Shell'
import { Img } from '@/components/site/Img'
import { Cta } from '@/components/site/Cta'
import { Ruler } from '@/components/site/Ruler'
import { PartSvg } from '@/components/site/PartSvg'
import { Lines } from '@/components/site/bits'

export default async function Home({ searchParams }: { searchParams: SearchParams }) {
  const c = await siteContent(await isPreview(searchParams))
  const page = c.page('home')
  const { hero, part } = page
  const pins = part.pins as Data[]
  const n = pins.length
  const css = pins.map((p, i) => {
    const x = Math.max(0, Math.min(100, Number(p.x) || 0))
    const y = Math.max(0, Math.min(100, Number(p.y) || 0))
    return `#pin-${i + 1}{left:${x}%;top:${y}%}`
  }).join('')
  const tags = (hero.tags as string[]).map(c.ph).filter((t) => t !== '')
  const posLabel = c.ph(part.pos_label)
  const works = c.items('works').filter((w) => w.data.on_home).slice(0, 3)
  const cats = c.itemsById('categories')
  const a = pins[0]

  return (
    <Shell c={c} pageKey="home" path="/" seo={page.seo}>
      <style href="home-pins" precedence="default">{css}</style>
      <section className="wrap home-hero">
        {tags.length > 0 && (
          <div className="tags mono">
            {tags.map((t, i) => <Fragment key={i}>{i > 0 && <span aria-hidden="true">·</span>}<span>{t}</span></Fragment>)}
          </div>
        )}
        <h1 className="h1-home">{c.ph(hero.title)}</h1>
        {c.ph(hero.lead) !== '' && <p className="lead"><Lines text={c.ph(hero.lead)} /></p>}
      </section>

      <section id="sluzby" className="wrap part-section" aria-label={c.ph(part.aria)}>
        <div className="panel">
          <div className="panel-head mono-sm"><span>{c.ph(part.head_left)}</span><span className="js-only">{c.ph(part.head_right)}</span></div>
          <div className="part-body" data-part>
            <div className="part-figure">
              <PartSvg />
              {pins.map((p, i) => (
                <button key={i} type="button" className="pin" id={`pin-${i + 1}`} data-index={i}
                  aria-label={(posLabel !== '' ? posLabel + ' ' : '') + (i + 1) + ': ' + c.ph(p.title)}
                  aria-pressed={i === 0 ? 'true' : 'false'} aria-controls="part-detail">{i + 1}</button>
              ))}
            </div>

            {a && (
              <div className="part-detail" id="part-detail">
                <div aria-live="polite" className="part-detail-live">
                  <div className="mono mono-blue" data-f="pos">{`${posLabel} 1 / ${n}`.trim()}</div>
                  <h2 className="h2-detail" data-f="title">{c.ph(a.title)}</h2>
                  <p className="part-text" data-f="text"><Lines text={c.ph(a.text)} /></p>
                  <div className="part-spec mono" data-f="spec">{c.ph(a.spec)}</div>
                </div>
                <div className="chips">
                  {pins.map((p, i) => (
                    <button key={i} type="button" className="chip" data-index={i} aria-pressed={i === 0 ? 'true' : 'false'} aria-controls="part-detail">{c.ph(p.title)}</button>
                  ))}
                </div>
              </div>
            )}

            <ol className="part-list">
              {pins.map((p, i) => (
                <li key={i}>
                  <div className="mono mono-blue" data-f="pos">{`${posLabel} ${i + 1} / ${n}`.trim()}</div>
                  <h2 className="h2-detail" data-f="title">{c.ph(p.title)}</h2>
                  <p className="part-text" data-f="text"><Lines text={c.ph(p.text)} /></p>
                  {c.ph(p.spec) !== '' && <div className="part-spec mono" data-f="spec">{c.ph(p.spec)}</div>}
                </li>
              ))}
            </ol>
          </div>
        </div>
      </section>

      <Ruler />

      <section id="prace" className="wrap home-work">
        <div className="section-head">
          <h2 className="h2">{c.ph(page.work.title)}</h2>
          {c.ph(page.work.link) !== '' && <a className="link" href="/prace">{c.ph(page.work.link)}</a>}
        </div>
        {works.length > 0 && (
          <div className="home-work-grid">
            {(() => {
              const fig = (i: number) => {
                const w = works[i]
                const cat = cats.get(Number(w.data.category))
                return (
                  <figure key={w.id} className={`work-fig${i === 0 ? ' work-fig--big' : ''}`}>
                    <div className={`photo ${i === 0 ? 'photo--home-big' : 'photo--home-small'}`}>
                      <Img c={c} value={w.data.photo} sizes={i === 0 ? '(min-width: 900px) 58vw, 100vw' : '(min-width: 900px) 38vw, 100vw'} />
                      {cat && <span className="photo-badge">{c.ph(cat.data.label)}</span>}
                    </div>
                    <figcaption className="fig-cap">{c.ph(w.data.title)}</figcaption>
                  </figure>
                )
              }
              return (
                <>
                  {fig(0)}
                  {works.length > 1 && <div className="home-work-side">{works.slice(1).map((_, j) => fig(j + 1))}</div>}
                </>
              )
            })()}
          </div>
        )}
      </section>

      <Cta c={c} cta={c.ctaFor(page.cta)} large />
    </Shell>
  )
}
