<script setup lang="ts">
import { computed } from 'vue'
import { useQuery } from '@tanstack/vue-query'
import {
  IonButton,
  IonContent,
  IonIcon,
  IonPage,
  IonRefresher,
  IonRefresherContent,
} from '@ionic/vue'
import { arrowForwardOutline, chatbubblesOutline, notificationsOutline } from 'ionicons/icons'
import { api, apiError } from '@/api/client'
import AppHeader from '@/components/AppHeader.vue'
import ErrorState from '@/components/ErrorState.vue'
import LoadingSkeleton from '@/components/LoadingSkeleton.vue'
import PageContainer from '@/components/PageContainer.vue'
import StatusBadge from '@/components/StatusBadge.vue'
import EmptyState from '@/components/EmptyState.vue'
import { useAuthStore } from '@/stores/auth'
import { useAppConfigStore } from '@/stores/appConfig'
import { requestDetailRoute } from '@/navigation/safeNavigation'
import type { ApiEnvelope, ClientSummary } from '@/types/api'

interface DashboardData {
  counts: {
    total: number
    waiting: number
    scheduled: number
    completed: number
    mine: number
    unread_notifications: number
  }
  recent_requests: ClientSummary[]
}

const auth = useAuthStore()
const appConfig = useAppConfigStore()
const firstName = computed(() => auth.user?.name.split(' ')[0] ?? 'Petugas')
const query = useQuery({
  queryKey: ['dashboard'],
  queryFn: async () => (await api.get<ApiEnvelope<DashboardData>>('/dashboard')).data.data,
})

async function refresh(event: CustomEvent) {
  await query.refetch()
  ;(event.target as HTMLIonRefresherElement).complete()
}

function dateLabel(value?: string) {
  if (!value) return 'Belum dijadwalkan'
  return new Intl.DateTimeFormat('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }).format(
    new Date(value),
  )
}
</script>

<template>
  <IonPage>
    <AppHeader title="Jago KKPRL" eyebrow="Ruang layanan">
      <template #actions>
          <router-link class="notification-link" to="/tabs/notifications" aria-label="Notifikasi">
            <IonIcon :icon="notificationsOutline" />
            <b v-if="query.data.value?.counts.unread_notifications">
              {{ query.data.value.counts.unread_notifications }}
            </b>
          </router-link>
      </template>
    </AppHeader>
    <IonContent>
      <IonRefresher slot="fixed" @ion-refresh="refresh">
        <IonRefresherContent />
      </IonRefresher>
      <PageContainer>
        <section class="welcome">
          <span class="eyebrow">Ringkasan operasional</span>
          <h1 class="page-title">Selamat bertugas, {{ firstName }}.</h1>
          <p class="page-subtitle">Pantau permohonan dan tindak lanjut hari ini tanpa membuka panel web.</p>
        </section>

        <section v-if="appConfig.config?.update_available" class="update-banner surface">
          <div>
            <strong>Versi {{ appConfig.config.latest_version }} tersedia</strong>
            <small>Pembaruan internal terbaru sudah dapat dipasang.</small>
          </div>
          <IonButton size="small" fill="outline" @click="appConfig.openDistribution">
            Perbarui
          </IonButton>
        </section>

        <LoadingSkeleton
          v-if="query.isLoading.value"
          :rows="4"
          compact
          label="Memuat ringkasan operasional"
        />

        <ErrorState
          v-else-if="query.error.value"
          compact
          :message="apiError(query.error.value)"
          @retry="query.refetch()"
        />

        <div v-else class="metric-grid">
          <router-link to="/tabs/requests" class="metric-card surface">
            <span class="metric-value">{{ query.data.value?.counts.total ?? 0 }}</span>
            <span class="metric-label">Semua permohonan</span>
          </router-link>
          <router-link to="/tabs/requests?scope=mine" class="metric-card surface accent-card">
            <span class="metric-value">{{ query.data.value?.counts.mine ?? 0 }}</span>
            <span class="metric-label">Layanan saya</span>
          </router-link>
          <router-link to="/tabs/requests?status=waiting" class="metric-card surface">
            <span class="metric-value">{{ query.data.value?.counts.waiting ?? 0 }}</span>
            <span class="metric-label">Menunggu penanganan</span>
          </router-link>
          <router-link to="/tabs/requests?status=scheduled" class="metric-card surface">
            <span class="metric-value">{{ query.data.value?.counts.scheduled ?? 0 }}</span>
            <span class="metric-label">Sudah dijadwalkan</span>
          </router-link>
        </div>

        <div class="section-title">
          <span>Aktivitas terbaru</span>
          <router-link to="/tabs/requests">Lihat semua</router-link>
        </div>

        <div v-if="query.data.value?.recent_requests.length" class="request-stack">
          <router-link
            v-for="client in query.data.value.recent_requests"
            :key="client.ticket_number"
            :to="requestDetailRoute(client.ticket_number)"
            class="request-card surface"
          >
            <div class="request-topline">
              <span>{{ client.ticket_number }}</span>
              <StatusBadge :status="client.status" />
            </div>
            <h3>{{ client.name }}</h3>
            <p>{{ client.service?.name ?? 'Layanan KKPRL' }}</p>
            <div class="request-foot">
              <span>{{ dateLabel(client.next_schedule?.date) }}</span>
              <IonIcon :icon="arrowForwardOutline" />
            </div>
          </router-link>
        </div>
        <EmptyState
          v-else-if="!query.isLoading.value"
          title="Belum ada aktivitas"
          body="Permohonan terbaru akan muncul di sini."
        />

        <IonButton
          v-if="auth.can('public_feedback', 'list') || auth.can('satisfaction_surveys', 'list')"
          router-link="/feedback"
          expand="block"
          fill="outline"
          class="feedback-link"
        >
          <IonIcon slot="start" :icon="chatbubblesOutline" />
          Masukan & penilaian
        </IonButton>
      </PageContainer>
    </IonContent>
  </IonPage>
