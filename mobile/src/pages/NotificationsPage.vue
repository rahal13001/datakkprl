<script setup lang="ts">
import { computed, ref } from 'vue'
import { useQuery, useQueryClient } from '@tanstack/vue-query'
import { useRoute, useRouter } from 'vue-router'
import {
  IonButton,
  IonContent,
  IonIcon,
  IonPage,
  IonRefresher,
  IonRefresherContent,
} from '@ionic/vue'
import {
  calendarOutline,
  chatbubbleEllipsesOutline,
  checkmarkDoneOutline,
  notificationsOutline,
  personAddOutline,
  waterOutline,
} from 'ionicons/icons'
import { api, apiError } from '@/api/client'
import AppHeader from '@/components/AppHeader.vue'
import EmptyState from '@/components/EmptyState.vue'
import ErrorState from '@/components/ErrorState.vue'
import LoadingSkeleton from '@/components/LoadingSkeleton.vue'
import PageContainer from '@/components/PageContainer.vue'
import PaginationControl from '@/components/PaginationControl.vue'
import {
  navigationFallback,
  navigationMessage,
  safeInternalRoute,
  validTicket,
} from '@/navigation/safeNavigation'
import type { ApiEnvelope, MobileNotification } from '@/types/api'

interface NotificationMeta extends Record<string, unknown> {
  next_cursor?: string | null
  unread_count?: number
}

const router = useRouter()
const route = useRoute()
const queryClient = useQueryClient()
const actionError = ref('')
const cursor = ref<string>()
const cursorHistory = ref<string[]>([])
const page = ref(1)
const readAllLoading = ref(false)
const navigationNotice = computed(() => navigationMessage(route.query.navigation_error))
const query = useQuery({
  queryKey: ['notifications', cursor],
  queryFn: async () => {
    const response = await api.get<ApiEnvelope<MobileNotification[], NotificationMeta>>(
      '/notifications',
      { params: { cursor: cursor.value, per_page: 20 } },
    )
    return {
      items: response.data.data,
      nextCursor: response.data.meta?.next_cursor ?? null,
      unreadCount: response.data.meta?.unread_count ?? 0,
    }
  },
})

const notifications = computed(() => query.data.value?.items ?? [])

function icon(type: string) {
  if (type.startsWith('assignment.')) return personAddOutline
  if (type.startsWith('schedule.')) return calendarOutline
  if (type.includes('feedback') || type.startsWith('satisfaction.')) return chatbubbleEllipsesOutline
  if (type.startsWith('request.')) return waterOutline
  return notificationsOutline
}

function time(value: string) {
  return new Intl.DateTimeFormat('id-ID', {
    day: 'numeric',
    month: 'short',
    hour: '2-digit',
    minute: '2-digit',
  }).format(new Date(value))
}

async function open(notification: MobileNotification) {
  actionError.value = ''
  try {
    const notificationId = validTicket(notification.id)
    if (!notificationId) {
      await router.replace(navigationFallback('notifications', 'invalid_resource'))
      return
    }
    if (!notification.read_at) {
      await api.patch(`/notifications/${encodeURIComponent(notificationId)}/read`)
      await queryClient.invalidateQueries({ queryKey: ['notifications'] })
      await queryClient.invalidateQueries({ queryKey: ['dashboard'] })
    }
    const destination = safeInternalRoute(notification.route)
    await router.push(destination ?? navigationFallback('notifications', 'invalid_resource'))
  } catch (reason) {
    actionError.value = apiError(reason)
  }
}

async function readAll() {
  if (readAllLoading.value) return
  actionError.value = ''
  readAllLoading.value = true
  try {
    await api.post('/notifications/read-all')
    await queryClient.invalidateQueries({ queryKey: ['dashboard'] })
    await query.refetch()
  } catch (reason) {
    actionError.value = apiError(reason)
  } finally {
    readAllLoading.value = false
  }
}

function nextPage() {
  const next = query.data.value?.nextCursor
  if (!next) return
  cursorHistory.value.push(cursor.value ?? '')
  cursor.value = next
  page.value += 1
}

