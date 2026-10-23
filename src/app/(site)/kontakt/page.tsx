import { siteContent, isPreview, type SearchParams } from '@/lib/site'
import { Shell } from '@/components/site/Shell'
import { MapBox } from '@/components/site/MapBox'
import { Eyebrow, Lines } from '@/components/site/bits'
import { hasPlaceholder, telHref, formatBytes } from '@/lib/text'
import { blobEnabled } from '@/lib/storage'
import {
  inqMsg, startToken, INQ_FILE_TYPES, INQ_MAX_FILES, INQ_MAX_FILE_BYTES, INQ_MAX_TOTAL_BYTES,
} from '@/lib/inquiries'
import { InquiryForm, type InquiryTexts } from './InquiryForm'

export default async function Contact({ searchParams }: { searchParams: SearchParams }) {
  const c = await siteContent(await isPreview(searchParams))
  const page = c.page('contact')
  const h = page.hero
  const f = page.form
  const co = c.setting('company')
  const ph = c.ph

  const texts = Object.fromEntries(
    ['head_left', 'head_right', 'title', 'f_company', 'f_name', 'f_email', 'f_phone', 'f_quantity', 'f_deadline', 'f_message',
      'files_title', 'files_formats', 'files_button', 'files_limits', 'consent', 'consent_link', 'note', 'submit']
      .map((k) => [k, ph(f[k])]),
  ) as unknown as InquiryTexts
  Object.assign(texts, {
    uploading: ph(f.uploading) || ph(f.submit),
    msgCount: inqMsg(c, 'files_count', { max: INQ_MAX_FILES }),
    msgSize: inqMsg(c, 'files_size', { max: formatBytes(INQ_MAX_FILE_BYTES) }),
    msgTotal: inqMsg(c, 'files_total', { max: formatBytes(INQ_MAX_TOTAL_BYTES) }),
    msgType: inqMsg(c, 'files_type', { povolene: INQ_FILE_TYPES.join(', ').toUpperCase() }),
    msgRemove: inqMsg(c, 'remove'),
    msgUpload: inqMsg(c, 'files_upload', { soubor: '' }),
  })

  return (
    <Shell c={c} pageKey="contact" path="/kontakt" seo={page.seo}>
      <div className="wrap contact-layout">
        <section className="contact-info" aria-labelledby="kontakt-nadpis">
          <div>
            <Eyebrow c={c} text={h.label} />
            <h1 id="kontakt-nadpis" className="h1 h1--contact">{ph(h.title)}</h1>
            <a className="big-phone" href={telHref(co.phone)}>{co.phone}</a>
          </div>
          <dl className="contact-dl">
            {ph(co.email) !== '' && (
              <div><dt className="mono-sm">{ph(h.l_email)}</dt><dd>{hasPlaceholder(co.email) ? ph(co.email) : <a href={`mailto:${co.email}`}>{co.email}</a>}</dd></div>
            )}
            <div><dt className="mono-sm">{ph(h.l_address)}</dt><dd>{ph(co.street)}<br />{ph(`${co.zip} ${co.city}`.trim())}</dd></div>
            {ph(co.hours) !== '' && <div><dt className="mono-sm">{ph(h.l_hours)}</dt><dd><Lines text={ph(co.hours)} /></dd></div>}
            {(ph(co.ico) !== '' || ph(co.dic) !== '' || ph(co.operator) !== '') && (
              <div><dt className="mono-sm">{ph(h.l_billing)}</dt><dd>
                {ph(co.operator) !== '' && <>{ph(co.operator)}<br /></>}
                {ph(co.operator_address) !== '' && <>{ph(co.operator_address)}<br /></>}
                {ph(co.ico) !== '' && <>{ph(co.ico_label)} {ph(co.ico)}<br /></>}
                {ph(co.dic) !== '' && <>{ph(co.dic_label)} {ph(co.dic)}</>}
              </dd></div>
            )}
          </dl>
          <MapBox c={c} className="map--contact" />
        </section>

        <InquiryForm
          t={texts}
          started={startToken()}
          blob={blobEnabled()}
          limits={{ maxFiles: INQ_MAX_FILES, maxFile: INQ_MAX_FILE_BYTES, maxTotal: INQ_MAX_TOTAL_BYTES, types: INQ_FILE_TYPES }}
        />
      </div>
    </Shell>
  )
}
