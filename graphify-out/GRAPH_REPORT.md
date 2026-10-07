# Graph Report - listdatakkprl  (2026-10-08)

## Corpus Check
- Existing main project and KKPRL index retained. Map measurement refresh: 3 code sources, 1 Blade views, 6 Markdown documents through ADR-028. Not a full repository rebuild or an expansion of vendor/build assets.

## Summary
- 3465 nodes · 8901 edges · 183 communities (104 shown, 79 thin omitted)
- Extraction: 91% EXTRACTED · 1% INFERRED · 8% AMBIGUOUS · INFERRED: 78 edges (avg confidence: 0.89)
- Token cost: AST 0 tokens; delegated host semantic usage unavailable (unknown, not zero). External model API calls: 0.

## Community Hubs (Navigation)
- Filament Form Schemas
- Filament Tables and Actions
- Tidal Data Reader Tests
- Filament Tables and Actions
- Admin List Pages
- Admin Edit Pages
- Model Permission Policies
- Feature Test Infrastructure
- Queued Notification Mail
- Learning Material Admin
- Private Proposal Attachments
- Proposal Wizard and Access
- Edit Approval Regression Tests
- Akun Pemohon dan Approval
- Proposal Document Rendering
- Encrypted Client Records
- Akun Pemohon dan Approval
- Proposal Wizard and Access
- KkprlChapterThreeEvidenceTest
- HTTP Request Boundaries
- Encrypted Client Records
- Eloquent Model Infrastructure
- KkprlTideEvidenceTest
- Staff Edit Approval Service
- Client Admin Pages
- Encrypted Consultation Records
- Proposal Progress and Fixtures
- Staff Edit Approval Service
- Coordinate Reset Regression Tests
- Staff Assignment Workflow
- Proposal Progress and Fixtures
- Mobile Client and Scheduling
- Learning Material Downloads
- Regulation Document Chunking
- Proposal and Subject Admin
- Proposal Wizard and Access
- Encryption Backfill Command
- Staff Edit Approval Service
- Mobile Security and Richtext
- Encrypted Client Records
- Proposal Document Downloads
- Consultation Signatures and Records
- Activity Statistics Widgets
- Model Permission Policies
- Proposal Database Migrations
- GMRT Profiles and Coordinates
- Private File Downloads
- Proposal Document Rendering
- Map Measurement and Manual Values
- Consultation Booking Wizard
- FAQ Permissions and Records
- Tidal Evidence and Provider Limits
- Application Logging and Jobs
- Edit Approval Admin
- Model Permission Policies
- Filament Panel Configuration
- Staff Edit Approval Service
- Proposal Guide Administration
- Survey Result Admin
- Filament Form Schemas
- Bathymetry PNG Rendering
- KkprlBathymetryTest
- Mobile Authentication and Devices
- Learning Access Permissions
- Model Permission Policies
- Survey Result Permissions
- Performance Result Permissions
- Harmonics and Artifact Identity
- Chapter Three Image Analysis
- FAQ Permissions and Records
- Consultation Service Permissions
- Akun Pemohon dan Approval
- Private Artifact Integrity
- Shared Hosting Image Evidence
- KkprlProposalWizardTest
- KkprlSeabedProfileTest
- Admin Login Security
- Proposal Guide Downloads
- Consultation Location Permissions
- Eloquent Model Infrastructure
- Model Permission Policies
- Proposal Wizard Regression Tests
- Learning Session Analytics
- Model Permission Policies
- Mobile Notifications and Schedules
- Akun Pemohon dan Approval
- Private Proposal Attachments
- Consultation Report Permissions
- Learning Activity Logs
- Proposal Wizard and Access
- Chapter Three Image Analysis
- Satisfaction Survey Records
- Conditional Chapter Five Decisions
- Tidal Evidence and Provider Limits
- Akun Pemohon dan Approval
- Private Artifact Integrity
- KkprlBathymetryTest
- Chapter Three Image Analysis
- Holiday Permissions
- Role Permissions
- Regulation Document Chunking
- Model Permission Policies
- Mobile Architecture Plan
- Public Learning Regression Tests
- KkprlChapterFiveTest
- Proposal Field Catalog
- Chapter Rules and Snapshots
- Admin Edit Pages
- Firebase Push Delivery
- Admin Login Security
- Private Proposal Attachments
- Chapter Rules and Snapshots
- Learning Access Admin
- Learning Material Admin
- Filament Tables and Actions
- Learning Material Admin
- General Gemini Chat
- Landing Navbar Branding
- Audited Staff Edit Decisions
- Akun Pemohon dan Approval
- Admin Login Security
- Encrypted Client Records
- Mobile Client and Scheduling
- Admin Login Security
- Consultation Location Permissions
- Public Livewire Pages
- Proposal Interface Validation
- Akun Pemohon dan Approval
- Chapter One Input Reliability
- Private Proposal Attachments
- Staff Performance Tables
- Chapter Export Decisions
- Tide Chart Unit Tests
- Chapter Three Evidence Interface
- Mobile Security and Richtext
- Public Status Lookup
- Proposal AI Chat Interface
- kkprl-proposal-wizard.blade.php
- Immutable Proposal Revision Decisions
- Plaintext Deprecation Command
- Private Proposal Attachments
- Filament Tables and Actions
- Staff Performance Tables
- Proposal Document Downloads
- Regulation Document Chunking
- Akun Pemohon dan Approval
- Akun Pemohon dan Approval
- KkprlProposalSchemaTest
- Tidal Attachment Interface
- Phone Normalization and Hashes
- Akun Pemohon dan Approval
- Bathymetry PNG Rendering
- Learning Category Permissions
- KkprlProposalDocumentDownloadTest
- Staff Edit Approval Service
- Proposal Field Catalog
- Mobile Security and Richtext
- Proposal Document Rendering
- Survey Result Permissions
- Chapter Three Image Analysis
- Proposal Guide Downloads
- kkprl-applicant-portal.blade.php
- Dependency Security Baseline
- Schedule Preview Editor
- Learning Search Filters
- Historical Release Evidence
- Bathymetry Builder Interface
- Profile Builder Interface
- Tidal Upload Interface
- Staff Edit Approval Service
- Layout akun responsif dengan CSRF Vite

## God Nodes (most connected - your core abstractions)
1. `Client` - 144 edges
2. `KkprlProposal` - 125 edges
3. `User` - 102 edges
4. `BeritaAcara` - 58 edges
5. `LearningMaterial` - 55 edges
6. `KkprlPayloadFixture` - 51 edges
7. `KkprlProposalDocumentService` - 45 edges
8. `KkprlApplicantFixture` - 45 edges
9. `KkprlApplicant` - 43 edges
10. `KkprlProposalAccessService` - 42 edges

## Surprising Connections (you probably didn't know these)
- `Revisi mewarisi owner root` --references--> `KkprlProposalRevisionService`  [INFERRED]
  docs/decisions/ADR-026-kepemilikan-dan-transisi-akses-pemohon.md → app/Services/KkprlProposalRevisionService.php
- `Guard pemohon terpisah` --references--> `KkprlApplicant`  [INFERRED]
  docs/kkprl-applicant-account-spec.md → app/Models/KkprlApplicant.php
- `Legacy owner terverifikasi seluruh revisi` --references--> `KkprlApplicantAdministrationService`  [INFERRED]
  docs/kkprl-applicant-account-spec.md → app/Services/KkprlApplicantAdministrationService.php
- `Password baru dimasukkan petugas` --references--> `KkprlApplicantAdministrationService`  [INFERRED]
  docs/kkprl-applicant-account-spec.md → app/Services/KkprlApplicantAdministrationService.php
- `Registrasi pending tanpa draft` --references--> `KkprlApplicantAuthService`  [INFERRED]
  docs/kkprl-applicant-account-spec.md → app/Services/KkprlApplicantAuthService.php

## Import Cycles
- 1-file cycle: `tests/js/kkprl-coordinate-manager.test.js -> tests/js/kkprl-coordinate-manager.test.js`

## Hyperedges (group relationships)
- **Policy-authorized privacy-safe mobile notification lifecycle** — docs_servicekkprl_mobile_development_plan_live_shield_mobile_capability_authorization, docs_servicekkprl_mobile_development_plan_keystore_backed_installation_token_lifecycle, docs_servicekkprl_mobile_development_plan_durable_inbox_and_privacy_safe_after_commit_fcm, docs_servicekkprl_mobile_development_plan_versioned_mobile_api_stale_write_protection [EXTRACTED 1.00]
- **Submission links immutable proposal state and complete dual-format artifacts** — docs_decisions_adr_003_dual_document_generation_adr_003_word_and_pdf_from_one_chapter_snapshot, docs_decisions_adr_003_dual_document_generation_single_snapshot_dual_format_generation, docs_decisions_adr_003_dual_document_generation_atomic_submit_artifact_completion, docs_decisions_adr_005_immutable_submission_revisions_adr_005_immutable_submission_revision_copies [INFERRED 0.85]
- **Scoped two-person approval with append-only audit and one active revision session** — docs_decisions_adr_007_two_person_edit_approval_adr_007_two_person_staff_edit_approval, docs_decisions_adr_006_audited_staff_edit_exception_append_only_staff_edit_provenance, docs_decisions_adr_006_audited_staff_edit_exception_shield_workflow_permission_separation, docs_decisions_adr_015_single_active_edit_approval_session_transactional_single_active_approval_guard [INFERRED 0.85]
- **Legacy guide continuity preserves source PDFs, archived material access, and migration rollback** — docs_pedoman_kkprl_legacy_learning_material_archive, routes_web_belajar_kkprl_material_show, routes_web_belajar_kkprl_material_pdf, routes_web_belajar_kkprl_material_download [EXTRACTED 1.00]

## Communities (183 total, 79 thin omitted)

### Community 0 - "Filament Form Schemas"
Cohesion: 0.04
Nodes (37): ClientForm, ClientInfolist, FaqForm, HolidayForm, KkprlApplicantsTable, LearningCategoryForm, LearningCategoryInfolist, LearningGroupForm (+29 more)

### Community 1 - "Filament Tables and Actions"
Cohesion: 0.06
Nodes (49): AssignmentsRelationManager, ConsultationReportsRelationManager, ClientsTable, App\Filament\Layanankkprl\Resources\ConsultationReportResource\Pages, HolidaysTable, AttachmentsRelationManager, DocumentsRelationManager, LearningCategoriesTable (+41 more)

### Community 2 - "Tidal Data Reader Tests"
Cohesion: 0.05
Nodes (24): InvalidTideData, App\Domain\Kkprl\ProposalAiResponseParser, ProposalAiResponseParser, KkprlTideChartRenderer, KkprlTideDataAnalyzer, KkprlTideDataReader, KkprlTideHarmonicAnalyzer, DateTimeImmutable (+16 more)

### Community 3 - "Filament Tables and Actions"
Cohesion: 0.05
Nodes (22): HolidayResource, KkprlApplicantResource, LearningActivityResource, ListLearningActivities, LearningMaterialResource, LearningSessionResource, ListLearningSessions, PublicFeedbackResource (+14 more)

### Community 4 - "Admin List Pages"
Cohesion: 0.04
Nodes (27): ListClients, ConsultationLocationResource, UnitEnum, ListConsultationLocations, CreateConsultationReport, ListConsultationReports, ListFaqs, ListHolidays (+19 more)

### Community 5 - "Admin Edit Pages"
Cohesion: 0.04
Nodes (25): EditClient, EditConsultationReport, EditFaq, EditHoliday, EditLearningCategory, Width, EditLearningGroup, Width (+17 more)

### Community 6 - "Model Permission Policies"
Cohesion: 0.05
Nodes (7): ClientPolicy, ConsultationReportPolicy, KkprlApplicantPolicy, KkprlProposalPolicy, LearningGroupPolicy, LearningMaterialPolicy, Illuminate\Foundation\Auth\User

### Community 7 - "Feature Test Infrastructure"
Cohesion: 0.09
Nodes (21): App\Models\KkprlProposalReviewEvent, App\Services\KkprlProposalSnapshotService, Database\Seeders\KkprlPermissionSeeder, Filament\Facades\Filament, Illuminate\Database\QueryException, Illuminate\Foundation\Testing\RefreshDatabase, Illuminate\Http\Client\Factory, Illuminate\Support\Facades\Auth (+13 more)

### Community 8 - "Queued Notification Mail"
Cohesion: 0.07
Nodes (18): ChunkRegulationJob, BeritaAcaraCompletedMail, BookingCreatedMail, BookingFinishedMail, ClientCreated, RescheduleProposalMail, StaffAssigned, StatusCompletedMail (+10 more)

### Community 9 - "Learning Material Admin"
Cohesion: 0.05
Nodes (24): CreateHoliday, CreateLearningMaterial, Width, CreatePublicFeedback, CreateRegulation, Width, CreateRunningText, BackedEnum (+16 more)

### Community 10 - "Private Proposal Attachments"
Cohesion: 0.10
Nodes (28): App\Domain\Kkprl\AttachmentQuota, App\Domain\Kkprl\AttachmentQuotaViolation, AttachmentQuotaViolation, App\Domain\Kkprl\InvalidAttachmentFile, InvalidAttachmentFile, App\Domain\Kkprl\ProposalChapterRules, App\Domain\Kkprl\ProposalInlineAnchorResolver, App\Domain\Kkprl\ProposalLocked (+20 more)

### Community 11 - "Proposal Wizard and Access"
Cohesion: 0.08
Nodes (19): App\Contracts\KkprlProposalAccessVerifier, KkprlProposalAccessVerifier, App\Domain\Kkprl\KkprlPhoneNormalizer, App\Domain\Kkprl\ProposalAccessDenied, ProposalAccessDenied, KkprlApplicantUploadController, KkprlProposalAttachmentDownloadController, WithKkprlFileUploads (+11 more)

### Community 12 - "Edit Approval Regression Tests"
Cohesion: 0.09
Nodes (7): User, ProposalGuidePolicy, Illuminate\Notifications\Notifiable, Laravel\Sanctum\HasApiTokens, Spatie\Permission\Traits\HasRoles, KkprlProposalEditApprovalTest, KkprlProposalFilamentReviewTest

### Community 13 - "Akun Pemohon dan Approval"
Cohesion: 0.09
Nodes (22): App\Livewire\AttendeeSign, App\Livewire\BelajarKkprl, App\Livewire\BelajarKkprlGroup, App\Livewire\CheckStatus, KkprlApplicantLogin, KkprlApplicantRegister, KkprlProposalAccess, KkprlProposalResume (+14 more)

### Community 14 - "Proposal Document Rendering"
Cohesion: 0.11
Nodes (5): App\Domain\Kkprl\ProposalDocumentSectionCatalog, ProposalDocumentSectionCatalog, App\Domain\Kkprl\ProposalSnapshot, KkprlProposalDocumentService, KkprlProposalSnapshotService

### Community 15 - "Encrypted Client Records"
Cohesion: 0.09
Nodes (3): self, NotificationLog, Illuminate\Database\Eloquent\Casts\Attribute

### Community 16 - "Akun Pemohon dan Approval"
Cohesion: 0.11
Nodes (12): KkprlApplicant, App\Models\User, KkprlApplicantAdministrationService, KkprlApplicantAuthService, KkprlProposalRevisionService, Database\Factories\UserFactory, Browser desktop mobile akun pending approved admin, Illuminate\Auth\Access\AuthorizationException (+4 more)

### Community 17 - "Proposal Wizard and Access"
Cohesion: 0.07
Nodes (7): ActivityResource, BackedEnum, UnitEnum, ActivitiesRelationManager, City, Province, Illuminate\Database\Eloquent\Relations\HasMany

### Community 19 - "HTTP Request Boundaries"
Cohesion: 0.16
Nodes (10): AppConfigController, FeedbackController, MobileNotificationController, ProfileController, App\Http\Controllers\LearningTrackingController, LearningTrackingController, Illuminate\Http\JsonResponse, Illuminate\Http\Request (+2 more)

### Community 20 - "Encrypted Client Records"
Cohesion: 0.10
Nodes (9): ClientUpdated, Client, App\Observers\AssignmentObserver, App\Observers\ClientObserver, ClientObserver, NotificationService, Illuminate\Database\Eloquent\Relations\HasManyThrough, Illuminate\Database\Eloquent\Relations\HasOne (+1 more)

### Community 21 - "Eloquent Model Infrastructure"
Cohesion: 0.16
Nodes (17): ActivityLog, AppSettings, App\Models\Assignment, App\Models\Client, App\Models\ConsultationReport, App\Models\Faq, App\Models\KkprlProposalChat, KkprlProposalChat (+9 more)

### Community 23 - "Staff Edit Approval Service"
Cohesion: 0.17
Nodes (8): EditApprovalDenied, EditApprovalUsed, EditScopeDenied, EditSessionExpired, KkprlProposalEditSession, App\Services\KkprlProposalEditApprovalService, KkprlProposalEditApprovalService, Carbon\CarbonInterface

### Community 24 - "Client Admin Pages"
Cohesion: 0.08
Nodes (11): ClientResource, CreateClient, App\Filament\Layanankkprl\Resources\Clients\Pages\ViewClient, ViewClient, ViewKkprlApplicant, ViewActivity, ViewSubject, BackedEnum (+3 more)

### Community 25 - "Encrypted Consultation Records"
Cohesion: 0.11
Nodes (4): BeritaAcara, self, App\Observers\BeritaAcaraObserver, BeritaAcaraObserver

### Community 26 - "Proposal Progress and Fixtures"
Cohesion: 0.12
Nodes (4): KkprlProposalDocumentGenerationTest, KkprlProposalRevisionTest, KkprlPayloadFixture, ProposalSnapshotTest

### Community 27 - "Staff Edit Approval Service"
Cohesion: 0.19
Nodes (3): KkprlProposalWizard, KkprlProposalDraftService, KkprlProposalAccessService

### Community 28 - "Coordinate Reset Regression Tests"
Cohesion: 0.12
Nodes (24): node:assert/strict, node:fs, node:test, node:vm, bootstrap.js (import reference), coordinateMeasurement(), cross(), decimal() (+16 more)

### Community 29 - "Staff Assignment Workflow"
Cohesion: 0.11
Nodes (6): CreateAssignments, Assignment, AssignmentObserver, AssignmentPolicy, BookingAvailabilityService, Illuminate\Auth\Access\Response

### Community 30 - "Proposal Progress and Fixtures"
Cohesion: 0.11
Nodes (4): ProposalProgress, ProposalProgressResult, ProposalChapterRules, ProposalProgressTest

### Community 31 - "Mobile Client and Scheduling"
Cohesion: 0.15
Nodes (5): AssignmentController, ClientController, ScheduleController, MobileTransformer, EnsuresMobileVersion

### Community 32 - "Learning Material Downloads"
Cohesion: 0.14
Nodes (6): LearningMaterialController, LearningMaterial, Legacy learning material archive, belajar-kkprl.material.download: GET /belajar-kkprl/materi/{material:slug}/download, withTrashed, belajar-kkprl.material.pdf: GET /belajar-kkprl/materi/{material:slug}/pdf, withTrashed, belajar-kkprl.material.show: GET /belajar-kkprl/materi/{material:slug}, withTrashed

### Community 33 - "Regulation Document Chunking"
Cohesion: 0.10
Nodes (5): Regulation, App\Observers\RegulationObserver, RegulationObserver, RegulationPolicy, ContentDeliveryService

### Community 34 - "Proposal and Subject Admin"
Cohesion: 0.10
Nodes (9): App\Filament\Layanankkprl\Resources\KkprlProposals\KkprlProposalResource, KkprlProposalResource, BackedEnum, UnitEnum, ListKkprlProposals, ViewKkprlProposal, ReviewEventsRelationManager, KkprlProposalInfolist (+1 more)

### Community 35 - "Proposal Wizard and Access"
Cohesion: 0.10
Nodes (26): ADR-027 — State input, kelengkapan dan editor Bab 1, Batas single wizard tanpa protokol konflik multitab, Editor menggunakan $set false sebelum blur commit, Metadata legacy dipertahankan bersama storage flat, Missing field report mengikuti evaluator backend, Representasi UI dan persist berbeda pada Bab 1, CKEditor editor placeholder, CKEditor getting and setting data (+18 more)

### Community 37 - "Staff Edit Approval Service"
Cohesion: 0.15
Nodes (12): App\Domain\Kkprl\ProposalAiRequestFailed, App\Services\ContentDeliveryService, KkprlAiService, Carbon\CarbonImmutable, Google\Auth\Credentials\ServiceAccountCredentials, GuzzleHttp\Exception\ConnectTimeoutException, GuzzleHttp\Psr7\Request, Illuminate\Http\Client\ConnectionException (+4 more)

