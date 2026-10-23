import { NextResponse, type NextRequest } from 'next/server'

/* Hlášky v adminu („Uloženo.“): cookie se předá stránce v hlavičce a hned se smaže. */
export function proxy(request: NextRequest) {
  const flash = request.cookies.get('vhw_flash')?.value
  if (!flash || request.method !== 'GET' || request.nextUrl.pathname.startsWith('/admin/api/')) return NextResponse.next()
  const h = new Headers(request.headers)
  h.set('x-flash', encodeURIComponent(flash))
  const res = NextResponse.next({ request: { headers: h } })
  res.cookies.set('vhw_flash', '', { path: '/admin', maxAge: 0 })
  return res
}

export const config = { matcher: '/admin/:path*' }
