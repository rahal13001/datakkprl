import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it } from 'vitest'
import {
  appUrlRoute,
  requestDetailRoute,
  safeInternalRoute,
  validPositiveId,
  validTicket,
} from '@/navigation/safeNavigation'
import { useAuthStore } from '@/stores/auth'
import router from '@/router'

describe('safe navigation', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
  })

  it('rejects missing, sentinel, malformed, and non-positive identifiers', () => {
    expect(validTicket(undefined)).toBeNull()
    expect(validTicket('undefined')).toBeNull()
    expect(validTicket(' null ')).toBeNull()
    expect(validTicket('')).toBeNull()
    expect(validTicket('ticket/child')).toBeNull()
    expect(validPositiveId('0')).toBeNull()
    expect(validPositiveId('NaN')).toBeNull()
  })

  it('constructs detail routes only from valid ticket numbers', () => {
    expect(requestDetailRoute('TICKET-20260727-AB12')).toEqual({
      name: 'request-detail',
      params: { ticket: 'TICKET-20260727-AB12' },
    })
    expect(requestDetailRoute(undefined)).toMatchObject({
      name: 'requests',
      query: { navigation_error: 'invalid_resource' },
    })
  })

  it('allowlists notification, redirect, and app-link destinations', () => {
    expect(safeInternalRoute('/requests/TICKET-20260727-AB12')).toEqual({
      name: 'request-detail',
      params: { ticket: 'TICKET-20260727-AB12' },
    })
    expect(safeInternalRoute('/requests/%75ndefined')).toBeNull()
    expect(safeInternalRoute('//evil.example/requests/1')).toBeNull()
    expect(safeInternalRoute('https://evil.example/requests/1')).toBeNull()
    expect(appUrlRoute('servicekkprl://requests/TICKET-20260727-AB12')).toEqual({
      name: 'request-detail',
      params: { ticket: 'TICKET-20260727-AB12' },
    })
    expect(
      appUrlRoute(
        'https://kawanruanglaut.timurbersinar.com/mobile/feedback/public/12',
      ),
    ).toEqual({
      name: 'public-feedback-detail',
      params: { id: '12' },
    })
    expect(appUrlRoute('https://evil.example/mobile/requests/1')).toBeNull()
  })

  it('redirects an authenticated undefined detail route before page loading', async () => {
    const auth = useAuthStore()
    auth.$patch({
      user: {
        id: 1,
        name: 'Test Officer',
        email: 'officer@example.test',
        roles: [],
        permissions: [],
        active: true,
      },
      capabilities: {
        clients: { list: true },
        notifications: { list: true },
      },
    })

    await router.push('/requests/undefined')

    expect(router.currentRoute.value.name).toBe('requests')
    expect(router.currentRoute.value.query.navigation_error).toBe('invalid_resource')
  })
})
