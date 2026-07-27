<script setup lang="ts">
import { ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import {
  IonButton,
  IonContent,
  IonIcon,
  IonInput,
  IonPage,
  IonSpinner,
} from '@ionic/vue'
import { lockClosedOutline, mailOutline, waterOutline } from 'ionicons/icons'
import { apiError } from '@/api/client'
import { initializePush } from '@/notifications/push'
import { useAuthStore } from '@/stores/auth'
import { safeInternalRoute } from '@/navigation/safeNavigation'

const auth = useAuthStore()
const router = useRouter()
const route = useRoute()
const email = ref('')
const password = ref('')
const loading = ref(false)
const error = ref('')

async function submit() {
  error.value = ''
  loading.value = true
  try {
    await auth.login(email.value, password.value)
    await initializePush(router)
    const redirect = safeInternalRoute(route.query.redirect) ?? { name: 'dashboard' }
    await router.replace(redirect)
  } catch (reason) {
    error.value = apiError(reason)
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <IonPage>
    <IonContent :fullscreen="true">
      <main class="login-shell">
        <section class="brand-panel">
          <div class="brand-mark"><IonIcon :icon="waterOutline" /></div>
          <span class="eyebrow">Marine Space Services</span>
          <h1>ServiceKKPRL</h1>
          <p>Kontrol layanan, jadwal, penugasan, dan masukan publik dalam satu aplikasi internal.</p>
          <div class="wave" aria-hidden="true" />
        </section>

        <form class="login-card surface" @submit.prevent="submit">
          <div>
            <span class="eyebrow">Akses petugas</span>
            <h2>Selamat datang</h2>
            <p>Gunakan akun Summary yang telah memperoleh akses ServiceKKPRL.</p>
          </div>

          <label>
            <span>Email</span>
            <div class="input-wrap">
              <IonIcon :icon="mailOutline" />
              <IonInput
                v-model="email"
                type="email"
                inputmode="email"
                autocomplete="username"
                placeholder="nama@instansi.go.id"
                required
              />
            </div>
          </label>

          <label>
            <span>Kata sandi</span>
            <div class="input-wrap">
              <IonIcon :icon="lockClosedOutline" />
              <IonInput
                v-model="password"
                type="password"
                autocomplete="current-password"
                placeholder="Masukkan kata sandi"
                required
              />
            </div>
          </label>

          <div v-if="error" class="error-box" role="alert">{{ error }}</div>

          <IonButton expand="block" type="submit" size="large" :disabled="loading">
            <IonSpinner v-if="loading" name="crescent" />
            <span v-else>Masuk ke aplikasi</span>
          </IonButton>

          <small>Akses dan tindakan mengikuti role serta permission Filament Shield.</small>
        </form>
      </main>
    </IonContent>
  </IonPage>
</template>

<style scoped>
.login-shell {
  min-height: 100%;
  padding: max(28px, env(safe-area-inset-top)) 18px max(28px, env(safe-area-inset-bottom));
}

.brand-panel {
  background: linear-gradient(145deg, #0b2b47, #105879);
  border-radius: 28px;
  color: white;
  min-height: 300px;
  overflow: hidden;
  padding: 30px;
  position: relative;
}

.brand-panel .eyebrow {
  color: #ffd28c;
}

.brand-panel h1 {
  font-size: clamp(2rem, 9vw, 3.3rem);
  letter-spacing: -0.06em;
  margin: 12px 0;
  white-space: nowrap;
}

.brand-panel p {
  color: #d7e9f0;
  line-height: 1.6;
  max-width: 440px;
}

.brand-mark {
  align-items: center;
  background: rgba(255, 255, 255, 0.12);
  border: 1px solid rgba(255, 255, 255, 0.16);
  border-radius: 18px;
  display: flex;
  font-size: 2rem;
  height: 58px;
  justify-content: center;
  margin-bottom: 30px;
  width: 58px;
}

.wave {
  background: rgba(255, 255, 255, 0.06);
  border-radius: 50%;
  bottom: -120px;
  height: 260px;
  position: absolute;
  right: -40px;
  width: 360px;
}

.login-card {
  margin: -28px 14px 0;
  padding: 26px 22px;
  position: relative;
  z-index: 2;
}

.login-card h2 {
  color: var(--app-ink);
  font-size: 1.55rem;
  letter-spacing: -0.03em;
  margin: 4px 0;
}

.login-card p,
.login-card small {
  color: var(--app-muted);
  line-height: 1.5;
}

label {
  display: block;
  margin-top: 18px;
}

label > span {
  color: var(--app-ink);
  display: block;
  font-size: 0.82rem;
  font-weight: 750;
  margin: 0 0 7px 4px;
}

.input-wrap {
  align-items: center;
  background: #f7f9fa;
  border: 1px solid var(--app-line);
  border-radius: 14px;
  display: flex;
  padding: 2px 13px;
}

.input-wrap ion-icon {
  color: #718691;
  font-size: 1.2rem;
}

ion-button {
  --border-radius: 14px;
  margin: 22px 0 14px;
}

@media (min-width: 800px) {
  .login-shell {
    align-items: center;
    display: grid;
    gap: 0;
    grid-template-columns: 1.15fr 0.85fr;
    margin: 0 auto;
    max-width: 1050px;
  }

  .brand-panel {
    min-height: 610px;
    padding: 58px;
  }

  .login-card {
    margin: 0 0 0 -38px;
    padding: 38px;
  }
}
</style>
