<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useQuery } from '@tanstack/vue-query'
import {
  IonContent,
  IonHeader,
  IonIcon,
  IonPage,
  IonRefresher,
  IonRefresherContent,
  IonSearchbar,
  IonSegment,
  IonSegmentButton,
  IonSkeletonText,
  IonToolbar,
} from '@ionic/vue'
import { calendarOutline, chevronForwardOutline, locationOutline } from 'ionicons/icons'
import { api, apiError } from '@/api/client'
import StatusBadge from '@/components/StatusBadge.vue'
import EmptyState from '@/components/EmptyState.vue'
import { navigationMessage, requestDetailRoute } from '@/navigation/safeNavigation'
import type { ApiEnvelope, ClientSummary } from '@/types/api'

const route = useRoute()

function filterValue(value: unknown, allowed: readonly string[], fallback: string): string {
  const candidate = Array.isArray(value) ? value[0] : value
  return typeof candidate === 'string' && allowed.includes(candidate) ? candidate : fallback
}

const status = ref(
  filterValue(route.query.status, ['all', 'waiting', 'scheduled', 'completed'], 'all'),
)
const scope = ref(filterValue(route.query.scope, ['all', 'mine'], 'all'))
const ticket = ref('')
const navigationNotice = computed(() => navigationMessage(route.query.navigation_error))

const params = computed(() => ({
  status: status.value === 'all' ? undefined : status.value,
  scope: scope.value,
  ticket: ticket.value || undefined,
  per_page: 40,
}))

const query = useQuery({
  queryKey: ['clients', params],
  queryFn: async () =>
    (await api.get<ApiEnvelope<ClientSummary[]>>('/clients', { params: params.value })).data.data,
})

watch(
  () => route.query,
  (value) => {
    status.value = filterValue(value.status, ['all', 'waiting', 'scheduled', 'completed'], 'all')
    scope.value = filterValue(value.scope, ['all', 'mine'], 'all')
  },
)

async function refresh(event: CustomEvent) {
  await query.refetch()
  ;(event.target as HTMLIonRefresherElement).complete()
}

function scheduleLabel(client: ClientSummary) {
  if (!client.next_schedule) return 'Belum ada jadwal'
  return new Intl.DateTimeFormat('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }).format(
    new Date(client.next_schedule.date),
  )
}
</script>

<template>
  <IonPage>
    <IonHeader>
      <IonToolbar>
        <div class="page-toolbar">
          <span class="eyebrow">Service desk</span>
          <h1>Permohonan layanan</h1>
        </div>
      </IonToolbar>
    </IonHeader>
    <IonContent>
      <IonRefresher slot="fixed" @ion-refresh="refresh">
        <IonRefresherContent />
      </IonRefresher>
      <main class="page-shell requests-shell">
        <div v-if="navigationNotice" class="notice-box" role="status">{{ navigationNotice }}</div>
        <div class="scope-switch surface">
          <button :class="{ active: scope === 'all' }" @click="scope = 'all'">Semua</button>
          <button :class="{ active: scope === 'mine' }" @click="scope = 'mine'">Layanan saya</button>
        </div>

        <IonSearchbar
          v-model="ticket"
          placeholder="Cari nomor tiket lengkap"
          :debounce="450"
          class="ticket-search"
        />

        <IonSegment v-model="status" :scrollable="true">
          <IonSegmentButton value="all">Semua</IonSegmentButton>
          <IonSegmentButton value="waiting">Menunggu</IonSegmentButton>
          <IonSegmentButton value="scheduled">Terjadwal</IonSegmentButton>
          <IonSegmentButton value="completed">Selesai</IonSegmentButton>
        </IonSegment>

        <div v-if="query.isLoading.value" class="request-list">
          <div v-for="index in 5" :key="index" class="request-row surface">
            <IonSkeletonText :animated="true" style="width: 40%" />
            <IonSkeletonText :animated="true" style="width: 75%; height: 22px" />
            <IonSkeletonText :animated="true" style="width: 55%" />
          </div>
        </div>

        <div v-else-if="query.error.value" class="error-box">{{ apiError(query.error.value) }}</div>

        <div v-else-if="query.data.value?.length" class="request-list">
          <router-link
            v-for="client in query.data.value"
            :key="client.ticket_number"
            :to="requestDetailRoute(client.ticket_number)"
            class="request-row surface"
          >
            <div class="row-head">
              <span>{{ client.ticket_number }}</span>
              <StatusBadge :status="client.status" />
            </div>
            <h2>{{ client.name }}</h2>
            <p>{{ client.instance || client.service?.name || 'Pemohon layanan' }}</p>
            <div class="row-meta">
              <span><IonIcon :icon="calendarOutline" /> {{ scheduleLabel(client) }}</span>
              <span v-if="client.location"><IonIcon :icon="locationOutline" /> {{ client.location.name }}</span>
              <IonIcon class="next" :icon="chevronForwardOutline" />
            </div>
          </router-link>
        </div>

        <EmptyState
          v-else
          title="Tidak ada permohonan"
          body="Ubah filter atau tarik layar untuk memperbarui data."
        />
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

.requests-shell {
  padding-top: 14px;
}

.scope-switch {
  display: grid;
  grid-template-columns: 1fr 1fr;
  padding: 4px;
}

.scope-switch button {
  background: transparent;
  border: 0;
  border-radius: 14px;
  color: var(--app-muted);
  font-weight: 750;
  padding: 11px;
}

.scope-switch button.active {
  background: var(--ion-color-primary);
  color: #fff;
}

.ticket-search {
  --background: #fff;
  --border-radius: 14px;
  --box-shadow: none;
  margin: 10px -8px 2px;
}

ion-segment {
  margin-bottom: 14px;
}

.request-list {
  display: grid;
  gap: 11px;
}

.request-row {
  color: inherit;
  padding: 16px;
  text-decoration: none;
}

.row-head {
  align-items: center;
  color: var(--app-muted);
  display: flex;
  font-size: 0.72rem;
  font-weight: 750;
  justify-content: space-between;
}

.request-row h2 {
  color: var(--app-ink);
  font-size: 1.05rem;
  margin: 13px 0 4px;
}

.request-row p {
  color: var(--app-muted);
  font-size: 0.82rem;
  margin: 0;
}

.row-meta {
  align-items: center;
  border-top: 1px solid #edf1f3;
  color: var(--app-muted);
  display: flex;
  flex-wrap: wrap;
  font-size: 0.74rem;
  gap: 12px;
  margin-top: 14px;
  padding-top: 12px;
}

.row-meta span {
  align-items: center;
  display: inline-flex;
  gap: 4px;
}

.row-meta .next {
  color: var(--ion-color-primary);
  margin-left: auto;
}
</style>
