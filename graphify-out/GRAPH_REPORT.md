# Graph Report - listdatakkprl  (2026-10-06)

## Corpus Check
- 342 files · ~68,329 words
- Verdict: corpus is large enough that graph structure adds value.

## Summary
- 2304 nodes · 6079 edges · 108 communities (65 shown, 43 thin omitted)
- Extraction: 98% EXTRACTED · 2% INFERRED · 0% AMBIGUOUS · INFERRED: 127 edges (avg confidence: 0.85)
- Token cost: AST 0; semantic host usage unavailable (not reported as zero).

## Graph Freshness
- Built from commit: `59717e47`
- Run `git rev-parse HEAD` and compare to check if the graph is stale.
- Run `graphify update .` after code changes (no API cost).

## Community Hubs (Navigation)
- Admin Resources and Relations
- Content Permission Policies
- Deprecate Plaintext Data
- List Clients
- Edit Client
- Assignments Relation Manager
- Ai Chat Widget
- Dashboard Management
- Public Content Admin
- Booking Created Mail
- Activity Log
- Standalone KKPRL Proposal Guides
- Backfill Data Protection
- Create Learning Category
- Create Assignments
- Proposal Field Catalog
- App Config Controller
- Client Management
- Attachment Quota
- Proposal Ai Request Failed
- Berita Acara Completed Mail
- Authenticate Summary User
- Proposal Chapter Rules
- Kkprl Proposal Wizard
- Kkprl Proposal
- Learning Material Access
- Invalid Proposal Credentials
- Client Stats Overview
- Berita Acara Relation Manager
- Assignment Controller
- Learning Session Tracking
- Booking Wizard
- Monthly Service Chart
- Activity Type Chart
- Mobile File Controller
- Subject Management
- Faq Management
- Edit Approval Denied
- Proposal Inline Anchor Resolver
- Activity Management
- Satisfaction Survey Result
- Service Performance Result
- View Components
- Berita Acara Controller
- Consultation Report Controller
- Landing and Learning Pages
- Service Performance Results
- Learning Category
- Satisfaction Survey
- Has Many
- Active Revision Exists
- Berita Acara Attendee
- Consultation Location
- Kkprl Proposal Edit Approval
- Learning Material Access
- Learning Session
- Learning Activity Log
- Proposal Evidence Catalog
- Learning Material Administration
- List Regulations
- Chunk Regulation Job
- Client Updated
- Consultation Report
- Holiday Management
- Public Feedback
- Role Policy
- Client Resource
- Create Satisfaction Survey Result
- Create Service Performance Result
- Kkprl Proposal Edit Session
- Berita Acara Policy
- Client Policy
- Proposal Access Denied
- Faq Resource
- Holiday Resource
- Kkprl Proposal Edit Approval Resource
- Activities Relation Manager
- Schedule Management
- Assignment Observer
- Learning Access Resource
- Learning Activity Resource
- Learning Session Resource
- Backed Enum
- Consultation Location Resource
- Kkprl Proposal Access
- Public Dashboard Charts
- Learning Group
- Regulation Infolist
- Staff Score Summary Table
- Mobile Notification Service
- Learning Groups and Filters
- Check Status
- Proposal Payload Sanitizer
- Consultation Report Resource
- Backed Enum
- City Resource
- Business Day Service
- Public Feedback Page
- Satisfaction Survey Results
- Mobile Operational Notification
- View Components
- Proposal Guide Admin Forms
- Authenticate Mobile
- Public Attendance
- Staff Performance Service
- Landing Page Blade Components

## God Nodes (most connected - your core abstractions)
1. `Client` - 146 edges
2. `KkprlProposal` - 79 edges
3. `User` - 74 edges
4. `BeritaAcara` - 58 edges
5. `LearningMaterial` - 46 edges
6. `Assignment` - 40 edges
7. `MobileTransformer` - 40 edges
8. `KkprlProposalDocumentService` - 37 edges
9. `ConsultationReport` - 36 edges
10. `Regulation` - 35 edges

## Surprising Connections (you probably didn't know these)
- `Existing Materi Pembelajaran permissions` --rationale_for--> `ProposalGuideResource`  [INFERRED]
  docs/pedoman-kkprl.md → app/Filament/Layanankkprl/Resources/ProposalGuides/ProposalGuideResource.php
