<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue'
import { useQuery } from '@tanstack/vue-query'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'
import {
  IonContent,
  IonPage,
  IonSegment,
  IonSegmentButton,
} from '@ionic/vue'
import { api, apiError } from '@/api/client'
import AppHeader from '@/components/AppHeader.vue'
import EmptyState from '@/components/EmptyState.vue'
import ErrorState from '@/components/ErrorState.vue'
import LoadingSkeleton from '@/components/LoadingSkeleton.vue'
import PageContainer from '@/components/PageContainer.vue'
import PaginationControl from '@/components/PaginationControl.vue'
import { useAuthStore } from '@/stores/auth'
import {
  navigationFallback,
  navigationMessage,
  validPositiveId,
} from '@/navigation/safeNavigation'
import type { ApiEnvelope, PublicFeedback, SatisfactionSurvey } from '@/types/api'

interface CursorMeta extends Record<string, unknown> {
  next_cursor?: string | null
}

interface PageState {
  cursor?: string
  history: string[]
  page: number
}

const auth = useAuthStore()
const route = useRoute()
const router = useRouter()
const detailId = computed(() => route.params.id ? validPositiveId(route.params.id) : null)
const routeType = computed(() => String(route.name ?? '').includes('public') ? 'public' : 'satisfaction')
const tab = ref(
  detailId.value
    ? routeType.value
    : (auth.can('satisfaction_surveys', 'list') ? 'satisfaction' : 'public'),
)
const navigationNotice = computed(() => navigationMessage(route.query.navigation_error))
const satisfactionPage = reactive<PageState>({ cursor: undefined, history: [], page: 1 })
const publicPage = reactive<PageState>({ cursor: undefined, history: [], page: 1 })
const activePage = computed(() => tab.value === 'satisfaction' ? satisfactionPage : publicPage)

async function unavailable(reason: unknown): Promise<never> {
  if (axios.isAxiosError(reason) && [403, 404, 410].includes(reason.response?.status ?? 0)) {
    await router.replace(navigationFallback('feedback', 'resource_unavailable'))
  }
  throw reason
}

const satisfaction = useQuery({
  queryKey: ['satisfaction-surveys', detailId, computed(() => satisfactionPage.cursor)],
  queryFn: async () => {
    if (detailId.value && routeType.value === 'satisfaction') {
      const response = await api.get<ApiEnvelope<SatisfactionSurvey>>(
        `/satisfaction-surveys/${detailId.value}`,
      ).catch(unavailable)
      return { items: [response.data.data], nextCursor: null }
    }
    const response = await api.get<ApiEnvelope<SatisfactionSurvey[], CursorMeta>>(
      '/satisfaction-surveys',
      { params: { cursor: satisfactionPage.cursor, per_page: 20 } },
    )
    return {
      items: response.data.data,
      nextCursor: response.data.meta?.next_cursor ?? null,
    }
  },
  enabled: computed(() =>
    detailId.value
      ? routeType.value === 'satisfaction' && auth.can('satisfaction_surveys', 'view')
      : auth.can('satisfaction_surveys', 'list'),
  ),
})

const publicFeedback = useQuery({
  queryKey: ['public-feedback', detailId, computed(() => publicPage.cursor)],
  queryFn: async () => {
    if (detailId.value && routeType.value === 'public') {
      const response = await api.get<ApiEnvelope<PublicFeedback>>(
        `/public-feedback/${detailId.value}`,
      ).catch(unavailable)
      return { items: [response.data.data], nextCursor: null }
    }
    const response = await api.get<ApiEnvelope<PublicFeedback[], CursorMeta>>(
      '/public-feedback',
      { params: { cursor: publicPage.cursor, per_page: 20 } },
    )
    return {
      items: response.data.data,
      nextCursor: response.data.meta?.next_cursor ?? null,
    }
  },
  enabled: computed(() =>
    detailId.value
      ? routeType.value === 'public' && auth.can('public_feedback', 'view')
      : auth.can('public_feedback', 'list'),
  ),
})

watch([detailId, routeType], ([id, type]) => {
  if (id) tab.value = type
})

const activeQuery = computed(() => tab.value === 'satisfaction' ? satisfaction : publicFeedback)
const activeItems = computed(() => activeQuery.value.data.value?.items ?? [])
const activeNextCursor = computed(() => activeQuery.value.data.value?.nextCursor ?? null)

