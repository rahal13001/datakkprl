import { Capacitor } from '@capacitor/core'
import { Device } from '@capacitor/device'
import {
  PushNotifications,
  type ActionPerformed,
  type PermissionStatus,
} from '@capacitor/push-notifications'
import type { Router } from 'vue-router'
import { api } from '@/api/client'
import {
  navigationFallback,
  safeInternalRoute,
} from '@/navigation/safeNavigation'

const channels = [
  { id: 'assignments', name: 'Penugasan', importance: 4 },
  { id: 'schedule_changes', name: 'Perubahan Jadwal', importance: 4 },
  { id: 'new_requests', name: 'Permohonan Baru', importance: 3 },
  { id: 'feedback', name: 'Masukan & Penilaian', importance: 3 },
  { id: 'system_updates', name: 'Pembaruan Sistem', importance: 2 },
] as const

let listenersInitialized = false

async function devicePayload(permission: PermissionStatus, token?: string) {
  const [identifier, info] = await Promise.all([Device.getId(), Device.getInfo()])
  return {
    installation_id: identifier.identifier,
    platform: info.platform === 'ios' ? 'ios' : 'android',
    device_name: `${info.manufacturer ?? ''} ${info.model}`.trim(),
    app_version: import.meta.env.VITE_APP_VERSION ?? '1.0.0',
    build_number: Number(import.meta.env.VITE_BUILD_NUMBER ?? 1),
    push_registration: token,
    notification_permission: permission.receive,
  }
}

export async function initializePush(router: Router): Promise<void> {
  if (!Capacitor.isNativePlatform()) return

  if (!listenersInitialized) {
    for (const channel of channels) {
      await PushNotifications.createChannel({
        ...channel,
        description: channel.name,
        visibility: 0,
        vibration: channel.importance >= 3,
      })
    }

    await PushNotifications.addListener('registration', async ({ value }) => {
      const permission = await PushNotifications.checkPermissions()
      await api.put('/me/device', await devicePayload(permission, value))
    })

    await PushNotifications.addListener(
      'pushNotificationActionPerformed',
      async (event: ActionPerformed) => {
        const destination = safeInternalRoute(event.notification.data?.route)
        await router.push(
          destination ?? navigationFallback('notifications', 'invalid_resource'),
        )
      },
    )
    listenersInitialized = true
  }

  let permission = await PushNotifications.checkPermissions()
  if (permission.receive === 'prompt') {
    permission = await PushNotifications.requestPermissions()
  }

  await api.put('/me/device', await devicePayload(permission))
  if (permission.receive === 'granted') {
    await PushNotifications.register()
  }
}