- `PDF replacement and retention` --rationale_for--> `ProposalGuideResource`  [INFERRED]
  docs/pedoman-kkprl.md → app/Filament/Layanankkprl/Resources/ProposalGuides/ProposalGuideResource.php
- `Pedoman Perolehan Data Proposal KKPRL Skala Kecil` --references--> `ProposalGuideResource`  [INFERRED]
  docs/pedoman-kkprl.md → app/Filament/Layanankkprl/Resources/ProposalGuides/ProposalGuideResource.php
- `Pedoman Perolehan Data Proposal KKPRL Skala Kecil` --references--> `Pedoman KKPRL public document listing`  [INFERRED]
  docs/pedoman-kkprl.md → resources/views/guides/index.blade.php
- `Guide publication visibility` --rationale_for--> `ProposalGuideController`  [INFERRED]
  docs/pedoman-kkprl.md → app/Http/Controllers/ProposalGuideController.php

## Import Cycles
- None detected.

## Hyperedges (group relationships)
- **Legacy guide continuity preserves source PDFs, archived material access, and migration rollback** — docs_pedoman_kkprl_legacy_learning_material_archive, routes_web_belajar_kkprl_material_show, routes_web_belajar_kkprl_material_pdf, routes_web_belajar_kkprl_material_download [EXTRACTED 1.00]

## Communities (108 total, 43 thin omitted)

### Community 0 - "Admin Resources and Relations"
Cohesion: 0.07
Nodes (46): App\Filament\Layanankkprl\Resources\ConsultationReportResource\Pages, AttachmentsRelationManager, DocumentsRelationManager, ReviewEventsRelationManager, KkprlProposalsTable, LearningCategoriesTable, LearningGroupsTable, App\Filament\Layanankkprl\Resources\PublicFeedbackResource\Pages (+38 more)

### Community 1 - "Content Permission Policies"
Cohesion: 0.06
Nodes (6): ConsultationReportPolicy, LearningGroupPolicy, LearningMaterialPolicy, PublicFeedbackPolicy, UserPolicy, Illuminate\Foundation\Auth\User

### Community 2 - "Deprecate Plaintext Data"
Cohesion: 0.06
Nodes (11): DeprecatePlaintextData, KkprlPreflight, PdfAttachmentRenderFailed, ProposalDocumentSectionCatalog, ProposalSnapshot, KkprlProposalDocumentService, Barryvdh\DomPDF\Facade\Pdf, Illuminate\Console\Command (+3 more)

### Community 3 - "List Clients"
Cohesion: 0.05
Nodes (23): ListClients, CreateConsultationReport, ListConsultationReports, ListFaqs, ListHolidays, ListLearningCategories, ListLearningGroups, ListLearningMaterials (+15 more)

### Community 4 - "Edit Client"
Cohesion: 0.05
Nodes (23): EditClient, EditConsultationReport, EditFaq, EditHoliday, EditLearningCategory, Width, EditLearningGroup, Width (+15 more)

### Community 5 - "Assignments Relation Manager"
Cohesion: 0.04
Nodes (20): AssignmentsRelationManager, ConsultationReportsRelationManager, ClientForm, ClientInfolist, FaqForm, HolidayForm, LearningCategoryForm, LearningCategoryInfolist (+12 more)

### Community 6 - "Ai Chat Widget"
Cohesion: 0.05
Nodes (11): AiChatWidget, AiChatLog, Regulation, RegulationChunk, RegulationObserver, RegulationPolicy, AiService, ContentDeliveryService (+3 more)

### Community 7 - "Dashboard Management"
Cohesion: 0.05
Nodes (31): Dashboard, ViewClient, KkprlProposalResource, BackedEnum, UnitEnum, ListKkprlProposals, ViewKkprlProposal, Dashboard (+23 more)

### Community 8 - "Public Content Admin"
Cohesion: 0.09
Nodes (16): LearningCategoryResource, LearningGroupResource, ProposalGuideResource, PublicFeedbackResource, App\Filament\Layanankkprl\Resources\Services\Pages\CreateService, App\Filament\Layanankkprl\Resources\Services\Pages\EditService, ServiceResource, UserResource (+8 more)

