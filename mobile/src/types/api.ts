export interface CapabilityActions {
  [action: string]: boolean
}

export interface Capabilities {
  [resource: string]: CapabilityActions
}

export interface UserProfile {
  id: number
  name: string
  email: string
  nip?: string | null
  jabatan?: string | null
  instansi?: string | null
  avatar_url?: string | null
  roles: string[]
  permissions: string[]
  active: boolean
}

export interface StaffMember {
  id: number
  name: string
  nip?: string | null
  jabatan?: string | null
  instansi?: string | null
}

export interface Schedule {
  id: number
  date: string
  start_time: string
  end_time: string
  is_online: boolean
  meeting_link?: string | null
  version: string
}

export interface Assignment {
  id: number
  status: 'scheduled' | 'hadir' | 'izin_mendadak'
  score?: number | null
  schedule_id: number
  officer: {
    id: number
    name: string
    nip?: string | null
    jabatan?: string | null
  } | null
  version: string
}

export interface ConsultationReport {
  id: number
  content: string
  status: 'draft' | 'completed'
  documentation: Array<{ path: string; name: string }>
  has_signature: boolean
  signed_by?: {
    id: number
    name: string
    jabatan?: string | null
  } | null
  signed_at?: string | null
  version: string
  created_at: string
}

export interface BeritaAcara {
  id: number
  nomor_berita_acara?: string | null
  kbli?: string | null
  tanggal_pelaksanaan: string
  lokasi_permohonan?: string | null
  hasil_pendampingan?: string | null
  status: 'draft' | 'completed'
  attendance_is_open: boolean
  signing_deadline?: string | null
  attendees: Array<{
    id: number
    name: string
    position?: string | null
    institution?: string | null
    email?: string | null
    phone?: string | null
    is_officer: boolean
    is_signatory: boolean
    confirmed_at?: string | null
    signing_url: string
  }>
  version: string
}

export interface ClientSummary {
  ticket_number: string
  name: string
  instance?: string | null
  status: 'waiting' | 'scheduled' | 'completed'
  activity_type?: 'business' | 'non_business' | null
  service?: { id: number; name: string } | null
  location?: { id: number; name: string; is_online: boolean } | null
  next_schedule?: Schedule | null
  version: string
  updated_at: string
}

export interface ClientDetail extends ClientSummary {
  booking_type?: 'personal' | 'company'
  email: string
  whatsapp: string
  address?: string | null
  metadata?: Record<string, unknown> | null
  supporting_documents: Array<{ path: string; name: string }>
  supporting_document_links: string[]
  coordinate_file?: { path: string; name: string } | null
  schedules: Schedule[]
  assignments: Assignment[]
  consultation_reports: ConsultationReport[]
  berita_acara?: BeritaAcara | null
  satisfaction_survey?: SatisfactionSurvey | null
}

export interface SatisfactionSurvey {
  id: number
  ticket_number?: string | null
  criticism?: string | null
  suggestion?: string | null
  estimated_cost_savings?: number | null
  created_at: string
}

export interface PublicFeedback {
  id: number
  is_anonymous: boolean
  submitter: string
  feedback: string
  suggestion?: string | null
  officers: Array<{ id: number; name: string; jabatan?: string | null }>
  created_at: string
}

export interface MobileNotification {
  id: string
  type: string
  title: string
  body?: string | null
  ticket_number?: string | null
  route?: string | null
  read_at?: string | null
  created_at: string
}

export interface ApiEnvelope<
  T,
  TMeta extends Record<string, unknown> = Record<string, unknown>,
> {
  data: T
  meta?: TMeta
  links?: Record<string, string | null>
}

export interface ApiErrorBody {
  message: string
  code?: string
  request_id?: string
  errors?: Record<string, string[]>
}

export interface AppConfig {
  api_version: string
  platform: 'android' | 'ios'
  latest_version: string
  latest_build: number
  minimum_version: string
  minimum_build: number
  update_available: boolean
  update_required: boolean
  distribution_url?: string | null
  maintenance: {
    enabled: boolean
    message?: string | null
  }
}
