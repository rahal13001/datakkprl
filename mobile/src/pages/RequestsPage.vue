<script setup lang="ts">
import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useQuery } from '@tanstack/vue-query'
import {
  IonContent,
  IonIcon,
  IonPage,
  IonRefresher,
  IonRefresherContent,
  IonSegment,
  IonSegmentButton,
} from '@ionic/vue'
import { calendarOutline, locationOutline } from 'ionicons/icons'
import { api, apiError } from '@/api/client'
import AppHeader from '@/components/AppHeader.vue'
import DataListCard from '@/components/DataListCard.vue'
import EmptyState from '@/components/EmptyState.vue'
import ErrorState from '@/components/ErrorState.vue'
import LoadingSkeleton from '@/components/LoadingSkeleton.vue'
import PageContainer from '@/components/PageContainer.vue'
import PaginationControl from '@/components/PaginationControl.vue'
import SearchToolbar from '@/components/SearchToolbar.vue'
import StatusBadge from '@/components/StatusBadge.vue'
import { navigationMessage, requestDetailRoute } from '@/navigation/safeNavigation'
import type { ApiEnvelope, ClientSummary } from '@/types/api'

interface CursorMeta extends Record<string, unknown> {
  per_page?: number
  next_cursor?: string | null
  previous_cursor?: string | null
}

const route = useRoute()
const router = useRouter()
const allowedStatuses = ['all', 'waiting', 'scheduled', 'completed'] as const
const allowedScopes = ['all', 'mine'] as const

function queryValue(value: unknown, maxLength = 100): string {
  const candidate = Array.isArray(value) ? value[0] : value
  return typeof candidate === 'string' ? candidate.slice(0, maxLength) : ''
}

function filterValue(
  value: unknown,
  allowed: readonly string[],
  fallback: string,
): string {
  const candidate = queryValue(value)
  return allowed.includes(candidate) ? candidate : fallback
}

function pageValue(value: unknown): number {
  const parsed = Number.parseInt(queryValue(value, 8), 10)
  return Number.isInteger(parsed) && parsed > 0 ? parsed : 1
}

function normalizedQuery(overrides: Record<string, string | undefined>) {
  const next = {
    status: filterValue(route.query.status, allowedStatuses, 'all'),
    scope: filterValue(route.query.scope, allowedScopes, 'all'),
    search: queryValue(route.query.search),
    cursor: queryValue(route.query.cursor, 2048),
    page: String(pageValue(route.query.page)),
    navigation_error: queryValue(route.query.navigation_error, 50),
    ...overrides,
  }

  if (next.status === 'all') next.status = ''
  if (next.scope === 'all') next.scope = ''
  if (next.page === '1') next.page = ''

  return Object.fromEntries(
    Object.entries(next).filter((entry): entry is [string, string] => Boolean(entry[1])),
  )
}

function updateListQuery(
  overrides: Record<string, string | undefined>,
  resetPagination = false,
) {
  const pagination = resetPagination ? { cursor: undefined, page: undefined } : {}
  return router.replace({
    name: 'requests',
    query: normalizedQuery({ ...overrides, ...pagination }),
  })
}

const status = computed({
  get: () => filterValue(route.query.status, allowedStatuses, 'all'),
  set: (value: string) => void updateListQuery({ status: value }, true),
})
const scope = computed({
  get: () => filterValue(route.query.scope, allowedScopes, 'all'),
  set: (value: string) => void updateListQuery({ scope: value }, true),
})
const search = computed({
  get: () => queryValue(route.query.search),
  set: (value: string) => void updateListQuery({ search: value.slice(0, 100) }, true),
})
const cursor = computed(() => queryValue(route.query.cursor, 2048))
const page = computed(() => pageValue(route.query.page))
const navigationNotice = computed(() => navigationMessage(route.query.navigation_error))

const params = computed(() => ({
  status: status.value === 'all' ? undefined : status.value,
  scope: scope.value,
  search: search.value.trim().length >= 2 ? search.value.trim() : undefined,
  cursor: cursor.value || undefined,
  per_page: 20,
}))

