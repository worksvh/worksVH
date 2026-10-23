import type { Content } from '@/lib/content'
import { Img } from './Img'

export function MapBox({ c, className = '' }: { c: Content; className?: string }) {
  const co = c.setting('company')
  const hasUrl = String(co.map_url ?? '').trim() !== ''
  const inner = (
    <>
      <Img c={c} value={co.map_image} className="map-img" sizes="(min-width: 900px) 50vw, 100vw" />
      {hasUrl && c.ph(co.map_link) !== '' && <span className="map-cta mono">{c.ph(co.map_link)} ↗</span>}
    </>
  )
  return (
    <div className={`map ${className}`}>
      {hasUrl ? <a className="map-link" href={co.map_url} target="_blank" rel="noopener">{inner}</a> : inner}
      {c.ph(co.map_credit) !== '' && <span className="map-credit">{c.ph(co.map_credit)}</span>}
    </div>
  )
}
