'use server'

import { after } from 'next/server'
import { redirect } from 'next/navigation'
import { loadContent } from '@/lib/content'
import { clientIp } from '@/lib/auth'
import {
  validateFields, validateFiles, rateExceeded, inquiryStore, inquirySendMails, inqMsg, ipHash,
  startToken, startedOk, type InqFile, type InquiryValues,
} from '@/lib/inquiries'
import { isOwnBlob, storeHead } from '@/lib/storage'
import { log } from '@/lib/log'

export interface InquiryState {
  values: Partial<InquiryValues>
  errors: Record<string, string>
  formError: string | null
  started: string
}

export async function submitInquiry(_prev: InquiryState, fd: FormData): Promise<InquiryState> {
  const c = await loadContent()
  const get = (k: string) => {
    const v = fd.get(k)
    return typeof v === 'string' ? v : ''
  }
  const ip = await clientIp()
  const hash = ipHash(ip)

  // honeypot: robot dostane „úspěch“, nic se neuloží
  if (get('web').trim() !== '') {
    log('spam', 'honeypot ' + hash.slice(0, 12))
    redirect('/poptavka-odeslana#obsah')
  }

  const [values, errors] = validateFields(c, get)
  let formError: string | null = null
  if (!startedOk(get('started'))) {
    formError = inqMsg(c, 'too_fast')
    log('spam', 'too fast ' + hash.slice(0, 12))
  }
  if (!formError && (await rateExceeded(hash))) formError = inqMsg(c, 'rate')

  // přílohy: buď nahrané z prohlížeče do Blob (adresy), nebo přímo ve formuláři
  const files: InqFile[] = []
  for (const raw of fd.getAll('blob')) {
    try {
      const b = JSON.parse(String(raw)) as { url: string; name: string }
      const h = isOwnBlob(b.url, 'poptavky/tmp/') ? await storeHead(b.url) : null
      if (!h) {
        errors.files = inqMsg(c, 'files_upload', { soubor: String(b.name) })
        break
      }
      files.push({ name: String(b.name).replace(/[\\/]/g, '_'), size: h.size, ext: '', url: b.url })
    } catch {
      errors.files = inqMsg(c, 'files_upload', { soubor: '' })
    }
  }
  for (const f of fd.getAll('soubory')) {
    if (typeof f === 'string' || f.size === 0 || f.name === '') continue
    files.push({ name: f.name.replace(/[\\/]/g, '_'), size: f.size, ext: '', buffer: Buffer.from(await f.arrayBuffer()) })
  }
  if (!errors.files) {
    const fe = validateFiles(c, files)
    if (fe) errors.files = fe
  }

  const fresh = startToken()
  if (Object.keys(errors).length || formError) {
    return { values, errors, formError: formError ?? String(c.page('contact').form.error_summary), started: fresh }
  }

  let id: number
  try {
    id = await inquiryStore(values, files, hash)
  } catch (e) {
    log('error', 'Poptávku se nepodařilo uložit: ' + (e as Error).message)
    return { values, errors: {}, formError: String(c.page('contact').form.error_summary), started: fresh }
  }
  // e-maily až po odeslání odpovědi, ať zákazník nečeká
  after(async () => {
    try {
      await inquirySendMails(c, id)
    } catch (e) {
      log('error', `Poptávka ${id}: e-maily selhaly: ${(e as Error).message}`)
    }
  })
  redirect('/poptavka-odeslana#obsah')
}
