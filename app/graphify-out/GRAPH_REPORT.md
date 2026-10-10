# Graph Report - app  (2026-10-06)

## Corpus Check
- cluster-only mode — file stats not available

## Summary
- Current durable graph: 2261 nodes · 5973 undirected edges · 104 retained communities.
- Confidence counts: {'AMBIGUOUS': 5, 'EXTRACTED': 5849, 'INFERRED': 119}.
- Counter-update AST: zero model tokens; host semantic usage unavailable (unknown, not zero).

## Graph Freshness
- Built from commit: `35c14bf1`
- Run `git rev-parse HEAD` and compare to check if the graph is stale.
- Run `graphify update .` after code changes (no API cost).

## Counter Source Freshness

Selected counter sources are committed at `0c0cfdc`; their current SHA-256 and scoped manifests are verified. The original build commit and all older update/audit metadata are retained separately. This is an incremental source refresh, not a complete repository extraction at that commit.

## Historical community analytics

The following hubs, community membership summaries, cohesion, questions and prior audit sections describe the previous graph snapshot. No reclustering or centrality recomputation occurred for the counter update. Old assignments and all old audit sections are retained; new entities join the existing guide community.

## Community Hubs (Navigation)
- Community 0
- Community 1
- Community 2
- Community 3
- Community 4
- Community 5
- Community 6
- Community 7
- Community 8
- Community 9
- Community 10
- Community 11
- Community 12
- Community 13
- Community 14
- Community 15
- Community 16
- Community 17
- Community 18
- Community 19
- Community 20
- Community 21
- Community 22
- Community 23
- Community 24
- Community 25
- Community 26
- Community 27
- Community 28
- Community 29
- Community 30
- Community 31
- Community 32
- Community 33
- Community 34
- Community 35
- Community 36
- Community 37
- Community 38
- Community 39
- Community 40
- Community 41
- Community 42
- Community 43
- Community 44
- Community 45
- Community 46
- Community 47
- Community 48
- Community 49
- Community 50
- Community 51
- Community 52
- Community 53
- Community 54
- Community 55
- Community 56
- Community 57
- Community 58
- Community 59
- Community 60
- Community 61
- Community 62
- Community 63
- Community 64
- Community 65
- Community 66
- Community 67
- Community 68
- Community 69
- Community 70
- Community 71
- Community 72
- Community 73
- Community 74
- Community 75
- Community 76
- Community 77
- Community 78
- Community 79
- Community 80
- Community 81
- Community 82
- Community 83
- Community 84
- Community 85
- Community 86
- Community 87
- Community 88
- Community 89
- Community 90
- Community 91
- Community 92
- Community 93
- Community 94
- Community 95
- Community 96
- Community 97
- Community 98
- Community 99
- Community 100
- Community 101
- Community 102
- Community 103

## God Nodes (most connected - your core abstractions)
1. `Client` - 145 edges
2. `KkprlProposal` - 79 edges
3. `User` - 70 edges
4. `BeritaAcara` - 58 edges
5. `LearningMaterial` - 43 edges
6. `Assignment` - 40 edges
7. `MobileTransformer` - 40 edges
8. `KkprlProposalDocumentService` - 37 edges
9. `ConsultationReport` - 36 edges
10. `Service` - 35 edges

## Surprising Connections (you probably didn't know these)
- `BackfillDataProtection` --references--> `DataHashService`  [EXTRACTED]
  Console/Commands/BackfillDataProtection.php → Services/DataHashService.php
- `KkprlProposalAccessService` --references--> `KkprlProposalAccessVerifier`  [EXTRACTED]
  Services/KkprlProposalAccessService.php → Contracts/KkprlProposalAccessVerifier.php
- `KkprlProposalAccessService` --references--> `KkprlPhoneNormalizer`  [EXTRACTED]
  Services/KkprlProposalAccessService.php → Domain/Kkprl/KkprlPhoneNormalizer.php
- `KkprlProposalAttachmentService` --references--> `ProposalChapterRules`  [EXTRACTED]
  Services/KkprlProposalAttachmentService.php → Domain/Kkprl/ProposalChapterRules.php
- `KkprlProposalAttachmentService` --references--> `ProposalInlineAnchorResolver`  [EXTRACTED]
  Services/KkprlProposalAttachmentService.php → Domain/Kkprl/ProposalInlineAnchorResolver.php