### Community 38 - "Mobile Security and Richtext"
Cohesion: 0.11
Nodes (12): SafeRichText, SignatureService, Historical Samsung Navigation Verification, Mobile and Server Rich Text Allowlist Sanitization, Mobile Production Deployment Drift and Staging Gates, ServiceKKPRL Mobile Navigation Bug and Security Audit, Validated Mobile Navigation and Deep Links, DOMComment (+4 more)

### Community 39 - "Encrypted Client Records"
Cohesion: 0.11
Nodes (4): AttendeeSign, PublicAttendance, BeritaAcaraAttendee, self

### Community 40 - "Proposal Document Downloads"
Cohesion: 0.12
Nodes (13): App\Domain\Kkprl\PdfAttachmentRenderFailed, App\Domain\Kkprl\ProposalFieldCatalog, KkprlProposalDocumentDownloadController, KkprlApplicantPortal, App\Models\KkprlProposalDocument, Barryvdh\DomPDF\Facade\Pdf, canReadDocuments tanpa approval builder, Livewire\WithPagination (+5 more)

### Community 41 - "Consultation Signatures and Records"
Cohesion: 0.11
Nodes (4): BeritaAcaraRelationManager, SignaturePad, Filament\Forms\Components\Field, static

### Community 42 - "Activity Statistics Widgets"
Cohesion: 0.14
Nodes (12): ClientStatsOverview, LearningAnalyticsOverview, ActivityStatsOverview, Filament\Tables\Contracts\HasTable, Filament\Widgets\Concerns\InteractsWithPageTable, Filament\Widgets\StatsOverviewWidget, Filament\Widgets\StatsOverviewWidget\Stat, Illuminate\Contracts\Pagination\Paginator (+4 more)

### Community 43 - "Model Permission Policies"
Cohesion: 0.09
Nodes (3): BeritaAcaraPolicy, LearningMaterialAccessPolicy, Illuminate\Auth\Access\HandlesAuthorization

### Community 44 - "Proposal Database Migrations"
Cohesion: 0.13
Nodes (3): Illuminate\Database\Migrations\Migration, Illuminate\Database\Schema\Blueprint, Illuminate\Support\Facades\Schema

### Community 45 - "GMRT Profiles and Coordinates"
Cohesion: 0.09
Nodes (24): ADR-010 WGS84 shape coordinate validation, Map autosave and DD DMS normalization, Partial coordinate draft and export gate, WGS84 coordinate shape contract, ADR-020 GMRT seabed profile in chapter three, Bounded GMRT ProfileServer transect, GMRT CC BY 4.0 terms, GMRT data DOI 10.1594 IEDA.100001 (+16 more)

### Community 46 - "Private File Downloads"
Cohesion: 0.15
Nodes (8): MobileFileController, AttachRequestId, EnsureKkprlApplicant, EnsureMobileJsonRequest, PrivateFileService, Closure, Bounded JSON Mobile API and Platform Hardening, Symfony\Component\HttpFoundation\Response

### Community 47 - "Proposal Document Rendering"
Cohesion: 0.17
Nodes (3): ServicePerformanceResults, StaffPerformanceService, Illuminate\Support\Collection

### Community 48 - "Map Measurement and Manual Values"
Cohesion: 0.13
Nodes (23): ADR-028 — Ukuran peta sebagai pembanding dimensi Bab 1, Batas polygon lokal tanpa dukungan global polar holes, coordinateMeasurement model spherical dan kontrak getter, Dimensi isian tetap otoritatif untuk payload dan dokumen, Pembulatan tampilan bukan toleransi survei, Batas satu ring spherical tanpa validitas survei legal, Koordinat parsial dan polygon invalid tidak menghasilkan nilai stale, Pengukuran frontend informatif tanpa persist atau blocking (+15 more)

### Community 50 - "FAQ Permissions and Records"
Cohesion: 0.15
Nodes (11): App\Models\BeritaAcara, Schedule, App\Observers\PublicFeedbackObserver, PublicFeedbackObserver, App\Observers\SatisfactionSurveyObserver, SatisfactionSurveyObserver, App\Observers\ScheduleObserver, ScheduleObserver (+3 more)

### Community 51 - "Tidal Evidence and Provider Limits"
Cohesion: 0.11
Nodes (22): Gemini rate limits, ADR-018 Chapter-three tidal table evidence, Bounded complete spreadsheet ingestion, Gemini generateContent ThinkingConfig, Gemini thinking documentation, PhpSpreadsheet Excel dates, PhpSpreadsheet readers, Spreadsheet security and private summary export (+14 more)

### Community 52 - "Application Logging and Jobs"
Cohesion: 0.15
Nodes (7): DeprecatePlaintextData, App\Services\DataHashService, DataHashService, Illuminate\Console\Command, Illuminate\Support\Facades\Crypt, Illuminate\Support\Facades\Log, Throwable

### Community 53 - "Edit Approval Admin"
Cohesion: 0.12
Nodes (11): Dashboard, App\Filament\Layanankkprl\Resources\KkprlProposalEditApprovals\KkprlProposalEditApprovalResource, KkprlProposalEditApprovalResource, BackedEnum, UnitEnum, ListKkprlProposalEditApprovals, KkprlProposalEditApprovalsTable, Dashboard (+3 more)

### Community 54 - "Model Permission Policies"
Cohesion: 0.14
Nodes (4): Faq, App\Observers\FaqObserver, FaqObserver, FaqPolicy

### Community 55 - "Filament Panel Configuration"
Cohesion: 0.18
Nodes (17): DatakkprlPanelProvider, LayanankkprlPanelProvider, Filament\Http\Middleware\Authenticate, Filament\Http\Middleware\AuthenticateSession, Filament\Http\Middleware\DisableBladeIconComponents, Filament\Http\Middleware\DispatchServingFilamentEvent, Filament\Panel, Filament\PanelProvider (+9 more)

### Community 56 - "Staff Edit Approval Service"
Cohesion: 0.13
Nodes (13): App\Domain\Kkprl\ActiveRevisionExists, ActiveRevisionExists, App\Domain\Kkprl\EditApprovalDenied, App\Domain\Kkprl\EditApprovalUsed, App\Domain\Kkprl\EditScopeDenied, App\Domain\Kkprl\EditSessionExpired, App\Domain\Kkprl\InvalidProposalCredentials, InvalidProposalCredentials (+5 more)

### Community 57 - "Proposal Guide Administration"
Cohesion: 0.14
Nodes (13): ManageProposalGuides, ProposalGuideResource, Existing Materi Pembelajaran permissions, PDF replacement and retention, Pedoman Perolehan Data Proposal KKPRL Skala Kecil, Stable ID-based public URLs, Pedoman verification evidence, Filament\Resources\Pages\ManageRecords (+5 more)

### Community 58 - "Survey Result Admin"
Cohesion: 0.14
Nodes (6): CreateSatisfactionSurveyResult, EditSatisfactionSurveyResult, ListSatisfactionSurveyResults, SatisfactionSurveyResultResource, SatisfactionSurveyResultForm, SatisfactionSurveyResultsTable

### Community 59 - "Filament Form Schemas"
Cohesion: 0.14
Nodes (7): ActivityPerMonthChart, ActivityPerProvinceChart, OrganizerComparisonChart, Activity, App\Observers\ActivityObserver, ActivityObserver, Filament\Widgets\Concerns\InteractsWithPageFilters

### Community 62 - "Mobile Authentication and Devices"
Cohesion: 0.18
Nodes (8): AuthenticateSummaryUser, AuthController, EnsureMobileUserIsActive, UserDevice, MobileCapabilityService, Mobile Sanctum Ability and Device Revocation, Illuminate\Database\Eloquent\Concerns\HasUuids, Laravel\Sanctum\PersonalAccessToken

### Community 63 - "Learning Access Permissions"
Cohesion: 0.16
Nodes (3): App\Http\Controllers\LearningMaterialController, LearningMaterialAccess, LearningTrackingService

### Community 67 - "Harmonics and Artifact Identity"
Cohesion: 0.15
Nodes (19): ADR-019 Backend window tidal harmonics and optional AI narrative, Chapter-three evidence validation report, Conservative tidal harmonic quality policy, Incremental Givens QR tidal least squares, NOAA About Harmonic Constituents, NOAA Tidal Analysis and Predictions, pyTMD Tide Form Factor, Uptide README (+11 more)

### Community 69 - "FAQ Permissions and Records"
Cohesion: 0.14
Nodes (12): App\Models\Activity, App\Models\PublicFeedback, AppServiceProvider, App\Support\WindowsSafeFilesystem, WindowsSafeFilesystem, Filament\Support\Facades\FilamentView, Filament\View\PanelsRenderHook, Illuminate\Database\Eloquent\Relations\BelongsToMany (+4 more)

### Community 70 - "Consultation Service Permissions"
Cohesion: 0.15
Nodes (3): HasMany, Service, ServicePolicy

### Community 71 - "Akun Pemohon dan Approval"
Cohesion: 0.13
Nodes (18): ADR-001 Ticket and phone public proposal access, Credential and IP access throttling, Ticket phone scoped resume session, ADR 024 Akun dan guard pemohon terpisah, Pemisahan identitas pemohon petugas, Row lock login dan versi sesi fresh, Snapshot tidak memuat metadata akun, ADR 025 Approval dan reset akun pemohon (+10 more)

### Community 72 - "Private Artifact Integrity"
Cohesion: 0.12
Nodes (18): ADR-009 Snapshot renderer and private artifacts, Encrypted snapshot and manifest artifact identity, kkprl_private authorized artifact storage, Minimal OOXML and DomPDF rendering, Visual PDF renderer fail-closed, ADR-013 Bounded synchronous document generation, Production queue migration threshold, Synchronous generation status and idempotent retry (+10 more)

### Community 73 - "Shared Hosting Image Evidence"
Cohesion: 0.11
Nodes (18): ADR-017 Chapter-three image evidence on shared hosting, AI cooldown and stale checkpoint rejection, Allen Coral Atlas FAQ, Allen Coral Atlas Terms of Use, BIG pasang surut, Chapter-three evidence validation report, Copernicus Marine license, Evidence provenance and spatial integrity (+10 more)

### Community 76 - "Admin Login Security"
Cohesion: 0.19
Nodes (6): Login, DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException, Filament\Auth\Http\Responses\Contracts\LoginResponse, Filament\Auth\Pages\Login, Filament\Schemas\Components\Component, LoginResponse

### Community 77 - "Proposal Guide Downloads"
Cohesion: 0.24
Nodes (5): App\Http\Controllers\ProposalGuideController, ProposalGuideController, ProposalGuide, Guide publication visibility, ProposalGuideTest

### Community 79 - "Eloquent Model Infrastructure"
Cohesion: 0.13
Nodes (4): App\Models\KkprlProposalEditApproval, App\Models\KkprlProposalEditSession, LearningMaterialOpen, Illuminate\Database\Eloquent\Relations\BelongsTo

### Community 84 - "Mobile Notifications and Schedules"
Cohesion: 0.22
Nodes (4): MobileOperationalNotification, MobileNotificationService, Illuminate\Notifications\Notification, Ramsey\Uuid\Uuid

### Community 85 - "Akun Pemohon dan Approval"
Cohesion: 0.15
Nodes (17): Administration RED GREEN dan panel, Build final 58 modules 3.96 seconds, Filament support asset vendor synchronization, Fixture terisolasi tanpa API berbayar, Graphify final cutover audit, Identity RED GREEN 5 tests 17 assertions, Matriks negatif Livewire service operasi panjang, Regresi final 461 tests 2429 assertions (+9 more)

### Community 87 - "Consultation Report Permissions"
Cohesion: 0.20
Nodes (3): ConsultationReport, App\Observers\ConsultationReportObserver, ConsultationReportObserver

### Community 92 - "Conditional Chapter Five Decisions"
Cohesion: 0.16
Nodes (15): ADR-002 Draft-first conditional wizard, Mobile wizard bottom spacing, Partial draft and final-validation separation, Shared conditional chapter rules, ADR-004 Proposal Builder support-system boundary, e-Sea and OSS official permitting channels, Support workflow status semantics, ADR-022 Conditional chapter-five completeness (+7 more)

### Community 93 - "Tidal Evidence and Provider Limits"
Cohesion: 0.17
Nodes (15): Bathymetry Whole-Map Elevation Scope, GMRT Bathymetry Validation, Private Native Bathymetry Browser Evidence, Regresi akun 461 tests 2429 assertions dengan batas bukti, Validasi batimetri GMRT — 7 Oktober 2026, Akun pemohon email password dan approval petugas, Native GMRT Chapter Three Bathymetry, Private Chapter-Scoped Dual Document Export (+7 more)

### Community 94 - "Akun Pemohon dan Approval"
Cohesion: 0.14
Nodes (15): Browser desktop mobile 320x740 dan lifecycle editor, Fixture SQLite terisolasi tanpa API berbayar, Graphify refresh scoped kode dokumentasi dan audit, Metadata daftar proposal berasal flat atau nested sanitized, Regresi final Bab 1 469 tests 2462 assertions, Reproduksi autosave dengan respons 1.5 dan 6 detik, Snapshot legacy tidak berubah pada save tanpa edit isi, Validasi input Bab 1 — 8 Oktober 2026 (+7 more)

### Community 95 - "Private Artifact Integrity"
Cohesion: 0.14
Nodes (15): KKPRL Operational Retention Policy, Retensi data Proposal KKPRL, 36 isian Bab 1 dalam enam kelompok, Koordinat wajib dihitung satu field dengan validasi terpisah, Mapping template Proposal KKPRL ke field aplikasi, Nonbusiness e-Sea Cover Letter Deferred Scope, Satuan kedalaman datum tersedia tanpa asumsi datum, Template Chapter Field Mapping (+7 more)

### Community 100 - "Regulation Document Chunking"
Cohesion: 0.18
Nodes (3): RegulationChunk, AiService, Exception

### Community 102 - "Mobile Architecture Plan"
Cohesion: 0.24
Nodes (13): Durable Inbox and Privacy-Safe After-Commit FCM, Keystore-Backed Installation Token Lifecycle, Live Shield Mobile Capability Authorization, Online-First Mobile State Ownership, Pilot Signed APK Distribution and Release Gates, ServiceKKPRL Laravel-Authoritative Mobile Architecture, ServiceKKPRL Mobile Application Development Plan, Versioned Mobile API Stale Write Protection (+5 more)

### Community 106 - "Chapter Rules and Snapshots"
Cohesion: 0.24
Nodes (3): ProposalInlineAnchorResolver, ProposalSnapshot, KkprlProposalSnapshotService

### Community 107 - "Admin Edit Pages"
Cohesion: 0.18
Nodes (3): FaqResource, CreateFaq, FaqsTable

### Community 108 - "Firebase Push Delivery"
Cohesion: 0.26
Nodes (3): SendMobilePush, FirebaseCloudMessaging, Illuminate\Foundation\Queue\Queueable

### Community 110 - "Private Proposal Attachments"
Cohesion: 0.27
Nodes (5): App\Domain\Kkprl\InvalidBathymetry, InvalidBathymetry, InteractsWithKkprlBathymetry, App\Services\KkprlBathymetryMapRenderer, KkprlBathymetryService

### Community 113 - "Learning Material Admin"
Cohesion: 0.20
Nodes (3): LearningCategoryResource, CreateLearningCategory, Width

### Community 114 - "Filament Tables and Actions"
Cohesion: 0.20
Nodes (3): LearningGroupResource, CreateLearningGroup, Width

### Community 115 - "Learning Material Admin"
Cohesion: 0.18
Nodes (3): BackedEnum, UnitEnum, SatisfactionSurveyResource

### Community 116 - "General Gemini Chat"
Cohesion: 0.27
Nodes (3): AiChatWidget, AiChatLog, Illuminate\Support\Facades\Session

### Community 117 - "Landing Navbar Branding"
Cohesion: 0.35
Nodes (10): booking-wizard, Identitas navbar landing page, public-dashboard-charts, DJPRL logo — Direktorat Jenderal Penataan Ruang Laut, KKP logo — Kementerian Kelautan dan Perikanan, Accessible landing mobile menu, LPRL Sorong navbar identity, Landing navbar branding: KKP, DJPRL, LPRL Sorong (+2 more)

### Community 118 - "Audited Staff Edit Decisions"
Cohesion: 0.18
Nodes (11): ADR-006 Audited staff edit exception, Append-only staff edit provenance, KKPRL workflow policies excluded from Shield CRUD generation, Shield workflow permission separation, ADR-007 Two-person staff edit approval, Approval lifecycle sweeper, One-use scoped expiring edit approval, ADR-015 Single active edit approval and session (+3 more)

### Community 122 - "Mobile Client and Scheduling"
Cohesion: 0.29
Nodes (5): DashboardController, StaffController, Controller, Illuminate\Foundation\Auth\Access\AuthorizesRequests, Illuminate\Foundation\Validation\ValidatesRequests

### Community 125 - "Public Livewire Pages"
Cohesion: 0.22
Nodes (10): Encrypted Source of Truth and HMAC Lookup, Introductory Prompt for the Next Agent, Legacy Plaintext Deprecation, Additive Encrypted Columns and HMAC Migration, Filament5 Upgrade Guide, Idempotent Private Data Backfill with Rollback Window, Laravel13 Encrypted Casting, Laravel13 Upgrade Guide (+2 more)

### Community 126 - "Proposal Interface Validation"
Cohesion: 0.27
Nodes (10): Audit Awal UI/UX Jago KKPRL, Jago KKPRL Initial Mobile UX Audit, Maritime Civic Utility Design System, Mobile Redesign Contract Preservation, Truthful Mobile Pagination, Jago KKPRL Eight-Stage UI Implementation, Laporan Implementasi UI/UX Jago KKPRL, Searchable Employee Bottom Sheet (+2 more)

### Community 127 - "Akun Pemohon dan Approval"
Cohesion: 0.20
Nodes (10): Acceptance keamanan akun dan regresi, Akun pemohon dan approval KKPRL, Cutover tiket HP terencana, Dokumen generated dan builder berbeda, Guard pemohon terpisah, Legacy owner terverifikasi seluruh revisi, Migrasi additive akun dan owner nullable, Otorisasi fresh sebelum persistence (+2 more)

### Community 129 - "Private Proposal Attachments"
Cohesion: 0.31
Nodes (4): App\Domain\Kkprl\InvalidSeabedProfile, InvalidSeabedProfile, InteractsWithKkprlSeabedProfile, KkprlSeabedProfileService

### Community 131 - "Chapter Export Decisions"
Cohesion: 0.22
Nodes (9): ADR-003 Word and PDF from one chapter snapshot, Atomic submit artifact completion, Official section heading golden fixture, Official section numbering golden fixture, Official template section and numbering fixtures, Single snapshot dual-format generation, ADR-008 Chapter-scoped proposal exports, Chapter attachment render budget (+1 more)

### Community 133 - "Chapter Three Evidence Interface"
Cohesion: 0.25
Nodes (7): analyzeEvidence({{ $attachment->id }}), deleteEvidence({{ $attachment->id }}), livewire.partials.kkprl-bathymetry-builder, livewire.partials.kkprl-seabed-profile-builder, livewire.partials.kkprl-tide-data-attachment, livewire.partials.kkprl-tide-data-upload, uploadEvidence