const query = useQuery({
  queryKey: ['clients', params],
  queryFn: async () => {
    const response = await api.get<ApiEnvelope<ClientSummary[], CursorMeta>>(
      '/clients',
      { params: params.value },
    )
    return {
      items: response.data.data,
      meta: response.data.meta ?? {},
    }
  },
})

const items = computed(() => query.data.value?.items ?? [])
const meta = computed(() => query.data.value?.meta ?? {})
const activeFilterCount = computed(
  () => Number(status.value !== 'all') + Number(scope.value !== 'all'),
)

async function refresh(event: CustomEvent) {
  await query.refetch()
  ;(event.target as HTMLIonRefresherElement).complete()
}

function nextPage() {
  if (!meta.value.next_cursor) return
  void updateListQuery({
    cursor: meta.value.next_cursor,
    page: String(page.value + 1),
  })
}

function previousPage() {
  if (!meta.value.previous_cursor) return
  void updateListQuery({
    cursor: meta.value.previous_cursor,
    page: String(Math.max(1, page.value - 1)),
  })
}

function scheduleLabel(client: ClientSummary) {
  if (!client.next_schedule) return 'Belum ada jadwal'
  return new Intl.DateTimeFormat('id-ID', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
  }).format(new Date(client.next_schedule.date))
}
</script>

<template>
  <IonPage>
    <AppHeader title="Permohonan layanan" eyebrow="Pekerjaan layanan" />
    <IonContent>
      <IonRefresher slot="fixed" @ion-refresh="refresh">
        <IonRefresherContent />
      </IonRefresher>

      <PageContainer compact>
        <div v-if="navigationNotice" class="notice-box" role="status">
          {{ navigationNotice }}
        </div>

        <section class="requests-overview">
          <div>
            <span class="eyebrow">Workspace layanan</span>
            <h2>Temukan permohonan lebih cepat</h2>
            <p>Tiket, pemohon, perusahaan, status, dan penugasan dalam satu tampilan.</p>
          </div>
          <span class="requests-overview__count">
            <strong>{{ items.length }}</strong>
            <small>halaman ini</small>
          </span>
        </section>

        <section class="list-controls" aria-label="Filter permohonan">
          <div class="scope-switch surface">
            <button
              type="button"
              :class="{ active: scope === 'all' }"
              :aria-pressed="scope === 'all'"
              @click="scope = 'all'"
            >
              Semua
            </button>
            <button
              type="button"
              :class="{ active: scope === 'mine' }"
              :aria-pressed="scope === 'mine'"
              @click="scope = 'mine'"
            >
              Layanan saya
            </button>
          </div>

          <SearchToolbar
            v-model="search"
            placeholder="Cari tiket, pemohon, atau perusahaan"
            :busy="query.isFetching.value && !query.isLoading.value"
            :result-count="query.data.value ? items.length : undefined"
            :active-filter-count="activeFilterCount"
          />
          <p class="search-help">
            Ketik minimal 2 karakter. Cari dengan nomor tiket, nama pemohon, atau nama perusahaan.
          </p>

          <IonSegment v-model="status" :scrollable="true" aria-label="Filter status">
            <IonSegmentButton value="all">Semua</IonSegmentButton>
            <IonSegmentButton value="waiting">Menunggu</IonSegmentButton>
            <IonSegmentButton value="scheduled">Terjadwal</IonSegmentButton>
            <IonSegmentButton value="completed">Selesai</IonSegmentButton>
          </IonSegment>
        </section>

        <LoadingSkeleton v-if="query.isLoading.value" :rows="5" label="Memuat permohonan" />

        <ErrorState
          v-else-if="query.error.value"
          :message="apiError(query.error.value)"
          @retry="query.refetch()"
        />

        <div v-else-if="items.length" class="request-list">
          <DataListCard
            v-for="client in items"
            :key="client.ticket_number"
            :to="requestDetailRoute(client.ticket_number)"
            :eyebrow="client.ticket_number"
            :title="client.name"
            :subtitle="client.instance || client.service?.name || 'Pemohon layanan'"
          >
            <template #status>
              <StatusBadge :status="client.status" />
            </template>
            <template #meta>
              <span>
                <IonIcon :icon="calendarOutline" aria-hidden="true" />
                {{ scheduleLabel(client) }}
              </span>
              <span v-if="client.location">
                <IonIcon :icon="locationOutline" aria-hidden="true" />
                {{ client.location.name }}
              </span>
            </template>
          </DataListCard>
        </div>

        <EmptyState
          v-else
          :kind="search ? 'search' : 'empty'"
          :title="search ? 'Permohonan tidak ditemukan' : 'Tidak ada permohonan'"
          :body="
            search
              ? 'Coba nomor tiket, nama pemohon, atau nama perusahaan yang berbeda.'
              : 'Ubah filter atau tarik layar untuk memperbarui data.'
          "
        />

        <PaginationControl
          v-if="query.data.value && (items.length || page > 1 || meta.next_cursor)"
          :page="page"
          :item-count="items.length"
          :has-previous="Boolean(meta.previous_cursor)"
          :has-next="Boolean(meta.next_cursor)"
          :loading="query.isFetching.value"
          @previous="previousPage"
          @next="nextPage"
        />
      </PageContainer>
    </IonContent>
  </IonPage>
