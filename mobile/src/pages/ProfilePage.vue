<script setup lang="ts">
import { useRouter } from 'vue-router'
import {
  IonButton,
  IonChip,
  IonContent,
  IonHeader,
  IonIcon,
  IonPage,
  IonToolbar,
} from '@ionic/vue'
import {
  businessOutline,
  callOutline,
  logOutOutline,
  mailOutline,
  personOutline,
  shieldCheckmarkOutline,
} from 'ionicons/icons'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const router = useRouter()
const appVersion = import.meta.env.VITE_APP_VERSION ?? '1.0.0'

async function logout(all = false) {
  await auth.logout(all)
  await router.replace('/login')
}
</script>

<template>
  <IonPage>
    <IonHeader>
      <IonToolbar>
        <div class="page-toolbar">
          <span class="eyebrow">Akun petugas</span>
          <h1>Profil</h1>
        </div>
      </IonToolbar>
    </IonHeader>
    <IonContent>
      <main class="page-shell profile-shell">
        <section class="profile-hero">
          <div class="avatar">{{ auth.user?.name.slice(0, 1).toUpperCase() }}</div>
          <h2>{{ auth.user?.name }}</h2>
          <p>{{ auth.user?.jabatan || 'Petugas ServiceKKPRL' }}</p>
          <div>
            <IonChip v-for="role in auth.user?.roles" :key="role" color="secondary">
              <IonIcon :icon="shieldCheckmarkOutline" />
              {{ role }}
            </IonChip>
          </div>
        </section>

        <section class="surface profile-details">
          <div><IonIcon :icon="mailOutline" /><span><small>Email</small>{{ auth.user?.email }}</span></div>
          <div><IonIcon :icon="personOutline" /><span><small>NIP</small>{{ auth.user?.nip || '-' }}</span></div>
          <div><IonIcon :icon="businessOutline" /><span><small>Instansi</small>{{ auth.user?.instansi || '-' }}</span></div>
          <div><IonIcon :icon="callOutline" /><span><small>Status akses</small>Aktif</span></div>
        </section>

        <section class="surface access-card">
          <h3>Akses mobile</h3>
          <p>{{ auth.user?.permissions.length ?? 0 }} permission aktif. Tampilan dan tindakan selalu mengikuti Filament Shield.</p>
        </section>

        <IonButton expand="block" @click="logout(false)">
          <IonIcon slot="start" :icon="logOutOutline" />
          Keluar dari perangkat ini
        </IonButton>
        <IonButton expand="block" fill="clear" color="danger" @click="logout(true)">
          Keluar dari semua perangkat
        </IonButton>

        <p class="version">ServiceKKPRL {{ appVersion }}</p>
      </main>
    </IonContent>
  </IonPage>
</template>

<style scoped>
.page-toolbar {
  padding: 8px 18px;
}

.page-toolbar h1 {
  color: var(--app-ink);
  font-size: 1.25rem;
  margin: 2px 0;
}

.profile-shell {
  padding-top: 18px;
}

.profile-hero {
  background: linear-gradient(145deg, #0d3150, #176580);
  border-radius: 26px;
  color: #fff;
  padding: 28px 20px;
  text-align: center;
}

.avatar {
  align-items: center;
  background: #fff;
  border-radius: 20px;
  color: var(--ion-color-primary);
  display: flex;
  font-size: 1.8rem;
  font-weight: 850;
  height: 64px;
  justify-content: center;
  margin: 0 auto 14px;
  width: 64px;
}

.profile-hero h2 {
  font-size: 1.35rem;
  margin: 5px 0;
}

.profile-hero p {
  color: #d5e7ee;
  margin: 0 0 10px;
}

.profile-details {
  margin-top: 14px;
  padding: 6px 16px;
}

.profile-details > div {
  align-items: center;
  border-bottom: 1px solid #edf1f3;
  color: var(--ion-color-primary);
  display: flex;
  gap: 13px;
  padding: 14px 2px;
}

.profile-details > div:last-child {
  border-bottom: 0;
}

.profile-details span {
  color: var(--app-ink);
  display: grid;
  font-size: 0.88rem;
  gap: 2px;
}

.profile-details small {
  color: var(--app-muted);
  font-size: 0.7rem;
}

.access-card {
  margin: 14px 0 20px;
  padding: 17px;
}

.access-card h3 {
  color: var(--app-ink);
  margin: 0 0 5px;
}

.access-card p,
.version {
  color: var(--app-muted);
  font-size: 0.8rem;
  line-height: 1.5;
}

.version {
  margin-top: 24px;
  text-align: center;
}

ion-button {
  --border-radius: 14px;
}
</style>
