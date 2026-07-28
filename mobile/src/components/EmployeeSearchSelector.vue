<script setup lang="ts">
import { computed, onBeforeUnmount, ref, watch } from 'vue'
import { useQuery } from '@tanstack/vue-query'
import {
  IonButton,
  IonCheckbox,
  IonChip,
  IonContent,
  IonIcon,
  IonModal,
  IonProgressBar,
  IonSearchbar,
} from '@ionic/vue'
import {
  checkmarkCircleOutline,
  closeOutline,
  closeCircleOutline,
  peopleOutline,
  personAddOutline,
} from 'ionicons/icons'
import { api, apiError } from '@/api/client'
import EmptyState from '@/components/EmptyState.vue'
import ErrorState from '@/components/ErrorState.vue'
import type { ApiEnvelope, StaffMember } from '@/types/api'

const model = defineModel<number[]>({ required: true })
const props = withDefaults(defineProps<{
  label?: string
  helper?: string
  multiple?: boolean
  disabled?: boolean
  selectedEmployees?: StaffMember[]
  required?: boolean
}>(), {
  label: 'Pegawai',
  helper: 'Cari berdasarkan nama, NIP, jabatan, atau instansi.',
  multiple: true,
  disabled: false,
  selectedEmployees: () => [],
  required: false,
})

const open = ref(false)
const search = ref('')
const debouncedSearch = ref('')
const draft = ref<number[]>([])
const knownEmployees = ref(new Map<number, StaffMember>())
let debounceTimer: number | undefined

watch(search, (value) => {
  window.clearTimeout(debounceTimer)
  debounceTimer = window.setTimeout(() => {
    debouncedSearch.value = value.trim()
  }, 400)
})

onBeforeUnmount(() => window.clearTimeout(debounceTimer))

const query = useQuery({
  queryKey: ['staff-search', debouncedSearch],
  queryFn: async () => (
    await api.get<ApiEnvelope<StaffMember[]>>('/staff', {
      params: { search: debouncedSearch.value || undefined },
    })
  ).data.data,
  enabled: computed(() => open.value),
})

watch(
  () => query.data.value,
  (employees) => employees?.forEach((employee) => knownEmployees.value.set(employee.id, employee)),
)
watch(
  () => props.selectedEmployees,
  (employees) => employees.forEach((employee) => knownEmployees.value.set(employee.id, employee)),
  { immediate: true },
)

function initials(name: string) {
  return name
    .split(/\s+/)
    .slice(0, 2)
    .map((part) => part[0]?.toUpperCase())
    .join('')
}

function show() {
  if (props.disabled) return
  draft.value = [...model.value]
  search.value = ''
  debouncedSearch.value = ''
  open.value = true
}

function cancel() {
  open.value = false
}

function toggle(id: number) {
  if (!props.multiple) {
    draft.value = [id]
    return
  }

  draft.value = draft.value.includes(id)
    ? draft.value.filter((value) => value !== id)
    : [...draft.value, id]
}

function confirm() {
  model.value = [...draft.value]
  open.value = false
}

function remove(id: number) {
  if (props.disabled) return
  model.value = model.value.filter((value) => value !== id)
}

function clearDraft() {
  draft.value = []
}
</script>

