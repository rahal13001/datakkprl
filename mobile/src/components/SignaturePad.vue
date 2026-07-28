<script setup lang="ts">
import { nextTick, onBeforeUnmount, ref } from 'vue'
import {
  IonButton,
  IonContent,
  IonIcon,
  IonModal,
} from '@ionic/vue'
import {
  checkmarkCircleOutline,
  closeOutline,
  createOutline,
  refreshOutline,
} from 'ionicons/icons'

const emit = defineEmits<{ change: [value: string | null] }>()
const canvas = ref<HTMLCanvasElement>()
const open = ref(false)
const signature = ref<string | null>(null)
const hasInk = ref(false)
let drawing = false
let resizeObserver: ResizeObserver | undefined

function context() {
  const value = canvas.value
  if (!value) return null
  const ctx = value.getContext('2d')
  if (!ctx) return null
  const ratio = Math.max(1, window.devicePixelRatio || 1)
  ctx.lineCap = 'round'
  ctx.lineJoin = 'round'
  ctx.lineWidth = 3 * ratio
  ctx.strokeStyle = '#0b3652'
  return ctx
}

function point(event: PointerEvent) {
  const value = canvas.value
  if (!value) return { x: 0, y: 0 }
  const bounds = value.getBoundingClientRect()
  return {
    x: (event.clientX - bounds.left) * (value.width / Math.max(bounds.width, 1)),
    y: (event.clientY - bounds.top) * (value.height / Math.max(bounds.height, 1)),
  }
}

function start(event: PointerEvent) {
  event.preventDefault()
  event.stopPropagation()
  drawing = true
  canvas.value?.setPointerCapture?.(event.pointerId)
  const ctx = context()
  const current = point(event)
  ctx?.beginPath()
  ctx?.moveTo(current.x, current.y)
}

function move(event: PointerEvent) {
  if (!drawing) return
  event.preventDefault()
  event.stopPropagation()
  const ctx = context()
  const current = point(event)
  ctx?.lineTo(current.x, current.y)
  ctx?.stroke()
  hasInk.value = true
}

function end(event?: PointerEvent) {
  if (!drawing) return
  event?.preventDefault()
  event?.stopPropagation()
  drawing = false
}

function resizeCanvas() {
  const value = canvas.value
  if (!value) return
  const bounds = value.getBoundingClientRect()
  if (bounds.width < 2 || bounds.height < 2) return

  const previous = hasInk.value ? value.toDataURL('image/png') : null
  const ratio = Math.max(1, window.devicePixelRatio || 1)
  value.width = Math.round(bounds.width * ratio)
  value.height = Math.round(bounds.height * ratio)

  const restore = previous ?? signature.value
  if (!restore) return
  const image = new Image()
  image.onload = () => context()?.drawImage(image, 0, 0, value.width, value.height)
  image.src = restore
}

async function show() {
  open.value = true
  await nextTick()
}

async function presented() {
  await nextTick()
  resizeObserver?.disconnect()
  if (canvas.value) {
    resizeObserver = new ResizeObserver(resizeCanvas)
    resizeObserver.observe(canvas.value)
  }
  resizeCanvas()
}

function cancel() {
  open.value = false
  drawing = false
}

function clearCanvas() {
  const value = canvas.value
  if (value) context()?.clearRect(0, 0, value.width, value.height)
  hasInk.value = false
}

function clearSignature() {
  signature.value = null
  clearCanvas()
  emit('change', null)
}

function confirm() {
  if (!canvas.value || !hasInk.value) return
  signature.value = canvas.value.toDataURL('image/png')
  emit('change', signature.value)
  open.value = false
}

onBeforeUnmount(() => resizeObserver?.disconnect())
</script>

<template>
  <section class="signature-field">
    <button type="button" class="signature-field__preview" @click="show">
      <img v-if="signature" :src="signature" alt="Pratinjau tanda tangan" />
      <span v-else class="signature-field__placeholder">
        <IonIcon :icon="createOutline" aria-hidden="true" />
        <strong>Buat tanda tangan</strong>
        <small>Ketuk untuk membuka area tanda tangan penuh</small>
      </span>
      <span class="signature-field__edit">{{ signature ? 'Ubah' : 'Mulai' }}</span>
    </button>

    <IonButton
      v-if="signature"
      size="small"
      fill="clear"
      color="medium"
      type="button"
      @click="clearSignature"
    >
      Hapus tanda tangan
    </IonButton>

    <IonModal
      class="signature-modal"
      :is-open="open"
      :initial-breakpoint="1"
      :breakpoints="[0, 1]"
      @did-present="presented"
      @did-dismiss="cancel"
    >
      <IonContent class="signature-modal__content" :scroll-y="false">
        <header class="signature-modal__header">
          <div>
            <span class="eyebrow">Dokumen digital</span>
            <h2>Tulis tanda tangan</h2>
            <p>Gunakan jari atau stylus pada kotak di bawah.</p>
          </div>
          <IonButton fill="clear" color="medium" aria-label="Tutup editor tanda tangan" @click="cancel">
            <IonIcon slot="icon-only" :icon="closeOutline" />
          </IonButton>
        </header>

        <div class="signature-modal__canvas-wrap">
          <canvas
            ref="canvas"
            aria-label="Area menggambar tanda tangan"
            @pointerdown="start"
            @pointermove="move"
            @pointerup="end"
            @pointercancel="end"
          />
          <span class="signature-modal__line" aria-hidden="true" />
          <small>Tanda tangan di dalam area</small>
        </div>

        <div slot="fixed" class="signature-modal__actions">
          <IonButton fill="outline" color="medium" type="button" @click="clearCanvas">
            <IonIcon slot="start" :icon="refreshOutline" />
            Ulangi
          </IonButton>
          <IonButton type="button" :disabled="!hasInk" @click="confirm">
            <IonIcon slot="start" :icon="checkmarkCircleOutline" />
            Gunakan tanda tangan
          </IonButton>
        </div>
      </IonContent>
    </IonModal>
  </section>
