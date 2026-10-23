import { notFound } from 'next/navigation'

/* Neexistující adresy webu → stránka 404 (not-found.tsx). */
export default function Rest(): never {
  notFound()
}
