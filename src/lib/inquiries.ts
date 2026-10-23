import { randomBytes } from 'node:crypto'
import { all, one, val, exec, batch, now } from './db'
import { hmac } from './auth'
import { formatBytes, formatDate, hasPlaceholder } from './text'
import { storePut, storeDelete, storeMove, storeRead, blobEnabled } from './storage'
import { sendMail, type Attachment } from './mail'
import { log } from './log'
import type { Content, Data } from './content'

/* Pravidla poptávkového formuláře (sdílí je server i formulář v prohlížeči). */

export const INQ_MAX_FILES = 5
export const INQ_MAX_FILE_BYTES = 20 * 1024 * 1024
export const INQ_MAX_TOTAL_BYTES = 50 * 1024 * 1024
export const INQ_MAIL_ATTACH_BYTES = 10 * 1024 * 1024
export const INQ_MIN_SECONDS = 3
export const INQ_MAX_PER_HOUR = 5
export const INQUIRY_STATUSES: Record<string, string> = { new: 'Nová', progress: 'Vyřizuje se', done: 'Hotovo' }

/** Povolené přípony souborů. */
export const INQ_FILE_TYPES = ['pdf', 'step', 'stp', 'igs', 'iges', 'dwg', 'dxf', 'jpg', 'jpeg', 'png', 'zip']

/** Typ obsahu podle přípony (CAD formáty prohlížeče hlásí různě, proto ho určujeme sami). */
export const INQ_MIME: Record<string, string> = {
  pdf: 'application/pdf', jpg: 'image/jpeg', jpeg: 'image/jpeg', png: 'image/png', zip: 'application/zip',
  step: 'application/octet-stream', stp: 'application/octet-stream', igs: 'application/octet-stream',
  iges: 'application/octet-stream', dwg: 'application/octet-stream', dxf: 'application/octet-stream',
}

export const ipHash = (ip: string) => hmac('ip:' + ip)

/** Podepsaný čas otevření formuláře (ochrana proti robotům, kteří formulář vyplní hned). */
export function startToken(): string {
  const t = String(Date.now())
  return t + '.' + hmac('inq:' + t)
}

export function startedOk(token: string): boolean {
  const [t, sig] = token.split('.')
  if (!t || !sig || sig !== hmac('inq:' + t)) return false
  const age = Date.now() - Number(t)
  return age >= INQ_MIN_SECONDS * 1000 && age < 2 * 86400_000
}

export function inqMsg(c: Content, key: string, vars: Record<string, string | number> = {}): string {
  let msg = String(c.setting('form_msgs')[key] ?? key)
  for (const [k, v] of Object.entries(vars)) msg = msg.replaceAll(`{${k}}`, String(v))
  return msg
}

export interface InquiryValues {
  company: string; name: string; email: string; phone: string; quantity: string; deadline: string; message: string; consent: boolean
}

export const EMAIL_RE = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/

/** Ověří textová pole poptávky. */
export function validateFields(c: Content, get: (k: string) => string): [InquiryValues, Record<string, string>] {
  const limits: Record<string, number> = { company: 200, name: 200, email: 200, phone: 50, quantity: 100, deadline: 100, message: 10000 }
  const v: any = {}
  for (const [k, max] of Object.entries(limits)) v[k] = [...get(k).replace(/\r\n/g, '\n').trim()].slice(0, max).join('')
  v.consent = get('consent') !== '' && get('consent') !== '0'
  const err: Record<string, string> = {}
  for (const k of ['company', 'name', 'email', 'message']) if (v[k] === '') err[k] = inqMsg(c, 'required')
  if (v.email !== '' && !EMAIL_RE.test(v.email)) err.email = inqMsg(c, 'email')
  if (!v.consent) err.consent = inqMsg(c, 'consent')
  return [v as InquiryValues, err]
}

export interface InqFile {
  name: string
  size: number
  ext: string
  buffer?: Buffer // soubor poslaný přímo ve formuláři (lokálně)
  url?: string // soubor nahraný z prohlížeče do Blob
}

