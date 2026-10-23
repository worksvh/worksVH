/*
 * Převod formuláře s názvy polí ve stylu data[hero][pins][0][title] na vnořená data.
 * Pořadí položek opakovačů se drží podle pořadí v HTML (klíče mohou být i „n1729…“ u nově přidaných).
 */

export type FormValue = string | FormNode
export class FormNode {
  readonly keys: string[] = []
  readonly map = new Map<string, FormValue>()
  private auto = 0

  get(k: string): FormValue | undefined {
    return this.map.get(k)
  }
  set(k: string, v: FormValue) {
    if (!this.map.has(k)) this.keys.push(k)
    this.map.set(k, v)
  }
  push(v: FormValue) {
    this.set('#' + this.auto++, v)
  }
  /** Hodnoty v pořadí z formuláře. */
  list(): FormValue[] {
    return this.keys.map((k) => this.map.get(k)!)
  }
}

export function parseForm(fd: FormData): FormNode {
  const root = new FormNode()
  for (const [name, value] of fd.entries()) {
    if (typeof value !== 'string') continue
    const m = /^([^[\]]+)((?:\[[^\]]*\])*)$/.exec(name)
    if (!m) continue
    const parts = [m[1], ...[...m[2].matchAll(/\[([^\]]*)\]/g)].map((x) => x[1])]
    let node = root
    for (let i = 0; i < parts.length; i++) {
      const k = parts[i]
      const last = i === parts.length - 1
      if (last) {
        if (k === '') node.push(value)
        else node.set(k, value) // poslední vyhrává (skryté „0“ + zaškrtnuté „1“)
      } else {
        let next = k === '' ? undefined : node.get(k)
        if (!(next instanceof FormNode)) {
          next = new FormNode()
          if (k === '') node.push(next)
          else node.set(k, next)
        }
        node = next
      }
    }
  }
  return root
}

export const str = (v: FormValue | undefined | null): string => (typeof v === 'string' ? v : '')
export const node = (v: FormValue | undefined | null): FormNode => (v instanceof FormNode ? v : new FormNode())
