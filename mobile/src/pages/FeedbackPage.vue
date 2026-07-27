<script setup lang="ts">
import { computed, ref } from 'vue'
import { useQuery } from '@tanstack/vue-query'
import { useRoute } from 'vue-router'
import {
  IonBackButton,
  IonButtons,
  IonContent,
  IonHeader,
  IonPage,
  IonSegment,
  IonSegmentButton,
  IonToolbar,
} from '@ionic/vue'
import { api, apiError } from '@/api/client'
import EmptyState from '@/components/EmptyState.vue'
import { useAuthStore } from '@/stores/auth'
import type { ApiEnvelope, PublicFeedback, SatisfactionSurvey } from '@/types/api'

const auth = useAuthStore()
const route = useRoute()
const detailId = computed(() => route.params.id ? Number(route.params.id) : null)
const routeType = computed(() => String(route.name ?? '').includes('public') ? 'public' : 'satisfaction')
const tab = ref(detailId.value ? routeType.value : (auth.can('satisfaction_surveys', 'list') ? 'satisfaction' : 'public'))
const satisfaction = useQuery({
  queryKey: ['satisfaction-surveys', detailId],
  queryFn: async () => {
    if (detailId.value && routeType.value === 'satisfaction') {
      const response = await api.get<ApiEnvelope<SatisfactionSurvey>>(
        `/satisfaction-surveys/${detailId.value}`,
      )
      return [response.data.data]
    }
    return (await api.get<ApiEnvelope<SatisfactionSurvey[]>>('/satisfaction-surveys')).data.data
  },
  enabled: detailId.value
    ? auth.can('satisfaction_surveys', 'view')
    : auth.can('satisfaction_surveys', 'list'),
})
const publicFeedback = useQuery({
  queryKey: ['public-feedback', detailId],
  queryFn: async () => {
    if (detailId.value && routeType.value === 'public') {
      const response = await api.get<ApiEnvelope<PublicFeedback>>(
        `/public-feedback/${detailId.value}`,
      )
      return [response.data.data]
    }
    return (await api.get<ApiEnvelope<PublicFeedback[]>>('/public-feedback')).data.data
  },
  enabled: detailId.value
    ? auth.can('public_feedback', 'view')
    : auth.can('public_feedback', 'list'),
})

function date(value: string) {
  return new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium' }).format(new Date(value))
}
</script>

<template>
  <IonPage>
    <IonHeader>
      <IonToolbar>
        <IonButtons slot="start"><IonBackButton default-href="/tabs/dashboard" /></IonButtons>
        <div class="feedback-toolbar">
          <span class="eyebrow">Laporan publik</span>
          <h1>Masukan & penilaian</h1>
        </div>
      </IonToolbar>
    </IonHeader>
    <IonContent>
      <main class="page-shell feedback-shell">
        <IonSegment v-model="tab">
          <IonSegmentButton
            v-if="auth.can('satisfaction_surveys', 'list')"
            value="satisfaction"
          >
            Kepuasan
          </IonSegmentButton>
          <IonSegmentButton v-if="auth.can('public_feedback', 'list')" value="public">
            Masukan publik
          </IonSegmentButton>
        </IonSegment>

        <div v-if="tab === 'satisfaction'">
          <div v-if="satisfaction.error.value" class="error-box">
            {{ apiError(satisfaction.error.value) }}
          </div>
          <div v-else-if="satisfaction.data.value?.length" class="feedback-list">
            <article v-for="item in satisfaction.data.value" :key="item.id" class="surface feedback-card">
              <div class="feedback-meta">
                <strong>{{ item.ticket_number }}</strong><span>{{ date(item.created_at) }}</span>
              </div>
              <h2>Kritik / umpan balik</h2>
              <p>{{ item.criticism }}</p>
              <h2>Saran</h2>
              <p>{{ item.suggestion }}</p>
            </article>
          </div>
          <EmptyState v-else title="Belum ada survei" />
        </div>

        <div v-else>
          <div v-if="publicFeedback.error.value" class="error-box">
            {{ apiError(publicFeedback.error.value) }}
          </div>
          <div v-else-if="publicFeedback.data.value?.length" class="feedback-list">
            <article v-for="item in publicFeedback.data.value" :key="item.id" class="surface feedback-card">
              <div class="feedback-meta">
                <strong>{{ item.submitter }}</strong><span>{{ date(item.created_at) }}</span>
              </div>
              <p>{{ item.feedback }}</p>
              <h2 v-if="item.suggestion">Saran perbaikan</h2>
              <p v-if="item.suggestion">{{ item.suggestion }}</p>
            </article>
          </div>
          <EmptyState v-else title="Belum ada masukan publik" />
        </div>
      </main>
    </IonContent>
  </IonPage>
</template>

<style scoped>
.feedback-toolbar {
  padding: 8px 12px 8px 0;
}

.feedback-toolbar h1 {
  color: var(--app-ink);
  font-size: 1.12rem;
  margin: 2px 0;
}

.feedback-shell {
  padding-top: 14px;
}

.feedback-list {
  display: grid;
  gap: 12px;
  margin-top: 14px;
}

.feedback-card {
  padding: 17px;
}

.feedback-meta {
  align-items: center;
  color: var(--app-muted);
  display: flex;
  font-size: 0.75rem;
  justify-content: space-between;
}

.feedback-meta strong {
  color: var(--ion-color-primary);
}

.feedback-card h2 {
  color: var(--app-ink);
  font-size: 0.8rem;
  margin: 15px 0 4px;
  text-transform: uppercase;
}

.feedback-card p {
  color: var(--app-muted);
  font-size: 0.88rem;
  line-height: 1.6;
  margin: 0;
  white-space: pre-wrap;
}
</style>
