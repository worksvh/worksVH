import { siteContent, isPreview, type SearchParams } from '@/lib/site'
import { Shell } from '@/components/site/Shell'
import { Eyebrow, Rich } from '@/components/site/bits'
import { telHref } from '@/lib/text'

export default async function Thanks({ searchParams }: { searchParams: SearchParams }) {
  const c = await siteContent(await isPreview(searchParams))
  const page = c.page('thanks')
  const m = page.main
  const phone = String(c.setting('company').phone)
  return (
    <Shell c={c} pageKey="thanks" path="/poptavka-odeslana" seo={page.seo}>
      <section className="wrap simple-page">
        <Eyebrow c={c} text={m.label} />
        <h1 className="h1 h1--contact">{c.ph(m.title)}</h1>
        <Rich className="rich text-18" html={c.ph(m.text)} />
        {c.ph(m.phone_label) !== '' && <p className="mono simple-label">{c.ph(m.phone_label)}</p>}
        <a className="big-phone" href={telHref(phone)}>{phone}</a>
        {c.ph(m.back) !== '' && <p><a className="link" href="/">{c.ph(m.back)}</a></p>}
      </section>
    </Shell>
  )
}
