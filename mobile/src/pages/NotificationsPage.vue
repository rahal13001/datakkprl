<script setup lang="ts">
import { computed, ref } from 'vue'
import { useQuery, useQueryClient } from '@tanstack/vue-query'
import { useRoute, useRouter } from 'vue-router'
import {
  IonButton,
  IonContent,
  IonHeader,
  IonIcon,
  IonPage,
  IonRefresher,
  IonRefresherContent,
  IonToolbar,
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
import EmptyState from '@/components/EmptyState.vue'
import {
  navigationFallback,
  navigationMessage,
  safeInternalRoute,
  validTicket,
} from '@/navigation/safeNavigation'
import type { ApiEnvelope, MobileNotification } from '@/types/api'

const router = useRouter()
const route = useRoute()
const queryClient = useQueryClient()
const actionError = ref('')
const navigationNotice = computed(() => navigationMessage(route.query.navigation_error))
const query = useQuery({
  queryKey: ['notifications'],
  queryFn: async () =>
    (await api.get<ApiEnvelope<MobileNotification[]>>('/notifications', { params: { per_page: 50 } }))
      .data.data,
})

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
    await router.push(
      destination ?? navigationFallback('notifications', 'invalid_resource'),
    )
  } catch (reason) {
    actionError.value = apiError(reason)
  }
}

async function readAll() {
  await api.post('/notifications/read-all')
  await query.refetch()
}

async function refresh(event: CustomEvent) {
  await query.refetch()
  ;(event.target as HTMLIonRefresherElement).complete()
}
</script>

<template>
  <IonPage>
    <IonHeader>
      <IonToolbar>
        <div class="notification-toolbar">
          <div>
            <span class="eyebrow">Pusat informasi</span>
            <h1>Notifikasi</h1>
          </div>
          <IonButton fill="clear" size="small" @click="readAll">
            <IonIcon slot="start" :icon="checkmarkDoneOutline" />
            Baca semua
          </IonButton>
        </div>
      </IonToolbar>
    </IonHeader>
    <IonContent>
      <IonRefresher slot="fixed" @ion-refresh="refresh"><IonRefresherContent /></IonRefresher>
      <main class="page-shell notification-shell">
        <div v-if="navigationNotice" class="notice-box" role="status">{{ navigationNotice }}</div>
        <div v-if="actionError" class="error-box" role="alert">{{ actionError }}</div>
        <div v-if="query.error.value" class="error-box">{{ apiError(query.error.value) }}</div>
        <div v-else-if="query.data.value?.length" class="notification-list">
          <button
            v-for="notification in query.data.value"
            :key="notification.id"
            class="notification-item surface"
            :class="{ unread: !notification.read_at }"
            @click="open(notification)"
          >
            <span class="notification-icon"><IonIcon :icon="icon(notification.type)" /></span>
            <span class="notification-copy">
              <strong>{{ notification.title }}</strong>
              <span>{{ notification.body }}</span>
              <small>{{ time(notification.created_at) }}</small>
            </span>
            <i v-if="!notification.read_at" />
          </button>
        </div>
        <EmptyState
          v-else-if="!query.isLoading.value"
          title="Belum ada notifikasi"
          body="Penugasan, perubahan jadwal, dan masukan baru akan tersimpan di sini."
        />
      </main>
    </IonContent>
  </IonPage>
</template>

<style scoped>
.notification-toolbar {
  align-items: center;
  display: flex;
  justify-content: space-between;
  padding: 8px 12px 8px 18px;
}

.notification-toolbar h1 {
  color: var(--app-ink);
  font-size: 1.25rem;
  margin: 2px 0;
}

.notification-shell {
  padding-top: 14px;
}

.notification-list {
  display: grid;
  gap: 10px;
}

.notification-item {
  align-items: flex-start;
  display: grid;
  gap: 12px;
  grid-template-columns: 42px 1fr auto;
  padding: 15px;
  text-align: left;
  width: 100%;
}

.notification-item.unread {
  border-color: #b8d5df;
  box-shadow: 0 10px 35px rgba(13, 49, 80, 0.1);
}

.notification-icon {
  align-items: center;
  background: #e8f0f3;
  border-radius: 13px;
  color: var(--ion-color-primary);
  display: flex;
  font-size: 1.2rem;
  height: 42px;
  justify-content: center;
  width: 42px;
}

.notification-copy {
  display: grid;
  gap: 4px;
}

.notification-copy strong {
  color: var(--app-ink);
  font-size: 0.9rem;
}

.notification-copy > span,
.notification-copy small {
  color: var(--app-muted);
  font-size: 0.78rem;
  line-height: 1.45;
}

.notification-item i {
  background: var(--ion-color-secondary);
  border-radius: 50%;
  height: 8px;
  margin-top: 8px;
  width: 8px;
}
</style>
