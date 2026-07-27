const allowedTags = new Set([
  'B',
  'BR',
  'DIV',
  'EM',
  'I',
  'LI',
  'OL',
  'P',
  'STRONG',
  'TABLE',
  'TBODY',
  'TD',
  'TH',
  'THEAD',
  'TR',
  'UL',
])
const removedWithContent = new Set(['IFRAME', 'OBJECT', 'SCRIPT', 'STYLE', 'SVG'])

function cleanNode(node: Node): void {
  for (const child of Array.from(node.childNodes)) {
    if (child.nodeType === Node.COMMENT_NODE) {
      child.remove()
      continue
    }
    if (!(child instanceof Element)) continue

    if (removedWithContent.has(child.tagName)) {
      child.remove()
      continue
    }

    cleanNode(child)
    if (!allowedTags.has(child.tagName)) {
      child.replaceWith(...Array.from(child.childNodes))
      continue
    }

    for (const attribute of Array.from(child.attributes)) {
      child.removeAttribute(attribute.name)
    }
  }
}

export function sanitizeRichText(value: string): string {
  const template = document.createElement('template')
  template.innerHTML = value
  cleanNode(template.content)
  return template.innerHTML
}