## Import Cycles
- None detected.

## Communities (104 total, 41 thin omitted)

### Community 0 - "Community 0"
Cohesion: 0.05
Nodes (31): Asmit\FilamentUpload\Enums\PdfViewFit, Asmit\FilamentUpload\Forms\Components\AdvancedFileUpload, Filament\Actions\Action, Filament\Forms\Components\DateTimePicker, Filament\Forms\Components\FileUpload, Filament\Forms\Components\Repeater, Filament\Forms\Components\Select, Filament\Forms\Components\Textarea (+23 more)

### Community 1 - "Community 1"
Cohesion: 0.05
Nodes (7): Illuminate\Foundation\Auth\User, BeritaAcaraPolicy, ClientPolicy, ConsultationReportPolicy, KkprlProposalPolicy, LearningGroupPolicy, UserPolicy

### Community 2 - "Community 2"
Cohesion: 0.06
Nodes (23): App\Filament\Layanankkprl\Resources\ConsultationReportResource\Pages, App\Filament\Layanankkprl\Resources\Services\Pages\CreateService, App\Filament\Layanankkprl\Resources\Services\Pages\EditService, BackedEnum, Filament\Forms\Components\DatePicker, FaqResource, LearningCategoryResource, LearningMaterialResource (+15 more)

### Community 3 - "Community 3"
Cohesion: 0.06
Nodes (13): DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException, ProposalFieldCatalog, Filament\Auth\Http\Responses\Contracts\LoginResponse, Filament\Auth\Pages\Login, Login, Filament\Schemas\Components\Component, Illuminate\Support\Facades\Auth, Illuminate\Support\Facades\Session (+5 more)

### Community 4 - "Community 4"
Cohesion: 0.05
Nodes (6): LearningMaterialController, LearningMaterial, LearningMaterialAccess, LearningMaterialAccessPolicy, LearningMaterialPolicy, LearningTrackingService

### Community 5 - "Community 5"
Cohesion: 0.08
Nodes (14): Illuminate\Bus\Queueable, Illuminate\Mail\Mailable, Illuminate\Mail\Mailables\Content, Illuminate\Mail\Mailables\Envelope, Illuminate\Queue\SerializesModels, BookingCreatedMail, BookingFinishedMail, ClientCreated (+6 more)

### Community 6 - "Community 6"
Cohesion: 0.05
Nodes (21): Filament\Actions\ForceDeleteAction, Filament\Actions\RestoreAction, EditClient, EditConsultationReport, EditFaq, EditHoliday, EditLearningCategory, Width (+13 more)

### Community 7 - "Community 7"
Cohesion: 0.06
Nodes (16): ConsultationReportsRelationManager, FaqsTable, KkprlProposalEditApprovalsTable, AttachmentsRelationManager, DocumentsRelationManager, ReviewEventsRelationManager, KkprlProposalsTable, LearningCategoriesTable (+8 more)

### Community 8 - "Community 8"
Cohesion: 0.08
Nodes (9): Carbon\CarbonImmutable, ProposalAiRequestFailed, ProposalEvidenceCatalog, Google\Auth\Credentials\ServiceAccountCredentials, Illuminate\Http\Client\ConnectionException, Illuminate\Http\Client\Response, InteractsWithKkprlEvidence, FirebaseCloudMessaging (+1 more)

### Community 9 - "Community 9"
Cohesion: 0.18
Nodes (23): App\Filament\Layanankkprl\Resources\PublicFeedbackResource\Pages, App\Filament\Layanankkprl\Resources\SatisfactionSurveyResource\Pages, Filament\Actions\BulkActionGroup, Filament\Actions\DeleteAction, Filament\Actions\DeleteBulkAction, Filament\Actions\EditAction, Filament\Actions\ForceDeleteBulkAction, Filament\Actions\RestoreBulkAction (+15 more)

### Community 10 - "Community 10"
Cohesion: 0.08
Nodes (13): Filament\Support\Facades\FilamentView, Filament\View\PanelsRenderHook, Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit, Illuminate\Filesystem\Filesystem, Illuminate\Support\ServiceProvider, Illuminate\View\Compilers\BladeCompiler, SatisfactionSurvey, PublicFeedbackObserver (+5 more)

