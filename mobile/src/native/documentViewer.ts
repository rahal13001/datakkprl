import { Capacitor } from '@capacitor/core'
import { Directory, Filesystem } from '@capacitor/filesystem'
import { FileViewer } from '@capacitor/file-viewer'

function safeFilename(value: string): string {
  const normalized = value
    .normalize('NFKD')
    .replace(/[^\w.\- ]+/g, '')
    .replace(/\s+/g, '-')
    .replace(/-+/g, '-')
    .replace(/^[-.]+|[-.]+$/g, '')

  return normalized.slice(0, 120) || 'dokumen'
}

function extensionFor(contentType: string): string {
  if (contentType.includes('pdf')) return '.pdf'
  if (contentType.includes('png')) return '.png'
  if (contentType.includes('jpeg') || contentType.includes('jpg')) return '.jpg'
  if (contentType.includes('wordprocessingml')) return '.docx'
  if (contentType.includes('msword')) return '.doc'
  if (contentType.includes('spreadsheetml')) return '.xlsx'
  return ''
}

export function responseFilename(
  contentDisposition: unknown,
  fallback: string,
  contentType = '',
): string {
  const header = typeof contentDisposition === 'string' ? contentDisposition : ''
  const encoded = header.match(/filename\*=UTF-8''([^;]+)/i)?.[1]
  const plain = header.match(/filename="?([^";]+)"?/i)?.[1]
  let filename = fallback

  try {
    filename = encoded ? decodeURIComponent(encoded) : (plain ?? fallback)
  } catch {
    filename = plain ?? fallback
  }

  const sanitized = safeFilename(filename)
  return /\.[a-z0-9]{2,8}$/i.test(sanitized)
    ? sanitized
    : `${sanitized}${extensionFor(contentType)}`
}

async function blobToBase64(blob: Blob): Promise<string> {
  return await new Promise((resolve, reject) => {
    const reader = new FileReader()
    reader.onerror = () => reject(reader.error ?? new Error('Dokumen gagal dibaca.'))
    reader.onload = () => {
      const result = String(reader.result ?? '')
      resolve(result.includes(',') ? result.slice(result.indexOf(',') + 1) : result)
    }
    reader.readAsDataURL(blob)
  })
}

export async function openDocument(blob: Blob, filename: string): Promise<void> {
  if (!Capacitor.isNativePlatform()) {
    const url = URL.createObjectURL(blob)
    const anchor = document.createElement('a')
    anchor.href = url
    anchor.target = '_blank'
    anchor.rel = 'noopener noreferrer'
    anchor.download = filename
    document.body.appendChild(anchor)
    anchor.click()
    anchor.remove()
    window.setTimeout(() => URL.revokeObjectURL(url), 60_000)
    return
  }

  const cachePath = `jago-kkprl/${Date.now()}-${safeFilename(filename)}`
  await Filesystem.writeFile({
    path: cachePath,
    data: await blobToBase64(blob),
    directory: Directory.Cache,
    recursive: true,
  })
  const file = await Filesystem.getUri({
    path: cachePath,
    directory: Directory.Cache,
  })
  await FileViewer.openDocumentFromLocalPath({ path: file.uri })
}
