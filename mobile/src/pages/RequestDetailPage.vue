<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { onBeforeRouteLeave, useRoute, useRouter } from 'vue-router'
import { useQuery, useQueryClient } from '@tanstack/vue-query'
import axios from 'axios'
import {
  IonAccordion,
  IonAccordionGroup,
  IonButton,
  IonCheckbox,
  IonContent,
  IonInput,
  IonItem,
  IonLabel,
  IonPage,
  IonRefresher,
  IonRefresherContent,
  IonSelect,
  IonSelectOption,
  IonTextarea,
  alertController,
  toastController,
} from '@ionic/vue'
import { api, apiError } from '@/api/client'
import AppHeader from '@/components/AppHeader.vue'
import AttachmentUploader from '@/components/AttachmentUploader.vue'
import EmployeeSearchSelector from '@/components/EmployeeSearchSelector.vue'
import ErrorState from '@/components/ErrorState.vue'
import FormSection from '@/components/FormSection.vue'
import LoadingSkeleton from '@/components/LoadingSkeleton.vue'
import PageContainer from '@/components/PageContainer.vue'
import StatusBadge from '@/components/StatusBadge.vue'
import EmptyState from '@/components/EmptyState.vue'
import SignaturePad from '@/components/SignaturePad.vue'
import RichTextEditor from '@/components/RichTextEditor.vue'
import StickyFormActions from '@/components/StickyFormActions.vue'
import { useAuthStore } from '@/stores/auth'
import { openDocument, responseFilename } from '@/native/documentViewer'
import {
  navigationFallback,
  validPositiveId,
  validTicket,
} from '@/navigation/safeNavigation'
import type {
  ApiEnvelope,
  Assignment,
  BeritaAcara,
  ClientDetail,
  ConsultationReport,
  Schedule,
} from '@/types/api'

interface AttendeeDraft {
  id?: number
  name: string
  position: string
  institution: string
  email: string
  phone: string
  is_officer: boolean
  is_signatory: boolean
  signature?: string | null
}

interface BeritaAcaraDraft {
  nomor_berita_acara: string
  kbli: string
  tanggal_pelaksanaan: string
  lokasi_permohonan: string
  hasil_pendampingan: string
  status: 'draft' | 'completed'
  attendance_is_open: boolean
  applicant_signature?: string | null
  attendees: AttendeeDraft[]
}

const auth = useAuthStore()
const route = useRoute()
const router = useRouter()
const queryClient = useQueryClient()
const ticket = computed(() => validTicket(route.params.ticket))
const saving = ref(false)
const error = ref('')
const assignmentError = ref('')
const reportError = ref('')
const baError = ref('')
const statusDraft = ref<ClientDetail['status']>()
const clientDraft = ref({
  name: '',
  email: '',
  whatsapp: '',
  instance: '',
  address: '',
  booking_type: 'personal' as 'personal' | 'company',
  activity_type: null as 'business' | 'non_business' | null,
})
const scheduleDrafts = ref<Record<number, Schedule>>({})
const reportDrafts = ref<Record<number, Pick<ConsultationReport, 'content' | 'status'>>>({})

function clientApiPath(suffix = ''): string {
  if (!ticket.value) throw new Error('Nomor tiket tidak valid.')
  return `/clients/${encodeURIComponent(ticket.value)}${suffix}`
}

function nestedResourcePath(resource: string, id: unknown): string {
  const normalized = validPositiveId(typeof id === 'number' ? String(id) : id)
  if (!normalized) throw new Error('Identitas data terkait tidak valid.')
  return clientApiPath(`/${resource}/${normalized}`)
}

async function loadClient(): Promise<ClientDetail> {
  try {
    return (await api.get<ApiEnvelope<ClientDetail>>(clientApiPath())).data.data
  } catch (reason) {
    if (axios.isAxiosError(reason) && [403, 404, 410].includes(reason.response?.status ?? 0)) {
      await router.replace(navigationFallback('requests', 'resource_unavailable'))
    }
    throw reason
  }
}

const clientQuery = useQuery({
  queryKey: ['client', ticket],
  queryFn: loadClient,
  enabled: computed(() => ticket.value !== null),
})

const scheduleForm = ref({
  date: '',
  start_time: '09:00',
  end_time: '10:00',
  is_online: false,
  meeting_link: '',
})
const assignmentForm = ref({
  schedule_ids: [] as number[],
  user_ids: [] as number[],
  status: 'scheduled' as Assignment['status'],
})
const reportForm = ref({
  content: '',
  status: 'draft' as 'draft' | 'completed',
  signature: null as string | null,
})
const reportFiles = ref<File[]>([])
const reportSignatures = ref<Record<number, string | null>>({})
const reportUploadProgress = ref<number | null>(null)
const baDraft = ref<BeritaAcaraDraft>()
const baBaseline = ref('')
const baMapFiles = ref<File[]>([])
const baDocumentationFiles = ref<File[]>([])
const baOtherFiles = ref<File[]>([])
const baUploadProgress = ref<number | null>(null)
const reportFormDirty = computed(
  () => Boolean(
    plainText(reportForm.value.content).trim() ||
    reportFiles.value.length ||
    reportForm.value.signature ||
    reportForm.value.status !== 'draft',
  ),
)
const baFormDirty = computed(
  () => Boolean(
    baDraft.value &&
    (
      JSON.stringify(baDraft.value) !== baBaseline.value ||
      baMapFiles.value.length ||
      baDocumentationFiles.value.length ||
      baOtherFiles.value.length
    )
  ),
)

onBeforeRouteLeave(async () => {
  if (!reportFormDirty.value && !baFormDirty.value) return true

  const confirmation = await alertController.create({
    header: 'Perubahan belum disimpan',
    message: 'Jika Anda keluar sekarang, perubahan pada form akan hilang.',
    buttons: [
      { text: 'Tetap di halaman', role: 'cancel' },
      { text: 'Keluar', role: 'leave' },
    ],
  })
  await confirmation.present()
  const { role } = await confirmation.onDidDismiss()
  return role === 'leave'
})

async function refreshedMessage(message: string) {
  await clientQuery.refetch()
  await queryClient.invalidateQueries({ queryKey: ['dashboard'] })
  const toast = await toastController.create({ message, duration: 1800, color: 'success' })
  await toast.present()
}

async function saveClientStatus() {
  const client = clientQuery.data.value
  if (!client || !statusDraft.value) return
  await perform(async () => {
    await api.patch(clientApiPath(), {
      version: client.version,
      status: statusDraft.value,
    })
    await refreshedMessage('Status permohonan diperbarui.')
  })
}

async function saveClientIdentity() {
  const client = clientQuery.data.value
  if (!client) return
  await perform(async () => {
    await api.patch(clientApiPath(), {
      version: client.version,
      ...clientDraft.value,
      instance: clientDraft.value.instance || null,
      address: clientDraft.value.address || null,
    })
    await refreshedMessage('Data permohonan diperbarui.')
  })
}