const extOf = (name: string) => (name.includes('.') ? name.split('.').pop()!.toLowerCase() : '')

/** Ověří přílohy. Vrací chybu, nebo null. */
export function validateFiles(c: Content, files: InqFile[]): string | null {
  if (files.length > INQ_MAX_FILES) return inqMsg(c, 'files_count', { max: INQ_MAX_FILES })
  const allowed = INQ_FILE_TYPES.join(', ').toUpperCase()
  let total = 0
  for (const f of files) {
    f.ext = extOf(f.name)
    if (f.size > INQ_MAX_FILE_BYTES) return inqMsg(c, 'files_size', { soubor: f.name, max: formatBytes(INQ_MAX_FILE_BYTES) })
    if (!INQ_FILE_TYPES.includes(f.ext)) return inqMsg(c, 'files_type', { soubor: f.name, povolene: allowed })
    if (f.buffer && !magicOk(f.ext, f.buffer)) return inqMsg(c, 'files_type', { soubor: f.name, povolene: allowed })
    total += f.size
  }
  if (total > INQ_MAX_TOTAL_BYTES) return inqMsg(c, 'files_total', { max: formatBytes(INQ_MAX_TOTAL_BYTES) })
  return null
}

/** Kontrola obsahu u formátů, které mají pevný začátek souboru. */
export function magicOk(ext: string, head: Buffer): boolean {
  const starts = (sig: number[]) => sig.every((b, i) => head[i] === b)
  switch (ext) {
    case 'pdf': return starts([0x25, 0x50, 0x44, 0x46])
    case 'png': return starts([0x89, 0x50, 0x4e, 0x47])
    case 'jpg': case 'jpeg': return starts([0xff, 0xd8, 0xff])
    case 'zip': return starts([0x50, 0x4b])
    default: return true
  }
}

export async function rateExceeded(hash: string): Promise<boolean> {
  const n = await val<number>('SELECT COUNT(*) FROM inquiries WHERE ip_hash = ? AND created_at > ?', [hash, now(-3600)])
  return Number(n) >= INQ_MAX_PER_HOUR
}

/** Uloží poptávku a přílohy. Vrací id. */
export async function inquiryStore(v: InquiryValues, files: InqFile[], hash: string): Promise<number> {
  const t = now()
  const r = await exec(`INSERT INTO inquiries (created_at, company, name, email, phone, quantity, deadline, message, status, note, ip_hash, consent_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'new', '', ?, ?)`,
    [t, v.company, v.name, v.email, v.phone, v.quantity, v.deadline, v.message, hash, t])
  const id = r.id
  const stored: string[] = []
  try {
    const rows: [string, (string | number)[]][] = []
    for (const f of files) {
      const name = `poptavky/${id}/${randomBytes(16).toString('hex')}.${f.ext}`
      const where = f.url ? await storeMove(f.url, name) : await storePut(name, f.buffer!, INQ_MIME[f.ext] ?? 'application/octet-stream')
      stored.push(where)
      rows.push(['INSERT INTO inquiry_files (inquiry_id, stored_name, original_name, mime, size) VALUES (?, ?, ?, ?, ?)',
        [id, where, [...f.name].slice(0, 200).join(''), INQ_MIME[f.ext] ?? 'application/octet-stream', f.size]])
    }
    await batch(rows)
    return id
  } catch (e) {
    await storeDelete(stored).catch(() => undefined)
    await exec('DELETE FROM inquiries WHERE id = ?', [id])
    throw e
  }
}

export interface InquiryFile { id: number; inquiry_id: number; stored_name: string; original_name: string; mime: string; size: number }

export const inquiryFiles = (id: number) => all<InquiryFile>('SELECT * FROM inquiry_files WHERE inquiry_id = ? ORDER BY id', [id])

export async function inquiryDeleteFiles(id: number): Promise<void> {
  const files = await inquiryFiles(id)
  await storeDelete(files.map((f) => f.stored_name)).catch((e) => log('error', `Přílohy poptávky ${id}: ${e.message}`))
  await exec('DELETE FROM inquiry_files WHERE inquiry_id = ?', [id])
}

