<script setup lang="ts">
import { onMounted, ref, watch } from 'vue'
import { sanitizeRichText } from '@/security/sanitizeRichText'

const model = defineModel<string>({ required: true })
const editor = ref<HTMLElement>()

function format(command: 'bold' | 'italic' | 'insertUnorderedList') {
  editor.value?.focus()
  document.execCommand(command)
  update()
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
  <label class="editor-field">
    <span>Isi laporan</span>
    <span class="editor-toolbar">
      <button type="button" aria-label="Tebal" @click="format('bold')"><strong>B</strong></button>
      <button type="button" aria-label="Miring" @click="format('italic')"><em>I</em></button>
      <button type="button" aria-label="Daftar" @click="format('insertUnorderedList')">• Daftar</button>
    </span>
    <span
      ref="editor"
      class="editor"
      contenteditable="true"
      role="textbox"
      aria-multiline="true"
      @input="update"
      @blur="update"
      @paste.prevent="pastePlainText"
    />
  </label>
</template>

<style scoped>
.editor-field {
  color: var(--app-muted);
  display: grid;
  font-size: 0.78rem;
  gap: 6px;
}

.editor-toolbar {
  display: flex;
  gap: 6px;
}

.editor-toolbar button {
  background: #edf3f5;
  border: 0;
  border-radius: 8px;
  color: var(--app-ink);
  min-height: 34px;
  padding: 6px 11px;
}

.editor {
  background: #fff;
  border: 1px solid var(--app-line);
  border-radius: 11px;
  color: var(--app-ink);
  line-height: 1.55;
  min-height: 150px;
  outline: none;
  padding: 12px;
}

.editor:focus {
  border-color: var(--ion-color-primary);
}
</style>
