'use client'

import { useEffect } from 'react'

/* site.js (klikací díl, filtr prací, cookies) se spustí až po hydrataci Reactu, ať nemění HTML, které React právě přebírá. */
export function SiteScripts({ src }: { src: string }) {
  useEffect(() => {
    if (document.querySelector('script[data-site-js]')) return
    const s = document.createElement('script')
    s.src = src
    s.dataset.siteJs = '1'
    document.body.appendChild(s)
  }, [src])
  return null
}
