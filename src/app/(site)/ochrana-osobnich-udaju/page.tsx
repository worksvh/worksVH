import { siteContent, isPreview, type SearchParams } from '@/lib/site'
import { Shell } from '@/components/site/Shell'
import { Eyebrow, Rich } from '@/components/site/bits'

export default async function Privacy({ searchParams }: { searchParams: SearchParams }) {
  const c = await siteContent(await isPreview(searchParams))
  const page = c.page('privacy')
  const m = page.main
  return (
    <Shell c={c} pageKey="privacy" path="/ochrana-osobnich-udaju" seo={page.seo}>
      <article className="wrap simple-page">
        <Eyebrow c={c} text={m.label} />
        <h1 className="h1 h1--contact">{c.ph(m.title)}</h1>
        <Rich className="rich text-18 prose" html={c.ph(m.body)} />
      </article>
    </Shell>
  )
}
