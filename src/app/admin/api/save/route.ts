import { NextResponse } from 'next/server'
import { guard, field } from '@/lib/route'
import { adminEntity, entityParse, entitySave, flash, contentChanged } from '@/lib/admin'
import { parseForm } from '@/lib/form'

/* Uložení stránky, nastavení nebo položky. admin.js posílá formulář a čeká JSON: {redirect} nebo {errors}. */
export async function POST(req: Request) {
  const g = await guard(req)
  if (g instanceof Response) return g
  const ent = await adminEntity(field(g.fd, '_type'), field(g.fd, '_key'))
  if (!ent) return NextResponse.json({ error: 'Položka neexistuje.' }, { status: 404 })
  const { data, errors, slug, visible } = await entityParse(ent, parseForm(g.fd))
  if (Object.keys(errors).length) return NextResponse.json({ errors }, { status: 422 })
  const url = await entitySave(ent, data, slug, visible, g.user!.id)
  contentChanged()
  await flash('ok', 'Uloženo.')
  return NextResponse.json({ redirect: url })
}