### Community 136 - "Proposal AI Chat Interface"
Cohesion: 0.25
Nodes (7): confirmAndSave, livewire.partials.kkprl-chapter-five-options, livewire.partials.kkprl-chapter-three-evidence, livewire.partials.kkprl-preview-schedule, livewire.partials.kkprl-seabed-profile-script, $set(, sendMessage

### Community 137 - "kkprl-proposal-wizard.blade.php"
Cohesion: 0.25
Nodes (7): deleteAttachment({{ $attachment->id }}), generateChapterDocuments(, next, previous, $set(, submitProposal, uploadAttachments

### Community 138 - "Immutable Proposal Revision Decisions"
Cohesion: 0.25
Nodes (8): ADR-005 Immutable submission revision copies, Immutable linked revision family, Single active draft revision resume, ADR-012 Proposal integrity and enumeration hardening, Locked proposal model transition guard, Non-enumerating fresh artifact downloads, Parent-row locked attachment mutation, Revision-aware ticket uniqueness

### Community 145 - "Akun Pemohon dan Approval"
Cohesion: 0.33
Nodes (5): KkprlApplicantPermissionSeeder, Lima permission akun dan tiga role, Permission admin akun terpisah, Illuminate\Database\Seeder, Spatie\Permission\PermissionRegistrar

### Community 148 - "Tidal Attachment Interface"
Cohesion: 0.33
Nodes (5): analyzeTideData({{ $attachment->id }}), calculateTideData({{ $attachment->id }}), deleteTideData({{ $attachment->id }}), generateTideChart({{ $attachment->id }}), livewire.partials.kkprl-tide-harmonic-summary

### Community 150 - "Akun Pemohon dan Approval"
Cohesion: 0.33
Nodes (3): KkprlApplicantEvent, Audit reset dan kepemilikan terenkripsi, Audit akun append only tanpa secret

### Community 152 - "Learning Category Permissions"
Cohesion: 0.33
Nodes (6): Anonymous learning popularity counters, Anonymous PDF and external video access, Belajar KKPRL public learning reference, Category Group Material hierarchy, Hierarchical publication visibility, Layanankkprl learning administration

### Community 156 - "Mobile Security and Richtext"
Cohesion: 0.50
Nodes (3): AuthenticateMobile, Illuminate\Auth\AuthenticationException, Illuminate\Auth\Middleware\Authenticate

### Community 160 - "Proposal Guide Downloads"
Cohesion: 0.67
Nodes (3): Standalone Pedoman KKPRL, Landing page Pedoman KKPRL hero action, pedoman-kkprl: GET /pedoman-kkprl

### Community 161 - "kkprl-applicant-portal.blade.php"
Cohesion: 0.50
Nodes (3): createProposal, logout, openProposal({{ $proposal->id }})

### Community 162 - "Dependency Security Baseline"
Cohesion: 0.50
Nodes (4): ADR-011 Dependency security baseline, Constraint-preserving dependency patch baseline, Historical dependency review verification, Locked Composer audit release gate

## Ambiguous Edges - Review These
- `.table()` → `ClientsTable`  [AMBIGUOUS]
  app/Filament/Layanankkprl/Resources/Clients/ClientResource.php · relation: calls
- `.table()` → `User`  [AMBIGUOUS]
  app/Filament/Layanankkprl/Resources/Clients/ClientResource/RelationManagers/AssignmentsRelationManager.php · relation: calls
- `.table()` → `Assignment`  [AMBIGUOUS]
  app/Filament/Layanankkprl/Resources/Clients/ClientResource/RelationManagers/AssignmentsRelationManager.php · relation: calls
- `.table()` → `.form()`  [AMBIGUOUS]
  app/Filament/Layanankkprl/Resources/Clients/ClientResource/RelationManagers/AssignmentsRelationManager.php · relation: calls
- `.table()` → `Schedule`  [AMBIGUOUS]
  app/Filament/Layanankkprl/Resources/Clients/ClientResource/RelationManagers/AssignmentsRelationManager.php · relation: calls
- `.table()` → `.form()`  [AMBIGUOUS]
  app/Filament/Layanankkprl/Resources/Clients/ClientResource/RelationManagers/ConsultationReportsRelationManager.php · relation: calls
- `.configure()` → `.response()`  [AMBIGUOUS]
  app/Filament/Layanankkprl/Resources/Clients/Tables/ClientsTable.php · relation: calls
- `.table()` → `FaqsTable`  [AMBIGUOUS]
  app/Filament/Layanankkprl/Resources/Faqs/FaqResource.php · relation: calls
- `.table()` → `HolidaysTable`  [AMBIGUOUS]
  app/Filament/Layanankkprl/Resources/Holidays/HolidayResource.php · relation: calls
- `.table()` → `LearningCategoriesTable`  [AMBIGUOUS]
  app/Filament/Layanankkprl/Resources/LearningCategories/LearningCategoryResource.php · relation: calls
- `.table()` → `LearningGroupsTable`  [AMBIGUOUS]
  app/Filament/Layanankkprl/Resources/LearningGroups/LearningGroupResource.php · relation: calls
- `.table()` → `LearningMaterialsTable`  [AMBIGUOUS]
  app/Filament/Layanankkprl/Resources/LearningMaterials/LearningMaterialResource.php · relation: calls
- `.table()` → `RegulationsTable`  [AMBIGUOUS]
  app/Filament/Layanankkprl/Resources/Regulations/RegulationResource.php · relation: calls
- `.table()` → `SatisfactionSurveyResultsTable`  [AMBIGUOUS]
  app/Filament/Layanankkprl/Resources/SatisfactionSurveyResults/SatisfactionSurveyResultResource.php · relation: calls
- `.configure()` → `SatisfactionSurveyResult`  [AMBIGUOUS]
  app/Filament/Layanankkprl/Resources/SatisfactionSurveyResults/Tables/SatisfactionSurveyResultsTable.php · relation: calls
- `.table()` → `ServicePerformanceResultsTable`  [AMBIGUOUS]
  app/Filament/Layanankkprl/Resources/ServicePerformanceResults/ServicePerformanceResultResource.php · relation: calls
- `.configure()` → `ServicePerformanceResult`  [AMBIGUOUS]
  app/Filament/Layanankkprl/Resources/ServicePerformanceResults/Tables/ServicePerformanceResultsTable.php · relation: calls
- `.table()` → `ServicesTable`  [AMBIGUOUS]
  app/Filament/Layanankkprl/Resources/Services/ServiceResource.php · relation: calls
- `.build()` → `InvalidTideData`  [AMBIGUOUS]
  app/Services/KkprlTideChartRenderer.php · relation: calls
- `.build()` → `KkprlTideDataAnalyzer`  [AMBIGUOUS]
  app/Services/KkprlTideChartRenderer.php · relation: calls
- `.series()` → `InvalidTideData`  [AMBIGUOUS]
  app/Services/KkprlTideDataAnalyzer.php · relation: calls
- `.series()` → `.analyze()`  [AMBIGUOUS]
  app/Services/KkprlTideDataAnalyzer.php · relation: calls
- `.series()` → `KkprlTideHarmonicAnalyzer`  [AMBIGUOUS]
  app/Services/KkprlTideDataAnalyzer.php · relation: calls
- `.assertArchive()` → `InvalidTideData`  [AMBIGUOUS]
  app/Services/KkprlTideDataReader.php · relation: calls
- `.assertNoFormulas()` → `InvalidTideData`  [AMBIGUOUS]
  app/Services/KkprlTideDataReader.php · relation: calls
- `.csv()` → `InvalidTideData`  [AMBIGUOUS]
  app/Services/KkprlTideDataReader.php · relation: calls
- `.excel()` → `InvalidTideData`  [AMBIGUOUS]
  app/Services/KkprlTideDataReader.php · relation: calls
- `.read()` → `InvalidTideData`  [AMBIGUOUS]
  app/Services/KkprlTideDataReader.php · relation: calls
- `.srgiPreamble()` → `InvalidTideData`  [AMBIGUOUS]
  app/Services/KkprlTideDataReader.php · relation: calls
- `.a_broken_block_rejects_the_entire_patch()` → `ProposalAiResponseParser`  [AMBIGUOUS]
  tests/Unit/Kkprl/ProposalAiResponseParserTest.php · relation: calls
- `.bare_arrays_and_truncated_objects_are_hidden_without_becoming_drafts()` → `ProposalAiResponseParser`  [AMBIGUOUS]
  tests/Unit/Kkprl/ProposalAiResponseParserTest.php · relation: calls
- `.bare_nested_drafts_allow_braces_and_escaped_quotes_inside_strings()` → `ProposalAiResponseParser`  [AMBIGUOUS]
  tests/Unit/Kkprl/ProposalAiResponseParserTest.php · relation: calls
- `.multiple_data_blocks_are_merged_and_hidden()` → `ProposalAiResponseParser`  [AMBIGUOUS]
  tests/Unit/Kkprl/ProposalAiResponseParserTest.php · relation: calls
- `.ordinary_follow_up_questions_are_preserved()` → `ProposalAiResponseParser`  [AMBIGUOUS]
  tests/Unit/Kkprl/ProposalAiResponseParserTest.php · relation: calls
- `.a_file_without_headers_cannot_silently_lose_its_first_measurement()` → `KkprlTideDataReader`  [AMBIGUOUS]
  tests/Unit/Kkprl/TideDataTest.php · relation: calls
- `.a_full_month_of_minute_samples_is_read_without_truncation()` → `KkprlTideDataReader`  [AMBIGUOUS]
  tests/Unit/Kkprl/TideDataTest.php · relation: calls
- `.a_full_month_of_minute_samples_is_read_without_truncation()` → `KkprlTideDataAnalyzer`  [AMBIGUOUS]
  tests/Unit/Kkprl/TideDataTest.php · relation: calls
- `.a_local_timestamp_suffix_cannot_be_interpreted_in_the_wrong_timezone()` → `KkprlTideDataReader`  [AMBIGUOUS]
  tests/Unit/Kkprl/TideDataTest.php · relation: calls
- `.a_local_timestamp_suffix_cannot_be_interpreted_in_the_wrong_timezone()` → `KkprlTideDataAnalyzer`  [AMBIGUOUS]
  tests/Unit/Kkprl/TideDataTest.php · relation: calls
- `.ambiguous_mapping_and_unknown_units_are_rejected()` → `KkprlTideDataReader`  [AMBIGUOUS]
  tests/Unit/Kkprl/TideDataTest.php · relation: calls
- `.ambiguous_mapping_and_unknown_units_are_rejected()` → `KkprlTideDataAnalyzer`  [AMBIGUOUS]
  tests/Unit/Kkprl/TideDataTest.php · relation: calls
- `.compressed_workbook_expansion_is_bounded_before_parsing()` → `KkprlTideDataReader`  [AMBIGUOUS]
  tests/Unit/Kkprl/TideDataTest.php · relation: calls
- `.csv_computes_real_statistics_in_metres_and_keeps_sampling_limits_explicit()` → `KkprlTideDataReader`  [AMBIGUOUS]
  tests/Unit/Kkprl/TideDataTest.php · relation: calls
- `.csv_computes_real_statistics_in_metres_and_keeps_sampling_limits_explicit()` → `KkprlTideDataAnalyzer`  [AMBIGUOUS]
  tests/Unit/Kkprl/TideDataTest.php · relation: calls
- `.excel_formula_and_external_links_are_rejected()` → `KkprlTideDataReader`  [AMBIGUOUS]
  tests/Unit/Kkprl/TideDataTest.php · relation: calls
- `.formulae_in_later_excel_chunks_are_rejected()` → `KkprlTideDataReader`  [AMBIGUOUS]
  tests/Unit/Kkprl/TideDataTest.php · relation: calls
- `.html_renamed_as_excel_and_oversized_tables_are_rejected()` → `KkprlTideDataReader`  [AMBIGUOUS]
  tests/Unit/Kkprl/TideDataTest.php · relation: calls
- `.malformed_duplicate_or_formula_rows_are_rejected_without_silent_skips()` → `KkprlTideDataReader`  [AMBIGUOUS]
  tests/Unit/Kkprl/TideDataTest.php · relation: calls
- `.malformed_duplicate_or_formula_rows_are_rejected_without_silent_skips()` → `KkprlTideDataAnalyzer`  [AMBIGUOUS]
  tests/Unit/Kkprl/TideDataTest.php · relation: calls
- `.reads_genuine_excel_files_and_serial_dates_without_evaluating_formulae()` → `KkprlTideDataReader`  [AMBIGUOUS]
  tests/Unit/Kkprl/TideDataTest.php · relation: calls
- `.reads_genuine_excel_files_and_serial_dates_without_evaluating_formulae()` → `KkprlTideDataAnalyzer`  [AMBIGUOUS]
  tests/Unit/Kkprl/TideDataTest.php · relation: calls
- `.separate_date_and_time_columns_and_millimetres_are_supported()` → `KkprlTideDataReader`  [AMBIGUOUS]
  tests/Unit/Kkprl/TideDataTest.php · relation: calls
- `.separate_date_and_time_columns_and_millimetres_are_supported()` → `KkprlTideDataAnalyzer`  [AMBIGUOUS]
  tests/Unit/Kkprl/TideDataTest.php · relation: calls
- `.srgi_column_units_and_utc_timezone_cannot_be_misinterpreted()` → `KkprlTideDataReader`  [AMBIGUOUS]
  tests/Unit/Kkprl/TideDataTest.php · relation: calls
- `.srgi_column_units_and_utc_timezone_cannot_be_misinterpreted()` → `KkprlTideDataAnalyzer`  [AMBIGUOUS]
  tests/Unit/Kkprl/TideDataTest.php · relation: calls
- `.srgi_preamble_and_station_metadata_are_read_without_losing_samples()` → `KkprlTideDataReader`  [AMBIGUOUS]
  tests/Unit/Kkprl/TideDataTest.php · relation: calls
- `.srgi_preamble_and_station_metadata_are_read_without_losing_samples()` → `KkprlTideDataAnalyzer`  [AMBIGUOUS]
  tests/Unit/Kkprl/TideDataTest.php · relation: calls
- `.srgi_preamble_cannot_hide_missing_headers_formulae_or_invalid_data()` → `KkprlTideDataReader`  [AMBIGUOUS]
  tests/Unit/Kkprl/TideDataTest.php · relation: calls
- `.srgi_preamble_cannot_hide_missing_headers_formulae_or_invalid_data()` → `KkprlTideDataAnalyzer`  [AMBIGUOUS]
  tests/Unit/Kkprl/TideDataTest.php · relation: calls
- `.the_row_limit_excludes_the_header()` → `KkprlTideDataReader`  [AMBIGUOUS]
  tests/Unit/Kkprl/TideDataTest.php · relation: calls
- `.timestamp_suffix_and_iso_separator_keep_the_selected_timezone()` → `KkprlTideDataReader`  [AMBIGUOUS]
  tests/Unit/Kkprl/TideDataTest.php · relation: calls
- `.timestamp_suffix_and_iso_separator_keep_the_selected_timezone()` → `KkprlTideDataAnalyzer`  [AMBIGUOUS]
  tests/Unit/Kkprl/TideDataTest.php · relation: calls
- `.workbook_1904_calendar_and_separate_numeric_clock_are_preserved()` → `KkprlTideDataReader`  [AMBIGUOUS]
  tests/Unit/Kkprl/TideDataTest.php · relation: calls
- `.workbook_1904_calendar_and_separate_numeric_clock_are_preserved()` → `KkprlTideDataAnalyzer`  [AMBIGUOUS]
  tests/Unit/Kkprl/TideDataTest.php · relation: calls
- `.worksheet_cell_area_is_bounded_for_shared_hosting_memory()` → `KkprlTideDataReader`  [AMBIGUOUS]
  tests/Unit/Kkprl/TideDataTest.php · relation: calls
- `.classifies_unrounded_formzahl_with_explicit_boundary_convention()` → `KkprlTideHarmonicAnalyzer`  [AMBIGUOUS]
  tests/Unit/Kkprl/TideHarmonicAnalysisTest.php · relation: calls
- `.harmonic_recovery_covers_each_tidal_regime()` → `KkprlTideHarmonicAnalyzer`  [AMBIGUOUS]
  tests/Unit/Kkprl/TideHarmonicAnalysisTest.php · relation: calls
- `.large_linear_drift_cannot_hide_a_poor_tidal_fit()` → `KkprlTideHarmonicAnalyzer`  [AMBIGUOUS]
  tests/Unit/Kkprl/TideHarmonicAnalysisTest.php · relation: calls
- `.normalized_units_timezones_and_missing_samples_do_not_change_the_known_formzahl()` → `KkprlTideDataAnalyzer`  [AMBIGUOUS]
  tests/Unit/Kkprl/TideHarmonicAnalysisTest.php · relation: calls
- `.poor_harmonic_fit_and_zero_semidiurnal_amplitudes_do_not_invent_a_classification()` → `KkprlTideHarmonicAnalyzer`  [AMBIGUOUS]
  tests/Unit/Kkprl/TideHarmonicAnalysisTest.php · relation: calls
- `.recovers_known_amplitudes_and_formzahl_with_other_constituents_offset_and_drift()` → `KkprlTideHarmonicAnalyzer`  [AMBIGUOUS]
  tests/Unit/Kkprl/TideHarmonicAnalysisTest.php · relation: calls
- `.shorter_records_constant_water_levels_and_large_gaps_do_not_produce_a_type()` → `KkprlTideHarmonicAnalyzer`  [AMBIGUOUS]
  tests/Unit/Kkprl/TideHarmonicAnalysisTest.php · relation: calls
- `.sparse_sampling_is_rejected_even_when_no_single_gap_exceeds_three_hours()` → `KkprlTideHarmonicAnalyzer`  [AMBIGUOUS]
  tests/Unit/Kkprl/TideHarmonicAnalysisTest.php · relation: calls
- `.render()` → `.view()`  [AMBIGUOUS]
  app/Livewire/KkprlAiChat.php · relation: calls
- `.render()` → `ProposalFieldCatalog`  [AMBIGUOUS]
  app/Livewire/KkprlAiChat.php · relation: calls
- `.render()` → `.view()`  [AMBIGUOUS]
  app/Livewire/KkprlApplicantLogin.php · relation: calls
- `.logout()` → `.logout()`  [AMBIGUOUS]
  app/Livewire/KkprlApplicantPortal.php · relation: calls
- `.openProposal()` → `KkprlProposal`  [AMBIGUOUS]
  app/Livewire/KkprlApplicantPortal.php · relation: calls
- `.render()` → `KkprlProposalDocument`  [AMBIGUOUS]
  app/Livewire/KkprlApplicantPortal.php · relation: calls
- `.render()` → `.filled()`  [AMBIGUOUS]
  app/Livewire/KkprlApplicantPortal.php · relation: calls
- `.render()` → `.view()`  [AMBIGUOUS]
  app/Livewire/KkprlApplicantPortal.php · relation: calls
- `.render()` → `.view()`  [AMBIGUOUS]
  app/Livewire/KkprlProposalAccess.php · relation: calls
- `.createPublishedGroup()` → `LearningCategory`  [AMBIGUOUS]
  tests/Feature/BelajarKkprlTest.php · relation: calls
- `.detailed_tracking_is_disabled_without_collecting_sessions_or_activities()` → `LearningMaterialAccess`  [AMBIGUOUS]
  tests/Feature/BelajarKkprlTest.php · relation: calls
- `.detailed_tracking_is_disabled_without_collecting_sessions_or_activities()` → `LearningAccessResource`  [AMBIGUOUS]
  tests/Feature/BelajarKkprlTest.php · relation: calls
- `.detailed_tracking_is_disabled_without_collecting_sessions_or_activities()` → `LearningSessionResource`  [AMBIGUOUS]
  tests/Feature/BelajarKkprlTest.php · relation: calls
- `.detailed_tracking_is_disabled_without_collecting_sessions_or_activities()` → `LearningActivityResource`  [AMBIGUOUS]
  tests/Feature/BelajarKkprlTest.php · relation: calls
- `.learning_admin_index_pages_load_for_authorized_users()` → `User`  [AMBIGUOUS]
  tests/Feature/BelajarKkprlTest.php · relation: calls
- `.visitor_access_is_scoped_to_one_group_while_activities_track_each_material()` → `LearningActivityLog`  [AMBIGUOUS]
  tests/Feature/BelajarKkprlTest.php · relation: calls
- `.visitor_access_is_scoped_to_one_group_while_activities_track_each_material()` → `LearningSession`  [AMBIGUOUS]
  tests/Feature/BelajarKkprlTest.php · relation: calls
- `.requiredFields()` → `ProposalFieldCatalog`  [AMBIGUOUS]
  app/Domain/Kkprl/ProposalProgress.php · relation: calls
- `.catalog_covers_template_identity_activity_and_location_fields()` → `ProposalFieldCatalog`  [AMBIGUOUS]
  tests/Unit/Kkprl/ProposalFieldCatalogTest.php · relation: calls
- `.conditional_catalogs_are_separate_and_explicit()` → `ProposalFieldCatalog`  [AMBIGUOUS]
  tests/Unit/Kkprl/ProposalFieldCatalogTest.php · relation: calls
- `.resolve()` → `ProposalFieldCatalog`  [AMBIGUOUS]
  app/Domain/Kkprl/ProposalInlineAnchorResolver.php · relation: calls
- `.activeChapterFiveAttachment()` → `ProposalFieldCatalog`  [AMBIGUOUS]
  app/Services/KkprlProposalSnapshotService.php · relation: calls
- `.payloadForChapter()` → `ProposalFieldCatalog`  [AMBIGUOUS]
  app/Services/KkprlProposalSnapshotService.php · relation: calls
- `.form()` → `Province`  [AMBIGUOUS]
  app/Filament/Resources/ActivityResource.php · relation: calls
- `.form()` → `City`  [AMBIGUOUS]
  app/Filament/Resources/ActivityResource.php · relation: calls
- `.table()` → `.form()`  [AMBIGUOUS]
  app/Filament/Resources/ActivityResource.php · relation: calls
- `.table()` → `Province`  [AMBIGUOUS]
  app/Filament/Resources/ActivityResource.php · relation: calls
- `.form()` → `Province`  [AMBIGUOUS]
  app/Filament/Resources/SubjectResource/RelationManagers/ActivitiesRelationManager.php · relation: calls
- `.form()` → `City`  [AMBIGUOUS]
  app/Filament/Resources/SubjectResource/RelationManagers/ActivitiesRelationManager.php · relation: calls
- `.table()` → `.form()`  [AMBIGUOUS]
  app/Filament/Resources/SubjectResource/RelationManagers/ActivitiesRelationManager.php · relation: calls
- `.table()` → `Province`  [AMBIGUOUS]
  app/Filament/Resources/SubjectResource/RelationManagers/ActivitiesRelationManager.php · relation: calls
- `.payload()` → `KkprlPayloadFixture`  [AMBIGUOUS]
  tests/Feature/KkprlApplicantAccessTest.php · relation: calls
- `.test_pending_cannot_mount_wizard_or_chat_in_direct_livewire_requests()` → `.session()`  [AMBIGUOUS]
  tests/Feature/KkprlApplicantAccessTest.php · relation: calls
- `.bag_five_is_relevant_only_for_selected_subsections()` → `ProposalChapterRules`  [AMBIGUOUS]
  tests/Unit/Kkprl/ProposalChapterRulesTest.php · relation: calls
- `.base_chapters_are_always_relevant()` → `ProposalChapterRules`  [AMBIGUOUS]
  tests/Unit/Kkprl/ProposalChapterRulesTest.php · relation: calls
- `.inactive_conditional_chapters_are_not_relevant()` → `ProposalChapterRules`  [AMBIGUOUS]
  tests/Unit/Kkprl/ProposalChapterRulesTest.php · relation: calls
- `.reclamation_adds_bag_four()` → `ProposalChapterRules`  [AMBIGUOUS]
  tests/Unit/Kkprl/ProposalChapterRulesTest.php · relation: calls
- `.string_zero_from_a_select_does_not_activate_permits()` → `ProposalChapterRules`  [AMBIGUOUS]
  tests/Unit/Kkprl/ProposalChapterRulesTest.php · relation: calls
- `.filled()` → `.filled()`  [AMBIGUOUS]
  app/Domain/Kkprl/ProposalProgress.php · relation: calls
- `.filteredGroupsQuery()` → `LearningGroup`  [AMBIGUOUS]
  app/Livewire/BelajarKkprl.php · relation: calls
- `.filteredGroupsQuery()` → `.filled()`  [AMBIGUOUS]
  app/Livewire/BelajarKkprl.php · relation: calls
- `.filteredMaterialsQuery()` → `LearningMaterial`  [AMBIGUOUS]
  app/Livewire/BelajarKkprl.php · relation: calls
- `.filteredMaterialsQuery()` → `.filled()`  [AMBIGUOUS]
  app/Livewire/BelajarKkprl.php · relation: calls
- `.render()` → `LearningCategory`  [AMBIGUOUS]
  app/Livewire/BelajarKkprl.php · relation: calls
- `.officerSignature()` → `.filled()`  [AMBIGUOUS]
  app/Models/ConsultationReport.php · relation: calls
- `.booted()` → `.filled()`  [AMBIGUOUS]
  app/Models/UserDevice.php · relation: calls
- `.pushRegistration()` → `.filled()`  [AMBIGUOUS]
  app/Models/UserDevice.php · relation: calls
- `.handle()` → `.request()`  [AMBIGUOUS]
  app/Http/Controllers/KkprlApplicantUploadController.php · relation: calls
- `.handle()` → `KkprlProposal`  [AMBIGUOUS]
  app/Http/Controllers/KkprlApplicantUploadController.php · relation: calls
- `._startUpload()` → `KkprlProposal`  [AMBIGUOUS]
  app/Livewire/Concerns/WithKkprlFileUploads.php · relation: calls
- `.current()` → `.session()`  [AMBIGUOUS]
  app/Services/KkprlApplicantAuthService.php · relation: calls
- `.login()` → `.request()`  [AMBIGUOUS]
  app/Services/KkprlApplicantAuthService.php · relation: calls
- `.login()` → `.session()`  [AMBIGUOUS]
  app/Services/KkprlApplicantAuthService.php · relation: calls
- `.logout()` → `.logout()`  [AMBIGUOUS]
  app/Services/KkprlApplicantAuthService.php · relation: calls
- `.logout()` → `.session()`  [AMBIGUOUS]
  app/Services/KkprlApplicantAuthService.php · relation: calls
- `.register()` → `.request()`  [AMBIGUOUS]
  app/Services/KkprlApplicantAuthService.php · relation: calls
- `.assertApproved()` → `.current()`  [AMBIGUOUS]
  app/Services/KkprlProposalAccessService.php · relation: calls
- `.assertApproved()` → `ProposalAccessDenied`  [AMBIGUOUS]
  app/Services/KkprlProposalAccessService.php · relation: calls
- `.assertCanAccess()` → `ProposalAccessDenied`  [AMBIGUOUS]
  app/Services/KkprlProposalAccessService.php · relation: calls
- `.assertCanBuild()` → `ProposalAccessDenied`  [AMBIGUOUS]
  app/Services/KkprlProposalAccessService.php · relation: calls
- `.canReadDocuments()` → `.current()`  [AMBIGUOUS]
  app/Services/KkprlProposalAccessService.php · relation: calls
- `.current()` → `.current()`  [AMBIGUOUS]
  app/Services/KkprlProposalAccessService.php · relation: calls
- `.current()` → `.session()`  [AMBIGUOUS]
  app/Services/KkprlProposalAccessService.php · relation: calls
- `.forget()` → `.session()`  [AMBIGUOUS]
  app/Services/KkprlProposalAccessService.php · relation: calls
- `.forget()` → `.forget()`  [AMBIGUOUS]
  app/Services/KkprlProposalAccessService.php · relation: calls
- `.grantAccess()` → `.session()`  [AMBIGUOUS]
  app/Services/KkprlProposalAccessService.php · relation: calls
- `.grantAccess()` → `.forget()`  [AMBIGUOUS]
  app/Services/KkprlProposalAccessService.php · relation: calls
- `.resumeDraft()` → `InvalidProposalCredentials`  [AMBIGUOUS]
  app/Services/KkprlProposalAccessService.php · relation: calls
- `.review_access_comes_from_permission_not_role_name()` → `User`  [AMBIGUOUS]
  tests/Feature/KkprlProposalShieldAuthorizationTest.php · relation: calls
- `.review_access_comes_from_permission_not_role_name()` → `KkprlProposal`  [AMBIGUOUS]
  tests/Feature/KkprlProposalShieldAuthorizationTest.php · relation: calls
- `.user_without_review_permission_cannot_review()` → `User`  [AMBIGUOUS]
  tests/Feature/KkprlProposalShieldAuthorizationTest.php · relation: calls
- `.user_without_review_permission_cannot_review()` → `KkprlProposal`  [AMBIGUOUS]
  tests/Feature/KkprlProposalShieldAuthorizationTest.php · relation: calls
- `.getPages()` → `ListLearningAccesses`  [AMBIGUOUS]
  app/Filament/Layanankkprl/Resources/LearningAccesses/LearningAccessResource.php · relation: calls
- `.test_admin_reset_invalidates_password_and_existing_session_without_secret_audit()` → `KkprlApplicantEvent`  [AMBIGUOUS]
  tests/Feature/KkprlApplicantAdministrationTest.php · relation: calls
- `.test_owner_assignment_never_overwrites_another_account()` → `KkprlProposal`  [AMBIGUOUS]
  tests/Feature/KkprlApplicantAdministrationTest.php · relation: calls
- `.test_owner_assignment_preserves_locked_data_and_all_revisions()` → `KkprlProposal`  [AMBIGUOUS]
  tests/Feature/KkprlApplicantAdministrationTest.php · relation: calls
- `.getPages()` → `ListSatisfactionSurveyResults`  [AMBIGUOUS]
  app/Filament/Layanankkprl/Resources/SatisfactionSurveyResults/SatisfactionSurveyResultResource.php · relation: calls
- `.getPages()` → `CreateSatisfactionSurveyResult`  [AMBIGUOUS]
  app/Filament/Layanankkprl/Resources/SatisfactionSurveyResults/SatisfactionSurveyResultResource.php · relation: calls
- `.getPages()` → `EditSatisfactionSurveyResult`  [AMBIGUOUS]
  app/Filament/Layanankkprl/Resources/SatisfactionSurveyResults/SatisfactionSurveyResultResource.php · relation: calls
- `.__invoke()` → `.response()`  [AMBIGUOUS]
  app/Http/Controllers/Api/Mobile/V1/AppConfigController.php · relation: calls
- `.__invoke()` → `Client`  [AMBIGUOUS]
  app/Http/Controllers/Api/Mobile/V1/DashboardController.php · relation: calls
- `.__invoke()` → `.response()`  [AMBIGUOUS]
  app/Http/Controllers/Api/Mobile/V1/DashboardController.php · relation: calls
- `.analyzeEvidence()` → `.session()`  [AMBIGUOUS]
  app/Livewire/Concerns/InteractsWithKkprlEvidence.php · relation: calls
- `.analyzeEvidence()` → `ProposalEvidenceCatalog`  [AMBIGUOUS]
  app/Livewire/Concerns/InteractsWithKkprlEvidence.php · relation: calls
- `.evidenceAttachment()` → `ProposalEvidenceCatalog`  [AMBIGUOUS]
  app/Livewire/Concerns/InteractsWithKkprlEvidence.php · relation: calls
- `.evidenceChatCheckpoint()` → `KkprlProposalChat`  [AMBIGUOUS]
  app/Livewire/Concerns/InteractsWithKkprlEvidence.php · relation: calls
- `.evidenceViewData()` → `ProposalEvidenceCatalog`  [AMBIGUOUS]
  app/Livewire/Concerns/InteractsWithKkprlEvidence.php · relation: calls
- `.updatedEvidenceField()` → `ProposalEvidenceCatalog`  [AMBIGUOUS]
  app/Livewire/Concerns/InteractsWithKkprlEvidence.php · relation: calls
- `.updatedEvidenceSource()` → `ProposalEvidenceCatalog`  [AMBIGUOUS]
  app/Livewire/Concerns/InteractsWithKkprlEvidence.php · relation: calls
- `.uploadEvidence()` → `ProposalEvidenceCatalog`  [AMBIGUOUS]
  app/Livewire/Concerns/InteractsWithKkprlEvidence.php · relation: calls
- `.prepareTideCalculation()` → `.session()`  [AMBIGUOUS]
  app/Livewire/Concerns/InteractsWithKkprlTideData.php · relation: calls
- `.uploadTideData()` → `ProposalEvidenceCatalog`  [AMBIGUOUS]
  app/Livewire/Concerns/InteractsWithKkprlTideData.php · relation: calls
- `.attachment_cannot_be_persisted_on_a_non_private_disk()` → `KkprlProposalAttachment`  [AMBIGUOUS]
  tests/Feature/KkprlProposalAttachmentServiceTest.php · relation: calls
- `.unresolved_inline_anchor_is_rejected_at_upload()` → `KkprlPayloadFixture`  [AMBIGUOUS]
  tests/Feature/KkprlProposalAttachmentServiceTest.php · relation: calls
- `.submitted_proposal_rejects_direct_attachment_mutation()` → `KkprlProposalAttachment`  [AMBIGUOUS]
  tests/Feature/KkprlProposalSubmissionTest.php · relation: calls
- `.editablePreviewFields()` → `ProposalFieldCatalog`  [AMBIGUOUS]
  app/Livewire/Concerns/InteractsWithKkprlChapterFive.php · relation: calls
- `.updatedExistingPermitsChoice()` → `.session()`  [AMBIGUOUS]
  app/Livewire/Concerns/InteractsWithKkprlChapterFive.php · relation: calls
- `.completeBag()` → `ProposalFieldCatalog`  [AMBIGUOUS]
  tests/Feature/KkprlChapterOneInputTest.php · relation: calls
- `.completeBag()` → `KkprlPayloadFixture`  [AMBIGUOUS]
  tests/Feature/KkprlChapterOneInputTest.php · relation: calls
- `.test_empty_rich_text_does_not_complete_site_plan()` → `.completeBag()`  [AMBIGUOUS]
  tests/Feature/KkprlChapterOneInputTest.php · relation: calls
- `.test_missing_report_identifies_the_depth_unit_without_losing_false_or_zero()` → `.completeBag()`  [AMBIGUOUS]
  tests/Feature/KkprlChapterOneInputTest.php · relation: calls
- `.test_ui_scaffolding_and_flat_payload_conversion_do_not_change_existing_document_identity()` → `.completeBag()`  [AMBIGUOUS]
  tests/Feature/KkprlChapterOneInputTest.php · relation: calls
- `.idle_session_expires()` → `.session()`  [AMBIGUOUS]
  tests/Feature/KkprlProposalAccessTest.php · relation: calls
- `.getSelectedPeriodLabel()` → `.filled()`  [AMBIGUOUS]
  app/Filament/Layanankkprl/Widgets/StaffScoreSummaryTable.php · relation: calls
- `.table()` → `.filled()`  [AMBIGUOUS]
  app/Filament/Layanankkprl/Widgets/StaffScoreSummaryTable.php · relation: calls
- `.protectedAttribute()` → `.filled()`  [AMBIGUOUS]
  app/Models/ConsultationReport.php · relation: calls
- `.emergency_edit_permission_without_review_permission_cannot_request_ordinary_revision()` → `KkprlPayloadFixture`  [AMBIGUOUS]
  tests/Feature/KkprlProposalRevisionTest.php · relation: calls
- `.failed_revision_copy_cleans_up_copied_attachments()` → `KkprlPayloadFixture`  [AMBIGUOUS]
  tests/Feature/KkprlProposalRevisionTest.php · relation: calls
- `.needs_revision_creates_immutable_copy_with_same_main_ticket()` → `KkprlPayloadFixture`  [AMBIGUOUS]
  tests/Feature/KkprlProposalRevisionTest.php · relation: calls
- `.only_one_active_draft_revision_is_allowed()` → `KkprlPayloadFixture`  [AMBIGUOUS]
  tests/Feature/KkprlProposalRevisionTest.php · relation: calls
- `.ordinary_revision_request_uses_review_permission_not_emergency_edit_permission()` → `KkprlPayloadFixture`  [AMBIGUOUS]
  tests/Feature/KkprlProposalRevisionTest.php · relation: calls
- `.revision_copies_private_attachments_without_mutating_original()` → `KkprlPayloadFixture`  [AMBIGUOUS]
  tests/Feature/KkprlProposalRevisionTest.php · relation: calls
- `.revision_copy_rejects_a_legacy_attachment_on_a_non_private_disk()` → `KkprlPayloadFixture`  [AMBIGUOUS]
  tests/Feature/KkprlProposalRevisionTest.php · relation: calls
- `.submitFeedback()` → `SatisfactionSurvey`  [AMBIGUOUS]
  app/Livewire/CheckStatus.php · relation: calls
- `.canRedact()` → `.filled()`  [AMBIGUOUS]
  app/Console/Commands/DeprecatePlaintextData.php · relation: calls
- `.handle()` → `.log()`  [AMBIGUOUS]
  app/Console/Commands/DeprecatePlaintextData.php · relation: calls
- `.hasLegacyValue()` → `.filled()`  [AMBIGUOUS]
  app/Console/Commands/DeprecatePlaintextData.php · relation: calls
- `.getPages()` → `ListClients`  [AMBIGUOUS]
  app/Filament/Layanankkprl/Resources/Clients/ClientResource.php · relation: calls
- `.getPages()` → `CreateClient`  [AMBIGUOUS]
  app/Filament/Layanankkprl/Resources/Clients/ClientResource.php · relation: calls
- `.getPages()` → `ViewClient`  [AMBIGUOUS]
  app/Filament/Layanankkprl/Resources/Clients/ClientResource.php · relation: calls
- `.getPages()` → `EditClient`  [AMBIGUOUS]
  app/Filament/Layanankkprl/Resources/Clients/ClientResource.php · relation: calls
- `.getPages()` → `ListKkprlApplicants`  [AMBIGUOUS]
  app/Filament/Layanankkprl/Resources/KkprlApplicants/KkprlApplicantResource.php · relation: calls
- `.getPages()` → `ViewKkprlApplicant`  [AMBIGUOUS]
  app/Filament/Layanankkprl/Resources/KkprlApplicants/KkprlApplicantResource.php · relation: calls
- `.table()` → `KkprlApplicantsTable`  [AMBIGUOUS]
  app/Filament/Layanankkprl/Resources/KkprlApplicants/KkprlApplicantResource.php · relation: calls
- `.configure()` → `KkprlProposal`  [AMBIGUOUS]
  app/Filament/Layanankkprl/Resources/KkprlApplicants/Tables/KkprlApplicantsTable.php · relation: calls
- `.handle()` → `.filled()`  [AMBIGUOUS]
  app/Console/Commands/KkprlPreflight.php · relation: calls
- `.handle()` → `User`  [AMBIGUOUS]
  app/Jobs/SendMobilePush.php · relation: calls
- `.enabled()` → `.filled()`  [AMBIGUOUS]
  app/Services/FirebaseCloudMessaging.php · relation: calls
- `.generated_document_cannot_be_persisted_on_a_non_private_disk()` → `KkprlProposalDocument`  [AMBIGUOUS]
  tests/Feature/KkprlProposalDocumentGenerationTest.php · relation: calls
- `.generated_document_snapshot_cannot_be_deleted()` → `KkprlProposalDocument`  [AMBIGUOUS]
  tests/Feature/KkprlProposalDocumentGenerationTest.php · relation: calls
- `.generated_document_storage_path_cannot_be_changed()` → `KkprlProposalDocument`  [AMBIGUOUS]
  tests/Feature/KkprlProposalDocumentGenerationTest.php · relation: calls
- `.test_login_and_logout_keep_staff_guard_independent()` → `User`  [AMBIGUOUS]
  tests/Feature/KkprlApplicantIdentityTest.php · relation: calls
- `.test_staff_credentials_cannot_authenticate_as_applicant()` → `User`  [AMBIGUOUS]
  tests/Feature/KkprlApplicantIdentityTest.php · relation: calls
- `.test_stale_session_is_rejected_even_when_guard_cached_the_old_account()` → `KkprlApplicant`  [AMBIGUOUS]
  tests/Feature/KkprlApplicantIdentityTest.php · relation: calls
- `.blank_phone_is_normalized_to_null()` → `KkprlPhoneNormalizer`  [AMBIGUOUS]
  tests/Unit/Kkprl/KkprlPhoneNormalizerTest.php · relation: calls
- `.local_and_international_indonesian_formats_resolve_to_same_value()` → `KkprlPhoneNormalizer`  [AMBIGUOUS]
  tests/Unit/Kkprl/KkprlPhoneNormalizerTest.php · relation: calls
- `.can()` → `.can()`  [AMBIGUOUS]
  app/Services/MobileCapabilityService.php · relation: calls
- `.canAny()` → `.can()`  [AMBIGUOUS]
  app/Services/MobileCapabilityService.php · relation: calls
- `.staff_download_requires_attachment_permission()` → `User`  [AMBIGUOUS]
  tests/Feature/KkprlProposalAttachmentDownloadTest.php · relation: calls
- `.another_officer_cannot_end_the_edit_session()` → `User`  [AMBIGUOUS]
  tests/Feature/KkprlProposalEditApprovalTest.php · relation: calls
- `.approvalRequestFixture()` → `User`  [AMBIGUOUS]
  tests/Feature/KkprlProposalEditApprovalTest.php · relation: calls
- `.cancelling_session_records_the_staff_actor()` → `User`  [AMBIGUOUS]
  tests/Feature/KkprlProposalEditApprovalTest.php · relation: calls
- `.expired_approval_does_not_block_a_new_request()` → `User`  [AMBIGUOUS]
  tests/Feature/KkprlProposalEditApprovalTest.php · relation: calls
- `.expired_approval_is_not_startable()` → `User`  [AMBIGUOUS]
  tests/Feature/KkprlProposalEditApprovalTest.php · relation: calls
- `.field_outside_approval_scope_and_expired_session_are_rejected()` → `User`  [AMBIGUOUS]
  tests/Feature/KkprlProposalEditApprovalTest.php · relation: calls
- `.locked_approval_row_still_rejects_self_approval_with_a_stale_model()` → `User`  [AMBIGUOUS]
  tests/Feature/KkprlProposalEditApprovalTest.php · relation: calls
- `.locked_approval_row_still_rejects_self_rejection_with_a_stale_model()` → `User`  [AMBIGUOUS]
  tests/Feature/KkprlProposalEditApprovalTest.php · relation: calls
- `.second_officer_can_approve_and_requester_gets_one_scoped_session()` → `User`  [AMBIGUOUS]
  tests/Feature/KkprlProposalEditApprovalTest.php · relation: calls
- `.second_officer_can_approve_and_requester_gets_one_scoped_session()` → `KkprlProposalReviewEvent`  [AMBIGUOUS]
  tests/Feature/KkprlProposalEditApprovalTest.php · relation: calls
- `.section_scope_rejects_unknown_change_paths()` → `User`  [AMBIGUOUS]
  tests/Feature/KkprlProposalEditApprovalTest.php · relation: calls
- `.stale_approval_cannot_be_approved_after_revision_submission()` → `KkprlPayloadFixture`  [AMBIGUOUS]
  tests/Feature/KkprlProposalEditApprovalTest.php · relation: calls
- `.stale_approval_cannot_be_approved_after_revision_submission()` → `User`  [AMBIGUOUS]
  tests/Feature/KkprlProposalEditApprovalTest.php · relation: calls
- `.submitting_revision_ends_root_scoped_edit_session()` → `User`  [AMBIGUOUS]
  tests/Feature/KkprlProposalEditApprovalTest.php · relation: calls
- `.submitting_revision_ends_root_scoped_edit_session()` → `KkprlPayloadFixture`  [AMBIGUOUS]
  tests/Feature/KkprlProposalEditApprovalTest.php · relation: calls
- `.sweeper_closes_active_sessions_after_expiry()` → `User`  [AMBIGUOUS]
  tests/Feature/KkprlProposalEditApprovalTest.php · relation: calls
- `.sweeper_expires_unused_approved_approval()` → `User`  [AMBIGUOUS]
  tests/Feature/KkprlProposalEditApprovalTest.php · relation: calls
- `.approval_permission_alone_can_open_approval_resource()` → `User`  [AMBIGUOUS]
  tests/Feature/KkprlProposalFilamentReviewTest.php · relation: calls
- `.authorized_detail_renders_active_chapters_without_exposing_phone()` → `User`  [AMBIGUOUS]
  tests/Feature/KkprlProposalFilamentReviewTest.php · relation: calls
- `.authorized_detail_renders_active_chapters_without_exposing_phone()` → `KkprlProposal`  [AMBIGUOUS]
  tests/Feature/KkprlProposalFilamentReviewTest.php · relation: calls
- `.authorized_reviewer_can_open_proposal_and_approval_resources()` → `User`  [AMBIGUOUS]
  tests/Feature/KkprlProposalFilamentReviewTest.php · relation: calls
- `.user_without_review_permission_cannot_open_proposal_resource()` → `User`  [AMBIGUOUS]
  tests/Feature/KkprlProposalFilamentReviewTest.php · relation: calls
- `.png()` → `InvalidBathymetry`  [AMBIGUOUS]
  app/Services/KkprlBathymetryMapRenderer.php · relation: calls
- `.serviceBreakdown()` → `Client`  [AMBIGUOUS]
  app/Services/StaffPerformanceService.php · relation: calls
- `.serviceBreakdown()` → `Service`  [AMBIGUOUS]
  app/Services/StaffPerformanceService.php · relation: calls
- `.summaryQuery()` → `Assignment`  [AMBIGUOUS]
  app/Services/StaffPerformanceService.php · relation: calls
- `.summaryQuery()` → `Client`  [AMBIGUOUS]
  app/Services/StaffPerformanceService.php · relation: calls
- `.completePayload()` → `KkprlPayloadFixture`  [AMBIGUOUS]
  tests/Feature/KkprlProposalDocumentDownloadTest.php · relation: calls
- `.session_cannot_download_another_proposals_document()` → `KkprlApplicant`  [AMBIGUOUS]
  tests/Feature/KkprlProposalDocumentDownloadTest.php · relation: calls
- `.staff_download_requires_and_accepts_document_permission()` → `User`  [AMBIGUOUS]
  tests/Feature/KkprlProposalDocumentDownloadTest.php · relation: calls
- `.allFields()` → `ProposalFieldCatalog`  [AMBIGUOUS]
  app/Domain/Kkprl/ProposalPayloadSanitizer.php · relation: calls
- `.sanitizeChapter()` → `ProposalFieldCatalog`  [AMBIGUOUS]
  app/Domain/Kkprl/ProposalPayloadSanitizer.php · relation: calls
- `.availableOfficers()` → `User`  [AMBIGUOUS]
  app/Livewire/PublicFeedbackPage.php · relation: calls
- `.submit()` → `PublicFeedback`  [AMBIGUOUS]
  app/Livewire/PublicFeedbackPage.php · relation: calls
- `.availableYears()` → `SatisfactionSurveyResult`  [AMBIGUOUS]
  app/Livewire/SatisfactionSurveyResults.php · relation: calls
- `.mount()` → `.request()`  [AMBIGUOUS]
  app/Livewire/SatisfactionSurveyResults.php · relation: calls
- `.render()` → `SatisfactionSurveyResult`  [AMBIGUOUS]
  app/Livewire/SatisfactionSurveyResults.php · relation: calls
- `.assertWithin()` → `AttachmentQuotaViolation`  [AMBIGUOUS]
  app/Domain/Kkprl/AttachmentQuota.php · relation: calls
- `.generateBathymetry()` → `InvalidBathymetry`  [AMBIGUOUS]
  app/Livewire/Concerns/InteractsWithKkprlBathymetry.php · relation: calls
- `.generateBathymetry()` → `ProposalEvidenceCatalog`  [AMBIGUOUS]
  app/Livewire/Concerns/InteractsWithKkprlBathymetry.php · relation: calls
- `.generateBathymetry()` → `.session()`  [AMBIGUOUS]
  app/Livewire/Concerns/InteractsWithKkprlBathymetry.php · relation: calls
- `.generateSeabedProfile()` → `InvalidSeabedProfile`  [AMBIGUOUS]
  app/Livewire/Concerns/InteractsWithKkprlSeabedProfile.php · relation: calls
- `.generateSeabedProfile()` → `KkprlProposal`  [AMBIGUOUS]
  app/Livewire/Concerns/InteractsWithKkprlSeabedProfile.php · relation: calls
- `.generateSeabedProfile()` → `ProposalEvidenceCatalog`  [AMBIGUOUS]
  app/Livewire/Concerns/InteractsWithKkprlSeabedProfile.php · relation: calls
- `.generateSeabedProfile()` → `.session()`  [AMBIGUOUS]
  app/Livewire/Concerns/InteractsWithKkprlSeabedProfile.php · relation: calls
- `.fetch()` → `InvalidBathymetry`  [AMBIGUOUS]
  app/Services/KkprlBathymetryService.php · relation: calls
- `.parse()` → `InvalidBathymetry`  [AMBIGUOUS]
  app/Services/KkprlBathymetryService.php · relation: calls
- `.fetch()` → `InvalidSeabedProfile`  [AMBIGUOUS]
  app/Services/KkprlSeabedProfileService.php · relation: calls
- `.png()` → `InvalidSeabedProfile`  [AMBIGUOUS]
  app/Services/KkprlSeabedProfileService.php · relation: calls
- `.exact_limits_are_allowed()` → `AttachmentQuota`  [AMBIGUOUS]
  tests/Unit/Kkprl/AttachmentQuotaTest.php · relation: calls
- `.more_than_fifteen_mib_is_rejected_as_size_violation()` → `AttachmentQuota`  [AMBIGUOUS]
  tests/Unit/Kkprl/AttachmentQuotaTest.php · relation: calls
- `.more_than_ten_active_files_is_rejected_as_count_violation()` → `AttachmentQuota`  [AMBIGUOUS]
  tests/Unit/Kkprl/AttachmentQuotaTest.php · relation: calls
- `.protectedAttribute()` → `.filled()`  [AMBIGUOUS]
  app/Models/PublicFeedback.php · relation: calls
- `.meetingLink()` → `.filled()`  [AMBIGUOUS]
  app/Models/Schedule.php · relation: calls
- `.boot()` → `Activity`  [AMBIGUOUS]
  app/Providers/AppServiceProvider.php · relation: calls
- `.boot()` → `Faq`  [AMBIGUOUS]
  app/Providers/AppServiceProvider.php · relation: calls
- `.boot()` → `Regulation`  [AMBIGUOUS]
  app/Providers/AppServiceProvider.php · relation: calls
- `.boot()` → `Client`  [AMBIGUOUS]
  app/Providers/AppServiceProvider.php · relation: calls
- `.boot()` → `Assignment`  [AMBIGUOUS]
  app/Providers/AppServiceProvider.php · relation: calls
- `.boot()` → `ConsultationReport`  [AMBIGUOUS]
  app/Providers/AppServiceProvider.php · relation: calls
- `.boot()` → `BeritaAcara`  [AMBIGUOUS]
  app/Providers/AppServiceProvider.php · relation: calls
- `.boot()` → `Schedule`  [AMBIGUOUS]
  app/Providers/AppServiceProvider.php · relation: calls
- `.boot()` → `SatisfactionSurvey`  [AMBIGUOUS]
  app/Providers/AppServiceProvider.php · relation: calls
- `.boot()` → `PublicFeedback`  [AMBIGUOUS]
  app/Providers/AppServiceProvider.php · relation: calls
- `.register()` → `WindowsSafeFilesystem`  [AMBIGUOUS]
  app/Providers/AppServiceProvider.php · relation: calls
- `.curl_timeout_is_explained_and_logs_only_safe_transport_diagnostics()` → `.log()`  [AMBIGUOUS]
  tests/Feature/KkprlAiRateLimitTest.php · relation: calls
- `.logs_and_user_messages_do_not_expose_credentials_or_provider_content()` → `.log()`  [AMBIGUOUS]
  tests/Feature/KkprlAiRateLimitTest.php · relation: calls
- `.daily_quota_errors_explain_the_wait_and_preserve_image_and_manual_preview()` → `KkprlAiRateLimitTest`  [AMBIGUOUS]
  tests/Feature/KkprlChapterThreeEvidenceTest.php · relation: calls
- `.quota_failure_keeps_the_typed_chat_answer_available()` → `KkprlAiRateLimitTest`  [AMBIGUOUS]
  tests/Feature/KkprlChapterThreeEvidenceTest.php · relation: calls
- `.source_metadata_survives_snapshot_and_changes_invalidate_the_manifest()` → `KkprlPayloadFixture`  [AMBIGUOUS]
  tests/Feature/KkprlChapterThreeEvidenceTest.php · relation: calls
- `.populateProtectionHashes()` → `.email()`  [AMBIGUOUS]
  app/Models/Client.php · relation: calls
- `.protectedAttribute()` → `.filled()`  [AMBIGUOUS]
  app/Models/Client.php · relation: calls
- `.booted()` → `.phone()`  [AMBIGUOUS]
  app/Models/KkprlProposal.php · relation: calls
- `.booted()` → `.email()`  [AMBIGUOUS]
  app/Models/KkprlProposal.php · relation: calls
- `.booted()` → `ProposalLocked`  [AMBIGUOUS]
  app/Models/KkprlProposal.php · relation: calls
- `.protectedValueCallbacks()` → `.filled()`  [AMBIGUOUS]
  app/Models/KkprlProposal.php · relation: calls
- `.store()` → `.response()`  [AMBIGUOUS]
  app/Http/Controllers/Api/Mobile/V1/AssignmentController.php · relation: calls
- `.update()` → `User`  [AMBIGUOUS]
  app/Http/Controllers/Api/Mobile/V1/AssignmentController.php · relation: calls
- `.update()` → `.update()`  [AMBIGUOUS]
  app/Http/Controllers/Api/Mobile/V1/AssignmentController.php · relation: calls
- `.update()` → `.response()`  [AMBIGUOUS]
  app/Http/Controllers/Api/Mobile/V1/AssignmentController.php · relation: calls
- `.index()` → `.response()`  [AMBIGUOUS]
  app/Http/Controllers/Api/Mobile/V1/MobileNotificationController.php · relation: calls
- `.read()` → `.response()`  [AMBIGUOUS]
  app/Http/Controllers/Api/Mobile/V1/MobileNotificationController.php · relation: calls
- `.readAll()` → `.response()`  [AMBIGUOUS]
  app/Http/Controllers/Api/Mobile/V1/MobileNotificationController.php · relation: calls
- `.show()` → `.response()`  [AMBIGUOUS]
  app/Http/Controllers/Api/Mobile/V1/MobileNotificationController.php · relation: calls
- `.unreadCount()` → `.response()`  [AMBIGUOUS]
  app/Http/Controllers/Api/Mobile/V1/MobileNotificationController.php · relation: calls
- `.capabilities()` → `.response()`  [AMBIGUOUS]
  app/Http/Controllers/Api/Mobile/V1/ProfileController.php · relation: calls
- `.disableDevice()` → `.response()`  [AMBIGUOUS]
  app/Http/Controllers/Api/Mobile/V1/ProfileController.php · relation: calls
- `.show()` → `.response()`  [AMBIGUOUS]
  app/Http/Controllers/Api/Mobile/V1/ProfileController.php · relation: calls
- `.upsertDevice()` → `UserDevice`  [AMBIGUOUS]
  app/Http/Controllers/Api/Mobile/V1/ProfileController.php · relation: calls
- `.upsertDevice()` → `.filled()`  [AMBIGUOUS]
  app/Http/Controllers/Api/Mobile/V1/ProfileController.php · relation: calls
- `.upsertDevice()` → `.response()`  [AMBIGUOUS]
  app/Http/Controllers/Api/Mobile/V1/ProfileController.php · relation: calls
- `.__invoke()` → `User`  [AMBIGUOUS]
  app/Http/Controllers/Api/Mobile/V1/StaffController.php · relation: calls
- `.__invoke()` → `.response()`  [AMBIGUOUS]
  app/Http/Controllers/Api/Mobile/V1/StaffController.php · relation: calls
- `.activity()` → `LearningActivityLog`  [AMBIGUOUS]
  app/Http/Controllers/LearningTrackingController.php · relation: calls
- `.activity()` → `.response()`  [AMBIGUOUS]
  app/Http/Controllers/LearningTrackingController.php · relation: calls
- `.endSession()` → `.response()`  [AMBIGUOUS]
  app/Http/Controllers/LearningTrackingController.php · relation: calls
- `.me()` → `.response()`  [AMBIGUOUS]
  app/Http/Controllers/LearningTrackingController.php · relation: calls
- `.startSession()` → `.startSession()`  [AMBIGUOUS]
  app/Http/Controllers/LearningTrackingController.php · relation: calls
- `.startSession()` → `.response()`  [AMBIGUOUS]
  app/Http/Controllers/LearningTrackingController.php · relation: calls
- `.storeAccess()` → `.response()`  [AMBIGUOUS]
  app/Http/Controllers/LearningTrackingController.php · relation: calls
- `.response()` → `.exists()`  [AMBIGUOUS]
  app/Services/PrivateFileService.php · relation: calls
- `.payload()` → `KkprlPayloadFixture`  [AMBIGUOUS]
  tests/Feature/KkprlApplicantBoundaryTest.php · relation: calls
- `.test_approved_foreign_account_cannot_mutate_services_or_mint_upload_url()` → `KkprlApplicant`  [AMBIGUOUS]
  tests/Feature/KkprlApplicantBoundaryTest.php · relation: calls
- `.test_reset_during_ai_request_prevents_chat_or_proposal_write()` → `User`  [AMBIGUOUS]
  tests/Feature/KkprlApplicantBoundaryTest.php · relation: calls
- `.test_revision_inherits_root_owner_and_pending_cannot_open_it()` → `User`  [AMBIGUOUS]
  tests/Feature/KkprlApplicantBoundaryTest.php · relation: calls
- `.test_revocation_during_gmrt_request_does_not_store_artifacts_or_chat()` → `KkprlBathymetryTest`  [AMBIGUOUS]
  tests/Feature/KkprlApplicantBoundaryTest.php · relation: calls
- `.snapshot_and_word_pdf_export_preserve_map_grid_provenance_and_license()` → `KkprlPayloadFixture`  [AMBIGUOUS]
  tests/Feature/KkprlBathymetryTest.php · relation: calls
- `.audit()` → `KkprlApplicantEvent`  [AMBIGUOUS]
  app/Services/KkprlApplicantAdministrationService.php · relation: calls
- `.requestRevision()` → `ActiveRevisionExists`  [AMBIGUOUS]
  app/Services/KkprlProposalRevisionService.php · relation: calls
- `.requestRevision()` → `KkprlProposalReviewEvent`  [AMBIGUOUS]
  app/Services/KkprlProposalRevisionService.php · relation: calls
- `.requestRevision()` → `.request()`  [AMBIGUOUS]
  app/Services/KkprlProposalRevisionService.php · relation: calls
- `.test_authorized_staff_can_approve_and_reset_through_filament()` → `KkprlApplicant`  [AMBIGUOUS]
  tests/Feature/KkprlApplicantFilamentTest.php · relation: calls
- `.test_direct_actions_without_their_permission_cannot_mutate_or_audit()` → `KkprlApplicant`  [AMBIGUOUS]
  tests/Feature/KkprlApplicantFilamentTest.php · relation: calls
- `.test_list_permission_does_not_allow_detail_approval_or_reset()` → `KkprlApplicant`  [AMBIGUOUS]
  tests/Feature/KkprlApplicantFilamentTest.php · relation: calls
- `.test_without_permission_list_detail_navigation_and_direct_livewire_are_denied()` → `KkprlApplicant`  [AMBIGUOUS]
  tests/Feature/KkprlApplicantFilamentTest.php · relation: calls
- `.test_without_permission_list_detail_navigation_and_direct_livewire_are_denied()` → `KkprlApplicantResource`  [AMBIGUOUS]
  tests/Feature/KkprlApplicantFilamentTest.php · relation: calls
- `.applyChanges()` → `EditApprovalDenied`  [AMBIGUOUS]
  app/Services/KkprlProposalEditApprovalService.php · relation: calls
- `.applyChanges()` → `EditScopeDenied`  [AMBIGUOUS]
  app/Services/KkprlProposalEditApprovalService.php · relation: calls
- `.applyChanges()` → `.request()`  [AMBIGUOUS]
  app/Services/KkprlProposalEditApprovalService.php · relation: calls
- `.approve()` → `EditApprovalDenied`  [AMBIGUOUS]
  app/Services/KkprlProposalEditApprovalService.php · relation: calls
- `.assertActiveSession()` → `EditSessionExpired`  [AMBIGUOUS]
  app/Services/KkprlProposalEditApprovalService.php · relation: calls
- `.assertPermission()` → `EditApprovalDenied`  [AMBIGUOUS]
  app/Services/KkprlProposalEditApprovalService.php · relation: calls
- `.assertRevisionScope()` → `EditApprovalDenied`  [AMBIGUOUS]
  app/Services/KkprlProposalEditApprovalService.php · relation: calls
- `.endSession()` → `EditApprovalDenied`  [AMBIGUOUS]
  app/Services/KkprlProposalEditApprovalService.php · relation: calls
- `.hasScope()` → `.filled()`  [AMBIGUOUS]
  app/Services/KkprlProposalEditApprovalService.php · relation: calls
- `.isKnownScopePath()` → `ProposalFieldCatalog`  [AMBIGUOUS]
  app/Services/KkprlProposalEditApprovalService.php · relation: calls
- `.lifecycleEvent()` → `.request()`  [AMBIGUOUS]
  app/Services/KkprlProposalEditApprovalService.php · relation: calls
- `.normalizeScope()` → `EditScopeDenied`  [AMBIGUOUS]
  app/Services/KkprlProposalEditApprovalService.php · relation: calls
- `.normalizeScope()` → `EditApprovalDenied`  [AMBIGUOUS]
  app/Services/KkprlProposalEditApprovalService.php · relation: calls
- `.reject()` → `EditApprovalDenied`  [AMBIGUOUS]
  app/Services/KkprlProposalEditApprovalService.php · relation: calls
- `.request()` → `EditApprovalDenied`  [AMBIGUOUS]
  app/Services/KkprlProposalEditApprovalService.php · relation: calls
- `.startSession()` → `EditApprovalDenied`  [AMBIGUOUS]
  app/Services/KkprlProposalEditApprovalService.php · relation: calls
- `.startSession()` → `EditApprovalUsed`  [AMBIGUOUS]
  app/Services/KkprlProposalEditApprovalService.php · relation: calls
- `.startSession()` → `EditSessionExpired`  [AMBIGUOUS]
  app/Services/KkprlProposalEditApprovalService.php · relation: calls
- `.submit()` → `ProposalLocked`  [AMBIGUOUS]
  app/Services/KkprlProposalSubmissionService.php · relation: calls
- `.submit()` → `KkprlProposalReviewEvent`  [AMBIGUOUS]
  app/Services/KkprlProposalSubmissionService.php · relation: calls
- `.submit()` → `.request()`  [AMBIGUOUS]
  app/Services/KkprlProposalSubmissionService.php · relation: calls
- `.submit()` → `KkprlProposalEditSession`  [AMBIGUOUS]
  app/Services/KkprlProposalSubmissionService.php · relation: calls
- `.attachmentData()` → `PdfAttachmentRenderFailed`  [AMBIGUOUS]
  app/Services/KkprlProposalDocumentService.php · relation: calls
- `.generateChapterFormats()` → `ProposalLocked`  [AMBIGUOUS]
  app/Services/KkprlProposalDocumentService.php · relation: calls
- `.generateOne()` → `ProposalLocked`  [AMBIGUOUS]
  app/Services/KkprlProposalDocumentService.php · relation: calls
- `.generateOne()` → `.filled()`  [AMBIGUOUS]
  app/Services/KkprlProposalDocumentService.php · relation: calls
- `.viewData()` → `ProposalDocumentSectionCatalog`  [AMBIGUOUS]
  app/Services/KkprlProposalDocumentService.php · relation: calls
- `.viewData()` → `ProposalFieldCatalog`  [AMBIGUOUS]
  app/Services/KkprlProposalDocumentService.php · relation: calls
- `.chart_image_and_source_provenance_follow_snapshot_word_and_pdf_export()` → `KkprlPayloadFixture`  [AMBIGUOUS]
  tests/Feature/KkprlTideEvidenceTest.php · relation: calls
- `.latest_preview_correction_and_lock_are_rechecked_after_provider_returns()` → `KkprlProposalChat`  [AMBIGUOUS]
  tests/Feature/KkprlTideEvidenceTest.php · relation: calls
- `.spreadsheet_evidence_exports_as_a_calculation_summary_in_word_and_pdf()` → `KkprlPayloadFixture`  [AMBIGUOUS]
  tests/Feature/KkprlTideEvidenceTest.php · relation: calls
- `.getPages()` → `ListConsultationLocations`  [AMBIGUOUS]
  app/Filament/Layanankkprl/Resources/ConsultationLocations/ConsultationLocationResource.php · relation: calls
- `.getPages()` → `ListServices`  [AMBIGUOUS]
  app/Filament/Layanankkprl/Resources/Services/ServiceResource.php · relation: calls
- `.filtersSchema()` → `Service`  [AMBIGUOUS]
  app/Filament/Layanankkprl/Widgets/ActivityTypeChart.php · relation: calls
- `.filtersSchema()` → `ConsultationLocation`  [AMBIGUOUS]
  app/Filament/Layanankkprl/Widgets/ActivityTypeChart.php · relation: calls
- `.getData()` → `Client`  [AMBIGUOUS]
  app/Filament/Layanankkprl/Widgets/ActivityTypeChart.php · relation: calls
- `.filtersSchema()` → `Service`  [AMBIGUOUS]
  app/Filament/Layanankkprl/Widgets/MonthlyServiceChart.php · relation: calls
- `.filtersSchema()` → `ConsultationLocation`  [AMBIGUOUS]
  app/Filament/Layanankkprl/Widgets/MonthlyServiceChart.php · relation: calls
- `.getData()` → `Client`  [AMBIGUOUS]
  app/Filament/Layanankkprl/Widgets/MonthlyServiceChart.php · relation: calls
- `.getData()` → `Schedule`  [AMBIGUOUS]
  app/Filament/Layanankkprl/Widgets/MonthlyServiceChart.php · relation: calls
- `.filtersSchema()` → `Service`  [AMBIGUOUS]
  app/Filament/Layanankkprl/Widgets/ServiceLocationChart.php · relation: calls
- `.getData()` → `Client`  [AMBIGUOUS]
  app/Filament/Layanankkprl/Widgets/ServiceLocationChart.php · relation: calls
- `.getData()` → `ConsultationLocation`  [AMBIGUOUS]
  app/Filament/Layanankkprl/Widgets/ServiceLocationChart.php · relation: calls
- `.filtersSchema()` → `ConsultationLocation`  [AMBIGUOUS]
  app/Filament/Layanankkprl/Widgets/ServiceTypeChart.php · relation: calls
- `.getData()` → `Client`  [AMBIGUOUS]
  app/Filament/Layanankkprl/Widgets/ServiceTypeChart.php · relation: calls
- `.getData()` → `Service`  [AMBIGUOUS]
  app/Filament/Layanankkprl/Widgets/ServiceTypeChart.php · relation: calls
- `.baseQuery()` → `Client`  [AMBIGUOUS]
  app/Livewire/PublicDashboardCharts.php · relation: calls
- `.getMonthlyServiceChartConfigProperty()` → `Schedule`  [AMBIGUOUS]
  app/Livewire/PublicDashboardCharts.php · relation: calls
- `.getServiceLocationChartConfigProperty()` → `ConsultationLocation`  [AMBIGUOUS]
  app/Livewire/PublicDashboardCharts.php · relation: calls
- `.getServiceTypeChartConfigProperty()` → `Service`  [AMBIGUOUS]
  app/Livewire/PublicDashboardCharts.php · relation: calls
- `.normalizeDateRange()` → `.filled()`  [AMBIGUOUS]
  app/Livewire/PublicDashboardCharts.php · relation: calls
- `.render()` → `Service`  [AMBIGUOUS]
  app/Livewire/PublicDashboardCharts.php · relation: calls
- `.render()` → `ConsultationLocation`  [AMBIGUOUS]
  app/Livewire/PublicDashboardCharts.php · relation: calls
- `.getFaqs()` → `Faq`  [AMBIGUOUS]
  app/Services/ContentDeliveryService.php · relation: calls
- `.getRegulations()` → `Regulation`  [AMBIGUOUS]
  app/Services/ContentDeliveryService.php · relation: calls
- `.getServices()` → `Service`  [AMBIGUOUS]
  app/Services/ContentDeliveryService.php · relation: calls
- `.incrementDownloadCount()` → `Regulation`  [AMBIGUOUS]
  app/Services/ContentDeliveryService.php · relation: calls
- `.abortUnlessPublicPdf()` → `.filled()`  [AMBIGUOUS]
  app/Http/Controllers/LearningMaterialController.php · relation: calls
- `.download()` → `.download()`  [AMBIGUOUS]
  app/Http/Controllers/LearningMaterialController.php · relation: calls
- `.download()` → `.response()`  [AMBIGUOUS]
  app/Http/Controllers/LearningMaterialController.php · relation: calls
- `.pdf()` → `.response()`  [AMBIGUOUS]
  app/Http/Controllers/LearningMaterialController.php · relation: calls
- `.videoEmbedUrl()` → `.filled()`  [AMBIGUOUS]
  app/Models/LearningMaterial.php · relation: calls
- `.checkOfficerAvailability()` → `Assignment`  [AMBIGUOUS]
  app/Services/BookingAvailabilityService.php · relation: calls
- `.getAvailableSlots()` → `User`  [AMBIGUOUS]
  app/Services/BookingAvailabilityService.php · relation: calls
- `.getAvailableSlots()` → `Holiday`  [AMBIGUOUS]
  app/Services/BookingAvailabilityService.php · relation: calls
- `.validateOperationalHours()` → `Holiday`  [AMBIGUOUS]
  app/Services/BookingAvailabilityService.php · relation: calls
- `.addBusinessDays()` → `Holiday`  [AMBIGUOUS]
  app/Services/BusinessDayService.php · relation: calls
- `.backfillAttendees()` → `BeritaAcaraAttendee`  [AMBIGUOUS]
  app/Console/Commands/BackfillDataProtection.php · relation: calls
- `.backfillBeritaAcara()` → `BeritaAcara`  [AMBIGUOUS]
  app/Console/Commands/BackfillDataProtection.php · relation: calls
- `.backfillClients()` → `Client`  [AMBIGUOUS]
  app/Console/Commands/BackfillDataProtection.php · relation: calls
- `.backfillConsultationReports()` → `ConsultationReport`  [AMBIGUOUS]
  app/Console/Commands/BackfillDataProtection.php · relation: calls
- `.backfillNotificationLogs()` → `NotificationLog`  [AMBIGUOUS]
  app/Console/Commands/BackfillDataProtection.php · relation: calls
- `.backfillSatisfactionSurveys()` → `SatisfactionSurvey`  [AMBIGUOUS]
  app/Console/Commands/BackfillDataProtection.php · relation: calls
- `.backfillSchedules()` → `Schedule`  [AMBIGUOUS]
  app/Console/Commands/BackfillDataProtection.php · relation: calls
- `.countClientsMissingEncryptedIdentityFields()` → `Client`  [AMBIGUOUS]
  app/Console/Commands/BackfillDataProtection.php · relation: calls
- `.forEachSensitivePath()` → `Client`  [AMBIGUOUS]
  app/Console/Commands/BackfillDataProtection.php · relation: calls
- `.forEachSensitivePath()` → `ConsultationReport`  [AMBIGUOUS]
  app/Console/Commands/BackfillDataProtection.php · relation: calls
- `.forEachSensitivePath()` → `BeritaAcara`  [AMBIGUOUS]
  app/Console/Commands/BackfillDataProtection.php · relation: calls
- `.forEachSensitivePath()` → `BeritaAcaraAttendee`  [AMBIGUOUS]
  app/Console/Commands/BackfillDataProtection.php · relation: calls
- `.hasValue()` → `.filled()`  [AMBIGUOUS]
  app/Console/Commands/BackfillDataProtection.php · relation: calls
- `.logSummary()` → `.log()`  [AMBIGUOUS]
  app/Console/Commands/BackfillDataProtection.php · relation: calls
- `.reportDryRunCounts()` → `Client`  [AMBIGUOUS]
  app/Console/Commands/BackfillDataProtection.php · relation: calls
- `.reportDryRunCounts()` → `BeritaAcara`  [AMBIGUOUS]
  app/Console/Commands/BackfillDataProtection.php · relation: calls
- `.reportDryRunCounts()` → `BeritaAcaraAttendee`  [AMBIGUOUS]
  app/Console/Commands/BackfillDataProtection.php · relation: calls
- `.submit()` → `BeritaAcaraAttendee`  [AMBIGUOUS]
  app/Livewire/PublicAttendance.php · relation: calls
- `.populateProtectionHashes()` → `.token()`  [AMBIGUOUS]
  app/Models/BeritaAcaraAttendee.php · relation: calls
- `.populateProtectionHashes()` → `.email()`  [AMBIGUOUS]
  app/Models/BeritaAcaraAttendee.php · relation: calls
- `.protectedAttribute()` → `.filled()`  [AMBIGUOUS]
  app/Models/BeritaAcaraAttendee.php · relation: calls
- `.generateTideChart()` → `InvalidTideData`  [AMBIGUOUS]
  app/Livewire/Concerns/InteractsWithKkprlTideCharts.php · relation: calls
- `.generateTideChart()` → `KkprlProposal`  [AMBIGUOUS]
  app/Livewire/Concerns/InteractsWithKkprlTideCharts.php · relation: calls
- `.booted()` → `InvalidAttachmentFile`  [AMBIGUOUS]
  app/Models/KkprlProposalAttachment.php · relation: calls
- `.booted()` → `KkprlProposal`  [AMBIGUOUS]
  app/Models/KkprlProposalAttachment.php · relation: calls
- `.booted()` → `ProposalLocked`  [AMBIGUOUS]
  app/Models/KkprlProposalAttachment.php · relation: calls
- `.assertPlacement()` → `InvalidAttachmentFile`  [AMBIGUOUS]
  app/Services/KkprlProposalAttachmentService.php · relation: calls
- `.delete()` → `ProposalLocked`  [AMBIGUOUS]
  app/Services/KkprlProposalAttachmentService.php · relation: calls
- `.delete()` → `InvalidAttachmentFile`  [AMBIGUOUS]
  app/Services/KkprlProposalAttachmentService.php · relation: calls
- `.delete()` → `.delete()`  [AMBIGUOUS]
  app/Services/KkprlProposalAttachmentService.php · relation: calls
- `.inspect()` → `.log()`  [AMBIGUOUS]
  app/Services/KkprlProposalAttachmentService.php · relation: calls
- `.inspect()` → `InvalidAttachmentFile`  [AMBIGUOUS]
  app/Services/KkprlProposalAttachmentService.php · relation: calls
- `.replace()` → `ProposalLocked`  [AMBIGUOUS]
  app/Services/KkprlProposalAttachmentService.php · relation: calls
- `.replace()` → `InvalidAttachmentFile`  [AMBIGUOUS]
  app/Services/KkprlProposalAttachmentService.php · relation: calls
- `.replace()` → `.delete()`  [AMBIGUOUS]
  app/Services/KkprlProposalAttachmentService.php · relation: calls
- `.storeMany()` → `.delete()`  [AMBIGUOUS]
  app/Services/KkprlProposalAttachmentService.php · relation: calls
- `.storeMany()` → `ProposalLocked`  [AMBIGUOUS]
  app/Services/KkprlProposalAttachmentService.php · relation: calls
- `.storeMany()` → `InvalidAttachmentFile`  [AMBIGUOUS]
  app/Services/KkprlProposalAttachmentService.php · relation: calls
- `.form()` → `SignaturePad`  [AMBIGUOUS]
  app/Filament/Layanankkprl/Resources/Clients/ClientResource/RelationManagers/BeritaAcaraRelationManager.php · relation: calls
- `.table()` → `.response()`  [AMBIGUOUS]
  app/Filament/Layanankkprl/Resources/Clients/ClientResource/RelationManagers/BeritaAcaraRelationManager.php · relation: calls
- `.form()` → `ClientForm`  [AMBIGUOUS]
  app/Filament/Layanankkprl/Resources/Clients/ClientResource.php · relation: calls
- `.infolist()` → `ClientInfolist`  [AMBIGUOUS]
  app/Filament/Layanankkprl/Resources/Clients/ClientResource.php · relation: calls
- `.configure()` → `.filled()`  [AMBIGUOUS]
  app/Filament/Layanankkprl/Resources/Clients/Schemas/ClientInfolist.php · relation: calls
- `.form()` → `FaqForm`  [AMBIGUOUS]
  app/Filament/Layanankkprl/Resources/Faqs/FaqResource.php · relation: calls
- `.form()` → `HolidayForm`  [AMBIGUOUS]
  app/Filament/Layanankkprl/Resources/Holidays/HolidayResource.php · relation: calls
- `.form()` → `LearningCategoryForm`  [AMBIGUOUS]
  app/Filament/Layanankkprl/Resources/LearningCategories/LearningCategoryResource.php · relation: calls
- `.infolist()` → `LearningCategoryInfolist`  [AMBIGUOUS]
  app/Filament/Layanankkprl/Resources/LearningCategories/LearningCategoryResource.php · relation: calls
- `.form()` → `LearningGroupForm`  [AMBIGUOUS]
  app/Filament/Layanankkprl/Resources/LearningGroups/LearningGroupResource.php · relation: calls
- `.infolist()` → `LearningGroupInfolist`  [AMBIGUOUS]
  app/Filament/Layanankkprl/Resources/LearningGroups/LearningGroupResource.php · relation: calls
- `.form()` → `LearningMaterialForm`  [AMBIGUOUS]
  app/Filament/Layanankkprl/Resources/LearningMaterials/LearningMaterialResource.php · relation: calls
- `.infolist()` → `LearningMaterialInfolist`  [AMBIGUOUS]
  app/Filament/Layanankkprl/Resources/LearningMaterials/LearningMaterialResource.php · relation: calls
- `.configure()` → `.filled()`  [AMBIGUOUS]
  app/Filament/Layanankkprl/Resources/LearningMaterials/Schemas/LearningMaterialInfolist.php · relation: calls
- `.form()` → `RegulationForm`  [AMBIGUOUS]
  app/Filament/Layanankkprl/Resources/Regulations/RegulationResource.php · relation: calls
- `.form()` → `SatisfactionSurveyResultForm`  [AMBIGUOUS]
  app/Filament/Layanankkprl/Resources/SatisfactionSurveyResults/SatisfactionSurveyResultResource.php · relation: calls
- `.configure()` → `SatisfactionSurveyResult`  [AMBIGUOUS]
  app/Filament/Layanankkprl/Resources/SatisfactionSurveyResults/Schemas/SatisfactionSurveyResultForm.php · relation: calls
- `.configure()` → `ServicePerformanceResult`  [AMBIGUOUS]
  app/Filament/Layanankkprl/Resources/ServicePerformanceResults/Schemas/ServicePerformanceResultForm.php · relation: calls
- `.form()` → `ServicePerformanceResultForm`  [AMBIGUOUS]
  app/Filament/Layanankkprl/Resources/ServicePerformanceResults/ServicePerformanceResultResource.php · relation: calls
- `.form()` → `ServiceForm`  [AMBIGUOUS]
  app/Filament/Layanankkprl/Resources/Services/ServiceResource.php · relation: calls
- `.conflicts()` → `Assignment`  [AMBIGUOUS]
  app/Actions/Mobile/CreateAssignments.php · relation: calls
- `.handle()` → `User`  [AMBIGUOUS]
  app/Actions/Mobile/CreateAssignments.php · relation: calls
- `.handle()` → `Assignment`  [AMBIGUOUS]
  app/Actions/Mobile/CreateAssignments.php · relation: calls
- `.checkIfRevertToWaiting()` → `StatusWaitingMail`  [AMBIGUOUS]
  app/Observers/AssignmentObserver.php · relation: calls
- `.checkIfRevertToWaiting()` → `.log()`  [AMBIGUOUS]
  app/Observers/AssignmentObserver.php · relation: calls
- `.created()` → `StaffAssigned`  [AMBIGUOUS]
  app/Observers/AssignmentObserver.php · relation: calls
- `.created()` → `.log()`  [AMBIGUOUS]
  app/Observers/AssignmentObserver.php · relation: calls
- `.created()` → `StatusScheduledMail`  [AMBIGUOUS]
  app/Observers/AssignmentObserver.php · relation: calls
- `.getPages()` → `ListLearningActivities`  [AMBIGUOUS]
  app/Filament/Layanankkprl/Resources/LearningActivities/LearningActivityResource.php · relation: calls
- `.table()` → `LearningActivityLog`  [AMBIGUOUS]
  app/Filament/Layanankkprl/Resources/LearningActivities/LearningActivityResource.php · relation: calls
- `.table()` → `LearningMaterialAccess`  [AMBIGUOUS]
  app/Filament/Layanankkprl/Resources/LearningActivities/LearningActivityResource.php · relation: calls
- `.getPages()` → `ListLearningSessions`  [AMBIGUOUS]
  app/Filament/Layanankkprl/Resources/LearningSessions/LearningSessionResource.php · relation: calls
- `.table()` → `LearningMaterialAccess`  [AMBIGUOUS]
  app/Filament/Layanankkprl/Resources/LearningSessions/LearningSessionResource.php · relation: calls
- `.learning_admin_resources_are_registered_in_layanankkprl_navigation_group()` → `LearningSessionResource`  [AMBIGUOUS]
  tests/Feature/BelajarKkprlTest.php · relation: calls
- `.learning_admin_resources_are_registered_in_layanankkprl_navigation_group()` → `LearningActivityResource`  [AMBIGUOUS]
  tests/Feature/BelajarKkprlTest.php · relation: calls
- `.beritaAcara()` → `.beritaAcara()`  [AMBIGUOUS]
  app/Http/Controllers/Api/Mobile/V1/MobileFileController.php · relation: calls
- `.exists()` → `.exists()`  [AMBIGUOUS]
  app/Services/PrivateFileService.php · relation: calls
- `.get()` → `.exists()`  [AMBIGUOUS]
  app/Services/PrivateFileService.php · relation: calls
- `.get()` → `.get()`  [AMBIGUOUS]
  app/Services/PrivateFileService.php · relation: calls
- `.getPages()` → `ListKkprlProposalEditApprovals`  [AMBIGUOUS]
  app/Filament/Layanankkprl/Resources/KkprlProposalEditApprovals/KkprlProposalEditApprovalResource.php · relation: calls
- `.table()` → `KkprlProposalEditApprovalsTable`  [AMBIGUOUS]
  app/Filament/Layanankkprl/Resources/KkprlProposalEditApprovals/KkprlProposalEditApprovalResource.php · relation: calls
- `.getPages()` → `ListKkprlProposals`  [AMBIGUOUS]
  app/Filament/Layanankkprl/Resources/KkprlProposals/KkprlProposalResource.php · relation: calls
- `.getPages()` → `ViewKkprlProposal`  [AMBIGUOUS]
  app/Filament/Layanankkprl/Resources/KkprlProposals/KkprlProposalResource.php · relation: calls
- `.infolist()` → `KkprlProposalInfolist`  [AMBIGUOUS]
  app/Filament/Layanankkprl/Resources/KkprlProposals/KkprlProposalResource.php · relation: calls
- `.table()` → `KkprlProposalsTable`  [AMBIGUOUS]
  app/Filament/Layanankkprl/Resources/KkprlProposals/KkprlProposalResource.php · relation: calls
- `.chapterText()` → `ProposalFieldCatalog`  [AMBIGUOUS]
  app/Filament/Layanankkprl/Resources/KkprlProposals/Schemas/KkprlProposalInfolist.php · relation: calls
- `.chapterText()` → `ProposalProgress`  [AMBIGUOUS]
  app/Filament/Layanankkprl/Resources/KkprlProposals/Schemas/KkprlProposalInfolist.php · relation: calls
- `.getConsultationLocationsProperty()` → `ConsultationLocation`  [AMBIGUOUS]
  app/Livewire/BookingWizard.php · relation: calls
- `.getServicesProperty()` → `Service`  [AMBIGUOUS]
  app/Livewire/BookingWizard.php · relation: calls
- `.normalizeSupportingDocumentLinks()` → `.filled()`  [AMBIGUOUS]
  app/Livewire/BookingWizard.php · relation: calls
- `.selectLocation()` → `ConsultationLocation`  [AMBIGUOUS]
  app/Livewire/BookingWizard.php · relation: calls
- `.selectService()` → `Service`  [AMBIGUOUS]
  app/Livewire/BookingWizard.php · relation: calls
- `.submit()` → `Client`  [AMBIGUOUS]
  app/Livewire/BookingWizard.php · relation: calls
- `.submit()` → `Schedule`  [AMBIGUOUS]
  app/Livewire/BookingWizard.php · relation: calls
- `.signInApplicant()` → `.session()`  [AMBIGUOUS]
  tests/Support/KkprlApplicantFixture.php · relation: calls
- `.getData()` → `Activity`  [AMBIGUOUS]
  app/Filament/Widgets/ActivityPerMonthChart.php · relation: calls
- `.getData()` → `Activity`  [AMBIGUOUS]
  app/Filament/Widgets/ActivityPerProvinceChart.php · relation: calls
- `.getData()` → `Activity`  [AMBIGUOUS]
  app/Filament/Widgets/OrganizerComparisonChart.php · relation: calls
- `.assertAvailable()` → `.filled()`  [AMBIGUOUS]
  app/Http/Controllers/ProposalGuideController.php · relation: calls
- `.download()` → `.download()`  [AMBIGUOUS]
  app/Http/Controllers/ProposalGuideController.php · relation: calls
- `.download()` → `.response()`  [AMBIGUOUS]
  app/Http/Controllers/ProposalGuideController.php · relation: calls
- `.pdf()` → `.response()`  [AMBIGUOUS]
  app/Http/Controllers/ProposalGuideController.php · relation: calls
- `.test_admin_access_and_actions_require_existing_material_permissions()` → `User`  [AMBIGUOUS]
  tests/Feature/ProposalGuideTest.php · relation: calls
- `.test_admin_access_and_actions_require_existing_material_permissions()` → `ProposalGuide`  [AMBIGUOUS]
  tests/Feature/ProposalGuideTest.php · relation: calls
- `.test_admin_form_rejects_non_pdf_oversized_and_forged_paths()` → `User`  [AMBIGUOUS]
  tests/Feature/ProposalGuideTest.php · relation: calls
- `.test_authorized_admin_can_add_and_update_pdf_through_the_admin_form()` → `User`  [AMBIGUOUS]
  tests/Feature/ProposalGuideTest.php · relation: calls
- `.test_authorized_admin_can_add_and_update_pdf_through_the_admin_form()` → `ProposalGuide`  [AMBIGUOUS]
  tests/Feature/ProposalGuideTest.php · relation: calls
- `.test_draft_and_missing_files_are_not_served_and_empty_page_remains_useful()` → `ProposalGuide`  [AMBIGUOUS]
  tests/Feature/ProposalGuideTest.php · relation: calls
- `.test_migration_moves_only_the_legacy_guide_preserves_history_and_redirects_old_urls()` → `LearningCategory`  [AMBIGUOUS]
  tests/Feature/ProposalGuideTest.php · relation: calls
- `.test_migration_moves_only_the_legacy_guide_preserves_history_and_redirects_old_urls()` → `LearningGroup`  [AMBIGUOUS]
  tests/Feature/ProposalGuideTest.php · relation: calls
- `.test_migration_moves_only_the_legacy_guide_preserves_history_and_redirects_old_urls()` → `LearningMaterial`  [AMBIGUOUS]
  tests/Feature/ProposalGuideTest.php · relation: calls
- `.test_migration_moves_only_the_legacy_guide_preserves_history_and_redirects_old_urls()` → `ProposalGuide`  [AMBIGUOUS]
  tests/Feature/ProposalGuideTest.php · relation: calls
- `.test_pdf_and_download_follow_updates_without_changing_the_url()` → `ProposalGuide`  [AMBIGUOUS]
  tests/Feature/ProposalGuideTest.php · relation: calls
- `.test_public_page_is_independent_and_shows_only_published_guides_in_order()` → `ProposalGuide`  [AMBIGUOUS]
  tests/Feature/ProposalGuideTest.php · relation: calls
- `.getStats()` → `Service`  [AMBIGUOUS]
  app/Filament/Layanankkprl/Resources/Clients/Widgets/ClientStatsOverview.php · relation: calls
- `.getStats()` → `LearningMaterialAccess`  [AMBIGUOUS]
  app/Filament/Layanankkprl/Widgets/LearningAnalyticsOverview.php · relation: calls
- `.getStats()` → `LearningMaterialOpen`  [AMBIGUOUS]
  app/Filament/Layanankkprl/Widgets/LearningAnalyticsOverview.php · relation: calls
- `.getStats()` → `LearningSession`  [AMBIGUOUS]
  app/Filament/Layanankkprl/Widgets/LearningAnalyticsOverview.php · relation: calls
- `.getStats()` → `LearningActivityLog`  [AMBIGUOUS]
  app/Filament/Layanankkprl/Widgets/LearningAnalyticsOverview.php · relation: calls
- `.attachment_manifest_change_creates_a_new_immutable_document_version()` → `KkprlPayloadFixture`  [AMBIGUOUS]
  tests/Feature/KkprlProposalDocumentGenerationTest.php · relation: calls
- `.configured_pdf_converter_renders_scanned_pdf_pages_for_both_formats()` → `KkprlPayloadFixture`  [AMBIGUOUS]
  tests/Feature/KkprlProposalDocumentGenerationTest.php · relation: calls
- `.configured_real_pdf_renderer_renders_a_valid_image_only_pdf()` → `KkprlPayloadFixture`  [AMBIGUOUS]
  tests/Feature/KkprlProposalDocumentGenerationTest.php · relation: calls
- `.every_active_chapter_exports_with_its_official_title_in_both_formats()` → `KkprlPayloadFixture`  [AMBIGUOUS]
  tests/Feature/KkprlProposalDocumentGenerationTest.php · relation: calls
- `.generation_fails_when_an_attachment_binary_is_missing()` → `KkprlPayloadFixture`  [AMBIGUOUS]
  tests/Feature/KkprlProposalDocumentGenerationTest.php · relation: calls
- `.retry_does_not_overwrite_an_existing_generated_artifact()` → `KkprlPayloadFixture`  [AMBIGUOUS]
  tests/Feature/KkprlProposalDocumentGenerationTest.php · relation: calls
- `.scanned_pdf_without_renderer_fails_generation_instead_of_using_placeholder_text()` → `KkprlPayloadFixture`  [AMBIGUOUS]
  tests/Feature/KkprlProposalDocumentGenerationTest.php · relation: calls
- `.schedule_rows_render_as_a_repeatable_table_in_both_formats()` → `KkprlPayloadFixture`  [AMBIGUOUS]
  tests/Feature/KkprlProposalDocumentGenerationTest.php · relation: calls
- `.snapshot_rejects_a_legacy_attachment_on_a_non_private_disk()` → `KkprlPayloadFixture`  [AMBIGUOUS]
  tests/Feature/KkprlProposalDocumentGenerationTest.php · relation: calls
- `.word_and_pdf_use_the_same_locked_snapshot_and_manifest()` → `.file()`  [AMBIGUOUS]
  tests/Feature/KkprlProposalDocumentGenerationTest.php · relation: calls
- `.word_and_pdf_use_the_same_locked_snapshot_and_manifest()` → `KkprlPayloadFixture`  [AMBIGUOUS]
  tests/Feature/KkprlProposalDocumentGenerationTest.php · relation: calls
- `.snapshot_excludes_inactive_conditional_payload()` → `KkprlPayloadFixture`  [AMBIGUOUS]
  tests/Unit/Kkprl/ProposalSnapshotTest.php · relation: calls
- `.snapshot_filters_stale_nested_bag_five_fields()` → `KkprlPayloadFixture`  [AMBIGUOUS]
  tests/Unit/Kkprl/ProposalSnapshotTest.php · relation: calls
- `.snapshot_is_deterministic_and_manifest_is_chapter_scoped()` → `KkprlPayloadFixture`  [AMBIGUOUS]
  tests/Unit/Kkprl/ProposalSnapshotTest.php · relation: calls
- `.attendanceLink()` → `.response()`  [AMBIGUOUS]
  app/Http/Controllers/Api/Mobile/V1/BeritaAcaraController.php · relation: calls
- `.persist()` → `.store()`  [AMBIGUOUS]
  app/Http/Controllers/Api/Mobile/V1/BeritaAcaraController.php · relation: calls
- `.persist()` → `.update()`  [AMBIGUOUS]
  app/Http/Controllers/Api/Mobile/V1/BeritaAcaraController.php · relation: calls
- `.show()` → `.response()`  [AMBIGUOUS]
  app/Http/Controllers/Api/Mobile/V1/BeritaAcaraController.php · relation: calls
- `.signingLinks()` → `.response()`  [AMBIGUOUS]
  app/Http/Controllers/Api/Mobile/V1/BeritaAcaraController.php · relation: calls
- `.store()` → `.response()`  [AMBIGUOUS]
  app/Http/Controllers/Api/Mobile/V1/BeritaAcaraController.php · relation: calls
- `.storeFiles()` → `.store()`  [AMBIGUOUS]
  app/Http/Controllers/Api/Mobile/V1/BeritaAcaraController.php · relation: calls
- `.update()` → `.response()`  [AMBIGUOUS]
  app/Http/Controllers/Api/Mobile/V1/BeritaAcaraController.php · relation: calls
- `.delete()` → `.delete()`  [AMBIGUOUS]
  app/Services/SignatureService.php · relation: calls
- `.getPages()` → `ListFaqs`  [AMBIGUOUS]
  app/Filament/Layanankkprl/Resources/Faqs/FaqResource.php · relation: calls
- `.getPages()` → `CreateFaq`  [AMBIGUOUS]
  app/Filament/Layanankkprl/Resources/Faqs/FaqResource.php · relation: calls
- `.getPages()` → `EditFaq`  [AMBIGUOUS]
  app/Filament/Layanankkprl/Resources/Faqs/FaqResource.php · relation: calls
- `.getPages()` → `ListHolidays`  [AMBIGUOUS]
  app/Filament/Layanankkprl/Resources/Holidays/HolidayResource.php · relation: calls
- `.getPages()` → `CreateHoliday`  [AMBIGUOUS]
  app/Filament/Layanankkprl/Resources/Holidays/HolidayResource.php · relation: calls
- `.getPages()` → `EditHoliday`  [AMBIGUOUS]
  app/Filament/Layanankkprl/Resources/Holidays/HolidayResource.php · relation: calls
- `.index()` → `.response()`  [AMBIGUOUS]
  app/Http/Controllers/Api/Mobile/V1/ConsultationReportController.php · relation: calls
- `.store()` → `.response()`  [AMBIGUOUS]
  app/Http/Controllers/Api/Mobile/V1/ConsultationReportController.php · relation: calls
- `.storeDocumentation()` → `.store()`  [AMBIGUOUS]
  app/Http/Controllers/Api/Mobile/V1/ConsultationReportController.php · relation: calls
- `.storeSignature()` → `.store()`  [AMBIGUOUS]
  app/Http/Controllers/Api/Mobile/V1/ConsultationReportController.php · relation: calls
- `.update()` → `.update()`  [AMBIGUOUS]
  app/Http/Controllers/Api/Mobile/V1/ConsultationReportController.php · relation: calls
- `.update()` → `.response()`  [AMBIGUOUS]
  app/Http/Controllers/Api/Mobile/V1/ConsultationReportController.php · relation: calls
- `.protectedAttribute()` → `.filled()`  [AMBIGUOUS]
  app/Models/BeritaAcara.php · relation: calls
- `.askGemini()` → `AiChatLog`  [AMBIGUOUS]
  app/Services/AiService.php · relation: calls
- `.searchContext()` → `Faq`  [AMBIGUOUS]
  app/Services/AiService.php · relation: calls
- `.searchContext()` → `RegulationChunk`  [AMBIGUOUS]
  app/Services/AiService.php · relation: calls
- `.searchContext()` → `Regulation`  [AMBIGUOUS]
  app/Services/AiService.php · relation: calls
- `.chunkDocument()` → `RegulationChunk`  [AMBIGUOUS]
  app/Services/DocumentChunkerService.php · relation: calls
- `.protectedAttribute()` → `.filled()`  [AMBIGUOUS]
  app/Models/SatisfactionSurvey.php · relation: calls
- `.complete_wizard_can_submit_and_then_becomes_locked()` → `ProposalProgress`  [AMBIGUOUS]
  tests/Feature/KkprlProposalWizardTest.php · relation: calls
- `.complete_wizard_can_submit_and_then_becomes_locked()` → `KkprlPayloadFixture`  [AMBIGUOUS]
  tests/Feature/KkprlProposalWizardTest.php · relation: calls
- `.map_edits_are_saved_and_invalid_coordinates_block_submission()` → `KkprlPayloadFixture`  [AMBIGUOUS]
  tests/Feature/KkprlProposalWizardTest.php · relation: calls
- `.stale_conditional_documents_are_not_rendered_after_condition_is_disabled()` → `ProposalProgress`  [AMBIGUOUS]
  tests/Feature/KkprlProposalWizardTest.php · relation: calls
- `.stale_conditional_documents_are_not_rendered_after_condition_is_disabled()` → `KkprlPayloadFixture`  [AMBIGUOUS]
  tests/Feature/KkprlProposalWizardTest.php · relation: calls
- `.table()` → `.form()`  [AMBIGUOUS]
  app/Filament/Layanankkprl/Resources/ConsultationReportResource.php · relation: calls
- `.form()` → `BeritaAcaraRelationManager`  [AMBIGUOUS]
  app/Filament/Layanankkprl/Resources/Users/UserResource.php · relation: calls
- `.form()` → `.filled()`  [AMBIGUOUS]
  app/Filament/Layanankkprl/Resources/Users/UserResource.php · relation: calls
- `.graph_and_profile_provenance_survive_snapshot_and_word_pdf_export()` → `KkprlPayloadFixture`  [AMBIGUOUS]
  tests/Feature/KkprlSeabedProfileTest.php · relation: calls
- `.fail()` → `.log()`  [AMBIGUOUS]
  app/Services/KkprlAiService.php · relation: calls
- `.availableQuarters()` → `ServicePerformanceResult`  [AMBIGUOUS]
  app/Livewire/ServicePerformanceResults.php · relation: calls
- `.availableYears()` → `ServicePerformanceResult`  [AMBIGUOUS]
  app/Livewire/ServicePerformanceResults.php · relation: calls
- `.mount()` → `.request()`  [AMBIGUOUS]
  app/Livewire/ServicePerformanceResults.php · relation: calls
- `.publicFeedbackQuery()` → `PublicFeedback`  [AMBIGUOUS]
  app/Livewire/ServicePerformanceResults.php · relation: calls
- `.render()` → `ServicePerformanceResult`  [AMBIGUOUS]
  app/Livewire/ServicePerformanceResults.php · relation: calls
- `.test_additive_migration_preserves_legacy_proposal_chat_attachment_document_and_revision()` → `KkprlPayloadFixture`  [AMBIGUOUS]
  tests/Feature/KkprlApplicantMigrationTest.php · relation: calls
- `.test_additive_migration_preserves_legacy_proposal_chat_attachment_document_and_revision()` → `KkprlProposalChat`  [AMBIGUOUS]
  tests/Feature/KkprlApplicantMigrationTest.php · relation: calls
- `.test_additive_migration_preserves_legacy_proposal_chat_attachment_document_and_revision()` → `KkprlProposalReviewEvent`  [AMBIGUOUS]
  tests/Feature/KkprlApplicantMigrationTest.php · relation: calls
- `.complete_draft_becomes_locked_submitted()` → `KkprlPayloadFixture`  [AMBIGUOUS]
  tests/Feature/KkprlProposalSubmissionTest.php · relation: calls
- `.document_generation_failure_keeps_proposal_as_draft()` → `KkprlPayloadFixture`  [AMBIGUOUS]
  tests/Feature/KkprlProposalSubmissionTest.php · relation: calls
- `.invalid_manual_coordinates_block_submission()` → `KkprlPayloadFixture`  [AMBIGUOUS]
  tests/Feature/KkprlProposalSubmissionTest.php · relation: calls
- `.review_events_are_append_only()` → `KkprlProposalReviewEvent`  [AMBIGUOUS]
  tests/Feature/KkprlProposalSubmissionTest.php · relation: calls
- `.review_events_cannot_be_deleted()` → `KkprlProposalReviewEvent`  [AMBIGUOUS]
  tests/Feature/KkprlProposalSubmissionTest.php · relation: calls
- `.submitted_model_rejects_direct_payload_mutation()` → `KkprlPayloadFixture`  [AMBIGUOUS]
  tests/Feature/KkprlProposalSubmissionTest.php · relation: calls
- `.submitted_proposal_cannot_be_reopened_as_a_draft()` → `KkprlPayloadFixture`  [AMBIGUOUS]
  tests/Feature/KkprlProposalSubmissionTest.php · relation: calls
- `.active_revision_shows_the_petugas_revision_note()` → `KkprlProposalReviewEvent`  [AMBIGUOUS]
  tests/Feature/KkprlProposalWizardTest.php · relation: calls
- `.activeUsers()` → `User`  [AMBIGUOUS]
  app/Services/MobileNotificationService.php · relation: calls
- `.assignmentChanged()` → `User`  [AMBIGUOUS]
  app/Services/MobileNotificationService.php · relation: calls
- `.dispatch()` → `MobileOperationalNotification`  [AMBIGUOUS]
  app/Services/MobileNotificationService.php · relation: calls
- `.dispatch()` → `SendMobilePush`  [AMBIGUOUS]
  app/Services/MobileNotificationService.php · relation: calls
- `.getPages()` → `ListLearningCategories`  [AMBIGUOUS]
  app/Filament/Layanankkprl/Resources/LearningCategories/LearningCategoryResource.php · relation: calls
- `.getPages()` → `CreateLearningCategory`  [AMBIGUOUS]
  app/Filament/Layanankkprl/Resources/LearningCategories/LearningCategoryResource.php · relation: calls
- `.getPages()` → `EditLearningCategory`  [AMBIGUOUS]
  app/Filament/Layanankkprl/Resources/LearningCategories/LearningCategoryResource.php · relation: calls
- `.publicIndex()` → `.response()`  [AMBIGUOUS]
  app/Http/Controllers/Api/Mobile/V1/FeedbackController.php · relation: calls
- `.publicShow()` → `.response()`  [AMBIGUOUS]
  app/Http/Controllers/Api/Mobile/V1/FeedbackController.php · relation: calls
- `.satisfactionIndex()` → `.response()`  [AMBIGUOUS]
  app/Http/Controllers/Api/Mobile/V1/FeedbackController.php · relation: calls
- `.satisfactionShow()` → `.response()`  [AMBIGUOUS]
  app/Http/Controllers/Api/Mobile/V1/FeedbackController.php · relation: calls
- `.report()` → `.filled()`  [AMBIGUOUS]
  app/Services/MobileTransformer.php · relation: calls
- `.clearChat()` → `AiChatLog`  [AMBIGUOUS]
  app/Livewire/AiChatWidget.php · relation: calls
- `.clearChat()` → `.session()`  [AMBIGUOUS]
  app/Livewire/AiChatWidget.php · relation: calls
- `.mount()` → `AiChatLog`  [AMBIGUOUS]
  app/Livewire/AiChatWidget.php · relation: calls
- `.mount()` → `.session()`  [AMBIGUOUS]
  app/Livewire/AiChatWidget.php · relation: calls
- `.sendMessage()` → `.session()`  [AMBIGUOUS]
  app/Livewire/AiChatWidget.php · relation: calls
- `.sendMessage()` → `.request()`  [AMBIGUOUS]
  app/Livewire/AiChatWidget.php · relation: calls
- `.test_legacy_session_and_ticket_routes_do_not_authenticate()` → `.session()`  [AMBIGUOUS]
  tests/Feature/KkprlApplicantAccessTest.php · relation: calls
- `.test_reset_rejects_old_credentials_and_every_old_session_version()` → `User`  [AMBIGUOUS]
  tests/Feature/KkprlApplicantBoundaryTest.php · relation: calls
- `.test_reset_rejects_old_credentials_and_every_old_session_version()` → `.session()`  [AMBIGUOUS]
  tests/Feature/KkprlApplicantBoundaryTest.php · relation: calls
- `.applicant_can_create_draft_and_receive_session_access()` → `.session()`  [AMBIGUOUS]
  tests/Feature/KkprlProposalAccessTest.php · relation: calls
- `.chart_is_private_downloadable_and_does_not_replace_manual_narrative()` → `.session()`  [AMBIGUOUS]
  tests/Feature/KkprlTideEvidenceTest.php · relation: calls
- `.getPages()` → `ListLearningGroups`  [AMBIGUOUS]
  app/Filament/Layanankkprl/Resources/LearningGroups/LearningGroupResource.php · relation: calls
- `.getPages()` → `CreateLearningGroup`  [AMBIGUOUS]
  app/Filament/Layanankkprl/Resources/LearningGroups/LearningGroupResource.php · relation: calls
- `.getPages()` → `EditLearningGroup`  [AMBIGUOUS]
  app/Filament/Layanankkprl/Resources/LearningGroups/LearningGroupResource.php · relation: calls
- `.getPages()` → `ListLearningMaterials`  [AMBIGUOUS]
  app/Filament/Layanankkprl/Resources/LearningMaterials/LearningMaterialResource.php · relation: calls
- `.getPages()` → `CreateLearningMaterial`  [AMBIGUOUS]
  app/Filament/Layanankkprl/Resources/LearningMaterials/LearningMaterialResource.php · relation: calls
- `.getPages()` → `EditLearningMaterial`  [AMBIGUOUS]
  app/Filament/Layanankkprl/Resources/LearningMaterials/LearningMaterialResource.php · relation: calls
- `.afterCreate()` → `ChunkRegulationJob`  [AMBIGUOUS]
  app/Filament/Layanankkprl/Resources/Regulations/Pages/CreateRegulation.php · relation: calls
- `.getPages()` → `ListServicePerformanceResults`  [AMBIGUOUS]
  app/Filament/Layanankkprl/Resources/ServicePerformanceResults/ServicePerformanceResultResource.php · relation: calls
- `.getPages()` → `CreateServicePerformanceResult`  [AMBIGUOUS]
  app/Filament/Layanankkprl/Resources/ServicePerformanceResults/ServicePerformanceResultResource.php · relation: calls
- `.getPages()` → `EditServicePerformanceResult`  [AMBIGUOUS]
  app/Filament/Layanankkprl/Resources/ServicePerformanceResults/ServicePerformanceResultResource.php · relation: calls
- `.confirmAndSave()` → `ProposalFieldCatalog`  [AMBIGUOUS]
  app/Livewire/KkprlAiChat.php · relation: calls
- `.confirmAndSave()` → `.session()`  [AMBIGUOUS]
  app/Livewire/KkprlAiChat.php · relation: calls
- `.getSystemPrompt()` → `ProposalFieldCatalog`  [AMBIGUOUS]
  app/Livewire/KkprlAiChat.php · relation: calls
- `.loadChatHistory()` → `KkprlProposalChat`  [AMBIGUOUS]
  app/Livewire/KkprlAiChat.php · relation: calls
- `.saveChatHistory()` → `KkprlProposalChat`  [AMBIGUOUS]
  app/Livewire/KkprlAiChat.php · relation: calls
- `.sendMessage()` → `ProposalFieldCatalog`  [AMBIGUOUS]
  app/Livewire/KkprlAiChat.php · relation: calls
- `.sendMessage()` → `.session()`  [AMBIGUOUS]
  app/Livewire/KkprlAiChat.php · relation: calls
- `.updatedPreviewData()` → `.session()`  [AMBIGUOUS]
  app/Livewire/KkprlAiChat.php · relation: calls
- `.protectedAttribute()` → `.filled()`  [AMBIGUOUS]
  app/Models/NotificationLog.php · relation: calls
- `.getPages()` → `ManageProposalGuides`  [AMBIGUOUS]
  app/Filament/Layanankkprl/Resources/ProposalGuides/ProposalGuideResource.php · relation: calls
- `.arbitrary_coordinate_text_and_unknown_shapes_are_rejected()` → `KkprlPayloadFixture`  [AMBIGUOUS]
  tests/Unit/Kkprl/ProposalProgressTest.php · relation: calls
- `.conditional_decisions_are_required_in_base_chapter()` → `ProposalProgress`  [AMBIGUOUS]
  tests/Unit/Kkprl/ProposalProgressTest.php · relation: calls
- `.conditional_decisions_are_required_in_base_chapter()` → `KkprlPayloadFixture`  [AMBIGUOUS]
  tests/Unit/Kkprl/ProposalProgressTest.php · relation: calls
- `.explicit_not_applicable_values_count_as_completed()` → `ProposalProgress`  [AMBIGUOUS]
  tests/Unit/Kkprl/ProposalProgressTest.php · relation: calls
- `.incomplete_schedule_row_keeps_reclamation_chapter_incomplete()` → `ProposalProgress`  [AMBIGUOUS]
  tests/Unit/Kkprl/ProposalProgressTest.php · relation: calls
- `.invalid_coordinate_is_reported_as_error_not_incomplete()` → `KkprlPayloadFixture`  [AMBIGUOUS]
  tests/Unit/Kkprl/ProposalProgressTest.php · relation: calls
- `.invalid_map_coordinates_prevent_a_complete_chapter()` → `ProposalProgress`  [AMBIGUOUS]
  tests/Unit/Kkprl/ProposalProgressTest.php · relation: calls
- `.invalid_map_coordinates_prevent_a_complete_chapter()` → `KkprlPayloadFixture`  [AMBIGUOUS]
  tests/Unit/Kkprl/ProposalProgressTest.php · relation: calls
- `.valid_map_coordinates_and_decimal_boundaries_are_accepted()` → `ProposalProgress`  [AMBIGUOUS]
  tests/Unit/Kkprl/ProposalProgressTest.php · relation: calls
- `.valid_map_coordinates_and_decimal_boundaries_are_accepted()` → `KkprlPayloadFixture`  [AMBIGUOUS]
  tests/Unit/Kkprl/ProposalProgressTest.php · relation: calls
- `.login()` → `.response()`  [AMBIGUOUS]
  app/Http/Controllers/Api/Mobile/V1/AuthController.php · relation: calls
- `.login()` → `UserDevice`  [AMBIGUOUS]
  app/Http/Controllers/Api/Mobile/V1/AuthController.php · relation: calls
- `.logout()` → `UserDevice`  [AMBIGUOUS]
  app/Http/Controllers/Api/Mobile/V1/AuthController.php · relation: calls
- `.logout()` → `.response()`  [AMBIGUOUS]
  app/Http/Controllers/Api/Mobile/V1/AuthController.php · relation: calls
- `.logoutAll()` → `.response()`  [AMBIGUOUS]
  app/Http/Controllers/Api/Mobile/V1/AuthController.php · relation: calls
- `.retireInstallation()` → `UserDevice`  [AMBIGUOUS]
  app/Http/Controllers/Api/Mobile/V1/AuthController.php · relation: calls
- `.handle()` → `.response()`  [AMBIGUOUS]
  app/Http/Middleware/EnsureMobileUserIsActive.php · relation: calls
- `.revokeCurrentInstallation()` → `UserDevice`  [AMBIGUOUS]
  app/Http/Middleware/EnsureMobileUserIsActive.php · relation: calls
- `.__invoke()` → `.filled()`  [AMBIGUOUS]
  app/Http/Controllers/KkprlProposalDocumentDownloadController.php · relation: calls
- `.evaluate()` → `.fieldsForChapter()`  [AMBIGUOUS]
  app/Domain/Kkprl/ProposalProgress.php · relation: calls
- `.evaluate()` → `.fieldFilled()`  [AMBIGUOUS]
  app/Domain/Kkprl/ProposalProgress.php · relation: calls
- `.evaluate()` → `.valueFor()`  [AMBIGUOUS]
  app/Domain/Kkprl/ProposalProgress.php · relation: calls
- `.evaluate()` → `.issuesForChapter()`  [AMBIGUOUS]
  app/Domain/Kkprl/ProposalProgress.php · relation: calls
- `.fieldFilled()` → `.filled()`  [AMBIGUOUS]
  app/Domain/Kkprl/ProposalProgress.php · relation: calls
- `.fieldsForChapter()` → `ProposalFieldCatalog`  [AMBIGUOUS]
  app/Domain/Kkprl/ProposalProgress.php · relation: calls
- `.issuesForChapter()` → `.valueFor()`  [AMBIGUOUS]
  app/Domain/Kkprl/ProposalProgress.php · relation: calls
- `.issuesForChapter()` → `.filled()`  [AMBIGUOUS]
  app/Domain/Kkprl/ProposalProgress.php · relation: calls
- `.issuesForChapter()` → `.pointsFromText()`  [AMBIGUOUS]
  app/Domain/Kkprl/ProposalProgress.php · relation: calls
- `.addScheduleRow()` → `.persist()`  [AMBIGUOUS]
  app/Livewire/KkprlProposalWizard.php · relation: calls
- `.editingPayload()` → `ProposalFieldCatalog`  [AMBIGUOUS]
  app/Livewire/KkprlProposalWizard.php · relation: calls
- `.mount()` → `.editingPayload()`  [AMBIGUOUS]
  app/Livewire/KkprlProposalWizard.php · relation: calls
- `.mount()` → `.normalizeConditionInputs()`  [AMBIGUOUS]
  app/Livewire/KkprlProposalWizard.php · relation: calls
- `.mount()` → `.refreshProgress()`  [AMBIGUOUS]
  app/Livewire/KkprlProposalWizard.php · relation: calls
- `.mount()` → `.normalizeStep()`  [AMBIGUOUS]
  app/Livewire/KkprlProposalWizard.php · relation: calls
- `.mount()` → `.syncAttachmentChapter()`  [AMBIGUOUS]
  app/Livewire/KkprlProposalWizard.php · relation: calls
- `.next()` → `.persist()`  [AMBIGUOUS]
  app/Livewire/KkprlProposalWizard.php · relation: calls
- `.next()` → `.syncAttachmentChapter()`  [AMBIGUOUS]
  app/Livewire/KkprlProposalWizard.php · relation: calls
- `.payloadForStorage()` → `.editingPayload()`  [AMBIGUOUS]
  app/Livewire/KkprlProposalWizard.php · relation: calls
- `.persist()` → `.payloadForStorage()`  [AMBIGUOUS]
  app/Livewire/KkprlProposalWizard.php · relation: calls
- `.persist()` → `.editingPayload()`  [AMBIGUOUS]
  app/Livewire/KkprlProposalWizard.php · relation: calls
- `.persist()` → `.refreshProgress()`  [AMBIGUOUS]
  app/Livewire/KkprlProposalWizard.php · relation: calls
- `.persist()` → `.syncAttachmentChapter()`  [AMBIGUOUS]
  app/Livewire/KkprlProposalWizard.php · relation: calls
- `.previous()` → `.syncAttachmentChapter()`  [AMBIGUOUS]
  app/Livewire/KkprlProposalWizard.php · relation: calls
- `.refreshProgress()` → `.normalizeStep()`  [AMBIGUOUS]
  app/Livewire/KkprlProposalWizard.php · relation: calls
- `.refreshState()` → `.editingPayload()`  [AMBIGUOUS]
  app/Livewire/KkprlProposalWizard.php · relation: calls
- `.refreshState()` → `.normalizeConditionInputs()`  [AMBIGUOUS]
  app/Livewire/KkprlProposalWizard.php · relation: calls
- `.refreshState()` → `.refreshProgress()`  [AMBIGUOUS]
  app/Livewire/KkprlProposalWizard.php · relation: calls
- `.refreshState()` → `.syncAttachmentChapter()`  [AMBIGUOUS]
  app/Livewire/KkprlProposalWizard.php · relation: calls
- `.removeScheduleRow()` → `.persist()`  [AMBIGUOUS]
  app/Livewire/KkprlProposalWizard.php · relation: calls
- `.render()` → `KkprlProposalDocument`  [AMBIGUOUS]
  app/Livewire/KkprlProposalWizard.php · relation: calls
- `.render()` → `KkprlProposalAttachment`  [AMBIGUOUS]
  app/Livewire/KkprlProposalWizard.php · relation: calls
- `.saveDraft()` → `.persist()`  [AMBIGUOUS]
  app/Livewire/KkprlProposalWizard.php · relation: calls
- `.updateCoordinates()` → `ProposalLocked`  [AMBIGUOUS]
  app/Livewire/KkprlProposalWizard.php · relation: calls
- `.updateCoordinates()` → `.persist()`  [AMBIGUOUS]
  app/Livewire/KkprlProposalWizard.php · relation: calls
- `.updatedCurrentStep()` → `.normalizeStep()`  [AMBIGUOUS]
  app/Livewire/KkprlProposalWizard.php · relation: calls
- `.updatedCurrentStep()` → `.syncAttachmentChapter()`  [AMBIGUOUS]
  app/Livewire/KkprlProposalWizard.php · relation: calls
- `.updatedPayload()` → `.normalizeConditionInputs()`  [AMBIGUOUS]
  app/Livewire/KkprlProposalWizard.php · relation: calls
- `.updatedPayload()` → `.persist()`  [AMBIGUOUS]
  app/Livewire/KkprlProposalWizard.php · relation: calls
- `.save()` → `ProposalLocked`  [AMBIGUOUS]
  app/Services/KkprlProposalDraftService.php · relation: calls
- `.save()` → `.save()`  [AMBIGUOUS]
  app/Services/KkprlProposalDraftService.php · relation: calls
- `.authenticate()` → `.session()`  [AMBIGUOUS]
  app/Filament/Pages/Auth/Login.php · relation: calls
- `.generateCaptchaChallenge()` → `.session()`  [AMBIGUOUS]
  app/Filament/Pages/Auth/Login.php · relation: calls
- `.validateCaptcha()` → `.session()`  [AMBIGUOUS]
  app/Filament/Pages/Auth/Login.php · relation: calls
- `.index()` → `.filled()`  [AMBIGUOUS]
  app/Http/Controllers/Api/Mobile/V1/ClientController.php · relation: calls
- `.index()` → `.response()`  [AMBIGUOUS]
  app/Http/Controllers/Api/Mobile/V1/ClientController.php · relation: calls
- `.show()` → `.response()`  [AMBIGUOUS]
  app/Http/Controllers/Api/Mobile/V1/ClientController.php · relation: calls
- `.update()` → `.update()`  [AMBIGUOUS]
  app/Http/Controllers/Api/Mobile/V1/ClientController.php · relation: calls
- `.update()` → `.response()`  [AMBIGUOUS]
  app/Http/Controllers/Api/Mobile/V1/ClientController.php · relation: calls
- `.store()` → `.response()`  [AMBIGUOUS]
  app/Http/Controllers/Api/Mobile/V1/ScheduleController.php · relation: calls
- `.update()` → `.update()`  [AMBIGUOUS]
  app/Http/Controllers/Api/Mobile/V1/ScheduleController.php · relation: calls
- `.update()` → `.response()`  [AMBIGUOUS]
  app/Http/Controllers/Api/Mobile/V1/ScheduleController.php · relation: calls
- `.ensureVersion()` → `.response()`  [AMBIGUOUS]
  app/Support/EnsuresMobileVersion.php · relation: calls
- `.learning_models_generate_unique_slugs_and_keep_relationships()` → `LearningCategory`  [AMBIGUOUS]
  tests/Feature/BelajarKkprlTest.php · relation: calls
- `.test_archived_regular_learning_material_cannot_be_opened()` → `LearningCategory`  [AMBIGUOUS]
  tests/Feature/ProposalGuideTest.php · relation: calls
- `.test_archived_regular_learning_material_cannot_be_opened()` → `LearningGroup`  [AMBIGUOUS]
  tests/Feature/ProposalGuideTest.php · relation: calls
- `.test_archived_regular_learning_material_cannot_be_opened()` → `LearningMaterial`  [AMBIGUOUS]
  tests/Feature/ProposalGuideTest.php · relation: calls
- `.sendCompletedMail()` → `BeritaAcaraCompletedMail`  [AMBIGUOUS]
  app/Observers/BeritaAcaraObserver.php · relation: calls
- `.sendCompletedMail()` → `.log()`  [AMBIGUOUS]
  app/Observers/BeritaAcaraObserver.php · relation: calls
- `.checkStatus()` → `StatusCompletedMail`  [AMBIGUOUS]
  app/Observers/ConsultationReportObserver.php · relation: calls
- `.checkStatus()` → `.log()`  [AMBIGUOUS]
  app/Observers/ConsultationReportObserver.php · relation: calls
- `.afterSave()` → `ChunkRegulationJob`  [AMBIGUOUS]
  app/Filament/Layanankkprl/Resources/Regulations/Pages/EditRegulation.php · relation: calls
- `.getPages()` → `ListRegulations`  [AMBIGUOUS]
  app/Filament/Layanankkprl/Resources/Regulations/RegulationResource.php · relation: calls
- `.getPages()` → `CreateRegulation`  [AMBIGUOUS]
  app/Filament/Layanankkprl/Resources/Regulations/RegulationResource.php · relation: calls
- `.getPages()` → `EditRegulation`  [AMBIGUOUS]
  app/Filament/Layanankkprl/Resources/Regulations/RegulationResource.php · relation: calls
- `.failed()` → `.log()`  [AMBIGUOUS]
  app/Jobs/ChunkRegulationJob.php · relation: calls
- `.handle()` → `.log()`  [AMBIGUOUS]
  app/Jobs/ChunkRegulationJob.php · relation: calls
- `.created()` → `ClientCreated`  [AMBIGUOUS]
  app/Observers/ClientObserver.php · relation: calls
- `.created()` → `.log()`  [AMBIGUOUS]
  app/Observers/ClientObserver.php · relation: calls
- `.updated()` → `ClientUpdated`  [AMBIGUOUS]
  app/Observers/ClientObserver.php · relation: calls
- `.updated()` → `.log()`  [AMBIGUOUS]
  app/Observers/ClientObserver.php · relation: calls
- `.log()` → `NotificationLog`  [AMBIGUOUS]
  app/Services/NotificationService.php · relation: calls
- `.sendBookingCreated()` → `BookingCreatedMail`  [AMBIGUOUS]
  app/Services/NotificationService.php · relation: calls
- `.sendClientUpdated()` → `ClientUpdated`  [AMBIGUOUS]
  app/Services/NotificationService.php · relation: calls
- `.sendRescheduleProposal()` → `RescheduleProposalMail`  [AMBIGUOUS]
  app/Services/NotificationService.php · relation: calls
- `.sendSurveyLink()` → `BookingFinishedMail`  [AMBIGUOUS]
  app/Services/NotificationService.php · relation: calls

## Knowledge Gaps
- **119 isolated node(s):** `booking-wizard`, `public-dashboard-charts`, `node:fs`, `node:vm`, `context` (+114 more)
  These have ≤1 connection - possible missing edges or undocumented components. (Counts symbols only; 789 node(s) total have ≤1 connection when file, concept and rationale nodes are included.)
- **79 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **What is the exact relationship between `.table()` and `ClientsTable`?**
  _Edge tagged AMBIGUOUS (relation: calls) - confidence is low._
- **What is the exact relationship between `.table()` and `User`?**
  _Edge tagged AMBIGUOUS (relation: calls) - confidence is low._
- **What is the exact relationship between `.table()` and `Assignment`?**
  _Edge tagged AMBIGUOUS (relation: calls) - confidence is low._
- **What is the exact relationship between `.table()` and `.form()`?**
  _Edge tagged AMBIGUOUS (relation: calls) - confidence is low._
- **What is the exact relationship between `.table()` and `Schedule`?**
  _Edge tagged AMBIGUOUS (relation: calls) - confidence is low._
- **What is the exact relationship between `.table()` and `.form()`?**
  _Edge tagged AMBIGUOUS (relation: calls) - confidence is low._
- **What is the exact relationship between `.configure()` and `.response()`?**
  _Edge tagged AMBIGUOUS (relation: calls) - confidence is low._
## Map Measurement Refresh Audit

- Updated UTC: `2026-10-07T23:26:22.643330+00:00`.
- Scope: Existing main project and KKPRL index retained. Map measurement refresh: 3 code sources, 1 Blade views, 6 Markdown documents through ADR-028. Not a full repository rebuild or an expansion of vendor/build assets.
- Baseline → final: 3421 → 3465 nodes; 8819 → 8901 directed relations.
- 718 ambiguous relations; 22 parser self-loop candidates.
- No missing/dangling endpoints. 134 directed parallel endpoint pairs retained.
- Source hashes and extraction provenance: `.graphify_update.json`; unchanged sources are not falsely stamped current.
- Validation provenance: Root actual Node built-in tests 10 PASS; JS syntax 2 commands exit0; targeted PHP 50tests/140assertions PASS; final full PHPUnit469tests/2462assertions PASS337.095s; Vite build59modules/3.60s PASS. Isolated browser DD/DMS polygon0.62ha, line1.11km, reactive local match/mismatch, map click and degenerate drag states, point hides panel, invalid latitude leaves no stale value; mobile320x740 scrollWidth320 and console clean. This graph refresh did not rerun application tests. No paid external model API, user database changes or deployment.
- Code declarations and documentation concepts indexed; no fixture passwords or proposal/private input values included.
