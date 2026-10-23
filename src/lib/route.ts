import { NextResponse } from 'next/server'
import { csrfValid, currentUser, type User } from './auth'

/* Pomocníci pro POST požadavky administrace. */

export const see = (url: string) => new NextResponse(null, { status: 303, headers: { Location: url } })

const expired = () =>
  new NextResponse(
    '<!doctype html><meta charset="utf-8"><title>Platnost formuláře vypršela</title><p style="font-family:sans-serif;padding:40px">Platnost formuláře vypršela. Vraťte se zpět, obnovte stránku a zkuste to znovu.</p>',
    { status: 419, headers: { 'Content-Type': 'text/html; charset=utf-8' } },
  )

/** Ověří token formuláře; pro přihlášené části i uživatele. */
export async function guard(req: Request, opts: { auth?: boolean; json?: boolean } = {}): Promise<{ fd: FormData; user: User | null } | NextResponse> {
  const isJson = (req.headers.get('content-type') || '').includes('application/json')
  const fd = isJson ? new FormData() : await req.formData()
  const token = (fd.get('_csrf') as string | null) || req.headers.get('x-csrf-token')
  if (!(await csrfValid(token))) return opts.json || isJson ? NextResponse.json({ error: 'csrf' }, { status: 419 }) : expired()
  const user = opts.auth === false ? null : await currentUser()
  if (opts.auth !== false && !user) return opts.json || isJson ? NextResponse.json({ error: 'auth' }, { status: 401 }) : see('/admin/prihlaseni')
  return { fd, user }
}

export const field = (fd: FormData, k: string) => {
  const v = fd.get(k)
  return typeof v === 'string' ? v : ''
}
