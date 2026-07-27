<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useQuery, useQueryClient } from '@tanstack/vue-query'
import {
  IonAccordion,
  IonAccordionGroup,
  IonBackButton,
  IonButton,
  IonButtons,
  IonCheckbox,
  IonContent,
  IonHeader,
  IonInput,
  IonItem,
  IonLabel,
  IonPage,
  IonRefresher,
  IonRefresherContent,
  IonSelect,
  IonSelectOption,
  IonSpinner,
  IonTextarea,
  IonToolbar,
  toastController,
} from '@ionic/vue'
import { api, apiError } from '@/api/client'
import StatusBadge from '@/components/StatusBadge.vue'
import EmptyState from '@/components/EmptyState.vue'
import SignaturePad from '@/components/SignaturePad.vue'
import RichTextEditor from '@/components/RichTextEditor.vue'
import { useAuthStore } from '@/stores/auth'
import type {
  ApiEnvelope,
  Assignment,
  BeritaAcara,
  ClientDetail,
  ConsultationReport,
  Schedule,
} from '@/types/api'

interface StaffMember {
  id: number
  name: string
  nip?: string | null
  jabatan?: string | null
}

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
const queryClient = useQueryClient()
const ticket = computed(() => String(route.params.ticket))
const saving = ref(false)
const error = ref('')
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

const clientQuery = useQuery({
  queryKey: ['client', ticket],
  queryFn: async () =>
    (await api.get<ApiEnvelope<ClientDetail>>(`/clients/${ticket.value}`)).data.data,
})