async function addSchedule() {
  await perform(async () => {
    await api.post(clientApiPath('/schedules'), {
      ...scheduleForm.value,
      meeting_link: scheduleForm.value.is_online ? scheduleForm.value.meeting_link : null,
    })
    scheduleForm.value = {
      date: '',
      start_time: '09:00',
      end_time: '10:00',
      is_online: false,
      meeting_link: '',
    }
    await refreshedMessage('Jadwal ditambahkan.')
  })
}

async function saveSchedule(schedule: Schedule) {
  const draft = scheduleDrafts.value[schedule.id]
  if (!draft) return
  await perform(async () => {
    await api.patch(nestedResourcePath('schedules', schedule.id), {
      version: schedule.version,
      date: draft.date,
      start_time: draft.start_time.slice(0, 5),
      end_time: draft.end_time.slice(0, 5),
      is_online: draft.is_online,
      meeting_link: draft.is_online ? draft.meeting_link : null,
    })
    await refreshedMessage('Jadwal diperbarui.')
  })
}

async function addAssignments() {
  if (saving.value) return
  assignmentError.value = ''
  if (!assignmentForm.value.schedule_ids.length || !assignmentForm.value.user_ids.length) {
    assignmentError.value = 'Pilih minimal satu jadwal dan satu pegawai sebelum melanjutkan.'
    return
  }

  const confirmation = await alertController.create({
    header: 'Konfirmasi penugasan',
    message: `Tetapkan ${assignmentForm.value.user_ids.length} pegawai pada ${assignmentForm.value.schedule_ids.length} jadwal?`,
    buttons: [
      { text: 'Batal', role: 'cancel' },
      { text: 'Tetapkan', role: 'confirm' },
    ],
  })
  await confirmation.present()
  const { role } = await confirmation.onDidDismiss()
  if (role !== 'confirm') return

  await perform(async () => {
    const response = await api.post<
      ApiEnvelope<Assignment[]> & { meta?: { warnings?: Array<{ message: string }> } }
    >(clientApiPath('/assignments'), assignmentForm.value)
    assignmentForm.value = { schedule_ids: [], user_ids: [], status: 'scheduled' }
    assignmentError.value = ''
    await refreshedMessage('Penugasan dibuat.')
    const warnings = response.data.meta?.warnings ?? []
    if (warnings.length) {
      const toast = await toastController.create({
        message: warnings.map((warning) => warning.message).join(' '),
        duration: 4500,
        color: 'warning',
      })
      await toast.present()
    }
  })
}

function reassignFromSelection(assignment: Assignment, userIds: number[]) {
  const [userId] = userIds
  if (userId) void reassignOfficer(assignment, userId)
}

async function updateAssignmentStatus(assignment: Assignment, status: Assignment['status']) {
  await perform(async () => {
    await api.patch(nestedResourcePath('assignments', assignment.id), {
      version: assignment.version,
      status,
    })
    await refreshedMessage('Status penugasan diperbarui.')
  })
}

async function reassignOfficer(assignment: Assignment, userId: number) {
  if (assignment.officer?.id === userId) return
  await perform(async () => {
    await api.patch(nestedResourcePath('assignments', assignment.id), {
      version: assignment.version,
      user_id: userId,
    })
    await refreshedMessage('Petugas penugasan diperbarui.')
  })
}

async function addReport() {
  if (saving.value) return
  reportError.value = ''
  if (!plainText(reportForm.value.content).trim()) {
    reportError.value = 'Isi laporan wajib diisi.'
    return
  }
  if (!reportFiles.value.length) {
    reportError.value = 'Pilih minimal satu foto dokumentasi.'
    return
  }
  if (reportForm.value.status === 'completed' && !reportForm.value.signature) {
    reportError.value = 'Tanda tangan petugas wajib diisi sebelum laporan diselesaikan.'
    return
  }

  const confirmation = await alertController.create({
    header: reportForm.value.status === 'completed' ? 'Kirim laporan?' : 'Simpan laporan?',
    message: plainText(reportForm.value.content).trim().slice(0, 180),
    buttons: [
      { text: 'Batal', role: 'cancel' },
      {
        text: reportForm.value.status === 'completed' ? 'Kirim' : 'Simpan',
        role: 'confirm',
      },
    ],
  })
  await confirmation.present()
  const { role } = await confirmation.onDidDismiss()
  if (role !== 'confirm') return

  await perform(async () => {
    const form = new FormData()
    form.append('content', reportForm.value.content)
    form.append('status', reportForm.value.status)
    if (reportForm.value.signature) form.append('signature', reportForm.value.signature)
    reportFiles.value.forEach((file) => form.append('documentation[]', file))
    try {
      await api.post(clientApiPath('/consultation-reports'), form, {
        onUploadProgress: (event) => {
          if (event.total) {
            reportUploadProgress.value = Math.round((event.loaded / event.total) * 100)
          }
        },
      })
    } finally {
      reportUploadProgress.value = null
    }
    reportForm.value = { content: '', status: 'draft', signature: null }
    reportFiles.value = []
    reportError.value = ''
    await refreshedMessage('Laporan konsultasi disimpan.')
  })
}

function saveReportAsDraft() {
  reportForm.value.status = 'draft'
  void addReport()
}

async function saveReport(report: ConsultationReport) {
  const draft = reportDrafts.value[report.id]
  if (!draft) return
  await perform(async () => {
    const form = new FormData()
    form.append('version', report.version)
    form.append('content', draft.content)
    form.append('status', draft.status)
    if (reportSignatures.value[report.id]) {
      form.append('signature', reportSignatures.value[report.id] as string)
    }
    await api.patch(nestedResourcePath('consultation-reports', report.id), form)
    reportSignatures.value[report.id] = null
    await refreshedMessage('Laporan konsultasi diperbarui.')
  })
}

