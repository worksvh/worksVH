import { redirect } from 'next/navigation'
import { currentUser } from '@/lib/auth'
import { safeBack } from '@/lib/admin'
import { AuthBox, Csrf } from '@/components/admin/AdminShell'

type SP = Promise<Record<string, string | undefined>>

export default async function Login({ searchParams }: { searchParams: SP }) {
  const sp = await searchParams
  if (await currentUser()) redirect('/admin')
  const back = safeBack(sp.zpet)
  const error = sp.chyba === 'blok'
    ? 'Příliš mnoho neúspěšných pokusů. Přihlášení je z této adresy zablokované na 15 minut.'
    : sp.chyba ? 'E-mail nebo heslo nesedí.' : null
  return (
    <AuthBox title="Přihlášení">
      <h1>Přihlášení do administrace</h1>
      {error && <p className="err-box" role="alert">{error}</p>}
      <form method="post" action="/admin/api/login" className="auth-form">
        <Csrf />
        <input type="hidden" name="zpet" value={back} />
        <div className="f-field"><label htmlFor="l-email">E-mail</label><input type="email" id="l-email" name="email" defaultValue={sp.email ?? ''} autoComplete="username" required autoFocus /></div>
        <div className="f-field"><label htmlFor="l-pass">Heslo</label><input type="password" id="l-pass" name="password" autoComplete="current-password" required /></div>
        <button type="submit" className="btn-primary">Přihlásit se</button>
      </form>
      <p><a href="/admin/zapomenute-heslo">Zapomněli jste heslo?</a></p>
    </AuthBox>
  )
}