function previousPage() {
  if (!cursorHistory.value.length) return
  cursor.value = cursorHistory.value.pop() || undefined
  page.value = Math.max(1, page.value - 1)
}

async function refresh(event: CustomEvent) {
  await query.refetch()
  ;(event.target as HTMLIonRefresherElement).complete()
}
</script>

<template>
  <IonPage>
    <AppHeader title="Notifikasi" eyebrow="Pusat informasi">
      <template #actions>
        <IonButton
          fill="clear"
          :disabled="readAllLoading || !query.data.value?.unreadCount"
          @click="readAll"
        >
          <IonIcon slot="start" :icon="checkmarkDoneOutline" aria-hidden="true" />
          {{ readAllLoading ? 'Memproses…' : 'Baca semua' }}
        </IonButton>
      </template>
    </AppHeader>
    <IonContent>
      <IonRefresher slot="fixed" @ion-refresh="refresh">
        <IonRefresherContent />
      </IonRefresher>
      <PageContainer compact>
        <div v-if="navigationNotice" class="notice-box" role="status">{{ navigationNotice }}</div>
        <div v-if="actionError" class="error-box" role="alert">{{ actionError }}</div>

        <LoadingSkeleton v-if="query.isLoading.value" :rows="5" label="Memuat notifikasi" />
        <ErrorState
          v-else-if="query.error.value"
          :message="apiError(query.error.value)"
          @retry="query.refetch()"
        />

        <div v-else-if="notifications.length" class="notification-list">
          <button
            v-for="notification in notifications"
            :key="notification.id"
            type="button"
            class="notification-item surface"
            :class="{ unread: !notification.read_at }"
            @click="open(notification)"
          >
            <span class="notification-icon">
              <IonIcon :icon="icon(notification.type)" aria-hidden="true" />
            </span>
            <span class="notification-copy">
              <strong>{{ notification.title }}</strong>
              <span>{{ notification.body }}</span>
              <small>{{ time(notification.created_at) }}</small>
            </span>
            <i v-if="!notification.read_at" aria-label="Belum dibaca" />
          </button>
        </div>
        <EmptyState
          v-else
          title="Belum ada notifikasi"
          body="Penugasan, perubahan jadwal, dan masukan baru akan tersimpan di sini."
        />

        <PaginationControl
          v-if="query.data.value"
          :page="page"
          :item-count="notifications.length"
          :has-previous="cursorHistory.length > 0"
          :has-next="Boolean(query.data.value.nextCursor)"
          :loading="query.isFetching.value"
          @previous="previousPage"
          @next="nextPage"
        />
      </PageContainer>
    </IonContent>
  </IonPage>
</template>

<style scoped>
.notification-list {
  display: grid;
  gap: var(--app-space-3);
}

.notification-item {
  align-items: flex-start;
  display: grid;
  gap: var(--app-space-3);
  grid-template-columns: 2.75rem 1fr auto;
  min-height: 5rem;
  padding: var(--app-space-3);
  text-align: left;
  width: 100%;
}

.notification-item.unread {
  background: color-mix(in srgb, var(--app-color-info-soft) 50%, white);
  border-color: color-mix(in srgb, var(--app-color-info) 35%, white);
}

.notification-icon {
  align-items: center;
  background: var(--app-color-info-soft);
  border-radius: var(--app-radius-md);
  color: var(--app-color-info);
  display: flex;
  font-size: var(--app-icon-md);
  height: 2.75rem;
  justify-content: center;
  width: 2.75rem;
}

.notification-copy {
  display: grid;
  gap: var(--app-space-1);
}

.notification-copy strong {
  color: var(--app-color-text);
  font-size: var(--app-font-size-sm);
}

.notification-copy > span,
.notification-copy small {
  color: var(--app-color-text-secondary);
  font-size: var(--app-font-size-xs);
  line-height: var(--app-line-height-body);
}

.notification-item i {
  background: var(--app-color-accent);
  border-radius: 50%;
  height: 0.5rem;
  margin-top: var(--app-space-2);
  width: 0.5rem;
}
</style>