async function saveBeritaAcara() {
  const client = clientQuery.data.value
  const ba = client?.berita_acara
  const draft = baDraft.value
  if (!client || !draft) return
  if (saving.value) return
  baError.value = ''
  if (!draft.tanggal_pelaksanaan) {
    baError.value = 'Tanggal pelaksanaan wajib diisi.'
    return
  }
  if (draft.attendees.some((attendee) => !attendee.name.trim())) {
    baError.value = 'Nama setiap peserta wajib diisi.'
    return
  }

  const confirmation = await alertController.create({
    header: draft.status === 'completed' ? 'Selesaikan Berita Acara?' : 'Simpan draft Berita Acara?',
    message: `${draft.attendees.length} peserta · ${date(draft.tanggal_pelaksanaan)}`,
    buttons: [
      { text: 'Batal', role: 'cancel' },
      { text: draft.status === 'completed' ? 'Selesaikan' : 'Simpan', role: 'confirm' },
    ],
  })
  await confirmation.present()
  const { role } = await confirmation.onDidDismiss()
  if (role !== 'confirm') return

  await perform(async () => {
    const form = new FormData()
    if (ba) form.append('version', ba.version)
    form.append('nomor_berita_acara', draft.nomor_berita_acara)
    form.append('kbli', draft.kbli)
    form.append('tanggal_pelaksanaan', draft.tanggal_pelaksanaan)
    form.append('lokasi_permohonan', draft.lokasi_permohonan)
    form.append('hasil_pendampingan', draft.hasil_pendampingan)
    form.append('status', draft.status)
    form.append('attendance_is_open', draft.attendance_is_open ? '1' : '0')
    if (draft.applicant_signature) {
      form.append('applicant_signature', draft.applicant_signature)
    }
    draft.attendees.forEach((attendee, index) => {
      if (attendee.id) form.append(`attendees[${index}][id]`, String(attendee.id))
      form.append(`attendees[${index}][name]`, attendee.name)
      form.append(`attendees[${index}][position]`, attendee.position)
      form.append(`attendees[${index}][institution]`, attendee.institution)
      form.append(`attendees[${index}][email]`, attendee.email)
      form.append(`attendees[${index}][phone]`, attendee.phone)
      form.append(`attendees[${index}][is_officer]`, attendee.is_officer ? '1' : '0')
      form.append(`attendees[${index}][is_signatory]`, attendee.is_signatory ? '1' : '0')
      if (attendee.signature) form.append(`attendees[${index}][signature]`, attendee.signature)
    })
    if (baMapFiles.value[0]) form.append('map_attachment', baMapFiles.value[0])
    baDocumentationFiles.value.forEach((file) => form.append('documentation_attachments[]', file))
    baOtherFiles.value.forEach((file) => form.append('other_attachments[]', file))

    const uploadConfig = {
      onUploadProgress: (event: { loaded: number; total?: number }) => {
        if (event.total) {
          baUploadProgress.value = Math.round((event.loaded / event.total) * 100)
        }
      },
    }
    try {
      if (ba) {
        await api.patch(nestedResourcePath('berita-acara', ba.id), form, uploadConfig)
      } else {
        await api.post(clientApiPath('/berita-acara'), form, uploadConfig)
      }
    } finally {
      baUploadProgress.value = null
    }
    baDraft.value = undefined
    baMapFiles.value = []
    baDocumentationFiles.value = []
    baOtherFiles.value = []
    baError.value = ''
    await refreshedMessage('Berita Acara diperbarui.')
  })
}

function saveBeritaAcaraAsDraft() {
  if (!baDraft.value) return
  baDraft.value.status = 'draft'
  void saveBeritaAcara()
}

function addAttendee() {
  baDraft.value?.attendees.push({
    name: '',
    position: '',
    institution: '',
    email: '',
    phone: '',
    is_officer: false,
    is_signatory: true,
  })
}

function removeNewAttendee(index: number) {
  const attendee = baDraft.value?.attendees[index]
  if (attendee && !attendee.id) baDraft.value?.attendees.splice(index, 1)
}

async function openProtected(path: string, fallbackName = 'dokumen.pdf') {
  await perform(async () => {
    const response = await api.get(path, { responseType: 'blob' })
    const contentType = String(response.headers['content-type'] ?? response.data.type ?? '')
    const filename = responseFilename(
      response.headers['content-disposition'],
      fallbackName,
      contentType,
    )
    await openDocument(response.data, filename)
  })
}

function privateFilePath(path: string) {
  const segments: string[] = []
  for (const segment of path.split('/')) {
    const normalized = validTicket(segment)
    if (!normalized) throw new Error('Identitas dokumen tidak valid.')
    segments.push(normalized)
  }
  if (!segments.length) throw new Error('Identitas dokumen tidak valid.')
  return clientApiPath(`/files/${segments.map(encodeURIComponent).join('/')}`)
}

async function perform(action: () => Promise<void>) {
  error.value = ''
  saving.value = true
  try {
    await action()
  } catch (reason) {
    error.value = apiError(reason)
  } finally {
    saving.value = false
  }
}

async function refresh(event: CustomEvent) {
  await clientQuery.refetch()
  ;(event.target as HTMLIonRefresherElement).complete()
}

function initializeDrafts(client: ClientDetail) {
  let initializedBa = false
  statusDraft.value ??= client.status
  clientDraft.value = {
    name: client.name,
    email: client.email,
    whatsapp: client.whatsapp,
    instance: client.instance ?? '',
    address: client.address ?? '',
    booking_type: client.booking_type ?? 'personal',
    activity_type: client.activity_type ?? null,
  }
  scheduleDrafts.value = Object.fromEntries(
    client.schedules.map((schedule) => [schedule.id, { ...schedule }]),
  )
  reportDrafts.value = Object.fromEntries(
    client.consultation_reports.map((report) => [
      report.id,
      { content: report.content, status: report.status },
    ]),
  )
  if (client.berita_acara && !baDraft.value) {
    const record: BeritaAcara = client.berita_acara
    baDraft.value = {
      nomor_berita_acara: record.nomor_berita_acara ?? '',
      kbli: record.kbli ?? '',
      tanggal_pelaksanaan: record.tanggal_pelaksanaan,
      lokasi_permohonan: record.lokasi_permohonan ?? '',
      hasil_pendampingan: record.hasil_pendampingan ?? '',
      status: record.status,
      attendance_is_open: record.attendance_is_open,
      attendees: record.attendees.map((attendee) => ({
        id: attendee.id,
        name: attendee.name,
        position: attendee.position ?? '',
        institution: attendee.institution ?? '',
        email: attendee.email ?? '',
        phone: attendee.phone ?? '',
        is_officer: attendee.is_officer,
        is_signatory: attendee.is_signatory,
      })),
    }
    initializedBa = true
  } else if (!client.berita_acara && !baDraft.value) {
    baDraft.value = {
      nomor_berita_acara: '',
      kbli: '',
      tanggal_pelaksanaan:
        client.schedules[0]?.date ?? new Date().toISOString().slice(0, 10),
      lokasi_permohonan: '',
      hasil_pendampingan: '',
      status: 'draft',
      attendance_is_open: false,
      attendees: [],
    }
    initializedBa = true
  }
  if (initializedBa) baBaseline.value = JSON.stringify(baDraft.value)
}

watch(ticket, () => {
  statusDraft.value = undefined
  scheduleDrafts.value = {}
  reportDrafts.value = {}
  baDraft.value = undefined
  baBaseline.value = ''
  baMapFiles.value = []
  baDocumentationFiles.value = []
  baOtherFiles.value = []
  reportUploadProgress.value = null
  baUploadProgress.value = null
  reportError.value = ''
  baError.value = ''
  error.value = ''
})

