import schemaJson from '@/content/schema.json'
import seedJson from '@/content/seed.json'

/*
 * Definice VŠECH editovatelných polí webu (src/content/schema.json). Administrace z ní sama generuje formuláře.
 * Nové pole = přidat ho do schema.json (a vypsat v šabloně). Žádný další kód adminu není potřeba.
 */

export type FieldType =
  | 'text' | 'textarea' | 'richtext' | 'tags' | 'image' | 'number' | 'select' | 'toggle'
  | 'url' | 'email' | 'tel' | 'repeater' | 'reference' | 'password'

export interface Field {
  type: FieldType
  label: string
  help?: string
  required?: boolean
  default?: unknown
  maxLength?: number
  min?: number
  max?: number
  fields?: Record<string, Field>
  item_label?: string
  options?: Record<string, string>
  collection?: string
  svg?: boolean
}
export type Fields = Record<string, Field>

export interface Group { label: string; help?: string; anchor?: string; fields: Fields }
export interface PageSchema { label: string; path: string; groups: Record<string, Group> }
export interface CollectionSchema {
  label: string
  singular: string
  path: string
  web: string
  title: string
  slug?: string
  thumb?: string
  visibility?: boolean
  fields: Fields
}
export interface SettingsSchema { label: string; help?: string; fields: Fields }

export interface Schema {
  pages: Record<string, PageSchema>
  collections: Record<string, CollectionSchema>
  settings: Record<string, SettingsSchema>
}

export const schema = schemaJson as unknown as Schema

export interface Seed {
  pages: Record<string, Record<string, unknown>>
  settings: Record<string, Record<string, unknown>>
  collections: Record<string, { slug?: string; data: Record<string, unknown> }[]>
}
export const seed = seedJson as unknown as Seed

export const pageSchema = (k: string): PageSchema | null => schema.pages[k] ?? null
export const collectionSchema = (k: string): CollectionSchema | null => schema.collections[k] ?? null
export const settingsSchema = (k: string): SettingsSchema | null => schema.settings[k] ?? null
