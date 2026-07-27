<script setup lang="ts">
import { IonButton, IonContent, IonIcon, IonPage } from '@ionic/vue'
import { cloudDownloadOutline } from 'ionicons/icons'
import { useAppConfigStore } from '@/stores/appConfig'

const appConfig = useAppConfigStore()
</script>

<template>
  <IonPage>
    <IonContent>
      <main class="update-shell">
        <div class="surface update-card">
          <IonIcon :icon="cloudDownloadOutline" />
          <span class="eyebrow">
            {{ appConfig.config?.maintenance.enabled ? 'Pemeliharaan sistem' : 'Pembaruan diperlukan' }}
          </span>
          <h1>
            {{ appConfig.config?.maintenance.enabled
              ? 'ServiceKKPRL sedang dalam pemeliharaan.'
              : 'Versi aplikasi ini sudah tidak didukung.' }}
          </h1>
          <p>
            {{ appConfig.config?.maintenance.enabled
              ? (appConfig.config.maintenance.message || 'Silakan coba kembali beberapa saat lagi.')
              : 'Pasang versi ServiceKKPRL terbaru dari Firebase App Distribution untuk melanjutkan.' }}
          </p>
          <IonButton
            v-if="!appConfig.config?.maintenance.enabled && appConfig.config?.distribution_url"
            expand="block"
            @click="appConfig.openDistribution"
          >
            Buka halaman pembaruan
          </IonButton>
        </div>
      </main>
    </IonContent>
  </IonPage>
</template>

<style scoped>
.update-shell {
  align-items: center;
  display: flex;
  min-height: 100%;
  padding: 24px;
}

.update-card {
  margin: auto;
  max-width: 460px;
  padding: 34px 26px;
  text-align: center;
}

.update-card > ion-icon {
  color: var(--ion-color-secondary);
  font-size: 3.5rem;
  margin-bottom: 16px;
}

.update-card h1 {
  color: var(--app-ink);
  font-size: 1.6rem;
  letter-spacing: -0.03em;
}

.update-card p {
  color: var(--app-muted);
  line-height: 1.6;
}

ion-button {
  --border-radius: 14px;
  margin-top: 24px;
}
</style>
