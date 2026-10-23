import { cookies } from 'next/headers'
import { cache } from 'react'
import { loadContent, type Content, type Preview } from './content'
import { currentUser } from './auth'
import { one } from './db'

export const PREVIEW_COOKIE = 'vhw_preview'

export type SearchParams = Promise<Record<string, string | string[] | undefined>>

/** Obsah pro stránku webu; s ?nahled=1 a přihlášením i s neuloženými změnami z adminu. */
export const siteContent = cache(async (preview: boolean): Promise<Content> => {
  let pv: Preview | null = null
  if (preview) {
    const token = (await cookies()).get(PREVIEW_COOKIE)?.value
    const user = token ? await currentUser() : null
    if (token && user) {
      const row = await one<{ data: string }>('SELECT data FROM previews WHERE token = ? AND user_id = ?', [token, user.id])
      if (row) pv = JSON.parse(row.data) as Preview
    }
  }
  return loadContent(pv)
})

export async function isPreview(sp: SearchParams): Promise<boolean> {
  return (await sp).nahled !== undefined
}