### Community 9 - "Booking Created Mail"
Cohesion: 0.09
Nodes (13): BookingCreatedMail, BookingFinishedMail, ClientCreated, RescheduleProposalMail, StaffAssigned, StatusCompletedMail, StatusScheduledMail, StatusWaitingMail (+5 more)

### Community 10 - "Activity Log"
Cohesion: 0.10
Nodes (10): ActivityLog, AppSettings, KkprlProposalChat, LearningMaterialOpen, RunningText, Illuminate\Database\Eloquent\Factories\HasFactory, Illuminate\Database\Eloquent\Model, Illuminate\Database\Eloquent\Relations\BelongsTo (+2 more)

### Community 11 - "Standalone KKPRL Proposal Guides"
Cohesion: 0.07
Nodes (27): ProposalGuideController, ProposalGuide, Existing Materi Pembelajaran permissions, PDF replacement and retention, Pedoman Perolehan Data Proposal KKPRL Skala Kecil, Guide publication visibility, Stable ID-based public URLs, Standalone Pedoman KKPRL (+19 more)

### Community 12 - "Backfill Data Protection"
Cohesion: 0.11
Nodes (5): BackfillDataProtection, KkprlProposalAccessVerifier, KkprlPhoneNormalizer, DataHashService, KkprlTicketPhoneAccessVerifier

### Community 13 - "Create Learning Category"
Cohesion: 0.06
Nodes (21): CreateLearningCategory, Width, CreateLearningGroup, Width, CreatePublicFeedback, CreateRegulation, Width, CreateRunningText (+13 more)

### Community 14 - "Create Assignments"
Cohesion: 0.08
Nodes (12): CreateAssignments, Assignment, User, AssignmentPolicy, ProposalGuidePolicy, BookingAvailabilityService, Database\Factories\UserFactory, Illuminate\Auth\Access\Response (+4 more)

### Community 15 - "Proposal Field Catalog"
Cohesion: 0.10
Nodes (9): ProposalFieldCatalog, Login, KkprlAiChat, DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException, Filament\Auth\Http\Responses\Contracts\LoginResponse, Filament\Auth\Pages\Login, Filament\Schemas\Components\Component, Illuminate\Support\Facades\Auth (+1 more)

### Community 16 - "App Config Controller"
Cohesion: 0.13
Nodes (11): AppConfigController, ClientController, DashboardController, FeedbackController, MobileNotificationController, StaffController, Controller, Illuminate\Foundation\Auth\Access\AuthorizesRequests (+3 more)

### Community 17 - "Client Management"
Cohesion: 0.11
Nodes (6): Client, self, ClientObserver, Illuminate\Database\Eloquent\Casts\Attribute, Illuminate\Database\Eloquent\Relations\HasManyThrough, Illuminate\Database\Eloquent\Relations\HasOne

### Community 18 - "Attachment Quota"
Cohesion: 0.15
Nodes (9): AttachmentQuota, AttachmentQuotaViolation, InvalidAttachmentFile, ProposalLocked, KkprlProposalAttachment, KkprlProposalAttachmentService, Illuminate\Http\UploadedFile, InvalidArgumentException (+1 more)

### Community 19 - "Proposal Ai Request Failed"
Cohesion: 0.11
Nodes (9): ProposalAiRequestFailed, FirebaseCloudMessaging, KkprlAiService, Carbon\CarbonImmutable, Google\Auth\Credentials\ServiceAccountCredentials, Illuminate\Http\Client\ConnectionException, Illuminate\Http\Client\Response, Illuminate\Support\Facades\Hash (+1 more)

### Community 20 - "Berita Acara Completed Mail"
Cohesion: 0.11
Nodes (4): BeritaAcaraCompletedMail, BeritaAcara, self, BeritaAcaraObserver

### Community 21 - "Authenticate Summary User"
Cohesion: 0.15
Nodes (9): AuthenticateSummaryUser, AuthController, ProfileController, EnsureMobileUserIsActive, UserDevice, MobileCapabilityService, Illuminate\Database\Eloquent\Concerns\HasUuids, Illuminate\Http\Request (+1 more)

### Community 22 - "Proposal Chapter Rules"
Cohesion: 0.12
Nodes (5): ProposalChapterRules, ProposalProgress, ProposalProgressResult, KkprlProposalInfolist, KkprlProposalSubmissionService

