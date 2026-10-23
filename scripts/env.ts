import { readFileSync, existsSync } from 'node:fs'

/** Načte .env pro skripty spouštěné mimo Next.js. */
export function loadEnv(file = '.env'): void {
  if (!existsSync(file)) return
  for (const line of readFileSync(file, 'utf8').split('\n')) {
    const m = /^\s*([A-Z0-9_]+)\s*=\s*(.*)\s*$/.exec(line)
    if (!m || line.trim().startsWith('#')) continue
    let v = m[2]
    if (/^(["']).*\1$/.test(v)) v = v.slice(1, -1)
    if (process.env[m[1]] === undefined) process.env[m[1]] = v
  }
}