/** Anonymizuje poptávky starší než lhůta z Nastavení a smaže jejich přílohy. Vrací počet. */
export async function inquiriesAnonymizeOld(c: Content): Promise<number> {
  const months = Math.max(1, Number(c.setting('mail').retention_months) || 24)
  const d = new Date()
  d.setMonth(d.getMonth() - months)
  const cutoff = now(Math.round((d.getTime() - Date.now()) / 1000))
  const ids = (await all<{ id: number }>('SELECT id FROM inquiries WHERE created_at < ? AND anonymized_at IS NULL', [cutoff])).map((r) => r.id)
  for (const id of ids) {
    await inquiryDeleteFiles(id)
    await exec(`UPDATE inquiries SET company = 'anonymizováno', name = 'anonymizováno', email = '', phone = '',
      message = 'Obsah poptávky byl po uplynutí lhůty anonymizován.', note = '', ip_hash = '', anonymized_at = ? WHERE id = ?`, [now(), id])
  }
  if (ids.length) log('info', `Anonymizováno poptávek: ${ids.length}`)
  return ids.length
}

/** Odešle e-mail firmě a potvrzení zákazníkovi. Chyby jen loguje. */
export async function inquirySendMails(c: Content, id: number): Promise<void> {
  const q = await one<Data>('SELECT * FROM inquiries WHERE id = ?', [id])
  if (!q) return
  const files = await inquiryFiles(id)
  const mail = c.setting('mail')
  const company = c.setting('company')
  const to = String(mail.inquiry_to ?? '').trim() || process.env.INQUIRY_TO || ''
  const appUrl = (process.env.APP_URL || '').replace(/\/$/, '')
  const from = String(mail.smtp_from ?? '').trim()
  const fromName = String(company.name ?? '')

  const lines: [string, string][] = [
    ['Firma', q.company], ['Jméno', q.name], ['E-mail', q.email], ['Telefon', q.phone],
    ['Počet kusů', q.quantity], ['Potřebný termín', q.deadline],
  ]
  let text = `Nová poptávka z webu (č. ${q.id}, ${formatDate(q.created_at)})\n\n`
  for (const [k, v] of lines) text += k + ':' + ' '.repeat(Math.max(1, 18 - [...k].length)) + (v !== '' ? v : '–') + '\n'
  text += `\nPopis:\n${q.message}\n\n`
  const total = files.reduce((s, f) => s + Number(f.size), 0)
  let attach: Attachment[] = []
  if (files.length) {
    text += 'Přílohy:\n'
    for (const f of files) text += ` - ${f.original_name} (${formatBytes(Number(f.size))})\n`
    if (total > INQ_MAIL_ATTACH_BYTES) {
      text += '(Přílohy jsou dohromady větší než 10 MB, stáhněte je v administraci.)\n'
    } else {
      for (const f of files) {
        if (/^https?:\/\//.test(f.stored_name)) attach.push({ filename: f.original_name, path: f.stored_name })
        else {
          const buf = await storeRead(f.stored_name)
          if (buf) attach.push({ filename: f.original_name, content: buf })
        }
      }
    }
  }
  text += `\nDetail v administraci: ${appUrl}/admin/poptavky/${id}\n`

  const subject = String(mail.company_subject).replaceAll('{firma}', q.company)
  const toOk = to !== '' && !hasPlaceholder(to)
  if (!toOk) log('error', `Poptávka ${id}: není nastavený e-mail pro poptávky (Nastavení › E-mail).`)
  else await sendMail({ to, subject, text, attachments: attach, replyTo: q.email, from, fromName })

  const body = String(mail.customer_body).replaceAll('{jmeno}', q.name).replaceAll('{telefon}', String(company.phone ?? ''))
  await sendMail({ to: q.email, subject: String(mail.customer_subject), text: body, replyTo: toOk ? to : null, from, fromName })
}

export const usesBlob = blobEnabled
