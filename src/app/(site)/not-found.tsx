import { siteContent } from '@/lib/site'
import { Shell } from '@/components/site/Shell'
import { Eyebrow, Lines } from '@/components/site/bits'

export default async function NotFound() {
  const c = await siteContent(false)
  const nf = c.setting('notfound')
  return (
    <Shell c={c} pageKey="404" path="" seo={{ title: nf.title }} noindex>
      <section className="wrap simple-page">
        <Eyebrow c={c} text={nf.label} />
        <h1 className="h1 h1--contact">{c.ph(nf.title)}</h1>
        {c.ph(nf.text) !== '' && <p className="text-18"><Lines text={c.ph(nf.text)} /></p>}
        <p className="simple-actions">
          <a className="btn btn-dark" href="/">{c.ph(nf.home)}</a>
          <a className="btn btn-outline" href="/kontakt">{c.ph(nf.contact)}</a>
        </p>
      </section>
    </Shell>
  )
}
