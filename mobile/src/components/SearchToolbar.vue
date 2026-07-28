<script setup lang="ts">
import { IonButton, IonIcon, IonProgressBar, IonSearchbar } from '@ionic/vue'
import { closeCircleOutline, optionsOutline } from 'ionicons/icons'

const model = defineModel<string>({ default: '' })

withDefaults(defineProps<{
  placeholder?: string
  busy?: boolean
  resultCount?: number
  activeFilterCount?: number
  showFilters?: boolean
  debounce?: number
}>(), {
  placeholder: 'Cari data',
  busy: false,
  resultCount: undefined,
  activeFilterCount: 0,
  showFilters: false,
  debounce: 450,
})

defineEmits<{ filters: [] }>()
</script>

<template>
  <section class="search-toolbar" aria-label="Pencarian dan filter">
    <div class="search-toolbar__row">
      <IonSearchbar
        v-model="model"
        :placeholder="placeholder"
        :debounce="debounce"
        :show-clear-button="'focus'"
        enterkeyhint="search"
        inputmode="search"
        aria-label="Pencarian"
      />
      <IonButton
        v-if="showFilters"
        class="search-toolbar__filter"
        fill="outline"
        aria-label="Buka filter"
        @click="$emit('filters')"
      >
        <IonIcon :icon="optionsOutline" aria-hidden="true" />
        <span v-if="activeFilterCount" class="search-toolbar__count">
          {{ activeFilterCount }}
        </span>
      </IonButton>
    </div>

    <IonProgressBar v-if="busy" type="indeterminate" aria-label="Pencarian berlangsung" />

    <div v-if="model || resultCount !== undefined" class="search-toolbar__context" role="status">
      <span v-if="model">
        Kata kunci: <strong>{{ model }}</strong>
      </span>
      <span v-if="resultCount !== undefined">{{ resultCount }} data pada halaman ini</span>
      <button v-if="model" type="button" @click="model = ''">
        <IonIcon :icon="closeCircleOutline" aria-hidden="true" />
        Hapus pencarian
      </button>
    </div>
  </section>
</template>

<style scoped>
.search-toolbar {
  display: grid;
  gap: var(--app-space-2);
}

.search-toolbar__row {
  align-items: stretch;
  display: flex;
  gap: var(--app-space-2);
}

ion-searchbar {
  --background: var(--app-color-surface);
  --border-radius: var(--app-radius-lg);
  --box-shadow: none;
  --color: var(--app-color-text);
  --icon-color: var(--app-color-text-secondary);
  margin: 0;
  min-height: var(--app-control-height);
  padding: 0;
}

.search-toolbar__filter {
  --border-color: var(--app-color-border-strong);
  flex: 0 0 var(--app-control-height);
  margin: 0;
  min-height: var(--app-control-height);
  position: relative;
  width: var(--app-control-height);
}

.search-toolbar__count {
  align-items: center;
  background: var(--app-color-accent);
  border: 2px solid white;
  border-radius: var(--app-radius-pill);
  color: white;
  display: flex;
  font-size: 0.625rem;
  height: 1.25rem;
  justify-content: center;
  position: absolute;
  right: -0.25rem;
  top: -0.25rem;
  width: 1.25rem;
}

ion-progress-bar {
  --background: transparent;
  --progress-background: var(--ion-color-secondary);
  border-radius: var(--app-radius-pill);
  height: 0.1875rem;
}

.search-toolbar__context {
  align-items: center;
  color: var(--app-color-text-secondary);
  display: flex;
  flex-wrap: wrap;
  font-size: var(--app-font-size-xs);
  gap: var(--app-space-2) var(--app-space-3);
  justify-content: space-between;
  padding: 0 var(--app-space-1);
}

.search-toolbar__context strong {
  color: var(--app-color-text);
}

.search-toolbar__context button {
  align-items: center;
  background: transparent;
  border: 0;
  color: var(--ion-color-primary);
  display: inline-flex;
  font: inherit;
  font-weight: 750;
  gap: var(--app-space-1);
  min-height: var(--app-touch-target);
  padding: 0;
}
</style>
