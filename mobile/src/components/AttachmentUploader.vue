<script setup lang="ts">
import { ref, useId } from 'vue'
import { IonButton, IonIcon, IonProgressBar } from '@ionic/vue'
import {
  cameraOutline,
  closeCircleOutline,
  cloudUploadOutline,
  documentAttachOutline,
} from 'ionicons/icons'

const model = defineModel<File[]>({ required: true })
const props = withDefaults(defineProps<{
  label: string
  helper?: string
  accept?: string
  maxFiles?: number
  maxSizeMb?: number
  multiple?: boolean
  required?: boolean
  allowCamera?: boolean
  disabled?: boolean
  progress?: number | null
}>(), {
  helper: '',
  accept: '*/*',
  maxFiles: 1,
  maxSizeMb: 10,
  multiple: false,
  required: false,
  allowCamera: false,
  disabled: false,
  progress: null,
})

const input = ref<HTMLInputElement>()
const cameraInput = ref<HTMLInputElement>()
const error = ref('')
const inputId = useId()

function formatSize(bytes: number) {
  if (bytes < 1024 * 1024) return `${Math.max(1, Math.round(bytes / 1024))} KB`
  return `${(bytes / (1024 * 1024)).toFixed(1)} MB`
}

function select(event: Event) {
  error.value = ''
  const files = Array.from((event.target as HTMLInputElement).files ?? [])
  const oversized = files.find((file) => file.size > props.maxSizeMb * 1024 * 1024)
  if (oversized) {
    error.value = `${oversized.name} melebihi batas ${props.maxSizeMb} MB.`
    ;(event.target as HTMLInputElement).value = ''
    return
  }

  const next = props.multiple ? [...model.value, ...files] : files.slice(0, 1)
  model.value = next.slice(0, props.maxFiles)
  if (next.length > props.maxFiles) {
    error.value = `Maksimal ${props.maxFiles} file dapat dipilih.`
  }
  ;(event.target as HTMLInputElement).value = ''
}

function remove(index: number) {
  model.value = model.value.filter((_, fileIndex) => fileIndex !== index)
}
</script>

<template>
  <section class="attachment-uploader">
    <div class="attachment-uploader__label">
      <label :for="inputId">
        {{ label }}<span v-if="required" aria-hidden="true"> *</span>
      </label>
      <p v-if="helper">{{ helper }}</p>
    </div>

    <input
      :id="inputId"
      ref="input"
      class="app-visually-hidden"
      type="file"
      :accept="accept"
      :multiple="multiple"
      :disabled="disabled"
      @change="select"
    />
    <input
      v-if="allowCamera"
      ref="cameraInput"
      class="app-visually-hidden"
      type="file"
      :accept="accept"
      capture="environment"
      :disabled="disabled"
      @change="select"
    />

    <div class="attachment-uploader__buttons">
      <IonButton fill="outline" type="button" :disabled="disabled" @click="input?.click()">
        <IonIcon slot="start" :icon="cloudUploadOutline" aria-hidden="true" />
        Pilih file
      </IonButton>
      <IonButton
        v-if="allowCamera"
        fill="outline"
        type="button"
        :disabled="disabled"
        @click="cameraInput?.click()"
      >
        <IonIcon slot="start" :icon="cameraOutline" aria-hidden="true" />
        Ambil foto
      </IonButton>
    </div>

    <div v-if="model.length" class="attachment-uploader__files" aria-live="polite">
      <article v-for="(file, index) in model" :key="`${file.name}-${file.lastModified}`">
        <IonIcon :icon="documentAttachOutline" aria-hidden="true" />
        <span>
          <strong>{{ file.name }}</strong>
          <small>{{ formatSize(file.size) }}</small>
        </span>
        <button
          type="button"
          :disabled="disabled"
          :aria-label="`Hapus ${file.name}`"
          @click="remove(index)"
        >
          <IonIcon :icon="closeCircleOutline" aria-hidden="true" />
        </button>
      </article>
    </div>

    <div v-if="progress !== null" class="attachment-uploader__progress" role="status">
      <span>Mengunggah {{ progress }}%</span>
      <IonProgressBar :value="progress / 100" />
    </div>

    <p v-if="error" class="attachment-uploader__error" role="alert">{{ error }}</p>
  </section>
</template>

<style scoped>
.attachment-uploader,
.attachment-uploader__label {
  display: grid;
  gap: var(--app-space-2);
}

.attachment-uploader__label label {
  color: var(--app-color-text);
  font-size: var(--app-font-size-sm);
  font-weight: 750;
}

.attachment-uploader__label label span {
  color: var(--app-color-danger);
}

.attachment-uploader__label p,
.attachment-uploader__error {
  color: var(--app-color-text-secondary);
  font-size: var(--app-font-size-xs);
  line-height: var(--app-line-height-body);
  margin: 0;
}

.attachment-uploader__buttons {
  display: flex;
  flex-wrap: wrap;
  gap: var(--app-space-2);
}

.attachment-uploader__buttons ion-button {
  margin: 0;
}

.attachment-uploader__files {
  display: grid;
  gap: var(--app-space-2);
}

.attachment-uploader__files article {
  align-items: center;
  background: var(--app-color-surface-muted);
  border: 1px solid var(--app-color-border);
  border-radius: var(--app-radius-md);
  display: grid;
  gap: var(--app-space-2);
  grid-template-columns: auto 1fr auto;
  min-height: var(--app-touch-target);
  padding: var(--app-space-2) var(--app-space-3);
}

.attachment-uploader__files article > ion-icon {
  color: var(--app-color-info);
  font-size: var(--app-icon-md);
}

.attachment-uploader__files span {
  display: grid;
  min-width: 0;
}

.attachment-uploader__files strong {
  color: var(--app-color-text);
  font-size: var(--app-font-size-xs);
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.attachment-uploader__files small {
  color: var(--app-color-text-secondary);
  font-size: 0.6875rem;
}

.attachment-uploader__files button {
  align-items: center;
  background: transparent;
  border: 0;
  color: var(--app-color-danger);
  display: flex;
  font-size: var(--app-icon-md);
  justify-content: center;
  min-height: var(--app-touch-target);
  min-width: var(--app-touch-target);
}

.attachment-uploader__progress {
  color: var(--app-color-text-secondary);
  display: grid;
  font-size: var(--app-font-size-xs);
  gap: var(--app-space-2);
}

.attachment-uploader__error {
  color: var(--app-color-danger);
}
</style>