<template>
  <div class="employee-selector">
    <div class="employee-selector__label">
      <strong>{{ label }}<span v-if="required" aria-hidden="true"> *</span></strong>
      <span>{{ helper }}</span>
    </div>

    <div v-if="model.length" class="employee-selector__selected" aria-live="polite">
      <IonChip v-for="id in model" :key="id">
        <span class="employee-selector__chip-avatar">
          {{ initials(knownEmployees.get(id)?.name ?? String(id)) }}
        </span>
        {{ knownEmployees.get(id)?.name ?? `Pegawai #${id}` }}
        <button
          type="button"
          :aria-label="`Hapus ${knownEmployees.get(id)?.name ?? `pegawai ${id}`}`"
          :disabled="disabled"
          @click="remove(id)"
        >
          <IonIcon :icon="closeCircleOutline" aria-hidden="true" />
        </button>
      </IonChip>
    </div>

    <IonButton expand="block" fill="outline" type="button" :disabled="disabled" @click="show">
      <IonIcon slot="start" :icon="personAddOutline" aria-hidden="true" />
      {{ model.length ? 'Ubah pilihan pegawai' : 'Pilih pegawai' }}
    </IonButton>

    <IonModal
      class="employee-picker-modal"
      :is-open="open"
      :initial-breakpoint="1"
      :breakpoints="[0, 1]"
      @did-dismiss="cancel"
    >
      <IonContent class="employee-sheet">
        <div class="employee-sheet__header">
          <span class="employee-sheet__icon"><IonIcon :icon="peopleOutline" /></span>
          <div>
            <span class="eyebrow">Penugasan</span>
            <h2>{{ multiple ? 'Pilih pegawai' : 'Pilih satu pegawai' }}</h2>
            <p>{{ draft.length }} dipilih</p>
          </div>
          <IonButton fill="clear" color="medium" aria-label="Tutup pemilihan pegawai" @click="cancel">
            <IonIcon slot="icon-only" :icon="closeOutline" />
          </IonButton>
        </div>

        <IonSearchbar
          v-model="search"
          :debounce="0"
          placeholder="Nama, NIP, jabatan, atau instansi"
          show-clear-button="focus"
          inputmode="search"
          enterkeyhint="search"
          aria-label="Cari pegawai"
        />
        <IonProgressBar v-if="query.isFetching.value" type="indeterminate" />

        <p class="employee-sheet__hint">
          Hasil berasal dari pencarian server dan dibatasi maksimal 50 pegawai.
        </p>

        <ErrorState
          v-if="query.error.value"
          compact
          :message="apiError(query.error.value)"
          @retry="query.refetch()"
        />

        <div v-else-if="query.data.value?.length" class="employee-list" role="list">
          <button
            v-for="employee in query.data.value"
            :key="employee.id"
            type="button"
            class="employee-row"
            :class="{ selected: draft.includes(employee.id) }"
            :aria-pressed="draft.includes(employee.id)"
            @click="toggle(employee.id)"
          >
            <span class="employee-row__avatar">{{ initials(employee.name) }}</span>
            <span class="employee-row__copy">
              <strong>{{ employee.name }}</strong>
              <span>{{ employee.jabatan || 'Jabatan belum diisi' }}</span>
              <small>
                {{ employee.nip ? `NIP ${employee.nip}` : 'NIP tidak tersedia' }}
                <template v-if="employee.instansi"> · {{ employee.instansi }}</template>
              </small>
            </span>
            <IonCheckbox
              :checked="draft.includes(employee.id)"
              :aria-label="`Pilih ${employee.name}`"
              @click.stop="toggle(employee.id)"
            />
          </button>
        </div>

        <EmptyState
          v-else-if="!query.isLoading.value"
          kind="search"
          title="Pegawai tidak ditemukan"
          body="Ubah kata kunci dan coba lagi."
        />

        <div slot="fixed" class="employee-sheet__actions">
          <div class="employee-sheet__selection">
            <span>{{ draft.length }} pegawai dipilih</span>
            <IonButton
              v-if="draft.length"
              fill="clear"
              color="medium"
              size="small"
              type="button"
              @click="clearDraft"
            >
              Bersihkan
            </IonButton>
          </div>
          <div class="employee-sheet__action-row">
            <IonButton fill="outline" color="medium" type="button" @click="cancel">
              Batal
            </IonButton>
            <IonButton type="button" :disabled="required && !draft.length" @click="confirm">
              <IonIcon slot="start" :icon="checkmarkCircleOutline" aria-hidden="true" />
              Gunakan {{ draft.length || '' }} pegawai
            </IonButton>
          </div>
        </div>
      </IonContent>
    </IonModal>
  </div>
</template>

<style scoped>
.employee-selector,
.employee-selector__label {
  display: grid;
  gap: var(--app-space-2);
}

.employee-selector__label strong {
  color: var(--app-color-text);
  font-size: var(--app-font-size-sm);
}

.employee-selector__label strong span,
.form-required {
  color: var(--app-color-danger);
}

.employee-selector__label > span {
  color: var(--app-color-text-secondary);
  font-size: var(--app-font-size-xs);
  line-height: var(--app-line-height-body);
}

.employee-selector__selected {
  display: flex;
  flex-wrap: wrap;
  gap: var(--app-space-2);
}

ion-chip {
  --background: var(--app-color-info-soft);
  --color: var(--app-color-info);
  min-height: var(--app-touch-target);
}