function nextPage() {
  if (!activeNextCursor.value) return
  activePage.value.history.push(activePage.value.cursor ?? '')
  activePage.value.cursor = activeNextCursor.value
  activePage.value.page += 1
}

function previousPage() {
  if (!activePage.value.history.length) return
  activePage.value.cursor = activePage.value.history.pop() || undefined
  activePage.value.page = Math.max(1, activePage.value.page - 1)
}

function date(value: string) {
  return new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium' }).format(new Date(value))
}
</script>

<template>
  <IonPage>
    <AppHeader
      title="Masukan & penilaian"
      eyebrow="Laporan publik"
      default-href="/tabs/dashboard"
    />
    <IonContent>
      <PageContainer compact>
        <div v-if="navigationNotice" class="notice-box" role="status">{{ navigationNotice }}</div>
        <IonSegment v-model="tab">
          <IonSegmentButton
            v-if="
              auth.can('satisfaction_surveys', 'list') ||
              (detailId && routeType === 'satisfaction')
            "
            value="satisfaction"
          >
            Kepuasan
          </IonSegmentButton>
          <IonSegmentButton
            v-if="auth.can('public_feedback', 'list') || (detailId && routeType === 'public')"
            value="public"
          >
            Masukan publik
          </IonSegmentButton>
        </IonSegment>

        <LoadingSkeleton
          v-if="activeQuery.isLoading.value"
          class="feedback-state"
          :rows="4"
          label="Memuat masukan"
        />
        <ErrorState
          v-else-if="activeQuery.error.value"
          class="feedback-state"
          :message="apiError(activeQuery.error.value)"
          @retry="activeQuery.refetch()"
        />

        <div v-else-if="activeItems.length" class="feedback-list">
          <article
            v-for="item in activeItems"
            :key="item.id"
            class="surface feedback-card"
          >
            <template v-if="tab === 'satisfaction'">
              <div class="feedback-meta">
                <strong>{{ (item as SatisfactionSurvey).ticket_number }}</strong>
                <span>{{ date(item.created_at) }}</span>
              </div>
              <h2>Kritik / umpan balik</h2>
              <p>{{ (item as SatisfactionSurvey).criticism }}</p>
              <h2>Saran</h2>
              <p>{{ (item as SatisfactionSurvey).suggestion }}</p>
            </template>
            <template v-else>
              <div class="feedback-meta">
                <strong>{{ (item as PublicFeedback).submitter }}</strong>
                <span>{{ date(item.created_at) }}</span>
              </div>
              <p>{{ (item as PublicFeedback).feedback }}</p>
              <h2 v-if="(item as PublicFeedback).suggestion">Saran perbaikan</h2>
              <p v-if="(item as PublicFeedback).suggestion">
                {{ (item as PublicFeedback).suggestion }}
              </p>
            </template>
          </article>
        </div>

        <EmptyState
          v-else
          :title="tab === 'satisfaction' ? 'Belum ada survei' : 'Belum ada masukan publik'"
          body="Data terbaru akan muncul di halaman ini."
        />

        <PaginationControl
          v-if="!detailId && activeQuery.data.value"
          :page="activePage.page"
          :item-count="activeItems.length"
          :has-previous="activePage.history.length > 0"
          :has-next="Boolean(activeNextCursor)"
          :loading="activeQuery.isFetching.value"
          @previous="previousPage"
          @next="nextPage"
        />
      </PageContainer>
    </IonContent>
  </IonPage>
</template>

<style scoped>
ion-segment {
  margin-bottom: var(--app-space-4);
}

.feedback-state,
.feedback-list {
  margin-top: var(--app-space-3);
}

.feedback-list {
  display: grid;
  gap: var(--app-space-3);
}

.feedback-card {
  padding: var(--app-space-4);
}

.feedback-meta {
  align-items: center;
  color: var(--app-color-text-secondary);
  display: flex;
  font-size: var(--app-font-size-xs);
  gap: var(--app-space-3);
  justify-content: space-between;
}

.feedback-meta strong {
  color: var(--ion-color-primary);
}

.feedback-card h2 {
  color: var(--app-color-text);
  font-size: var(--app-font-size-xs);
  letter-spacing: 0.04em;
  margin: var(--app-space-4) 0 var(--app-space-1);
  text-transform: uppercase;
}

.feedback-card p {
  color: var(--app-color-text-secondary);
  font-size: var(--app-font-size-sm);
  line-height: var(--app-line-height-body);
  margin: 0;
  white-space: pre-wrap;
}
</style>