### Community 23 - "Kkprl Proposal Wizard"
Cohesion: 0.19
Nodes (3): KkprlProposalWizard, KkprlProposalAccessService, KkprlProposalDraftService

### Community 25 - "Learning Material Access"
Cohesion: 0.15
Nodes (6): LearningMaterialController, LearningMaterial, Legacy learning material archive, belajar-kkprl.material.download: GET /belajar-kkprl/materi/{material:slug}/download, withTrashed, belajar-kkprl.material.pdf: GET /belajar-kkprl/materi/{material:slug}/pdf, withTrashed, belajar-kkprl.material.show: GET /belajar-kkprl/materi/{material:slug}, withTrashed

### Community 26 - "Invalid Proposal Credentials"
Cohesion: 0.11
Nodes (10): InvalidProposalCredentials, ProposalAccessRateLimited, KkprlProposalAttachmentDownloadController, Illuminate\Auth\Access\AuthorizationException, Illuminate\Support\Facades\Crypt, Illuminate\Support\Facades\DB, Illuminate\Support\Facades\RateLimiter, Illuminate\Support\Facades\Storage (+2 more)

### Community 27 - "Client Stats Overview"
Cohesion: 0.13
Nodes (13): ClientStatsOverview, LearningAnalyticsOverview, ActivityStatsOverview, Filament\Tables\Contracts\HasTable, Filament\Widgets\Concerns\InteractsWithPageTable, Filament\Widgets\StatsOverviewWidget, Filament\Widgets\StatsOverviewWidget\Stat, Illuminate\Contracts\Pagination\Paginator (+5 more)

### Community 28 - "Berita Acara Relation Manager"
Cohesion: 0.11
Nodes (4): BeritaAcaraRelationManager, SignaturePad, Filament\Forms\Components\Field, static

### Community 29 - "Assignment Controller"
Cohesion: 0.17
Nodes (5): AssignmentController, ScheduleController, MobileTransformer, EnsuresMobileVersion, Illuminate\Support\Arr

### Community 30 - "Learning Session Tracking"
Cohesion: 0.13
Nodes (3): LearningTrackingController, LearningTrackingService, Illuminate\Validation\Rule

### Community 32 - "Monthly Service Chart"
Cohesion: 0.12
Nodes (5): MonthlyServiceChart, ServiceLocationChart, ServiceTypeChart, Filament\Forms\Components\DatePicker, Filament\Widgets\ChartWidget\Concerns\HasFiltersSchema

### Community 33 - "Activity Type Chart"
Cohesion: 0.13
Nodes (6): ActivityTypeChart, ActivityPerMonthChart, ActivityPerProvinceChart, OrganizerComparisonChart, Filament\Widgets\ChartWidget, Filament\Widgets\Concerns\InteractsWithPageFilters

### Community 34 - "Mobile File Controller"
Cohesion: 0.16
Nodes (7): MobileFileController, AttachRequestId, EnsureMobileJsonRequest, PrivateFileService, Closure, Symfony\Component\HttpFoundation\Response, Symfony\Component\HttpFoundation\StreamedResponse

### Community 36 - "Faq Management"
Cohesion: 0.14
Nodes (4): Faq, FaqObserver, FaqPolicy, Illuminate\Auth\Access\HandlesAuthorization

### Community 37 - "Edit Approval Denied"
Cohesion: 0.31
Nodes (3): EditApprovalDenied, KkprlProposalEditApprovalService, Carbon\CarbonInterface

### Community 38 - "Proposal Inline Anchor Resolver"
Cohesion: 0.14
Nodes (4): ProposalInlineAnchorResolver, KkprlProposalDocumentDownloadController, KkprlProposalDocument, KkprlProposalSnapshotService

### Community 39 - "Activity Management"
Cohesion: 0.14
Nodes (10): Activity, ActivityObserver, AppServiceProvider, WindowsSafeFilesystem, Filament\Support\Facades\FilamentView, Filament\View\PanelsRenderHook, Illuminate\Filesystem\Filesystem, Illuminate\Support\ServiceProvider (+2 more)