### Community 11 - "Community 11"
Cohesion: 0.16
Nodes (8): AppConfigController, FeedbackController, MobileNotificationController, ProfileController, LearningTrackingController, Illuminate\Http\JsonResponse, Illuminate\Http\Request, Illuminate\Notifications\DatabaseNotification

### Community 12 - "Community 12"
Cohesion: 0.14
Nodes (10): Illuminate\Database\Eloquent\Factories\HasFactory, Illuminate\Database\Eloquent\Model, Illuminate\Database\Eloquent\Relations\BelongsToMany, Illuminate\Database\Eloquent\SoftDeletes, Illuminate\Support\Str, ActivityLog, AiChatLog, AppSettings (+2 more)

### Community 13 - "Community 13"
Cohesion: 0.09
Nodes (13): Filament\Actions\CreateAction, ListFaqs, ListHolidays, ListKkprlProposals, ListLearningCategories, ListLearningGroups, ListPublicFeedback, ListRegulations (+5 more)

### Community 14 - "Community 14"
Cohesion: 0.09
Nodes (14): ClientStatsOverview, LearningAccessResource, ListLearningAccesses, LearningAnalyticsOverview, ActivityStatsOverview, Filament\Tables\Contracts\HasTable, Filament\Widgets\Concerns\InteractsWithPageTable, Filament\Widgets\StatsOverviewWidget (+6 more)

### Community 15 - "Community 15"
Cohesion: 0.12
Nodes (6): Illuminate\Database\Eloquent\Casts\Attribute, Illuminate\Database\Eloquent\Relations\HasManyThrough, Illuminate\Database\Eloquent\Relations\HasOne, Client, self, ClientObserver

### Community 16 - "Community 16"
Cohesion: 0.10
Nodes (4): BeritaAcaraCompletedMail, BeritaAcara, self, BeritaAcaraObserver

### Community 17 - "Community 17"
Cohesion: 0.09
Nodes (14): CreateClient, CreateFaq, CreateHoliday, CreateLearningCategory, Width, CreatePublicFeedback, CreateRunningText, CreateSatisfactionSurvey (+6 more)

### Community 18 - "Community 18"
Cohesion: 0.07
Nodes (4): Illuminate\Database\Eloquent\Relations\BelongsTo, KkprlProposalChat, KkprlProposalReviewEvent, LearningMaterialOpen

### Community 19 - "Community 19"
Cohesion: 0.09
Nodes (5): Illuminate\Support\Facades\Cache, Regulation, RegulationObserver, RegulationPolicy, ContentDeliveryService

### Community 20 - "Community 20"
Cohesion: 0.18
Nodes (6): Carbon\CarbonInterface, EditApprovalDenied, EditScopeDenied, EditSessionExpired, KkprlProposalEditSession, KkprlProposalEditApprovalService

### Community 21 - "Community 21"
Cohesion: 0.16
Nodes (10): AttachmentQuota, InvalidAttachmentFile, ProposalLocked, KkprlProposalAttachmentDownloadController, Illuminate\Http\UploadedFile, Illuminate\Support\Facades\Storage, InvalidArgumentException, Livewire\Attributes\On (+2 more)

### Community 22 - "Community 22"
Cohesion: 0.09
Nodes (6): KkprlProposalDocumentDownloadController, KkprlProposalAccess, KkprlProposalResume, KkprlProposalStart, KkprlProposalDocument, KkprlProposalAccessService

### Community 23 - "Community 23"
Cohesion: 0.17
Nodes (4): ProposalProgress, ProposalProgressResult, KkprlProposalWizard, KkprlProposalDraftService

### Community 24 - "Community 24"
Cohesion: 0.10
Nodes (7): AttendeeSign, Livewire\Attributes\Layout, BelajarKkprlGroup, Livewire\Component, LandingPage, PublicAttendance, SatisfactionSurveyResults

### Community 25 - "Community 25"
Cohesion: 0.07
Nodes (15): App\Filament\Resources\ActivityResource\Pages, App\Filament\Resources\CityResource\Pages, App\Filament\Resources\ProvinceResource\Pages, App\Filament\Resources\SubjectResource\Pages, Filament\Forms\Form, BackedEnum, UnitEnum, ProvinceResource (+7 more)

