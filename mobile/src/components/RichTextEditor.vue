<script setup lang="ts">
import { onMounted, ref, useId, watch } from 'vue'
import { sanitizeRichText } from '@/security/sanitizeRichText'

const model = defineModel<string>({ required: true })
const props = withDefaults(defineProps<{
  label?: string
  helper?: string
  error?: string
  required?: boolean
  minHeight?: string
}>(), {
  label: 'Isi laporan',
  helper: 'Gunakan format sederhana agar nyaman dibaca pada perangkat mobile.',
  error: '',
  required: false,
  minHeight: '11rem',
})

const editor = ref<HTMLElement>()
const fieldId = useId()
const active = ref({
  bold: false,
  italic: false,
  insertUnorderedList: false,
  insertOrderedList: false,
})

type ToggleCommand = keyof typeof active.value
type EditorCommand = ToggleCommand | 'undo' | 'redo'

function commandState() {
  for (const command of Object.keys(active.value) as ToggleCommand[]) {
    active.value[command] = document.queryCommandState(command)
  }
}

function format(command: EditorCommand) {
  editor.value?.focus()
  document.execCommand(command)
  update()
  commandState()
}

function update() {
  if (!editor.value) return
  const sanitized = sanitizeRichText(editor.value.innerHTML)
  if (editor.value.innerHTML !== sanitized) editor.value.innerHTML = sanitized
  model.value = sanitized
}

function pastePlainText(event: ClipboardEvent) {
  const text = event.clipboardData?.getData('text/plain') ?? ''
  document.execCommand('insertText', false, text)
  update()
}

onMounted(() => {
  if (editor.value) editor.value.innerHTML = sanitizeRichText(model.value)
})

watch(model, (value) => {
  const sanitized = sanitizeRichText(value)
  if (editor.value && editor.value.innerHTML !== sanitized) {
    editor.value.innerHTML = sanitized
  }
})
</script>

<template>
  <div class="editor-field">
    <label :for="fieldId">
      {{ label }}<span v-if="required" aria-hidden="true"> *</span>
    </label>
    <p v-if="helper" :id="`${fieldId}-helper`" class="editor-helper">{{ helper }}</p>

    <div class="editor-toolbar" role="toolbar" :aria-label="`Format ${label}`">
      <button
        type="button"
        aria-label="Tebal"
        :aria-pressed="active.bold"
        :class="{ active: active.bold }"
        @click="format('bold')"
      >
        <strong>B</strong>
      </button>
      <button
        type="button"
        aria-label="Miring"
        :aria-pressed="active.italic"
        :class="{ active: active.italic }"
        @click="format('italic')"
      >
        <em>I</em>
      </button>
      <button
        type="button"
        aria-label="Daftar berpoin"
        :aria-pressed="active.insertUnorderedList"
        :class="{ active: active.insertUnorderedList }"
        @click="format('insertUnorderedList')"
      >
        • Daftar
      </button>
      <button
        type="button"
        aria-label="Daftar bernomor"
        :aria-pressed="active.insertOrderedList"
        :class="{ active: active.insertOrderedList }"
        @click="format('insertOrderedList')"
      >
        1. Daftar
      </button>
      <span class="editor-toolbar__separator" aria-hidden="true" />
      <button type="button" aria-label="Urungkan" @click="format('undo')">↶</button>
      <button type="button" aria-label="Ulangi" @click="format('redo')">↷</button>
    </div>

    <div
      :id="fieldId"
      ref="editor"
      class="editor"
      :class="{ invalid: error }"
      contenteditable="true"
      role="textbox"
      :aria-label="label"
      aria-multiline="true"
      :aria-required="required"
      :aria-invalid="Boolean(error)"
      :aria-describedby="helper ? `${fieldId}-helper` : undefined"
      :style="{ minHeight }"
      @input="update"
      @blur="update"
      @keyup="commandState"
      @mouseup="commandState"
      @paste.prevent="pastePlainText"
    />
    <p v-if="error" class="editor-error" role="alert">{{ error }}</p>
  </div>
</template>

<style scoped>
.editor-field {
  display: grid;
  gap: var(--app-space-2);
}

.editor-field > label {
  color: var(--app-color-text);
  font-size: var(--app-font-size-sm);
  font-weight: 750;
}

.editor-field > label span {
  color: var(--app-color-danger);
}

.editor-helper,
.editor-error {
  color: var(--app-color-text-secondary);
  font-size: var(--app-font-size-xs);
  line-height: var(--app-line-height-body);
  margin: calc(-1 * var(--app-space-1)) 0 0;
}

.editor-toolbar {
  align-items: center;
  background: var(--app-color-surface-muted);
  border: 1px solid var(--app-color-border);
  border-radius: var(--app-radius-md);
  display: flex;
  gap: var(--app-space-1);
  overflow-x: auto;
  padding: var(--app-space-1);
}

.editor-toolbar button {
  background: transparent;
  border: 0;
  border-radius: var(--app-radius-sm);
  color: var(--app-color-text);
  flex: 0 0 auto;
  font-size: var(--app-font-size-sm);
  min-height: var(--app-touch-target);
  min-width: var(--app-touch-target);
  padding: var(--app-space-2);
}

.editor-toolbar button.active {
  background: var(--ion-color-primary);
  color: white;
}

.editor-toolbar__separator {
  background: var(--app-color-border);
  height: 1.75rem;
  margin: 0 var(--app-space-1);
  width: 1px;
}

.editor {
  background: var(--app-color-surface);
  border: 1px solid var(--app-color-border-strong);
  border-radius: var(--app-radius-md);
  color: var(--app-color-text);
  font-size: var(--app-font-size-md);
  line-height: var(--app-line-height-body);
  outline: none;
  overflow-y: auto;
  padding: var(--app-space-3);
}

.editor:focus {
  border-color: var(--app-color-focus);
  box-shadow: 0 0 0 3px color-mix(in srgb, var(--app-color-focus) 18%, transparent);
}

.editor.invalid {
  border-color: var(--app-color-danger);
}

.editor-error {
  color: var(--app-color-danger);
}
</style>