</template>

<style scoped>
.requests-overview {
  align-items: center;
  background:
    radial-gradient(circle at 100% 0, rgba(104, 230, 217, 0.22), transparent 45%),
    linear-gradient(145deg, #0b3652, #0d6077);
  border-radius: var(--app-radius-xl);
  box-shadow: 0 16px 34px rgba(8, 43, 69, 0.2);
  color: white;
  display: grid;
  gap: var(--app-space-4);
  grid-template-columns: 1fr auto;
  margin-bottom: var(--app-space-4);
  overflow: hidden;
  padding: var(--app-space-5);
}

.requests-overview .eyebrow {
  color: #7de4da;
}

.requests-overview h2 {
  font-size: 1.25rem;
  letter-spacing: -0.035em;
  margin: var(--app-space-2) 0 var(--app-space-1);
}

.requests-overview p {
  color: rgba(255, 255, 255, 0.76);
  font-size: var(--app-font-size-xs);
  line-height: var(--app-line-height-body);
  margin: 0;
}

.requests-overview__count {
  align-items: center;
  background: rgba(255, 255, 255, 0.12);
  border: 1px solid rgba(255, 255, 255, 0.16);
  border-radius: var(--app-radius-lg);
  display: grid;
  min-width: 4.2rem;
  padding: var(--app-space-3);
  text-align: center;
}

.requests-overview__count strong {
  font-size: 1.5rem;
}

.requests-overview__count small {
  color: rgba(255, 255, 255, 0.7);
  font-size: 0.6rem;
}

.list-controls {
  display: grid;
  gap: var(--app-space-3);
  margin-bottom: var(--app-space-4);
  padding: var(--app-space-3);
  background: rgba(255, 255, 255, 0.72);
  border: 1px solid var(--app-color-border);
  border-radius: var(--app-radius-xl);
  box-shadow: var(--app-shadow-sm);
}

.scope-switch {
  display: grid;
  grid-template-columns: 1fr 1fr;
  padding: var(--app-space-1);
}

.scope-switch button {
  background: transparent;
  border: 0;
  border-radius: var(--app-radius-md);
  color: var(--app-color-text-secondary);
  font-size: var(--app-font-size-sm);
  font-weight: 750;
  min-height: var(--app-touch-target);
  padding: var(--app-space-2) var(--app-space-3);
}

.scope-switch button.active {
  background: var(--ion-color-primary);
  color: #fff;
}

.search-help {
  color: var(--app-color-text-secondary);
  font-size: var(--app-font-size-xs);
  line-height: var(--app-line-height-body);
  margin: calc(-1 * var(--app-space-1)) var(--app-space-1) 0;
}

ion-segment {
  margin-top: var(--app-space-1);
}

.request-list {
  display: grid;
  gap: var(--app-space-3);
}

.request-list span {
  align-items: center;
  display: inline-flex;
  gap: var(--app-space-1);
}
</style>