</template>

<style scoped>
.signature-field {
  display: grid;
  gap: var(--app-space-1);
}

.signature-field__preview {
  align-items: center;
  background:
    radial-gradient(circle at 100% 0, rgba(21, 160, 166, 0.12), transparent 42%),
    var(--app-color-surface);
  border: 1px solid var(--app-color-border-strong);
  border-radius: var(--app-radius-lg);
  color: var(--app-color-text);
  display: flex;
  min-height: 9rem;
  overflow: hidden;
  padding: var(--app-space-4);
  position: relative;
  text-align: left;
  width: 100%;
}

.signature-field__preview img {
  height: 7rem;
  object-fit: contain;
  width: 100%;
}

.signature-field__placeholder {
  align-items: center;
  display: grid;
  gap: var(--app-space-1);
  justify-items: center;
  width: 100%;
}

.signature-field__placeholder ion-icon {
  color: var(--ion-color-primary);
  font-size: 2rem;
}

.signature-field__placeholder small {
  color: var(--app-color-text-secondary);
}

.signature-field__edit {
  background: var(--ion-color-primary);
  border-radius: var(--app-radius-pill);
  color: white;
  font-size: var(--app-font-size-xs);
  font-weight: 800;
  padding: 0.4rem 0.7rem;
  position: absolute;
  right: var(--app-space-3);
  top: var(--app-space-3);
}

:global(.signature-modal) {
  --border-radius: 1.75rem 1.75rem 0 0;
  --height: min(94%, 48rem);
}

.signature-modal__content {
  --background: var(--app-color-surface-muted);
  --padding-bottom: calc(7rem + env(safe-area-inset-bottom));
  --padding-end: var(--app-space-4);
  --padding-start: var(--app-space-4);
  --padding-top: var(--app-space-4);
}

.signature-modal__header {
  align-items: flex-start;
  display: flex;
  gap: var(--app-space-3);
  justify-content: space-between;
}

.signature-modal__header h2 {
  color: var(--app-color-text);
  font-size: var(--app-font-size-xl);
  letter-spacing: -0.035em;
  margin: var(--app-space-1) 0;
}

.signature-modal__header p {
  color: var(--app-color-text-secondary);
  font-size: var(--app-font-size-sm);
  margin: 0;
}

.signature-modal__canvas-wrap {
  background: white;
  border: 1px solid var(--app-color-border);
  border-radius: var(--app-radius-xl);
  box-shadow: var(--app-shadow-md);
  margin-top: var(--app-space-5);
  overflow: hidden;
  padding: var(--app-space-3);
  position: relative;
}

canvas {
  display: block;
  height: clamp(16rem, 48vh, 24rem);
  touch-action: none;
  width: 100%;
}

.signature-modal__line {
  border-top: 1px dashed #9eb0ba;
  bottom: 3rem;
  left: var(--app-space-5);
  position: absolute;
  right: var(--app-space-5);
}

.signature-modal__canvas-wrap small {
  bottom: var(--app-space-3);
  color: var(--app-color-text-secondary);
  font-size: var(--app-font-size-xs);
  position: absolute;
  right: var(--app-space-4);
}

.signature-modal__actions {
  background: rgba(255, 255, 255, 0.98);
  border-top: 1px solid var(--app-color-border);
  bottom: 0;
  box-shadow: 0 -12px 32px rgba(13, 49, 80, 0.12);
  display: grid;
  gap: var(--app-space-2);
  grid-template-columns: minmax(0, 0.8fr) minmax(0, 1.4fr);
  left: 0;
  padding: var(--app-space-3) var(--app-space-4)
    calc(var(--app-space-3) + env(safe-area-inset-bottom));
  position: absolute;
  right: 0;
  z-index: 20;
}

.signature-modal__actions ion-button {
  margin: 0;
  min-height: var(--app-control-height);
}
</style>
