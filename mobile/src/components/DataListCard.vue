<script setup lang="ts">
import { computed } from 'vue'
import type { RouteLocationRaw } from 'vue-router'
import { IonIcon } from '@ionic/vue'
import { chevronForwardOutline } from 'ionicons/icons'

const props = defineProps<{
  to: RouteLocationRaw
  title: string
  eyebrow?: string
  subtitle?: string
}>()

const initials = computed(() => props.title
  .split(/\s+/)
  .slice(0, 2)
  .map((part) => part[0]?.toUpperCase())
  .join(''))
</script>

<template>
  <router-link :to="to" class="data-list-card surface">
    <div class="data-list-card__topline">
      <span>{{ eyebrow }}</span>
      <slot name="status" />
    </div>
    <div class="data-list-card__identity">
      <span class="data-list-card__avatar">{{ initials }}</span>
      <div class="data-list-card__copy">
        <h2>{{ title }}</h2>
        <p v-if="subtitle">{{ subtitle }}</p>
      </div>
    </div>
    <div v-if="$slots.meta" class="data-list-card__meta">
      <slot name="meta" />
      <IonIcon class="data-list-card__next" :icon="chevronForwardOutline" aria-hidden="true" />
    </div>
  </router-link>
</template>

<style scoped>
.data-list-card {
  background:
    radial-gradient(circle at 100% 0, rgba(28, 168, 169, 0.08), transparent 40%),
    var(--app-color-surface);
  color: inherit;
  display: grid;
  gap: var(--app-space-3);
  padding: var(--app-space-4);
  position: relative;
  text-decoration: none;
  transition:
    border-color var(--app-motion-fast) ease,
    transform var(--app-motion-fast) ease;
}

.data-list-card:active {
  border-color: var(--app-color-border-strong);
  transform: scale(0.995);
}

.data-list-card::before {
  background: linear-gradient(180deg, var(--ion-color-secondary), var(--ion-color-primary));
  border-radius: var(--app-radius-pill);
  bottom: var(--app-space-4);
  content: '';
  left: 0;
  position: absolute;
  top: var(--app-space-4);
  width: 0.22rem;
}

.data-list-card__topline {
  align-items: center;
  color: var(--app-color-text-secondary);
  display: flex;
  font-size: var(--app-font-size-xs);
  font-weight: 750;
  justify-content: space-between;
}

.data-list-card__identity {
  align-items: center;
  display: grid;
  gap: var(--app-space-3);
  grid-template-columns: auto 1fr;
}

.data-list-card__avatar {
  align-items: center;
  background: linear-gradient(145deg, #0d4d68, #0b2f4b);
  border-radius: 1rem;
  box-shadow: 0 8px 18px rgba(13, 77, 104, 0.2);
  color: white;
  display: flex;
  font-size: var(--app-font-size-xs);
  font-weight: 850;
  height: 3rem;
  justify-content: center;
  width: 3rem;
}

.data-list-card__copy h2 {
  color: var(--app-color-text);
  font-size: 1.05rem;
  letter-spacing: -0.02em;
  margin: 0 0 var(--app-space-1);
}

.data-list-card__copy p {
  color: var(--app-color-text-secondary);
  font-size: var(--app-font-size-sm);
  margin: 0;
}

.data-list-card__meta {
  align-items: center;
  border-top: 1px solid var(--app-color-border);
  color: var(--app-color-text-secondary);
  display: flex;
  flex-wrap: wrap;
  font-size: var(--app-font-size-xs);
  gap: var(--app-space-3);
  padding-top: var(--app-space-3);
}

.data-list-card__next {
  color: var(--ion-color-primary);
  font-size: var(--app-icon-sm);
  margin-left: auto;
}
</style>
