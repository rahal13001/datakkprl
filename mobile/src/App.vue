<script setup lang="ts">
import { onMounted, onUnmounted, ref } from 'vue'
import { IonApp, IonIcon, IonRouterOutlet } from '@ionic/vue'
import { cloudOfflineOutline } from 'ionicons/icons'
import { type PluginListenerHandle } from '@capacitor/core'
import { Network } from '@capacitor/network'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useQueryClient } from '@tanstack/vue-query'

const offline = ref(false)
const router = useRouter()
const auth = useAuthStore()
const queryClient = useQueryClient()
let listener: PluginListenerHandle | undefined

async function unauthorized() {
  await auth.clearSession()
  await router.replace('/login')
}

onMounted(async () => {
  offline.value = !(await Network.getStatus()).connected
  listener = await Network.addListener('networkStatusChange', (status) => {
    offline.value = !status.connected
    if (status.connected && auth.authenticated) {
      auth.refreshProfile().catch(() => undefined)
      queryClient.invalidateQueries().catch(() => undefined)
    }
  })
  window.addEventListener('servicekkprl:unauthorized', unauthorized)
})

onUnmounted(() => {
  listener?.remove()
  window.removeEventListener('servicekkprl:unauthorized', unauthorized)
})
</script>

<template>
  <IonApp>
    <div v-if="offline" class="offline-banner" role="status">
      <IonIcon :icon="cloudOfflineOutline" />
      Mode offline — perubahan dinonaktifkan
    </div>
    <IonRouterOutlet />
  </IonApp>
</template>