### Community 26 - "Community 26"
Cohesion: 0.14
Nodes (6): AssignmentController, ClientController, ScheduleController, Illuminate\Validation\Rule, MobileTransformer, EnsuresMobileVersion

### Community 27 - "Community 27"
Cohesion: 0.13
Nodes (20): Filament\Http\Middleware\Authenticate, Filament\Http\Middleware\AuthenticateSession, Filament\Http\Middleware\DisableBladeIconComponents, Filament\Http\Middleware\DispatchServingFilamentEvent, Dashboard, Filament\Pages\Dashboard\Concerns\HasFiltersForm, Dashboard, Filament\Panel (+12 more)

### Community 28 - "Community 28"
Cohesion: 0.10
Nodes (5): ActivityTypeChart, MonthlyServiceChart, ServiceLocationChart, Filament\Widgets\ChartWidget\Concerns\HasFiltersSchema, ConsultationLocation

### Community 29 - "Community 29"
Cohesion: 0.10
Nodes (8): ActiveRevisionExists, AttachmentQuotaViolation, EditApprovalUsed, InvalidProposalCredentials, ProposalAccessDenied, ProposalAccessRateLimited, Illuminate\Support\Facades\RateLimiter, RuntimeException

### Community 30 - "Community 30"
Cohesion: 0.13
Nodes (8): DOMComment, DOMDocument, DOMElement, DOMNode, ConsultationReportController, Illuminate\Support\Arr, SafeRichText, SignatureService

### Community 34 - "Community 34"
Cohesion: 0.13
Nodes (7): Database\Factories\UserFactory, Illuminate\Auth\Access\Response, Illuminate\Notifications\Notifiable, Laravel\Sanctum\HasApiTokens, User, AssignmentPolicy, Spatie\Permission\Traits\HasRoles

### Community 35 - "Community 35"
Cohesion: 0.13
Nodes (5): ProposalChapterRules, ProposalInlineAnchorResolver, Illuminate\Validation\ValidationException, KkprlProposalSnapshotService, KkprlProposalSubmissionService

### Community 36 - "Community 36"
Cohesion: 0.11
Nodes (4): Filament\Forms\Components\Field, BeritaAcaraRelationManager, SignaturePad, static

### Community 37 - "Community 37"
Cohesion: 0.09
Nodes (8): Filament\Actions, ListConsultationLocations, CreateConsultationReport, ListConsultationReports, ListRunningTexts, ListUsers, ListProvinces, ListSubjects

### Community 38 - "Community 38"
Cohesion: 0.11
Nodes (8): ViewClient, KkprlProposalResource, BackedEnum, UnitEnum, ViewKkprlProposal, ViewActivity, Filament\Resources\Pages\ViewRecord, ViewSubject

### Community 39 - "Community 39"
Cohesion: 0.16
Nodes (7): Closure, MobileFileController, AttachRequestId, EnsureMobileJsonRequest, PrivateFileService, Symfony\Component\HttpFoundation\Response, Symfony\Component\HttpFoundation\StreamedResponse

### Community 40 - "Community 40"
Cohesion: 0.15
Nodes (7): ActivityPerMonthChart, ActivityPerProvinceChart, Filament\Widgets\ChartWidget, Filament\Widgets\Concerns\InteractsWithPageFilters, OrganizerComparisonChart, Activity, ActivityObserver

### Community 42 - "Community 42"
Cohesion: 0.14
Nodes (3): Illuminate\Auth\Access\HandlesAuthorization, SatisfactionSurveyResult, SatisfactionSurveyResultPolicy

### Community 44 - "Community 44"
Cohesion: 0.14
Nodes (3): Faq, FaqObserver, FaqPolicy

### Community 45 - "Community 45"
Cohesion: 0.16
Nodes (4): KkprlProposalAccessVerifier, KkprlPhoneNormalizer, DataHashService, KkprlTicketPhoneAccessVerifier

### Community 46 - "Community 46"
Cohesion: 0.11
Nodes (7): ListClients, Filament\Pages\Concerns\ExposesTableToWidgets, ActivityResource, EditActivity, ListActivities, BackedEnum, UnitEnum