### Community 42 - "View Components"
Cohesion: 0.29
Nodes (9): Asmit\FilamentUpload\Enums\PdfViewFit, Asmit\FilamentUpload\Forms\Components\AdvancedFileUpload, Filament\Forms\Components\DateTimePicker, Filament\Forms\Components\FileUpload, Filament\Forms\Components\Select, Filament\Forms\Components\Textarea, Filament\Forms\Components\TextInput, Filament\Forms\Components\Toggle (+1 more)

### Community 43 - "Berita Acara Controller"
Cohesion: 0.18
Nodes (3): BeritaAcaraController, SignatureService, Illuminate\Support\Facades\Route

### Community 44 - "Consultation Report Controller"
Cohesion: 0.18
Nodes (6): ConsultationReportController, SafeRichText, DOMComment, DOMDocument, DOMElement, DOMNode

### Community 45 - "Landing and Learning Pages"
Cohesion: 0.16
Nodes (5): AttendeeSign, BelajarKkprlGroup, LandingPage, Livewire\Attributes\Layout, Livewire\Component

### Community 49 - "Has Many"
Cohesion: 0.15
Nodes (3): HasMany, Service, ServicePolicy

### Community 50 - "Active Revision Exists"
Cohesion: 0.16
Nodes (5): ActiveRevisionExists, EditApprovalUsed, EditScopeDenied, EditSessionExpired, RuntimeException

### Community 58 - "Learning Material Administration"
Cohesion: 0.15
Nodes (4): LearningMaterialResource, CreateLearningMaterial, Width, LearningMaterialsTable

### Community 59 - "List Regulations"
Cohesion: 0.15
Nodes (3): ListRegulations, RegulationResource, RegulationsTable

### Community 60 - "Chunk Regulation Job"
Cohesion: 0.20
Nodes (7): ChunkRegulationJob, SendMobilePush, Illuminate\Contracts\Queue\ShouldQueue, Illuminate\Foundation\Bus\Dispatchable, Illuminate\Foundation\Queue\Queueable, Illuminate\Queue\InteractsWithQueue, Throwable

### Community 61 - "Client Updated"
Cohesion: 0.16
Nodes (3): ClientUpdated, NotificationLog, Exception

### Community 64 - "Public Feedback"
Cohesion: 0.20
Nodes (3): PublicFeedback, PublicFeedbackObserver, Illuminate\Database\Eloquent\Relations\BelongsToMany

### Community 66 - "Client Resource"
Cohesion: 0.17
Nodes (3): ClientResource, CreateClient, ClientsTable

### Community 67 - "Create Satisfaction Survey Result"
Cohesion: 0.18
Nodes (4): CreateSatisfactionSurveyResult, EditSatisfactionSurveyResult, SatisfactionSurveyResultResource, SatisfactionSurveyResultsTable

### Community 68 - "Create Service Performance Result"
Cohesion: 0.18
Nodes (4): CreateServicePerformanceResult, EditServicePerformanceResult, ServicePerformanceResultResource, ServicePerformanceResultsTable

### Community 69 - "Kkprl Proposal Edit Session"
Cohesion: 0.17
Nodes (3): KkprlProposalEditSession, KkprlProposalReviewEvent, KkprlProposalRevisionService

### Community 72 - "Proposal Access Denied"
Cohesion: 0.18
Nodes (3): ProposalAccessDenied, ProposalAiResponseParser, Illuminate\Support\Facades\Validator

### Community 73 - "Faq Resource"
Cohesion: 0.18
Nodes (3): FaqResource, CreateFaq, FaqsTable

### Community 74 - "Holiday Resource"
Cohesion: 0.18
Nodes (3): HolidayResource, CreateHoliday, HolidaysTable

### Community 75 - "Kkprl Proposal Edit Approval Resource"
Cohesion: 0.21
Nodes (5): KkprlProposalEditApprovalResource, BackedEnum, UnitEnum, ListKkprlProposalEditApprovals, KkprlProposalEditApprovalsTable

### Community 76 - "Activities Relation Manager"
Cohesion: 0.23
Nodes (3): ActivitiesRelationManager, City, Province

### Community 77 - "Schedule Management"
Cohesion: 0.23
Nodes (4): Schedule, SatisfactionSurveyObserver, ScheduleObserver, Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit

### Community 82 - "Backed Enum"
Cohesion: 0.18
Nodes (3): BackedEnum, UnitEnum, SatisfactionSurveyResource

