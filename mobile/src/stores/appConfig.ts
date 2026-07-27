import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { api } from '@/api/client'
import type { ApiEnvelope, AppConfig } from '@/types/api'

export const useAppConfigStore = defineStore('app-config', () => {
  const config = ref<AppConfig | null>(null)
  const unavailable = computed(
    () => config.value?.update_required === true || config.value?.maintenance.enabled === true,
  )

  async function refresh(): Promise<void> {
    const response = await api.get<ApiEnvelope<AppConfig>>('/app-config', {
      params: {
        version: import.meta.env.VITE_APP_VERSION ?? '1.0.0',
        build: Number(import.meta.env.VITE_BUILD_NUMBER ?? 1),
      },
    })
    config.value = response.data.data
  }

  function openDistribution(): void {
    if (config.value?.distribution_url) {
      window.open(config.value.distribution_url, '_blank', 'noopener,noreferrer')
    }
  }

  return { config, unavailable, refresh, openDistribution }
})
