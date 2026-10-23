import { passwordResetUser } from '@/lib/auth'
import { AuthBox, Csrf } from '@/components/admin/AdminShell'

type SP = Promise<Record<string, string | undefined>>

export default async function Reset({ searchParams }: { searchParams: SP }) {
  const sp = await searchParams
  const token = sp.token ?? ''
  const valid = Boolean(await passwordResetUser(token))
  return (
    <AuthBox title="Nové heslo">
      <h1>Nové heslo</h1>
      {!valid ? (
        <>
          <p className="err-box" role="alert">Odkaz je neplatný nebo vypršel (platí 1 hodinu a jen jednou). Požádejte o nový.</p>
          <p><a href="/admin/zapomenute-heslo">Poslat nový odkaz</a></p>
        </>
      ) : (
        <>
          {sp.chyba && <p className="err-box" role="alert">{sp.chyba}</p>}
          <form method="post" action="/admin/api/reset" className="auth-form">
            <Csrf />
            <input type="hidden" name="token" value={token} />
            <div className="f-field"><label htmlFor="r-p1">Nové heslo (min. 10 znaků)</label><input type="password" id="r-p1" name="password" autoComplete="new-password" minLength={10} required autoFocus /></div>
            <div className="f-field"><label htmlFor="r-p2">Nové heslo znovu</label><input type="password" id="r-p2" name="password2" autoComplete="new-password" minLength={10} required /></div>
            <button type="submit" className="btn-primary">Nastavit heslo</button>
          </form>
        </>
      )}
    </AuthBox>
  )
}
