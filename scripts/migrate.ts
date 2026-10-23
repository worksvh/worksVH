/*
 * npm run migrate – vytvoří/aktualizuje tabulky v databázi a doplní výchozí obsah tam, kde chybí.
 * Existující obsah nepřepisuje.
 */
import { readdirSync, readFileSync } from 'node:fs'
import path from 'node:path'
import { loadEnv } from './env'

loadEnv()
const { db, all, val, exec, now } = await import('../src/lib/db')
const { seed } = await import('../src/lib/schema')

const dir = path.join(process.cwd(), 'src', 'migrations')
await exec('CREATE TABLE IF NOT EXISTS migrations (name TEXT PRIMARY KEY, applied_at TEXT NOT NULL)')
const done = new Set((await all<{ name: string }>('SELECT name FROM migrations')).map((r) => r.name))
for (const file of readdirSync(dir).filter((f) => f.endsWith('.sql')).sort()) {
  if (done.has(file)) continue
  await db().executeMultiple(readFileSync(path.join(dir, file), 'utf8'))
  await exec('INSERT INTO migrations (name, applied_at) VALUES (?, ?)', [file, now()])
  console.log('Migrace:', file)
}

const added: string[] = []
for (const [key, data] of Object.entries(seed.pages)) {
  if (!(await val('SELECT 1 FROM pages WHERE key = ?', [key]))) {
    await exec('INSERT INTO pages (key, data, updated_at) VALUES (?, ?, ?)', [key, JSON.stringify(data), now()])
    added.push(`stránka ${key}`)
  }
}
for (const [key, data] of Object.entries(seed.settings)) {
  if (!(await val('SELECT 1 FROM settings WHERE key = ?', [key]))) {
    const d = { ...data }
    if (key === 'mail' && !d.inquiry_to) d.inquiry_to = process.env.INQUIRY_TO || ''
    await exec('INSERT INTO settings (key, data, updated_at) VALUES (?, ?, ?)', [key, JSON.stringify(d), now()])
    added.push(`nastavení ${key}`)
  }
}
// kategorie musí být první kvůli referencím
for (const c of ['categories', 'services', 'works', 'machines', 'people']) {
  if (await val('SELECT 1 FROM items WHERE collection = ?', [c])) continue
  let i = 0
  for (const row of seed.collections[c] ?? []) {
    const data = { ...row.data }
    for (const [k, v] of Object.entries(data)) {
      if (typeof v === 'string' && v.startsWith('cat:')) {
        data[k] = (await val("SELECT id FROM items WHERE collection = 'categories' AND slug = ?", [v.slice(4)])) ?? null
      }
    }
    await exec('INSERT INTO items (collection, slug, data, sort, visible, created_at, updated_at) VALUES (?, ?, ?, ?, 1, ?, ?)',
      [c, row.slug ?? null, JSON.stringify(data), ++i, now(), now()])
  }
  added.push(`sbírka ${c}`)
}
console.log(added.length ? 'Výchozí obsah: ' + added.join(', ') : 'Výchozí obsah: vše už existuje.')
