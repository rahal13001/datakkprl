import { Capacitor } from '@capacitor/core'
import { App } from '@capacitor/app'
import type { Router } from 'vue-router'
import { appUrlRoute, navigationFallback } from '@/navigation/safeNavigation'

let initialized = false

export async function initializeAppLinks(router: Router): Promise<void> {
  if (!Capacitor.isNativePlatform() || initialized) return
  initialized = true

  const navigate = async (url: unknown) => {
    const destination = appUrlRoute(url)
    await router.push(
      destination ?? navigationFallback('notifications', 'invalid_resource'),
    )
  }

  await App.addListener('appUrlOpen', ({ url }) => navigate(url))
  const launch = await App.getLaunchUrl()
  if (launch?.url) await navigate(launch.url)
}
