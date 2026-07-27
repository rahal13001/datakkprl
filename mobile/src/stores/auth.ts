import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { Device } from '@capacitor/device'
import { Network } from '@capacitor/network'
import axios from 'axios'
import { api } from '@/api/client'
import { secureStorage } from '@/storage/secureStorage'
import type { ApiEnvelope, Capabilities, UserProfile } from '@/types/api'

interface LoginResponse {
  token: string
  expires_at: string
  user: UserProfile
  capabilities: Capabilities
}

export const useAuthStore = defineStore('auth', () => {
  const user = ref<UserProfile | null>(null)
  const capabilities = ref<Capabilities>({})
  const ready = ref(false)
  const expiresAt = ref<string | null>(null)

  const authenticated = computed(() => Boolean(user.value))

  function can(resource: string, action: string): boolean {
    return capabilities.value[resource]?.[action] === true
  }

  async function initialize(): Promise<void> {
    const token = await secureStorage.get<string>('token')
    if (!token) {
      ready.value = true
      return
    }

    user.value = await secureStorage.get<UserProfile>('profile')
    capabilities.value = (await secureStorage.get<Capabilities>('capabilities')) ?? {}
    expiresAt.value = await secureStorage.get<string>('expires_at')
    if (!(await Network.getStatus()).connected) {
      ready.value = true
      return
    }

    try {
      await refreshProfile()
    } catch (error) {
      if (axios.isAxiosError(error) && [401, 403].includes(error.response?.status ?? 0)) {
        await clearSession()
      }
    } finally {
      ready.value = true
    }
  }

  async function login(email: string, password: string): Promise<void> {
    const [identifier, info] = await Promise.all([Device.getId(), Device.getInfo()])
    const response = await api.post<ApiEnvelope<LoginResponse>>('/auth/login', {
      email,
      password,
      installation_id: identifier.identifier,
      device_name: `${info.manufacturer ?? ''} ${info.model}`.trim(),
      platform: info.platform === 'ios' ? 'ios' : 'android',
      app_version: import.meta.env.VITE_APP_VERSION ?? '1.0.0',
      build_number: Number(import.meta.env.VITE_BUILD_NUMBER ?? 1),
      notification_permission: 'prompt',
    })

    await secureStorage.set('token', response.data.data.token)
    expiresAt.value = response.data.data.expires_at
    user.value = response.data.data.user
    capabilities.value = response.data.data.capabilities
    await persistSession()
  }

  async function refreshProfile(): Promise<void> {
    const response = await api.get<
      ApiEnvelope<{ user: UserProfile; capabilities: Capabilities }>
    >('/me')
    user.value = response.data.data.user
    capabilities.value = response.data.data.capabilities
    await persistSession()
  }

  async function logout(allDevices = false): Promise<void> {
    try {
      await api.post(allDevices ? '/auth/logout-all' : '/auth/logout')
    } finally {
      await clearSession()
    }
  }

  async function clearSession(): Promise<void> {
    user.value = null
    capabilities.value = {}
    expiresAt.value = null
    await secureStorage.clear()
  }

  async function persistSession(): Promise<void> {
    if (user.value) await secureStorage.set('profile', user.value)
    await secureStorage.set('capabilities', capabilities.value)
    if (expiresAt.value) await secureStorage.set('expires_at', expiresAt.value)
  }

  return {
    user,
    capabilities,
    ready,
    expiresAt,
    authenticated,
    can,
    initialize,
    login,
    logout,
    refreshProfile,
    clearSession,
  }
})
