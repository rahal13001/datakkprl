import axios, { AxiosError } from 'axios'
import { Network } from '@capacitor/network'
import { secureStorage } from '@/storage/secureStorage'
import type { ApiErrorBody } from '@/types/api'

export const api = axios.create({
  baseURL:
    import.meta.env.VITE_API_BASE_URL ??
    'https://kawanruanglaut.timurbersinar.com/api/mobile/v1',
  timeout: 20_000,
  headers: {
    Accept: 'application/json',
  },
})

api.interceptors.request.use(async (config) => {
  const method = config.method?.toLowerCase() ?? 'get'
  if (!['get', 'head', 'options'].includes(method)) {
    const network = await Network.getStatus()
    if (!network.connected) {
      throw new Error('Perubahan tidak tersedia saat perangkat offline.')
    }
  }

  const token = await secureStorage.get<string>('token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

api.interceptors.response.use(
  (response) => response,
  async (error: AxiosError) => {
    if (error.response?.status === 401) {
      await secureStorage.clear()
      window.dispatchEvent(new CustomEvent('servicekkprl:unauthorized'))
    }
    return Promise.reject(error)
  },
)

export function apiError(error: unknown): string {
  const axiosError = error as AxiosError<ApiErrorBody>
  const body = axiosError.response?.data
  if (body?.errors) {
    return Object.values(body.errors).flat().join(' ')
  }
  return body?.message ?? axiosError.message ?? 'Terjadi kesalahan. Silakan coba lagi.'
}
