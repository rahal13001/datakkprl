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
  --background: rgba(255, 255, 255, 0.96);
  backdrop-filter: blur(18px);
  border-top: 1px solid rgba(163, 184, 194, 0.42);
  box-shadow: 0 -12px 32px rgba(8, 43, 69, 0.1);
  min-height: calc(4.75rem + env(safe-area-inset-bottom));
  padding: var(--app-space-1) var(--app-space-2) var(--app-safe-bottom);
}

ion-tab-button {
  --color: #718590;
  --color-selected: var(--ion-color-primary);
  border-radius: var(--app-radius-lg);
  font-weight: 650;
  min-height: var(--app-touch-target);
}

ion-tab-button.tab-selected {
  --background: linear-gradient(145deg, rgba(21, 160, 166, 0.16), rgba(13, 77, 104, 0.1));
  font-weight: 800;
}

ion-tab-button ion-icon {
  font-size: var(--app-icon-md);
}

ion-tab-button.tab-selected ion-icon {
  background: var(--ion-color-primary);
  border-radius: var(--app-radius-pill);
  color: white;
  padding: 0.32rem 0.7rem;
}
</style>
