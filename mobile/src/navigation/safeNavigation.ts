import type { RouteLocationRaw } from 'vue-router'

const invalidSegments = new Set(['undefined', 'null', 'nan'])
const staticPaths = new Set([
  '/',
  '/login',
  '/update-required',
  '/tabs/dashboard',
  '/tabs/requests',
  '/tabs/notifications',
  '/tabs/profile',
  '/feedback',
])

export type NavigationError = 'invalid_resource' | 'resource_unavailable'

function singleValue(value: unknown): string | null {
  if (Array.isArray(value)) return value.length === 1 ? singleValue(value[0]) : null
  if (typeof value !== 'string') return null

  const normalized = value.trim()
  if (
    !normalized ||
    normalized.length > 100 ||
    invalidSegments.has(normalized.toLowerCase()) ||
    /[/?#\\\u0000-\u001f\u007f]/.test(normalized)
  ) {
    return null
  }

  return normalized
}

export function validTicket(value: unknown): string | null {
  return singleValue(value)
}

export function validPositiveId(value: unknown): number | null {
  const normalized = singleValue(value)
  if (!normalized || !/^[1-9]\d*$/.test(normalized)) return null

  const id = Number(normalized)
  return Number.isSafeInteger(id) ? id : null
}

export function requestDetailRoute(ticket: unknown): RouteLocationRaw {
  const normalized = validTicket(ticket)
  return normalized
    ? { name: 'request-detail', params: { ticket: normalized } }
    : navigationFallback('requests', 'invalid_resource')
}

export function navigationFallback(
  destination: 'requests' | 'feedback' | 'notifications' | 'dashboard',
  error: NavigationError,
): RouteLocationRaw {
  return {
    name: destination,
    query: { navigation_error: error },
    replace: true,
  }
}

export function navigationMessage(value: unknown): string | null {
  const error = singleValue(value)
  if (error === 'invalid_resource') {
    return 'Tautan tidak valid. Anda telah diarahkan ke halaman yang aman.'
  }
  if (error === 'resource_unavailable') {
    return 'Data tidak tersedia, telah dihapus, atau tidak dapat Anda akses.'
  }
  return null
}

function safeRequestsQuery(url: URL): Record<string, string> {
  const query: Record<string, string> = {}
  const scope = url.searchParams.get('scope')
  const status = url.searchParams.get('status')
  if (scope === 'all' || scope === 'mine') query.scope = scope
  if (status && ['waiting', 'scheduled', 'completed'].includes(status)) {
    query.status = status
  }
  return query
}

export function safeInternalRoute(value: unknown): RouteLocationRaw | null {
  if (typeof value !== 'string') return null
  const raw = value.trim()
  if (!raw.startsWith('/') || raw.startsWith('//') || raw.includes('\\')) return null

  let url: URL
  try {
    url = new URL(raw, 'https://servicekkprl.invalid')
  } catch {
    return null
  }
  if (url.origin !== 'https://servicekkprl.invalid') return null

  const path = url.pathname.replace(/\/+$/, '') || '/'
  if (staticPaths.has(path)) {
    return path === '/tabs/requests'
      ? { path, query: safeRequestsQuery(url) }
      : { path }
  }

  const requestMatch = path.match(/^\/requests\/([^/]+)$/)
  if (requestMatch) {
    let ticket: string
    try {
      ticket = decodeURIComponent(requestMatch[1])
    } catch {
      return null
    }
    const normalized = validTicket(ticket)
    return normalized ? { name: 'request-detail', params: { ticket: normalized } } : null
  }

  const feedbackMatch = path.match(/^\/feedback\/(satisfaction|public)\/([^/]+)$/)
  if (feedbackMatch) {
    const id = validPositiveId(feedbackMatch[2])
    if (!id) return null
    return {
      name: feedbackMatch[1] === 'public' ? 'public-feedback-detail' : 'satisfaction-detail',
      params: { id: String(id) },
    }
  }

  return null
}

export function appUrlRoute(value: unknown): RouteLocationRaw | null {
  if (typeof value !== 'string') return null

  let url: URL
  try {
    url = new URL(value)
  } catch {
    return null
  }

  let internalPath: string
  if (url.protocol === 'servicekkprl:') {
    internalPath = `/${url.hostname}${url.pathname}${url.search}`
  } else if (
    url.protocol === 'https:' &&
    url.hostname === 'kawanruanglaut.timurbersinar.com' &&
    (url.pathname === '/mobile' || url.pathname.startsWith('/mobile/'))
  ) {
    internalPath = `${url.pathname.slice('/mobile'.length) || '/'}${url.search}`
  } else {
    return null
  }

  return safeInternalRoute(internalPath)
}