### Community 48 - "Community 48"
Cohesion: 0.20
Nodes (7): AuthenticateSummaryUser, AuthController, EnsureMobileUserIsActive, Illuminate\Database\Eloquent\Concerns\HasUuids, Laravel\Sanctum\PersonalAccessToken, UserDevice, MobileCapabilityService

### Community 53 - "Community 53"
Cohesion: 0.15
Nodes (3): HasMany, Service, ServicePolicy

### Community 54 - "Community 54"
Cohesion: 0.19
Nodes (4): CreateAssignments, Assignment, AssignmentObserver, BookingAvailabilityService

### Community 55 - "Community 55"
Cohesion: 0.22
Nodes (4): Illuminate\Notifications\Notification, MobileOperationalNotification, Ramsey\Uuid\Uuid, MobileNotificationService

### Community 58 - "Community 58"
Cohesion: 0.19
Nodes (4): Exception, NotificationLog, NotificationService, Throwable

### Community 59 - "Community 59"
Cohesion: 0.14
Nodes (5): LearningGroupResource, CreateLearningGroup, Width, EditLearningGroup, Width

### Community 60 - "Community 60"
Cohesion: 0.22
Nodes (3): ProposalGuideController, ProposalGuide, ProposalGuidePolicy

### Community 63 - "Community 63"
Cohesion: 0.22
Nodes (6): Illuminate\Contracts\Queue\ShouldQueue, Illuminate\Foundation\Bus\Dispatchable, Illuminate\Foundation\Queue\Queueable, Illuminate\Queue\InteractsWithQueue, ChunkRegulationJob, SendMobilePush

### Community 64 - "Community 64"
Cohesion: 0.21
Nodes (3): ActivitiesRelationManager, City, Province

### Community 65 - "Community 65"
Cohesion: 0.15
Nodes (5): CityResource, EditCity, ListCities, BackedEnum, UnitEnum

### Community 67 - "Community 67"
Cohesion: 0.20
Nodes (5): Barryvdh\DomPDF\Facade\Pdf, ProposalDocumentSectionCatalog, Smalot\PdfParser\Parser, Symfony\Component\Process\ExecutableFinder, ZipArchive

### Community 69 - "Community 69"
Cohesion: 0.20
Nodes (4): Illuminate\Auth\Access\AuthorizationException, Illuminate\Support\Facades\DB, PublicFeedbackPage, KkprlProposalRevisionService

### Community 70 - "Community 70"
Cohesion: 0.29
Nodes (3): DeprecatePlaintextData, Illuminate\Console\Command, Illuminate\Support\Facades\Crypt

### Community 74 - "Community 74"
Cohesion: 0.18
Nodes (3): BackedEnum, UnitEnum, SatisfactionSurveyResource

### Community 77 - "Community 77"
Cohesion: 0.20
Nodes (3): Illuminate\Support\Facades\Hash, Illuminate\Support\Facades\Http, RegulationChunk

### Community 79 - "Community 79"
Cohesion: 0.29
Nodes (5): DashboardController, StaffController, Controller, Illuminate\Foundation\Auth\Access\AuthorizesRequests, Illuminate\Foundation\Validation\ValidatesRequests

### Community 81 - "Community 81"
Cohesion: 0.22
Nodes (4): App\Filament\Layanankkprl\Resources\RunningTextResource\Pages, BackedEnum, UnitEnum, RunningTextResource

### Community 82 - "Community 82"
Cohesion: 0.25
Nodes (4): ProposalAiResponseParser, Illuminate\Support\Facades\Validator, Livewire\Attributes\Locked, Livewire\WithFileUploads

### Community 87 - "Community 87"
Cohesion: 0.29
Nodes (4): KkprlProposalEditApprovalResource, BackedEnum, UnitEnum, ListKkprlProposalEditApprovals

### Community 88 - "Community 88"
Cohesion: 0.25
Nodes (3): CreateLearningMaterial, Width, ListLearningMaterials

### Community 102 - "Community 102"
Cohesion: 0.50
Nodes (3): AuthenticateMobile, Illuminate\Auth\AuthenticationException, Illuminate\Auth\Middleware\Authenticate

