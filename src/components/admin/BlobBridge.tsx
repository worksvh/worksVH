'use client'

import { useEffect } from 'react'

declare global {
  interface Window { vhwBlobUpload?: (file: File) => Promise<string> }
}

/* Nahrávání obrázků z adminu přímo do Vercel Blob (server Vercelu nepřijme soubory nad 4,5 MB). admin.js volá window.vhwBlobUpload. */
export function BlobBridge() {
  useEffect(() => {
    window.vhwBlobUpload = async (file: File) => {
      const { upload } = await import('@vercel/blob/client')
      const csrf = document.body.getAttribute('data-csrf') || ''
      const safe = file.name.replace(/[^\w.\-]+/g, '_').slice(-120)
      const r = await upload(`media/tmp/${safe}`, file, { access: 'public', handleUploadUrl: '/admin/api/media/token', headers: { 'X-CSRF-Token': csrf } })
      return r.url
    }
  }, [])
  return null
}