</template>

<style scoped>
.notification-link {
  color: white;
  font-size: 1.35rem;
  position: relative;
}

.notification-link b {
  align-items: center;
  background: var(--ion-color-secondary);
  border: 2px solid #fff;
  border-radius: 999px;
  color: #fff;
  display: flex;
  font-size: 0.58rem;
  height: 18px;
  justify-content: center;
  position: absolute;
  right: -8px;
  top: -8px;
  width: 18px;
}

.welcome {
  margin: 10px 2px 24px;
}

.update-banner {
  align-items: center;
  display: flex;
  gap: 14px;
  justify-content: space-between;
  margin-bottom: 16px;
  padding: 14px 16px;
}

.update-banner > div {
  display: grid;
  gap: 3px;
}

.update-banner strong {
  color: var(--app-ink);
  font-size: 0.86rem;
}

.update-banner small {
  color: var(--app-muted);
  font-size: 0.72rem;
}

.metric-card {
  color: inherit;
  text-decoration: none;
}

.accent-card {
  background: linear-gradient(145deg, #0d3150, #145d79);
  border: 0;
}

.accent-card .metric-value,
.accent-card .metric-label {
  color: #fff;
}

.request-stack {
  display: grid;
  gap: 12px;
}

.request-card {
  color: inherit;
  padding: 16px;
  text-decoration: none;
}

.request-topline,
.request-foot {
  align-items: center;
  color: var(--app-muted);
  display: flex;
  font-size: 0.76rem;
  font-weight: 650;
  justify-content: space-between;
}

.request-card h3 {
  color: var(--app-ink);
  font-size: 1.05rem;
  margin: 14px 0 4px;
}

.request-card p {
  color: var(--app-muted);
  font-size: 0.84rem;
  margin: 0 0 14px;
}

.section-title a {
  color: var(--ion-color-primary);
  font-size: 0.78rem;
  text-decoration: none;
}

.feedback-link {
  --border-radius: 14px;
  margin-top: 22px;
}
</style>