watch(
  () => clientQuery.data.value,
  (client) => {
    if (client) initializeDrafts(client)
  },
  { immediate: true },
)

function date(value?: string | null) {
  if (!value) return '-'
  return new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium' }).format(new Date(value))
}

function plainText(value: string) {
  return new DOMParser().parseFromString(value, 'text/html').body.textContent ?? ''
}
</script>

<template>
  <IonPage>
    <AppHeader
      :title="ticket ?? 'Detail layanan'"
      eyebrow="Detail layanan"
      default-href="/tabs/requests"
    />
    <IonContent>
      <IonRefresher slot="fixed" @ion-refresh="refresh"><IonRefresherContent /></IonRefresher>

      <PageContainer v-if="clientQuery.isLoading.value" compact>
        <LoadingSkeleton :rows="3" label="Memuat detail permohonan" />
      </PageContainer>

      <PageContainer v-else-if="clientQuery.error.value" compact>
        <ErrorState
          :message="apiError(clientQuery.error.value)"
          @retry="clientQuery.refetch()"
        />
      </PageContainer>

      <PageContainer
        v-else-if="clientQuery.data.value"
        class="detail-shell"
        compact
        reserve-actions
      >
        <section class="request-hero">
          <div class="hero-line">
            <span class="request-hero__ticket">{{ ticket }}</span>
            <StatusBadge :status="clientQuery.data.value.status" />
          </div>
          <span class="eyebrow">{{ clientQuery.data.value.service?.name || 'Layanan KKPRL' }}</span>
          <h1>{{ clientQuery.data.value.name }}</h1>
          <p>{{ clientQuery.data.value.instance || 'Pemohon perorangan' }}</p>
          <div class="hero-meta">
            <span>{{ clientQuery.data.value.email }}</span>
            <span>{{ clientQuery.data.value.whatsapp }}</span>
          </div>
        </section>

        <div v-if="error" class="error-box" role="alert">{{ error }}</div>

        <section v-if="auth.can('clients', 'update')" class="surface quick-action">
          <div>
            <strong>Status permohonan</strong>
            <small>Perubahan mengikuti alur layanan yang sama dengan panel web.</small>
          </div>
          <IonSelect v-model="statusDraft" interface="popover">
            <IonSelectOption value="waiting">Menunggu</IonSelectOption>
            <IonSelectOption value="scheduled">Dijadwalkan</IonSelectOption>
            <IonSelectOption value="completed">Selesai</IonSelectOption>
          </IonSelect>
          <IonButton size="small" :disabled="saving" @click="saveClientStatus">Simpan</IonButton>
        </section>

        <IonAccordionGroup :multiple="true" :value="['identity']">
          <IonAccordion value="identity">
            <IonItem slot="header">
              <IonLabel>
                <strong>Identitas dan layanan</strong>
                <p>{{ clientQuery.data.value.booking_type === 'company' ? 'Perusahaan' : 'Perorangan' }}</p>
              </IonLabel>
            </IonItem>
            <div slot="content" class="accordion-content">
              <form
                v-if="auth.can('clients', 'update')"
                class="inline-form"
                @submit.prevent="saveClientIdentity"
              >
                <IonInput v-model="clientDraft.name" label="Nama pemohon" label-placement="stacked" required />
                <IonInput v-model="clientDraft.email" type="email" label="Email" label-placement="stacked" required />
                <IonInput v-model="clientDraft.whatsapp" type="tel" label="WhatsApp" label-placement="stacked" required />
                <IonSelect v-model="clientDraft.booking_type" label="Jenis pemohon" label-placement="stacked">
                  <IonSelectOption value="personal">Perorangan</IonSelectOption>
                  <IonSelectOption value="company">Perusahaan</IonSelectOption>
                </IonSelect>
                <IonInput v-model="clientDraft.instance" label="Instansi / perusahaan" label-placement="stacked" />
                <IonTextarea v-model="clientDraft.address" label="Alamat" label-placement="stacked" :auto-grow="true" />
                <IonSelect v-model="clientDraft.activity_type" label="Jenis kegiatan" label-placement="stacked">
                  <IonSelectOption :value="null">Belum ditentukan</IonSelectOption>
                  <IonSelectOption value="business">Berusaha</IonSelectOption>
                  <IonSelectOption value="non_business">Nonberusaha</IonSelectOption>
                </IonSelect>
                <IonButton type="submit" size="small" :disabled="saving">Simpan identitas</IonButton>
              </form>
              <div v-else class="sub-card">
                <span>{{ clientQuery.data.value.email }}</span>
                <span>{{ clientQuery.data.value.whatsapp }}</span>
                <small>{{ clientQuery.data.value.address || 'Alamat belum diisi' }}</small>
              </div>
              <section class="sub-card">
                <strong>Dokumen layanan</strong>
                <div class="button-row">
                  <IonButton
                    size="small"
                    fill="outline"
                    @click="openProtected(clientApiPath('/ticket'), `Tiket-${ticket}.pdf`)"
                  >
                    Tiket
                  </IonButton>
                  <IonButton
                    size="small"
                    fill="outline"
                    @click="openProtected(clientApiPath('/report-pdf'), `Laporan-${ticket}.pdf`)"
                  >
                    PDF laporan
                  </IonButton>
                </div>
                <IonButton
                  v-for="document in clientQuery.data.value.supporting_documents"
                  :key="document.path"
                  size="small"
                  fill="clear"
                  @click="openProtected(privateFilePath(document.path), document.name)"
                >
                  {{ document.name }}
                </IonButton>
                <IonButton
                  v-if="clientQuery.data.value.coordinate_file"
                  size="small"
                  fill="clear"
                  @click="
                    openProtected(
                      privateFilePath(clientQuery.data.value.coordinate_file.path),
                      clientQuery.data.value.coordinate_file.name,
                    )
                  "
                >
                  {{ clientQuery.data.value.coordinate_file.name }}
                </IonButton>
              </section>
            </div>
          </IonAccordion>

          <IonAccordion value="schedule">
            <IonItem slot="header">
              <IonLabel>
                <strong>Jadwal konsultasi</strong>
                <p>{{ clientQuery.data.value.schedules.length }} sesi</p>
              </IonLabel>
            </IonItem>
            <div slot="content" class="accordion-content">
              <div
                v-for="schedule in clientQuery.data.value.schedules"
                :key="schedule.id"
                class="sub-card"
              >
                <template v-if="auth.can('schedules', 'update') && scheduleDrafts[schedule.id]">
                  <IonInput
                    v-model="scheduleDrafts[schedule.id].date"
                    type="date"
                    label="Tanggal"
                    label-placement="stacked"
                  />
                  <div class="two-columns">
                    <IonInput
                      v-model="scheduleDrafts[schedule.id].start_time"
                      type="time"
                      label="Mulai"
                      label-placement="stacked"
                    />
                    <IonInput
                      v-model="scheduleDrafts[schedule.id].end_time"
                      type="time"
                      label="Selesai"
                      label-placement="stacked"
                    />
                  </div>
                  <IonCheckbox v-model="scheduleDrafts[schedule.id].is_online">Pertemuan online</IonCheckbox>
                  <IonInput
                    v-if="scheduleDrafts[schedule.id].is_online"
                    v-model="scheduleDrafts[schedule.id].meeting_link"
                    type="url"
                    label="Tautan pertemuan"
                    label-placement="stacked"
                  />
                  <IonButton size="small" fill="outline" :disabled="saving" @click="saveSchedule(schedule)">
                    Simpan jadwal
                  </IonButton>
                </template>
                <template v-else>
                  <strong>{{ date(schedule.date) }}</strong>
                  <span>{{ schedule.start_time }}–{{ schedule.end_time }}</span>
                  <small>{{ schedule.is_online ? 'Online' : 'Tatap muka' }}</small>
                </template>
              </div>
              <EmptyState v-if="!clientQuery.data.value.schedules.length" title="Belum ada jadwal" />

              <form
                v-if="auth.can('schedules', 'create')"
                class="inline-form"
                @submit.prevent="addSchedule"
              >
                <h3>Tambah jadwal</h3>
                <IonInput v-model="scheduleForm.date" type="date" label="Tanggal" label-placement="stacked" required />
                <div class="two-columns">
                  <IonInput v-model="scheduleForm.start_time" type="time" label="Mulai" label-placement="stacked" required />
                  <IonInput v-model="scheduleForm.end_time" type="time" label="Selesai" label-placement="stacked" required />
                </div>
                <IonCheckbox v-model="scheduleForm.is_online">Pertemuan online</IonCheckbox>
                <IonInput
                  v-if="scheduleForm.is_online"
                  v-model="scheduleForm.meeting_link"
                  type="url"
                  label="Tautan pertemuan"
                  label-placement="stacked"
                  required
                />
                <IonButton type="submit" size="small" :disabled="saving">Tambah jadwal</IonButton>
              </form>
            </div>
          </IonAccordion>

          <IonAccordion value="assignment">
            <IonItem slot="header">
              <IonLabel>
                <strong>Petugas yang ditugaskan</strong>
                <p>{{ clientQuery.data.value.assignments.length }} penugasan</p>
              </IonLabel>
            </IonItem>
            <div slot="content" class="accordion-content">
              <div
                v-for="assignment in clientQuery.data.value.assignments"
                :key="assignment.id"
                class="assignment-row sub-card"
              >
                <div>
                  <strong>{{ assignment.officer?.name }}</strong>
                  <small>{{ assignment.officer?.jabatan || 'Petugas' }}</small>
                </div>
                <EmployeeSearchSelector
                  v-if="auth.can('assignments', 'update')"
                  :model-value="assignment.officer ? [assignment.officer.id] : []"
                  :selected-employees="assignment.officer ? [assignment.officer] : []"
                  label="Petugas penugasan"
                  helper="Cari pegawai pengganti berdasarkan nama, NIP, jabatan, atau instansi."
                  :multiple="false"
                  :required="true"
                  :disabled="saving"
                  @update:model-value="reassignFromSelection(assignment, $event)"
                />
                <IonSelect
                  :value="assignment.status"
                  interface="popover"
                  :disabled="!auth.can('assignments', 'update') || saving"
                  @ion-change="updateAssignmentStatus(assignment, $event.detail.value)"
                >
                  <IonSelectOption value="scheduled">Terjadwal</IonSelectOption>
                  <IonSelectOption value="hadir">Hadir</IonSelectOption>
                  <IonSelectOption value="izin_mendadak">Izin mendadak</IonSelectOption>
                </IonSelect>
              </div>
              <EmptyState v-if="!clientQuery.data.value.assignments.length" title="Belum ada petugas" />

              <form
                v-if="auth.can('assignments', 'create')"
                class="inline-form"
                @submit.prevent="addAssignments"
              >
                <h3>Buat penugasan</h3>
                <p class="form-intro">
                  Pilih jadwal dan pegawai, lalu tinjau ringkasan sebelum penugasan dikirim.
                </p>
                <FormSection
                  title="Jadwal penugasan"
                  description="Pilih satu atau beberapa jadwal yang relevan."
                  :step="1"
                >
                  <IonSelect
                    v-model="assignmentForm.schedule_ids"
                    label="Jadwal"
                    label-placement="stacked"
                    :multiple="true"
                    required
                  >
                    <IonSelectOption
                      v-for="schedule in clientQuery.data.value.schedules"
                      :key="schedule.id"
                      :value="schedule.id"
                    >
                      {{ date(schedule.date) }} · {{ schedule.start_time }}
                    </IonSelectOption>
                  </IonSelect>
                </FormSection>

                <FormSection
                  title="Pegawai yang ditugaskan"
                  description="Gunakan pencarian agar daftar panjang tetap mudah digunakan."
                  :step="2"
                  :error="assignmentError"
                >
                  <EmployeeSearchSelector
                    v-model="assignmentForm.user_ids"
                    :required="true"
                    :disabled="saving"
                  />
                </FormSection>

                <FormSection
                  title="Ringkasan penugasan"
                  description="Nilai yang dikirim tetap memakai struktur penugasan yang sudah ada."
                  :step="3"
                >
                  <div class="assignment-summary">
                    <span>
                      <strong>{{ assignmentForm.schedule_ids.length }}</strong>
                      jadwal
                    </span>
                    <span>
                      <strong>{{ assignmentForm.user_ids.length }}</strong>
                      pegawai
                    </span>
                    <span>
                      <strong>Terjadwal</strong>
                      status awal
                    </span>
                  </div>
                </FormSection>

                <IonButton
                  type="submit"
                  expand="block"
                  :disabled="
                    saving ||
                    !assignmentForm.schedule_ids.length ||
                    !assignmentForm.user_ids.length
                  "
                >
                  {{ saving ? 'Menyimpan penugasan…' : 'Tinjau dan tetapkan pegawai' }}
                </IonButton>
              </form>
            </div>
          </IonAccordion>

          <IonAccordion value="report">
            <IonItem slot="header">
              <IonLabel>
                <strong>Laporan konsultasi</strong>
                <p>{{ clientQuery.data.value.consultation_reports.length }} laporan</p>
              </IonLabel>
            </IonItem>
            <div slot="content" class="accordion-content">
              <article
                v-for="report in clientQuery.data.value.consultation_reports"
                :key="report.id"
                class="sub-card report-card"
              >
                <div><StatusBadge :status="report.status" /><small>{{ date(report.created_at) }}</small></div>
                <template v-if="auth.can('consultation_reports', 'update') && reportDrafts[report.id]">
                  <RichTextEditor v-model="reportDrafts[report.id].content" />
                  <IonSelect v-model="reportDrafts[report.id].status" label="Status" label-placement="stacked">
                    <IonSelectOption value="draft">Draft</IonSelectOption>
                    <IonSelectOption value="completed">Selesai</IonSelectOption>
                  </IonSelect>
                  <div v-if="report.has_signature" class="signature-saved">
                    Tanda tangan tersimpan
                    <small>
                      {{ report.signed_by?.name || 'Petugas KKPRL' }}
                      <template v-if="report.signed_at"> · {{ date(report.signed_at) }}</template>
                    </small>
                  </div>
                  <SignaturePad @change="reportSignatures[report.id] = $event" />
                  <IonButton size="small" fill="outline" :disabled="saving" @click="saveReport(report)">
                    Simpan perubahan
                  </IonButton>
                </template>
                <p v-else>{{ plainText(report.content) }}</p>
              </article>

              <form
                v-if="auth.can('consultation_reports', 'create')"
                class="inline-form"
                @submit.prevent="addReport"
              >
                <h3>Buat laporan</h3>
                <p class="form-intro">
                  Isi laporan, tambahkan dokumentasi, lalu periksa ringkasan sebelum disimpan.
                </p>

                <FormSection
                  title="Uraian dan hasil"
                  description="Toolbar dibatasi pada format HTML yang diterima backend."
                  :step="1"
                  :error="reportError"
                >
                  <RichTextEditor
                    v-model="reportForm.content"
                    label="Isi laporan"
                    :required="true"
                    :error="reportError && !plainText(reportForm.content).trim() ? reportError : ''"
                  />
                </FormSection>

                <FormSection
                  title="Dokumentasi"
                  description="Unggah 1–3 foto, masing-masing maksimal 10 MB."
                  :step="2"
                >
                  <AttachmentUploader
                    v-model="reportFiles"
                    label="Foto dokumentasi"
                    helper="Pilih dari galeri atau ambil foto menggunakan kamera Android."
                    accept="image/*"
                    :max-files="3"
                    :max-size-mb="10"
                    :multiple="true"
                    :required="true"
                    :allow-camera="true"
                    :disabled="saving"
                    :progress="reportUploadProgress"
                  />
                </FormSection>

                <FormSection
                  title="Tanda tangan petugas"
                  description="Tanda tangan disimpan terenkripsi dan ditampilkan pada PDF laporan."
                  :step="3"
                >
                  <SignaturePad @change="reportForm.signature = $event" />
                </FormSection>

                <FormSection
                  title="Status dan pratinjau"
                  description="Laporan selesai memerlukan tanda tangan petugas."
                  :step="4"
                >
                  <IonSelect v-model="reportForm.status" label="Status" label-placement="stacked">
                    <IonSelectOption value="draft">Draft</IonSelectOption>
                    <IonSelectOption value="completed">Selesai</IonSelectOption>
                  </IonSelect>
                  <article class="form-preview">
                    <span class="eyebrow">Pratinjau ringkas</span>
                    <p>
                      {{ plainText(reportForm.content).trim() || 'Isi laporan belum ditulis.' }}
                    </p>
                    <small>
                      {{ reportFiles.length }} foto dipilih ·
                      {{ reportForm.signature ? 'sudah ditandatangani' : 'belum ditandatangani' }} ·
                      status {{ reportForm.status }}
                    </small>
                  </article>
                </FormSection>

                <StickyFormActions
                  :primary-label="reportForm.status === 'completed' ? 'Kirim laporan' : 'Simpan laporan'"
                  secondary-label="Simpan draft"
                  :loading="saving"
                  :disabled="
                    !plainText(reportForm.content).trim() ||
                    !reportFiles.length ||
                    (reportForm.status === 'completed' && !reportForm.signature)
                  "
                  :dirty="reportFormDirty"
                  @primary="addReport"
                  @secondary="saveReportAsDraft"
                />
              </form>
            </div>
          </IonAccordion>

          <IonAccordion value="ba">
            <IonItem slot="header">
              <IonLabel>
                <strong>Berita Acara</strong>
                <p>{{ clientQuery.data.value.berita_acara?.status ?? 'Belum tersedia' }}</p>
              </IonLabel>
            </IonItem>
            <div slot="content" class="accordion-content">
              <template v-if="baDraft">
                <div v-if="clientQuery.data.value.berita_acara" class="sub-card">
                  <strong>{{ clientQuery.data.value.berita_acara.nomor_berita_acara || 'Nomor belum diisi' }}</strong>
                  <span>{{ date(clientQuery.data.value.berita_acara.tanggal_pelaksanaan) }}</span>
                  <small>
                    {{ clientQuery.data.value.berita_acara.attendees.length }} peserta · batas tanda tangan
                    {{ date(clientQuery.data.value.berita_acara.signing_deadline) }}
                  </small>
                  <IonButton
                    size="small"
                    fill="outline"
                    @click="
                      openProtected(
                        clientApiPath('/berita-acara-pdf'),
                        `Berita-Acara-${ticket}.pdf`,
                      )
                    "
                  >
                    Buka PDF
                  </IonButton>
                </div>
                <form
                  v-if="auth.can('berita_acara', clientQuery.data.value.berita_acara ? 'update' : 'create')"
                  class="inline-form"
                  @submit.prevent="saveBeritaAcara"
                >
                  <h3>{{ clientQuery.data.value.berita_acara ? 'Edit Berita Acara' : 'Buat Berita Acara' }}</h3>
                  <p class="form-intro">
                    Lengkapi section secara berurutan. Anda tetap dapat menyimpan sebagai draft.
                  </p>
                  <div v-if="baError" class="error-box" role="alert">{{ baError }}</div>

                  <FormSection
                    title="Informasi dasar"
                    description="Nomor, KBLI, waktu, dan lokasi pelaksanaan."
                    :step="1"
                  >
                      <div class="two-columns">
                        <IonInput v-model="baDraft.nomor_berita_acara" label="Nomor" label-placement="stacked" />
                        <IonInput v-model="baDraft.kbli" label="KBLI" label-placement="stacked" />
                      </div>
                      <IonInput
                        v-model="baDraft.tanggal_pelaksanaan"
                        type="date"
                        label="Tanggal pelaksanaan"
                        label-placement="stacked"
                        required
                      />
                      <IonTextarea
                        v-model="baDraft.lokasi_permohonan"
                        label="Lokasi permohonan"
                        label-placement="stacked"
                        :auto-grow="true"
                      />
                  </FormSection>

                  <FormSection
                    title="Hasil pendampingan"
                    description="Gunakan format sederhana yang tetap kompatibel dengan backend dan PDF."
                    :step="2"
                  >
                    <RichTextEditor
                      v-model="baDraft.hasil_pendampingan"
                      label="Uraian hasil pendampingan"
                    />
                  </FormSection>

                  <FormSection
                    title="Status, presensi, dan pemohon"
                    description="Atur status dokumen dan tanda tangan pemohon."
                    :step="3"
                  >
                    <IonSelect v-model="baDraft.status" label="Status" label-placement="stacked">
                      <IonSelectOption value="draft">Draft</IonSelectOption>
                      <IonSelectOption value="completed">Selesai</IonSelectOption>
                    </IonSelect>
                    <IonCheckbox v-model="baDraft.attendance_is_open">Presensi peserta dibuka</IonCheckbox>
                    <section class="signature-section">
                      <strong>Tanda tangan pemohon</strong>
                      <small>Gambar diproses oleh layanan tanda tangan yang sama dengan panel web.</small>
                      <SignaturePad @change="baDraft.applicant_signature = $event" />
                    </section>
                  </FormSection>

                  <FormSection
                    title="Peserta dan penanda tangan"
                    :description="`${baDraft.attendees.length} peserta terdaftar.`"
                    :step="4"
                  >
                    <template #action>
                      <IonButton size="small" fill="outline" type="button" @click="addAttendee">
                        Tambah
                      </IonButton>
                    </template>
                    <section class="attendee-editor">
                      <EmptyState
                        v-if="!baDraft.attendees.length"
                        title="Belum ada peserta"
                        body="Tambahkan peserta bila Berita Acara memerlukan daftar hadir atau penanda tangan."
                      />
                      <article
                        v-for="(attendee, attendeeIndex) in baDraft.attendees"
                        :key="attendee.id ?? `new-${attendeeIndex}`"
                        class="sub-card attendee-card"
                      >
                        <IonInput v-model="attendee.name" label="Nama" label-placement="stacked" required />
                        <div class="two-columns">
                          <IonInput v-model="attendee.position" label="Jabatan" label-placement="stacked" />
                          <IonInput v-model="attendee.institution" label="Instansi" label-placement="stacked" />
                        </div>
                        <div class="two-columns">
                          <IonInput v-model="attendee.email" type="email" label="Email" label-placement="stacked" />
                          <IonInput v-model="attendee.phone" type="tel" label="Telepon" label-placement="stacked" />
                        </div>
                        <IonCheckbox v-model="attendee.is_officer">Petugas</IonCheckbox>
                        <IonCheckbox v-model="attendee.is_signatory">Penanda tangan</IonCheckbox>
                        <SignaturePad
                          v-if="attendee.is_signatory"
                          @change="attendee.signature = $event"
                        />
                        <IonButton
                          v-if="!attendee.id"
                          type="button"
                          size="small"
                          fill="clear"
                          color="danger"
                          @click="removeNewAttendee(attendeeIndex)"
                        >
                          Hapus peserta baru
                        </IonButton>
                      </article>
                    </section>
                  </FormSection>

                  <FormSection
                    title="Lampiran"
                    description="File mengikuti batas tipe dan ukuran backend yang sudah berlaku."
                    :step="5"
                  >
                    <div class="file-grid">
                      <AttachmentUploader
                        v-model="baMapFiles"
                        label="Lampiran peta"
                        helper="PDF, JPG, JPEG, atau PNG · maksimal 10 MB."
                        accept=".pdf,image/jpeg,image/png"
                        :max-files="1"
                        :max-size-mb="10"
                        :disabled="saving"
                        :progress="baUploadProgress"
                      />
                      <AttachmentUploader
                        v-model="baDocumentationFiles"
                        label="Dokumentasi"
                        helper="Maksimal 6 foto, masing-masing 10 MB."
                        accept="image/*"
                        :max-files="6"
                        :max-size-mb="10"
                        :multiple="true"
                        :allow-camera="true"
                        :disabled="saving"
                      />
                      <AttachmentUploader
                        v-model="baOtherFiles"
                        label="Lampiran lain"
                        helper="Maksimal 5 file, masing-masing 10 MB."
                        :max-files="5"
                        :max-size-mb="10"
                        :multiple="true"
                        :disabled="saving"
                      />
                    </div>
                  </FormSection>

                  <FormSection
                    title="Pratinjau ringkas"
                    description="Periksa isi sebelum menyimpan atau menyelesaikan dokumen."
                    :step="6"
                  >
                    <article class="form-preview">
                      <strong>{{ baDraft.nomor_berita_acara || 'Nomor belum diisi' }}</strong>
                      <span>{{ date(baDraft.tanggal_pelaksanaan) }}</span>
                      <p>{{ plainText(baDraft.hasil_pendampingan).trim() || 'Hasil pendampingan belum diisi.' }}</p>
                      <small>
                        {{ baDraft.attendees.length }} peserta ·
                        {{ baDocumentationFiles.length + baOtherFiles.length + baMapFiles.length }} lampiran baru ·
                        status {{ baDraft.status }}
                      </small>
                    </article>
                  </FormSection>

                  <StickyFormActions
                    :primary-label="baDraft.status === 'completed' ? 'Selesaikan Berita Acara' : 'Simpan Berita Acara'"
                    secondary-label="Simpan draft"
                    :loading="saving"
                    :disabled="!baDraft.tanggal_pelaksanaan"
                    :dirty="baFormDirty"
                    @primary="saveBeritaAcara"
                    @secondary="saveBeritaAcaraAsDraft"
                  />
                </form>
              </template>
              <EmptyState v-else title="Berita Acara belum tersedia" />
            </div>
          </IonAccordion>

          <IonAccordion value="feedback">
            <IonItem slot="header">
              <IonLabel>
                <strong>Masukan pemohon</strong>
                <p>{{ clientQuery.data.value.satisfaction_survey ? 'Sudah diisi' : 'Belum diisi' }}</p>
              </IonLabel>
            </IonItem>
            <div slot="content" class="accordion-content">
              <div v-if="clientQuery.data.value.satisfaction_survey" class="sub-card feedback-card">
                <strong>Kritik / umpan balik</strong>
                <p>{{ clientQuery.data.value.satisfaction_survey.criticism }}</p>
                <strong>Saran</strong>
                <p>{{ clientQuery.data.value.satisfaction_survey.suggestion }}</p>
              </div>
              <EmptyState v-else title="Belum ada masukan" />
            </div>
          </IonAccordion>
        </IonAccordionGroup>
      </PageContainer>
    </IonContent>
  </IonPage>