.employee-selector__chip-avatar {
  align-items: center;
  background: var(--ion-color-primary);
  border-radius: 50%;
  color: white;
  display: inline-flex;
  font-size: 0.625rem;
  font-weight: 850;
  height: 1.5rem;
  justify-content: center;
  width: 1.5rem;
}

ion-chip button {
  align-items: center;
  background: transparent;
  border: 0;
  color: currentColor;
  display: inline-flex;
  font-size: var(--app-icon-sm);
  justify-content: center;
  min-height: var(--app-touch-target);
  min-width: var(--app-touch-target);
}

.employee-sheet {
  --background: var(--app-color-surface-muted);
  --padding-bottom: calc(9rem + env(safe-area-inset-bottom));
  --padding-end: var(--app-space-4);
  --padding-start: var(--app-space-4);
  --padding-top: var(--app-space-4);
}

:global(.employee-picker-modal) {
  --border-radius: 1.75rem 1.75rem 0 0;
  --height: min(94%, 48rem);
}

.employee-sheet__header {
  align-items: center;
  display: flex;
  gap: var(--app-space-3);
  padding: var(--app-space-2) 0 var(--app-space-3);
}

.employee-sheet__header > div {
  display: grid;
  gap: 0.125rem;
}

.employee-sheet__header > ion-button {
  margin-left: auto;
}

.employee-sheet__icon {
  align-items: center;
  background: var(--app-color-info-soft);
  border-radius: var(--app-radius-lg);
  color: var(--app-color-info);
  display: flex;
  font-size: var(--app-icon-lg);
  height: 3rem;
  justify-content: center;
  width: 3rem;
}

.employee-sheet h2 {
  color: var(--app-color-text);
  font-size: var(--app-font-size-lg);
  margin: 0;
}

.employee-sheet__header p,
.employee-sheet__hint {
  color: var(--app-color-text-secondary);
  font-size: var(--app-font-size-xs);
  line-height: var(--app-line-height-body);
  margin: 0;
}

.employee-sheet ion-searchbar {
  --background: var(--app-color-surface);
  --border-radius: var(--app-radius-lg);
  --box-shadow: none;
  padding: 0;
}

.employee-sheet__hint {
  margin: var(--app-space-2) 0 var(--app-space-3);
}

.employee-list {
  display: grid;
  gap: var(--app-space-2);
}

.employee-row {
  align-items: center;
  background: var(--app-color-surface);
  border: 1px solid var(--app-color-border);
  border-radius: var(--app-radius-lg);
  color: inherit;
  display: grid;
  gap: var(--app-space-3);
  grid-template-columns: auto 1fr auto;
  min-height: 4.75rem;
  padding: var(--app-space-3);
  text-align: left;
  width: 100%;
}

.employee-row.selected {
  background: var(--app-color-info-soft);
  border-color: var(--app-color-info);
}

.employee-row__avatar {
  align-items: center;
  background: var(--ion-color-primary);
  border-radius: var(--app-radius-md);
  color: white;
  display: flex;
  font-size: var(--app-font-size-xs);
  font-weight: 850;
  height: 2.75rem;
  justify-content: center;
  width: 2.75rem;
}

.employee-row__copy {
  display: grid;
  gap: 0.125rem;
  min-width: 0;
}

.employee-row__copy strong {
  color: var(--app-color-text);
  font-size: var(--app-font-size-sm);
}

.employee-row__copy span,
.employee-row__copy small {
  color: var(--app-color-text-secondary);
  font-size: var(--app-font-size-xs);
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.employee-sheet__actions {
  background: rgba(255, 255, 255, 0.98);
  border-top: 1px solid var(--app-color-border);
  bottom: 0;
  box-shadow: 0 -12px 32px rgba(13, 49, 80, 0.12);
  display: grid;
  gap: var(--app-space-2);
  left: 0;
  padding: var(--app-space-2) var(--app-space-4)
    calc(var(--app-space-3) + env(safe-area-inset-bottom));
  position: absolute;
  right: 0;
  z-index: 20;
}

.employee-sheet__selection,
.employee-sheet__action-row {
  align-items: center;
  display: flex;
  gap: var(--app-space-2);
  justify-content: space-between;
}

.employee-sheet__selection {
  color: var(--app-color-text-secondary);
  font-size: var(--app-font-size-xs);
  font-weight: 750;
}

.employee-sheet__action-row ion-button {
  flex: 1;
  min-height: var(--app-control-height);
}

.employee-sheet__actions ion-button {
  margin: 0;
}
</style>