const staffQuery = useQuery({
  queryKey: ['staff'],
  queryFn: async () => (await api.get<ApiEnvelope<StaffMember[]>>('/staff')).data.data,
  enabled: auth.can('assignments', 'create') || auth.can('assignments', 'update'),
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
})
const reportFiles = ref<File[]>([])
const baDraft = ref<BeritaAcaraDraft>()
const baMapFile = ref<File | null>(null)
const baDocumentationFiles = ref<File[]>([])
const baOtherFiles = ref<File[]>([])

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
    await api.patch(`/clients/${ticket.value}`, {
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
    await api.patch(`/clients/${ticket.value}`, {
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
    await api.post(`/clients/${ticket.value}/schedules`, {
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
    await api.patch(`/clients/${ticket.value}/schedules/${schedule.id}`, {
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
  await perform(async () => {
    const response = await api.post<
      ApiEnvelope<Assignment[]> & { meta?: { warnings?: Array<{ message: string }> } }
    >(`/clients/${ticket.value}/assignments`, assignmentForm.value)
    assignmentForm.value = { schedule_ids: [], user_ids: [], status: 'scheduled' }
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

async function updateAssignmentStatus(assignment: Assignment, status: Assignment['status']) {
  await perform(async () => {
    await api.patch(`/clients/${ticket.value}/assignments/${assignment.id}`, {
      version: assignment.version,
      status,
    })
    await refreshedMessage('Status penugasan diperbarui.')
  })
}

async function reassignOfficer(assignment: Assignment, userId: number) {
  if (assignment.officer?.id === userId) return
  await perform(async () => {
    await api.patch(`/clients/${ticket.value}/assignments/${assignment.id}`, {
      version: assignment.version,
      user_id: userId,
    })
    await refreshedMessage('Petugas penugasan diperbarui.')
  })
}

function selectReportFiles(event: Event) {
  reportFiles.value = Array.from((event.target as HTMLInputElement).files ?? []).slice(0, 3)
}

async function addReport() {
  await perform(async () => {
    const form = new FormData()
    form.append('content', reportForm.value.content)
    form.append('status', reportForm.value.status)
    reportFiles.value.forEach((file) => form.append('documentation[]', file))
    await api.post(`/clients/${ticket.value}/consultation-reports`, form)
    reportForm.value = { content: '', status: 'draft' }
    reportFiles.value = []
    await refreshedMessage('Laporan konsultasi disimpan.')
  })
}

async function saveReport(report: ConsultationReport) {
  const draft = reportDrafts.value[report.id]
  if (!draft) return
  await perform(async () => {
    const form = new FormData()
    form.append('version', report.version)
    form.append('content', draft.content)
    form.append('status', draft.status)
    await api.patch(`/clients/${ticket.value}/consultation-reports/${report.id}`, form)
    await refreshedMessage('Laporan konsultasi diperbarui.')
  })
}

async function saveBeritaAcara() {
  const client = clientQuery.data.value
  const ba = client?.berita_acara
  const draft = baDraft.value
  if (!client || !draft) return

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
    if (baMapFile.value) form.append('map_attachment', baMapFile.value)
    baDocumentationFiles.value.forEach((file) => form.append('documentation_attachments[]', file))
    baOtherFiles.value.forEach((file) => form.append('other_attachments[]', file))

    if (ba) {
      await api.patch(`/clients/${ticket.value}/berita-acara/${ba.id}`, form)
    } else {
      await api.post(`/clients/${ticket.value}/berita-acara`, form)
    }
    baDraft.value = undefined
    await refreshedMessage('Berita Acara diperbarui.')
  })
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

function selectBaFiles(kind: 'map' | 'documentation' | 'other', event: Event) {
  const files = Array.from((event.target as HTMLInputElement).files ?? [])
  if (kind === 'map') baMapFile.value = files[0] ?? null
  if (kind === 'documentation') baDocumentationFiles.value = files.slice(0, 6)
  if (kind === 'other') baOtherFiles.value = files.slice(0, 5)
}

async function openProtected(path: string) {
  await perform(async () => {
    const response = await api.get(path, { responseType: 'blob' })
    const url = URL.createObjectURL(response.data)
    window.open(url, '_blank', 'noopener,noreferrer')
    window.setTimeout(() => URL.revokeObjectURL(url), 60_000)
  })
}

function privateFilePath(path: string) {
  return `/clients/${ticket.value}/files/${path.split('/').map(encodeURIComponent).join('/')}`
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
  }
}

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
    <IonHeader>
      <IonToolbar>
        <IonButtons slot="start"><IonBackButton default-href="/tabs/requests" /></IonButtons>
        <div class="detail-toolbar">
          <span>{{ ticket }}</span>
          <strong>Detail layanan</strong>
        </div>
      </IonToolbar>
    </IonHeader>
    <IonContent>
      <IonRefresher slot="fixed" @ion-refresh="refresh"><IonRefresherContent /></IonRefresher>

      <main v-if="clientQuery.isLoading.value" class="loading-shell">
        <IonSpinner name="crescent" />
      </main>

      <main v-else-if="clientQuery.error.value" class="page-shell">
        <div class="error-box">{{ apiError(clientQuery.error.value) }}</div>
      </main>

      <main
        v-else-if="clientQuery.data.value"
        class="page-shell detail-shell"
      >
        <section class="request-hero">
          <div class="hero-line">
            <span class="eyebrow">{{ clientQuery.data.value.service?.name }}</span>
            <StatusBadge :status="clientQuery.data.value.status" />
          </div>
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

        <IonAccordionGroup :multiple="true" :value="['identity', 'schedule', 'assignment']">
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
                  <IonButton size="small" fill="outline" @click="openProtected(`/clients/${ticket}/ticket`)">
                    Tiket
                  </IonButton>
                  <IonButton size="small" fill="outline" @click="openProtected(`/clients/${ticket}/report-pdf`)">
                    PDF laporan
                  </IonButton>
                </div>
                <IonButton
                  v-for="document in clientQuery.data.value.supporting_documents"
                  :key="document.path"
                  size="small"
                  fill="clear"
                  @click="openProtected(privateFilePath(document.path))"
                >
                  {{ document.name }}
                </IonButton>
                <IonButton
                  v-if="clientQuery.data.value.coordinate_file"
                  size="small"
                  fill="clear"
                  @click="openProtected(privateFilePath(clientQuery.data.value.coordinate_file.path))"
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
                <IonSelect
                  v-if="auth.can('assignments', 'update')"
                  :value="assignment.officer?.id"
                  label="Petugas"
                  label-placement="stacked"
                  interface="popover"
                  :disabled="saving"
                  @ion-change="reassignOfficer(assignment, Number($event.detail.value))"
                >
                  <IonSelectOption
                    v-for="officer in staffQuery.data.value"
                    :key="officer.id"
                    :value="officer.id"
                  >
                    {{ officer.name }}
                  </IonSelectOption>
                </IonSelect>
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
                <IonSelect
                  v-model="assignmentForm.user_ids"
                  label="Petugas"
                  label-placement="stacked"
                  :multiple="true"
                  required
                >
                  <IonSelectOption
                    v-for="officer in staffQuery.data.value"
                    :key="officer.id"
                    :value="officer.id"
                  >
                    {{ officer.name }} · {{ officer.jabatan || 'Petugas' }}
                  </IonSelectOption>
                </IonSelect>
                <IonButton type="submit" size="small" :disabled="saving">Tetapkan petugas</IonButton>
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
                <RichTextEditor v-model="reportForm.content" />
                <IonSelect v-model="reportForm.status" label="Status" label-placement="stacked">
                  <IonSelectOption value="draft">Draft</IonSelectOption>
                  <IonSelectOption value="completed">Selesai</IonSelectOption>
                </IonSelect>
                <label class="file-field">
                  <span>Dokumentasi (1–3 foto)</span>
                  <input type="file" accept="image/*" multiple required @change="selectReportFiles" />
                </label>
                <IonButton type="submit" size="small" :disabled="saving">Simpan laporan</IonButton>
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
                    @click="openProtected(`/clients/${ticket}/berita-acara-pdf`)"
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
                  <IonTextarea
                    v-model="baDraft.hasil_pendampingan"
                    label="Hasil pendampingan"
                    label-placement="stacked"
                    :auto-grow="true"
                  />
                  <IonSelect v-model="baDraft.status" label="Status" label-placement="stacked">
                    <IonSelectOption value="draft">Draft</IonSelectOption>
                    <IonSelectOption value="completed">Selesai</IonSelectOption>
                  </IonSelect>
                  <IonCheckbox v-model="baDraft.attendance_is_open">Presensi peserta dibuka</IonCheckbox>

                  <section class="signature-section">
                    <strong>Tanda tangan pemohon</strong>
                    <small>Gambar baru diproses oleh layanan tanda tangan yang sama dengan panel web.</small>
                    <SignaturePad @change="baDraft.applicant_signature = $event" />
                  </section>

                  <section class="attendee-editor">
                    <div class="section-heading">
                      <div>
                        <strong>Peserta dan penanda tangan</strong>
                        <small>{{ baDraft.attendees.length }} peserta</small>
                      </div>
                      <IonButton size="small" fill="outline" type="button" @click="addAttendee">
                        Tambah
                      </IonButton>
                    </div>
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

                  <div class="file-grid">
                    <label class="file-field">
                      <span>Lampiran peta</span>
                      <input type="file" accept=".pdf,image/*" @change="selectBaFiles('map', $event)" />
                    </label>
                    <label class="file-field">
                      <span>Dokumentasi (maks. 6)</span>
                      <input type="file" accept="image/*" multiple @change="selectBaFiles('documentation', $event)" />
                    </label>
                    <label class="file-field">
                      <span>Lampiran lain (maks. 5)</span>
                      <input type="file" multiple @change="selectBaFiles('other', $event)" />
                    </label>
                  </div>
                  <IonButton type="submit" size="small" :disabled="saving">Simpan Berita Acara</IonButton>
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
      </main>
    </IonContent>
  </IonPage>
</template>

<style scoped>
.detail-toolbar {
  display: grid;
  padding: 5px 12px 5px 0;
}

.detail-toolbar span {
  color: var(--app-muted);
  font-size: 0.68rem;
}

.detail-toolbar strong {
  color: var(--app-ink);
  font-size: 0.98rem;
}

.loading-shell {
  align-items: center;
  display: flex;
  height: 100%;
  justify-content: center;
}

.detail-shell {
  padding-top: 16px;
}

.request-hero {
  background: linear-gradient(145deg, #0d3150, #176780);
  border-radius: 25px;
  color: #fff;
  padding: 22px;
}

.request-hero .eyebrow {
  color: #ffd18a;
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
  font-size: 1.5rem;
  letter-spacing: -0.04em;
  margin: 18px 0 5px;
}

.request-hero p {
  color: #d8e9ef;
  margin: 0 0 18px;
}

.hero-meta {
  border-top: 1px solid rgba(255, 255, 255, 0.15);
  color: #d8e9ef;
  font-size: 0.76rem;
  justify-content: flex-start;
  padding-top: 14px;
}

.quick-action {
  align-items: end;
  display: grid;
  gap: 10px;
  grid-template-columns: 1fr;
  margin: 14px 0;
  padding: 16px;
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
  gap: 10px;
}

ion-accordion {
  background: #fff;
  border: 1px solid var(--app-line);
  border-radius: 17px;
  overflow: hidden;
}

.accordion-content {
  background: #f9fbfb;
  display: grid;
  gap: 10px;
  padding: 12px;
}

.sub-card {
  background: #fff;
  border: 1px solid #e5ebee;
  border-radius: 13px;
  display: grid;
  gap: 4px;
  padding: 13px;
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
  background: #fff;
  border: 1px dashed #b9cbd2;
  border-radius: 14px;
  display: grid;
  gap: 12px;
  margin-top: 5px;
  padding: 15px;
}

.inline-form h3 {
  font-size: 0.9rem;
  margin: 0;
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

.signature-section {
  border-top: 1px solid var(--app-line);
  padding-top: 14px;
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
