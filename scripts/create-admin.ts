/*
 * npm run create-admin -- email@firma.cz [--password=…]
 * Bez --password se heslo zadá interaktivně.
 */
import { createInterface } from 'node:readline'
import { loadEnv } from './env'

loadEnv()
const { val } = await import('../src/lib/db')
const { userCreate, passwordProblem } = await import('../src/lib/auth')

const email = (process.argv[2] ?? '').trim()
if (!/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(email)) {
  console.error('Použití: npm run create-admin -- email@firma.cz [--password=…]')
  process.exit(1)
}
if (await val('SELECT 1 FROM users WHERE email = ?', [email])) {
  console.error('Uživatel s tímto e-mailem už existuje.')
  process.exit(1)
}
let pw = process.argv.find((a) => a.startsWith('--password='))?.slice(11) ?? ''
if (!pw) {
  const rl = createInterface({ input: process.stdin, output: process.stdout })
  pw = await new Promise<string>((r) => rl.question('Heslo (min. 10 znaků): ', r))
  rl.close()
}
const p = passwordProblem(pw)
if (p) {
  console.error(p)
  process.exit(1)
}
await userCreate(email, pw, email.split('@')[0])
console.log(`Správce ${email} vytvořen. Přihlášení: ${process.env.APP_URL || ''}/admin`)
