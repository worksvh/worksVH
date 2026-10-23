import { createHmac, randomBytes, timingSafeEqual, createHash } from 'node:crypto'
import { cookies, headers } from 'next/headers'
import { cache } from 'react'
import bcrypt from 'bcryptjs'
import { one, exec, val, now } from './db'

export const LOGIN_MAX_ATTEMPTS = 5
export const LOGIN_BLOCK_MINUTES = 15
export const SESSION_COOKIE = 'vhw_s'
const SESSION_DAYS = 14

export interface User { id: number; email: string; name: string; last_login_at: string | null }

function appKey(): string {
  const k = process.env.APP_KEY
  if (!k || k.length < 16) throw new Error('Chybí APP_KEY (dlouhý náhodný řetězec) v nastavení serveru.')
  return k
}

export const hmac = (data: string) => createHmac('sha256', appKey()).update(data).digest('base64url')

const safeEq = (a: string, b: string) => {
  const x = Buffer.from(a), y = Buffer.from(b)
  return x.length === y.length && timingSafeEqual(x, y)
}

/* ---------------------------------------------------------------- relace (podepsaná cookie) */

interface Session { uid: number; sid: string; exp: number }

export function encodeSession(uid: number): string {
  const s: Session = { uid, sid: randomBytes(12).toString('base64url'), exp: Date.now() + SESSION_DAYS * 86400_000 }
  const body = Buffer.from(JSON.stringify(s)).toString('base64url')
  return body + '.' + hmac('s:' + body)
}

function decodeSession(raw: string | undefined): Session | null {
  if (!raw) return null
  const [body, sig] = raw.split('.')
  if (!body || !sig || !safeEq(sig, hmac('s:' + body))) return null
  try {
    const s = JSON.parse(Buffer.from(body, 'base64url').toString()) as Session
    return s.exp > Date.now() && s.uid ? s : null
  } catch {
    return null
  }
}

export const sessionCookieOptions = () => ({
  httpOnly: true,
  sameSite: 'lax' as const,
  secure: process.env.NODE_ENV === 'production',
  path: '/',
  maxAge: SESSION_DAYS * 86400,
})

export const getSession = cache(async (): Promise<Session | null> => {
  return decodeSession((await cookies()).get(SESSION_COOKIE)?.value)
})

/** Přihlášený uživatel (ověřený v databázi; smazaný účet přístup ztratí hned). */
export const currentUser = cache(async (): Promise<User | null> => {
  const s = await getSession()
  if (!s) return null
  return one<User>('SELECT id, email, name, last_login_at FROM users WHERE id = ?', [s.uid])
})

/** Token proti CSRF, svázaný s relací. */
export async function csrfToken(): Promise<string> {
  const s = await getSession()
  return hmac('csrf:' + (s?.sid ?? 'anon'))
}

/** Ověří, že požadavek přišel z našeho webu (Origin) a nese platný token. */
export async function csrfValid(sent: string | null | undefined): Promise<boolean> {
  const h = await headers()
  const origin = h.get('origin')
  const host = h.get('x-forwarded-host') || h.get('host')
  if (origin && host && new URL(origin).host !== host) return false
  return Boolean(sent) && safeEq(String(sent), await csrfToken())
}

/* ---------------------------------------------------------------- přihlášení */

export async function clientIp(): Promise<string> {
  const h = await headers()
  return (h.get('x-forwarded-for') || '').split(',')[0].trim() || h.get('x-real-ip') || '0.0.0.0'
}

export async function loginBlocked(ip: string): Promise<boolean> {
  const n = await val<number>('SELECT COUNT(*) FROM login_attempts WHERE ip = ? AND attempted_at > ?', [ip, now(-LOGIN_BLOCK_MINUTES * 60)])
  return Number(n) >= LOGIN_MAX_ATTEMPTS
}

// bcrypt z PHP má prefix $2y$, knihovna zná $2b$ (je to totéž)
const normHash = (h: string) => h.replace(/^\$2y\$/, '$2b$')
const DUMMY_HASH = '$2b$10$abcdefghijklmnopqrstuuJ9s2bS9fN8wZ7tq7U8bQk0xVt6eY0C'

export async function verifyPassword(pw: string, hash: string): Promise<boolean> {
  try { return await bcrypt.compare(pw, normHash(hash)) } catch { return false }
}

export const hashPassword = (pw: string) => bcrypt.hash(pw, 10)

/** Ověří e-mail a heslo. Neúspěch se zapíše do login_attempts. */
export async function loginAttempt(email: string, password: string, ip: string): Promise<User | null> {
  const u = await one<User & { password_hash: string }>('SELECT * FROM users WHERE email = ?', [email.trim()])
  // ověření hesla i pro neexistující účet, ať odezva neprozradí, že účet neexistuje
  const ok = await verifyPassword(password, u?.password_hash ?? DUMMY_HASH)
  if (!ok || !u) {
    await exec('INSERT INTO login_attempts (ip, attempted_at) VALUES (?, ?)', [ip, now()])
    await exec('DELETE FROM login_attempts WHERE attempted_at < ?', [now(-86400)])
    return null
  }
  await exec('DELETE FROM login_attempts WHERE ip = ?', [ip])
  await exec('UPDATE users SET last_login_at = ? WHERE id = ?', [now(), u.id])
  return u
}

export function passwordProblem(pw: string): string | null {
  return [...pw].length < 10 ? 'Heslo musí mít aspoň 10 znaků.' : null
}

export async function userCreate(email: string, password: string, name = ''): Promise<number> {
  const r = await exec('INSERT INTO users (email, password_hash, name, created_at) VALUES (?, ?, ?, ?)',
    [email.trim(), await hashPassword(password), name.trim(), now()])
  return r.id
}

/* ---------------------------------------------------------------- obnova hesla */

const sha256 = (s: string) => createHash('sha256').update(s).digest('hex')

export async function passwordResetCreate(userId: number): Promise<string> {
  const token = randomBytes(32).toString('hex')
  await exec('DELETE FROM password_resets WHERE user_id = ? OR expires_at < ?', [userId, now()])
  await exec('INSERT INTO password_resets (token_hash, user_id, expires_at) VALUES (?, ?, ?)', [sha256(token), userId, now(3600)])
  return token
}

export async function passwordResetUser(token: string): Promise<(User & { password_hash: string }) | null> {
  if (!token) return null
  return one('SELECT u.* FROM password_resets r JOIN users u ON u.id = r.user_id WHERE r.token_hash = ? AND r.expires_at > ?', [sha256(token), now()])
}

export async function passwordResetConsume(token: string): Promise<void> {
  await exec('DELETE FROM password_resets WHERE token_hash = ?', [sha256(token)])
}
