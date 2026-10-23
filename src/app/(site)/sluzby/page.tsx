import { siteContent, isPreview, type SearchParams } from '@/lib/site'
import type { Data } from '@/lib/content'
import { Shell } from '@/components/site/Shell'
import { Cta } from '@/components/site/Cta'
import { Eyebrow, Rich, WithPhone } from '@/components/site/bits'

export default async function Services({ searchParams }: { searchParams: SearchParams }) {
  const c = await siteContent(await isPreview(searchParams))
  const page = c.page('services')
  const { hero, toc } = page
  const pos = c.ph(toc.pos_label)
  const services = c.items('services')

  return (
    <Shell c={c} pageKey="services" path="/sluzby" seo={page.seo}>
      <section className="wrap page-hero">
        <Eyebrow c={c} text={hero.label} />
        <h1 className="h1 h1--narrow">{c.ph(hero.title)}</h1>
      </section>

      <div className="wrap services-layout">
        <nav id="seznam-sluzeb" className="services-toc" aria-label={c.ph(toc.aria)}>
          {services.map((s, i) => (
            <a key={s.id} href={`#${s.slug}`}><span className="mono mono-blue">{i + 1}</span>{c.ph(s.data.title)}</a>
          ))}
          {c.ph(toc.note) !== '' && <p className="services-note"><WithPhone c={c} text={c.ph(toc.note)} /></p>}
        </nav>

        <div className="services-cards">
          {services.map((s, i) => {
            const d = s.data
            const pairs = d.pairs as Data[]
            return (
              <article key={s.id} id={s.slug} className="card service-card">
                <div className="card-top mono-sm"><span>{`${pos} ${i + 1}`.trim()}</span>{c.ph(d.tag) !== '' && <span>{c.ph(d.tag)}</span>}</div>
                <h2 className="h2-card">{c.ph(d.title)}</h2>
                <Rich className="rich service-desc" html={c.ph(d.description)} />
                {pairs.length > 0 && (
                  <dl className={`pairs pairs--${pairs.length}`}>
                    {pairs.map((p, j) => <div key={j}><dt className="mono-sm">{c.ph(p.label)}</dt><dd>{c.ph(p.value)}</dd></div>)}
                  </dl>
                )}
              </article>
            )
          })}
        </div>
      </div>

      <Cta c={c} cta={c.ctaFor(page.cta)} />
    </Shell>
  )
}
