<script setup lang="ts">
import {
  IonIcon,
  IonLabel,
  IonPage,
  IonRouterOutlet,
  IonTabBar,
  IonTabButton,
  IonTabs,
} from '@ionic/vue'
import {
  homeOutline,
  notificationsOutline,
  personCircleOutline,
  waterOutline,
} from 'ionicons/icons'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
</script>

<template>
  <IonPage>
    <IonTabs>
      <IonRouterOutlet />
      <IonTabBar slot="bottom">
        <IonTabButton v-if="auth.can('dashboard', 'view')" tab="dashboard" href="/tabs/dashboard">
          <IonIcon :icon="homeOutline" />
          <IonLabel>Beranda</IonLabel>
        </IonTabButton>
        <IonTabButton v-if="auth.can('clients', 'list')" tab="requests" href="/tabs/requests">
          <IonIcon :icon="waterOutline" />
          <IonLabel>Layanan</IonLabel>
        </IonTabButton>
        <IonTabButton tab="notifications" href="/tabs/notifications">
          <IonIcon :icon="notificationsOutline" />
          <IonLabel>Notifikasi</IonLabel>
        </IonTabButton>
        <IonTabButton tab="profile" href="/tabs/profile">
          <IonIcon :icon="personCircleOutline" />
          <IonLabel>Profil</IonLabel>
        </IonTabButton>
      </IonTabBar>
    </IonTabs>
  </IonPage>
</template>

<style scoped>
ion-tab-bar {
  --background: rgba(255, 255, 255, 0.97);
  border-top: 1px solid var(--app-line);
  padding: 5px 8px max(5px, env(safe-area-inset-bottom));
}

ion-tab-button {
  --color: #7b8c96;
  --color-selected: var(--ion-color-primary);
  font-weight: 650;
}
</style>
