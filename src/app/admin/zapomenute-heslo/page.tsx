import { AuthBox, Csrf } from '@/components/admin/AdminShell'

type SP = Promise<Record<string, string | undefined>>

export default async function Forgot({ searchParams }: { searchParams: SP }) {
  const sent = (await searchParams).odeslano !== undefined
  return (
    <AuthBox title="Obnova hesla">
      <h1>Obnova hesla</h1>
      {sent ? (
        <p role="status">Pokud e-mail patří správci webu, poslali jsme na něj odkaz pro nastavení nového hesla. Odkaz platí 1 hodinu.</p>
      ) : (
        <>
          <p>Zadejte e-mail, kterým se přihlašujete. Pošleme vám odkaz pro nastavení nového hesla.</p>
          <form method="post" action="/admin/api/forgot" className="auth-form">
            <Csrf />
            <div className="f-field"><label htmlFor="f-email">E-mail</label><input type="email" id="f-email" name="email" autoComplete="username" required autoFocus /></div>
            <button type="submit" className="btn-primary">Poslat odkaz</button>
          </form>
        </>
      )}
      <p><a href="/admin/prihlaseni">← Zpět na přihlášení</a></p>
    </AuthBox>
  )
}
