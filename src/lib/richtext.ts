import { parse, HTMLElement, TextNode, type Node } from 'node-html-parser'
import { escapeHtml } from './text'

/**
 * Sanitizace omezeného HTML z editoru: odstavce, tučně, odkaz, seznam.
 * Vše ostatní se buď rozbalí (zůstane text), nebo zahodí (script, style…).
 */
export function richtextSanitize(html: string): string {
  html = html.trim()
  if (html === '') return ''
  if (!html.includes('<')) {
    // čistý text: odstavce podle prázdných řádků
    return html
      .split(/\r?\n\s*\r?\n/)
      .filter((p) => p.trim() !== '')
      .map((p) => '<p>' + escapeHtml(p.trim()).replace(/\r?\n/g, '<br>') + '</p>')
      .join('')
  }
  const root = parse('<div>' + html + '</div>', { comment: false, blockTextElements: { script: false, style: false, noscript: false, pre: true } })
  let out = cleanChildren(root.firstChild as HTMLElement)
  out = out.replace(/(<br>\s*){3,}/g, '<br><br>')
  if (!/^\s*<(p|ul|ol)>/.test(out)) out = '<p>' + out + '</p>'
  out = out.replace(/<p>(\s|<br>|&nbsp;)*<\/p>/gu, '')
  return out.trim()
}

const DROP = new Set(['script', 'style', 'iframe', 'object', 'embed', 'svg', 'math', 'template', 'noscript', 'head', 'title', 'meta', 'link'])
const BLOCK = new Set(['p', 'div', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'blockquote'])

function cleanChildren(n: Node): string {
  return n.childNodes.map(cleanNode).join('')
}

// text z parseru je ještě s entitami (&amp;…); dekódujeme a znovu bezpečně zakódujeme
const textOf = (t: TextNode) => escapeHtml(t.text)

function cleanNode(n: Node): string {
  if (n instanceof TextNode) return textOf(n)
  if (!(n instanceof HTMLElement)) return ''
  const tag = n.rawTagName?.toLowerCase() ?? ''
  if (DROP.has(tag)) return ''
  if (BLOCK.has(tag)) {
    const c = cleanChildren(n).trim()
    return c === '' ? '' : `<p>${c}</p>`
  }
  switch (tag) {
    case 'br':
      return '<br>'
    case 'strong':
    case 'b': {
      const c = cleanChildren(n)
      return c === '' ? '' : `<strong>${c}</strong>`
    }
    case 'ul':
    case 'ol': {
      let items = ''
      for (const li of n.childNodes) {
        if (li instanceof HTMLElement && li.rawTagName?.toLowerCase() === 'li') items += '<li>' + cleanChildren(li).trim() + '</li>'
        else if (li.text.trim() !== '') items += '<li>' + cleanNode(li).trim() + '</li>'
      }
      return items === '' ? '' : `<${tag}>${items}</${tag}>`
    }
    case 'li':
      return '<li>' + cleanChildren(n) + '</li>'
    case 'a': {
      const href = (n.getAttribute('href') ?? '').trim()
      const c = cleanChildren(n)
      if (href === '' || !/^(https?:\/\/|mailto:|tel:|\/|#)/i.test(href)) return c
      const ext = /^https?:\/\//i.test(href)
      return `<a href="${escapeHtml(href)}"${ext ? ' rel="noopener"' : ''}>${c}</a>`
    }
    default:
      return cleanChildren(n) // span, em, font… → jen obsah
  }
}
