import { createApp } from 'vue'
import { createPinia } from 'pinia'
import { IonicVue } from '@ionic/vue'
import { VueQueryPlugin, QueryClient } from '@tanstack/vue-query'
import App from './App.vue'
import router from './router'
import { useAuthStore } from '@/stores/auth'
import { useAppConfigStore } from '@/stores/appConfig'
import { initializePush } from '@/notifications/push'
import { initializeAppLinks } from '@/navigation/appLinks'

import '@ionic/vue/css/core.css'
import '@ionic/vue/css/normalize.css'
import '@ionic/vue/css/structure.css'
import '@ionic/vue/css/typography.css'
import '@ionic/vue/css/padding.css'
import '@ionic/vue/css/float-elements.css'
import '@ionic/vue/css/text-alignment.css'
import '@ionic/vue/css/text-transformation.css'
import '@ionic/vue/css/flex-utils.css'
import '@ionic/vue/css/display.css'
import './theme/variables.css'
import './theme/app.css'

const app = createApp(App)
const pinia = createPinia()
const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      staleTime: 30_000,
      retry: 1,
      refetchOnWindowFocus: false,
    },
  },
})

app.use(IonicVue)
app.use(pinia)
app.use(VueQueryPlugin, { queryClient })
app.use(router)

const auth = useAuthStore()
const appConfig = useAppConfigStore()
app.mount('#app')

async function initializeApplication(): Promise<void> {
  const configRequest = appConfig.refresh().catch(() => {
    // Login remains available during a transient configuration check failure.
  })

  await auth.initialize()
  await initializeAppLinks(router).catch(() => undefined)

  if (auth.authenticated) {
    if (router.currentRoute.value.name === 'login') {
      await router.replace('/')
    }
    await initializePush(router).catch(() => undefined)
  }

  await configRequest
  if (appConfig.unavailable) {
    await router.replace('/update-required')
  }
}

void initializeApplication()
