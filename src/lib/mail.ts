import { promises as fs } from 'node:fs'
import path from 'node:path'
import { randomBytes } from 'node:crypto'
import { log } from './log'

export interface Attachment { filename: string; content?: Buffer; path?: string }

/**
 * Pošle textový e-mail přes Resend. Když chybí RESEND_API_KEY nebo adresa odesílatele,
 * e-mail se jen zapíše do logu (lokálně do storage/logs/mail/). Vrací true při úspěchu.
 */
export async function sendMail(opts: {
  to: string
  subject: string
  text: string
  from?: string
  fromName?: string
  replyTo?: string | null
  attachments?: Attachment[]
}): Promise<boolean> {
  const key = process.env.RESEND_API_KEY || ''
  const fromAddr = (opts.from || '').trim() || process.env.MAIL_FROM || ''
  if (!key || !fromAddr) {
    return mailToLog(opts, !key ? 'chybí RESEND_API_KEY' : 'chybí adresa odesílatele (MAIL_FROM)')
  }
  const from = opts.fromName ? `${opts.fromName.replace(/["<>]/g, '')} <${fromAddr}>` : fromAddr
  try {
    const r = await fetch('https://api.resend.com/emails', {
      method: 'POST',
      headers: { Authorization: `Bearer ${key}`, 'Content-Type': 'application/json' },
      body: JSON.stringify({
        from,
        to: [opts.to],
        subject: opts.subject,
        text: opts.text,
        reply_to: opts.replyTo || undefined,
        attachments: opts.attachments?.map((a) =>
          a.content ? { filename: a.filename, content: a.content.toString('base64') } : { filename: a.filename, path: a.path }),
      }),
    })
    if (!r.ok) {
      log('error', `E-mail pro ${opts.to} se nepodařilo odeslat: ${r.status} ${await r.text()}`)
      return false
    }
    log('mail', `OK -> ${opts.to} | ${opts.subject}`)
    return true
  } catch (e) {
    log('error', `E-mail pro ${opts.to} se nepodařilo odeslat: ${(e as Error).message}`)
    return false
  }
}

async function mailToLog(opts: { to: string; subject: string; text: string; attachments?: Attachment[] }, reason: string): Promise<boolean> {
  const names = (opts.attachments ?? []).map((a) => a.filename).join(', ')
  log('mail', `NEODESLÁNO (${reason}) -> ${opts.to} | ${opts.subject}`)
  if (process.env.VERCEL) return true
  try {
    const dir = path.join(process.cwd(), 'storage', 'logs', 'mail')
    await fs.mkdir(dir, { recursive: true })
    const stamp = new Date().toISOString().replace(/[-:T]/g, '').slice(0, 14)
    await fs.writeFile(path.join(dir, `${stamp}-${randomBytes(3).toString('hex')}.txt`),
      `To: ${opts.to}\nSubject: ${opts.subject}\n\n${opts.text}\n\nPřílohy: ${names}\n`)
  } catch { /* nevadí */ }
  return true
}