## Knowledge Gaps
- **41 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `Client` connect `Community 15` to `Community 0`, `Community 1`, `Community 2`, `Community 5`, `Community 9`, `Community 10`, `Community 12`, `Community 14`, `Community 16`, `Community 18`, `Community 24`, `Community 26`, `Community 28`, `Community 30`, `Community 31`, `Community 32`, `Community 39`, `Community 43`, `Community 54`, `Community 55`, `Community 58`, `Community 74`, `Community 75`, `Community 78`, `Community 79`, `Community 82`, `Community 86`, `Community 89`, `Community 98`, `Community 99`, `Community 100`?**
  _High betweenness centrality (0.141) - this node is a cross-community bridge._
- **Why does `User` connect `Community 34` to `Community 1`, `Community 2`, `Community 100`, `Community 69`, `Community 103`, `Community 8`, `Community 9`, `Community 12`, `Community 77`, `Community 60`, `Community 79`, `Community 20`, `Community 85`, `Community 54`, `Community 55`, `Community 26`, `Community 31`, `Community 63`?**
  _High betweenness centrality (0.117) - this node is a cross-community bridge._
- **Why does `KkprlProposal` connect `Community 31` to `Community 0`, `Community 1`, `Community 33`, `Community 3`, `Community 35`, `Community 67`, `Community 69`, `Community 7`, `Community 8`, `Community 12`, `Community 45`, `Community 82`, `Community 20`, `Community 21`, `Community 22`, `Community 23`, `Community 29`?**
  _High betweenness centrality (0.088) - this node is a cross-community bridge._
- **Should `Community 0` be split into smaller, more focused modules?**
  _Cohesion score 0.0502497796062298 - nodes in this community are weakly interconnected._
- **Should `Community 1` be split into smaller, more focused modules?**
  _Cohesion score 0.04728604728604729 - nodes in this community are weakly interconnected._
- **Should `Community 2` be split into smaller, more focused modules?**
  _Cohesion score 0.06376811594202898 - nodes in this community are weakly interconnected._
- **Should `Community 3` be split into smaller, more focused modules?**
  _Cohesion score 0.057692307692307696 - nodes in this community are weakly interconnected._
## Proposal Guide Counters Refresh Audit

- Counter feature source commit: `0c0cfdc`. Old build and earlier provenance/audits preserved.
- Baseline → current: 2251 → 2261 nodes; 5947 → 5973 relations; 104 retained communities; 0 preserved hyperedges.
- Refreshed 3 PHP sources through native Graphify AST; added 7 semantic entities/15 relations with actual source locations and contexts. 5898 non-scope edges retained.
- Successful full GET PDF/download requests atomically increment separate counters; HEAD, Range, unavailable documents, index and redirect requests do not count. Valid UUID view_token requests deduplicate for 30 minutes via atomic Cache::add, preventing repeated browser PDF fetches from inflating one rendered opening. Bare/invalid-token URLs retain request counting; a new render creates fresh tokens. Numeric admin columns are sortable; content mass assignment excludes counters.
- Existing unrelated nodes/edges/confidence, community assignments and hyperedges preserved. Endpoint checks pass; 0 parallel endpoint pairs survive. Fresh unresolved PHP receiver candidates (5) marked AMBIGUOUS 0.2.
- Source hashes and health: `proposal-guide-counters-audit.json`; 3 selected manifest rows stamped, 334 total rows retained. Missing source files: 0 (historical metadata is not silently removed).
- Diagnostic warnings: baseline 19 parser self loops and 0 AMBIGUOUS edges; current 19 self loops and 5 AMBIGUOUS edges. No new self-loop identities. 5 new ambiguity markings include 5 reclassified existing relations and 0 new unresolved AST relation candidates. These warnings do not imply proven recursion. No dangling/missing endpoints or unrelated relation loss.
- Final implementation evidence: focused 30 tests/299 assertions PASS; Pint five PHP files PASS; full suite **483 tests/2,714 assertions PASS (189.34 s)**. Viewer regression first failed 2-versus-1, then passed. Browser fixture verified one view per render, another on refresh, one download per click, real admin draft96/65 and clean public console. Temporary fixture/file and account removed; original draft remains96/65, unpublished, without PDF. Review/diff-check PASS. Graph update does not independently rerun application/browser checks or mutate user database/deploy/commit. Host semantic tokens unavailable, not zero.