</template>

<style scoped>
.detail-shell {
  padding-top: var(--app-space-4);
}

.request-hero {
  background:
    radial-gradient(circle at 92% 5%, rgba(92, 232, 217, 0.28), transparent 34%),
    radial-gradient(circle at 8% 100%, rgba(235, 174, 65, 0.16), transparent 38%),
    linear-gradient(145deg, #082b45, #0e6078);
  border-radius: var(--app-radius-xl);
  box-shadow: 0 18px 38px rgba(8, 43, 69, 0.24);
  color: #fff;
  overflow: hidden;
  padding: var(--app-space-5);
  position: relative;
}

.request-hero .eyebrow {
  color: #7de4da;
  display: inline-block;
  margin-top: var(--app-space-4);
}

.request-hero__ticket {
  background: rgba(255, 255, 255, 0.12);
  border: 1px solid rgba(255, 255, 255, 0.18);
  border-radius: var(--app-radius-pill);
  color: white;
  font-size: 0.68rem;
  font-weight: 800;
  letter-spacing: 0.04em;
  padding: 0.42rem 0.72rem;
}

.hero-line,
.hero-meta {
  align-items: center;
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  justify-content: space-between;
}

.request-hero h1 {
  font-size: 1.65rem;
  letter-spacing: -0.04em;
  margin: var(--app-space-2) 0 var(--app-space-1);
}

.request-hero p {
  color: #d8e9ef;
  margin: 0 0 var(--app-space-4);
}

.hero-meta {
  border-top: 1px solid rgba(255, 255, 255, 0.15);
  color: #d8e9ef;
  font-size: var(--app-font-size-xs);
  justify-content: flex-start;
  padding-top: var(--app-space-3);
}

.quick-action {
  align-items: end;
  display: grid;
  gap: var(--app-space-3);
  grid-template-columns: 1fr;
  margin: var(--app-space-4) 0;
  padding: var(--app-space-4);
}

.quick-action > div {
  display: grid;
  gap: 3px;
}

.quick-action strong,
.inline-form h3 {
  color: var(--app-ink);
}

.quick-action small {
  color: var(--app-muted);
  line-height: 1.45;
}

ion-accordion-group {
  display: grid;
  gap: var(--app-space-3);
}

ion-accordion {
  background: var(--app-color-surface);
  border: 1px solid var(--app-color-border);
  border-radius: var(--app-radius-xl);
  box-shadow: var(--app-shadow-sm);
  overflow: hidden;
}

.detail-shell :deep(ion-accordion > ion-item[slot='header']) {
  --background: var(--app-color-surface);
  --min-height: 4.75rem;
  --padding-start: var(--app-space-4);
  --inner-padding-end: var(--app-space-4);
}

.accordion-content {
  background: var(--app-color-surface-muted);
  display: grid;
  gap: var(--app-space-3);
  padding: var(--app-space-4);
}

.sub-card {
  background: var(--app-color-surface);
  border: 1px solid var(--app-color-border);
  border-radius: var(--app-radius-md);
  display: grid;
  gap: 4px;
  padding: var(--app-space-3);
}

.sub-card strong {
  color: var(--app-ink);
  font-size: 0.88rem;
}

.sub-card span,
.sub-card small,
.sub-card p {
  color: var(--app-muted);
  font-size: 0.78rem;
}

.assignment-row {
  align-items: end;
  grid-template-columns: 1fr;
}

.assignment-row > div {
  display: grid;
}

.inline-form {
  background: var(--app-color-surface);
  border: 1px solid var(--app-color-border-strong);
  border-radius: var(--app-radius-lg);
  display: grid;
  gap: var(--app-space-3);
  margin-top: 5px;
  padding: var(--app-space-4);
}

.inline-form h3 {
  font-size: 0.9rem;
  margin: 0;
}

.form-intro {
  color: var(--app-color-text-secondary);
  font-size: var(--app-font-size-xs);
  line-height: var(--app-line-height-body);
  margin: calc(-1 * var(--app-space-1)) 0 var(--app-space-1);
}

.assignment-summary {
  display: grid;
  gap: var(--app-space-2);
  grid-template-columns: repeat(3, minmax(0, 1fr));
}

.assignment-summary span {
  background: var(--app-color-surface-muted);
  border: 1px solid var(--app-color-border);
  border-radius: var(--app-radius-md);
  color: var(--app-color-text-secondary);
  display: grid;
  font-size: var(--app-font-size-xs);
  gap: var(--app-space-1);
  padding: var(--app-space-3);
  text-align: center;
}

.assignment-summary strong {
  color: var(--app-color-text);
  font-size: var(--app-font-size-md);
}

.form-preview {
  background: var(--app-color-surface-muted);
  border: 1px solid var(--app-color-border);
  border-radius: var(--app-radius-md);
  display: grid;
  gap: var(--app-space-2);
  padding: var(--app-space-3);
}

.form-preview strong {
  color: var(--app-color-text);
}

.form-preview p {
  color: var(--app-color-text-secondary);
  display: -webkit-box;
  font-size: var(--app-font-size-sm);
  line-height: var(--app-line-height-body);
  margin: 0;
  overflow: hidden;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 5;
}

.form-preview span,
.form-preview small {
  color: var(--app-color-text-secondary);
  font-size: var(--app-font-size-xs);
}

.two-columns {
  display: grid;
  gap: 10px;
  grid-template-columns: 1fr 1fr;
}

.file-field {
  color: var(--app-muted);
  display: grid;
  font-size: 0.78rem;
  gap: 6px;
}

.file-grid,
.attendee-editor,
.signature-section {
  display: grid;
  gap: 12px;
}

.button-row {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.button-row ion-button {
  --border-radius: var(--app-radius-pill);
  min-height: var(--app-touch-target);
}

.signature-section {
  border-top: 1px solid var(--app-line);
  padding-top: 14px;
}

.signature-saved {
  background: var(--app-color-success-soft);
  border: 1px solid color-mix(in srgb, var(--app-color-success) 24%, white);
  border-radius: var(--app-radius-md);
  color: var(--app-color-success);
  display: grid;
  font-size: var(--app-font-size-sm);
  font-weight: 800;
  gap: var(--app-space-1);
  padding: var(--app-space-3);
}

.signature-saved small {
  color: var(--app-color-text-secondary);
  font-weight: 600;
}

.signature-section small,
.section-heading small {
  color: var(--app-muted);
  font-size: 0.75rem;
}

.section-heading {
  align-items: center;
  display: flex;
  gap: 12px;
  justify-content: space-between;
}

.section-heading > div {
  display: grid;
  gap: 3px;
}

.attendee-card {
  gap: 10px;
}

.report-card > div {
  align-items: center;
  display: flex;
  justify-content: space-between;
}

.report-card p,
.feedback-card p {
  line-height: 1.55;
  white-space: pre-wrap;
}

ion-button {
  --border-radius: 12px;
}

@media (max-width: 440px) {
  .two-columns,
  .assignment-summary {
    grid-template-columns: 1fr;
  }
}

@media (min-width: 700px) {
  .quick-action {
    grid-template-columns: 1fr 190px auto;
  }

  .assignment-row {
    grid-template-columns: minmax(180px, 1fr) minmax(180px, 1fr) auto;
  }

  .file-grid {
    grid-template-columns: repeat(3, 1fr);
  }
}
</style>
