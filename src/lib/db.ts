import { createClient, type Client, type InArgs, type InStatement, type Row } from '@libsql/client'

/* Databáze Turso (libSQL). Lokálně jde použít i soubor: TURSO_URL=file:storage/local.db */

let client: Client | null = null

export function db(): Client {
  if (!client) {
    const url = process.env.TURSO_URL || process.env.TURSO_DATABASE_URL || ''
    if (!url) throw new Error('Chybí TURSO_URL v nastavení serveru.')
    client = createClient({ url, authToken: process.env.TURSO_TOKEN || process.env.TURSO_AUTH_TOKEN || undefined })
  }
  return client
}

export type Params = InArgs
type Obj = Record<string, unknown>

const plain = (r: Row): Obj => {
  const o: Obj = {}
  for (const k of Object.keys(r)) {
    const v = r[k]
    o[k] = typeof v === 'bigint' ? Number(v) : v
  }
  return o
}

export async function all<T = Obj>(sql: string, args: Params = []): Promise<T[]> {
  const rs = await db().execute({ sql, args })
  return rs.rows.map(plain) as T[]
}

export async function one<T = Obj>(sql: string, args: Params = []): Promise<T | null> {
  return (await all<T>(sql, args))[0] ?? null
}

export async function val<T = unknown>(sql: string, args: Params = []): Promise<T | null> {
  const rs = await db().execute({ sql, args })
  const row = rs.rows[0]
  if (!row) return null
  const v = row[0]
  return (typeof v === 'bigint' ? Number(v) : v) as T
}

/** Zápis; vrací počet řádků a id nového řádku. */
export async function exec(sql: string, args: Params = []): Promise<{ changes: number; id: number }> {
  const rs = await db().execute({ sql, args })
  return { changes: rs.rowsAffected, id: rs.lastInsertRowid !== undefined ? Number(rs.lastInsertRowid) : 0 }
}

/** Několik zápisů v jedné transakci (jedním požadavkem). */
export async function batch(stmts: [string, Params][]): Promise<void> {
  if (!stmts.length) return
  await db().batch(stmts.map(([sql, args]): InStatement => ({ sql, args })), 'write')
}

/** Čas ve formátu „2026-10-23 12:30:00“ v pražském čase (stejně jako dřívější data). */
export function now(offsetSeconds = 0): string {
  return new Intl.DateTimeFormat('sv-SE', {
    timeZone: 'Europe/Prague', year: 'numeric', month: '2-digit', day: '2-digit',
    hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false,
  }).format(new Date(Date.now() + offsetSeconds * 1000)).replace('T', ' ')
}
