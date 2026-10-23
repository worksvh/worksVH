import { siteContent, type SearchParams } from '@/lib/site'
import { Shell } from '@/components/site/Shell'
import { Img } from '@/components/site/Img'
import { Cta } from '@/components/site/Cta'
import { Eyebrow, Lines } from '@/components/site/bits'

export default async function Work({ searchParams }: { searchParams: SearchParams }) {
  const sp = await searchParams
  const c = await siteContent(sp.nahled !== undefined)
  const page = c.page('work')
  const { hero, featured: fl, grid } = page
  const all = c.items('works')
  const featured = all.find((w) => w.data.featured) ?? null
  const works = all.filter((w) => !w.data.featured)
  const categories = c.items('categories', false)
  const catById = c.itemsById('categories')
  const sel = typeof sp.kategorie === 'string' ? sp.kategorie : ''
  const selected = categories.some((k) => k.slug === sel) ? sel : ''
  let visible = 0

  return (
    <Shell c={c} pageKey="work" path="/prace" seo={page.seo}>
      <section className="wrap page-hero page-hero--split">
        <div>
          <Eyebrow c={c} text={hero.label} />
          <h1 className="h1">{c.ph(hero.title)}</h1>
        </div>
        {c.ph(hero.lead) !== '' && <p className="hero-side"><Lines text={c.ph(hero.lead)} /></p>}
      </section>

      {featured && (
        <section id="vybrana-zakazka" className="wrap" aria-label={c.ph(fl.label)}>
          <article className="panel featured">
            <div className="photo photo--featured"><Img c={c} value={featured.data.photo} sizes="(min-width: 900px) 58vw, 100vw" lazy={false} /></div>
            <div className="featured-body">
              <div className="mono-sm mono-blue">{[c.ph(fl.label), c.ph(featured.data.year)].filter(Boolean).join(' · ')}</div>
              <h2 className="h2-detail">{c.ph(featured.data.title)}</h2>
              <dl className="featured-dl">
                {(['brief', 'solution', 'result'] as const).filter((k) => c.ph(featured.data[k]) !== '').map((k) => (
                  <div key={k}><dt className="mono-sm">{c.ph(fl[k])}</dt><dd><Lines text={c.ph(featured.data[k])} /></dd></div>
                ))}
              </dl>
            </div>
          </article>
        </section>
      )}

      <section id="zakazky" className="wrap work-list" data-filter-root>
        <div className="filters" role="group" aria-label={c.ph(grid.aria)}>
          <a className="chip chip--panel" href="/prace#zakazky" data-filter="" aria-current={selected === '' ? 'true' : undefined}>{c.ph(grid.all)}</a>
          {categories.map((k) => (
            <a key={k.id} className="chip chip--panel" href={`/prace?kategorie=${encodeURIComponent(k.slug)}#zakazky`} data-filter={k.slug}
              aria-current={selected === k.slug ? 'true' : undefined}>{c.ph(k.data.name)}</a>
          ))}
        </div>
        <div className="work-grid">
          {works.map((w) => {
            const cat = catById.get(Number(w.data.category))
            const slug = cat?.slug ?? ''
            const show = selected === '' || selected === slug
            if (show) visible++
            return (
              <figure key={w.id} className="work-card" data-cat={slug} hidden={!show}>
                <div className="photo photo--4x3">
                  <Img c={c} value={w.data.photo} sizes="(min-width: 1200px) 30vw, (min-width: 800px) 45vw, 100vw" />
                  {cat && <span className="photo-badge">{c.ph(cat.data.label)}</span>}
                </div>
                <figcaption>
                  <span className="work-card-title">{c.ph(w.data.title)}</span>
                  {c.ph(w.data.spec) !== '' && <span className="mono">{c.ph(w.data.spec)}</span>}
                </figcaption>
              </figure>
            )
          })}
        </div>
        <p className="work-empty" data-empty hidden={visible > 0}>{c.ph(grid.empty)}</p>
      </section>

      <Cta c={c} cta={c.ctaFor(page.cta)} />
    </Shell>
  )
}