### Community 83 - "Consultation Location Resource"
Cohesion: 0.22
Nodes (3): ConsultationLocationResource, UnitEnum, ListConsultationLocations

### Community 84 - "Kkprl Proposal Access"
Cohesion: 0.22
Nodes (3): KkprlProposalAccess, KkprlProposalResume, KkprlProposalStart

### Community 87 - "Regulation Infolist"
Cohesion: 0.33
Nodes (4): RegulationInfolist, Filament\Infolists\Components\IconEntry, Filament\Infolists\Components\ImageEntry, Filament\Infolists\Components\TextEntry

### Community 94 - "Backed Enum"
Cohesion: 0.29
Nodes (3): BackedEnum, UnitEnum, RunningTextResource

### Community 95 - "City Resource"
Cohesion: 0.29
Nodes (3): CityResource, BackedEnum, UnitEnum

### Community 102 - "Authenticate Mobile"
Cohesion: 0.50
Nodes (3): AuthenticateMobile, Illuminate\Auth\AuthenticationException, Illuminate\Auth\Middleware\Authenticate

## Ambiguous Edges - Review These
- `.download()` → `.download()`  [AMBIGUOUS]
  app/Http/Controllers/ProposalGuideController.php · relation: calls
- `.download()` → `.download()`  [AMBIGUOUS]
  app/Http/Controllers/LearningMaterialController.php · relation: calls
- `.update()` → `.update()`  [AMBIGUOUS]
  app/Http/Controllers/Api/Mobile/V1/AssignmentController.php · relation: calls
- `.update()` → `.update()`  [AMBIGUOUS]
  app/Http/Controllers/Api/Mobile/V1/ClientController.php · relation: calls
- `.update()` → `.update()`  [AMBIGUOUS]
  app/Http/Controllers/Api/Mobile/V1/ConsultationReportController.php · relation: calls
- `.beritaAcara()` → `.beritaAcara()`  [AMBIGUOUS]
  app/Http/Controllers/Api/Mobile/V1/MobileFileController.php · relation: calls
- `.update()` → `.update()`  [AMBIGUOUS]
  app/Http/Controllers/Api/Mobile/V1/ScheduleController.php · relation: calls
- `.startSession()` → `.startSession()`  [AMBIGUOUS]
  app/Http/Controllers/LearningTrackingController.php · relation: calls
- `.forget()` → `.forget()`  [AMBIGUOUS]
  app/Services/KkprlProposalAccessService.php · relation: calls
- `.delete()` → `.delete()`  [AMBIGUOUS]
  app/Services/KkprlProposalAttachmentService.php · relation: calls
- `.save()` → `.save()`  [AMBIGUOUS]
  app/Services/KkprlProposalDraftService.php · relation: calls
- `.can()` → `.can()`  [AMBIGUOUS]
  app/Services/MobileCapabilityService.php · relation: calls
- `.exists()` → `.exists()`  [AMBIGUOUS]
  app/Services/PrivateFileService.php · relation: calls
- `.get()` → `.get()`  [AMBIGUOUS]
  app/Services/PrivateFileService.php · relation: calls
- `.delete()` → `.delete()`  [AMBIGUOUS]
  app/Services/SignatureService.php · relation: calls

## Knowledge Gaps
- **4 isolated node(s):** `landing: GET /`, `public-dashboard-charts`, `booking-wizard`, `Livewire\trigger`
  These have ≤1 connection - possible missing edges or undocumented components. (Counts symbols only; 402 node(s) total have ≤1 connection when file, concept and rationale nodes are included.)
- **43 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.
## Pedoman KKPRL extraction audit

- Durable JSON: 2304 nodes, 6212 directed edges, 108 communities and 1 hyperedge.
- Parallel relations/call sites are preserved in MultiDiGraph JSON; community analytics above use a simple undirected projection.
- 15 name-only PHP self-call resolutions are marked AMBIGUOUS, confidence 0.2; they are not proven recursion.
- Four recursive methods remain structurally extracted; external Livewire\trigger import retains its declaration at ActivityStatsOverview.php:17.
- All edge endpoints and hyperedge members resolve. Source locations are evidence of extraction, not a complete PHP type analysis.
- Corpus scope: app PHP plus Pedoman KKPRL boundaries; other project documents/mobile sources are outside this additional graph.
