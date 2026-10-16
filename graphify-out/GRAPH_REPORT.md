# Graph Report — OpenStreetMap main map with WorldCover inset (2026-10-12)

Existing main project/KKPRL index retained. Only 8 PHP, 1 Blade, 1 XML and 10 Markdown sources are refreshed through ADR-035; this is not a whole-repository rebuild. User screenshots, exports, generated previews, fixture storage, credentials and private payload are excluded.

## Changes and rationale

- Fixed HTTPS OpenStreetMap raster appears on the main frame; WorldCover remains on the wider inset. Coordinates, original area/length, print scales and private landscape document rules remain unchanged.
- Street zoom selection changes tile sampling only, never viewport or geometry. Main zoom is bounded at 19 and each frame at 16 tiles; both providers share a deadline and bounded total requests.
- OSM provider validates indices and complete PNG content before text-safe cache. Application User-Agent/Referer, visible copyright attribution and provider-aware cache freshness implement the documented usage policy.
- New provider metadata is captured in the immutable versioned snapshot. Legacy documents reconstruct their historical providers rather than invalidating hashes when operator configuration changes.
- Explicit offline operator configuration is distinct from provider failure; failure is visible and cannot silently create a blank main basemap.
- Owner/approval/session/lock/hash checks after public tile fetch still guard private generation. Tests use HTTP/cache/storage fakes and XML offline defaults; no provider request is needed for ordinary regression.

## Provenance and limits

- Deterministic Graphify AST plus delegated host semantic extraction of documentation, UI, XML and cross-file code behavior; caller-to-callee direction is preserved.
- Name-only PHP parser candidates are AMBIGUOUS. Legacy Blade action aliases are retained for inbound identity as UI references, never fabricated cross-language calls.
- Previous communities and unrelated node/edge metadata are retained. Directed parallel relations and historical hyperedges are preserved. Raw cohesion is recomputed without reclustering.
- Main OSM detail depends on actual mapped data; WorldCover is annual coarse imagery, not current survey imagery. Shared-hosting execution remains separately unverified.

## Token and cost accounting

- Host semantic token counters unavailable: unknown, not zero. No token count or paid cost invented.
- External model API calls: 0. Earlier cost/accounting and audits remain historical; this scoped refresh does not assert that their old source/artifact hashes match current files.

## Counts and integrity

- 3855 nodes/9893 directed parallel relations/174 communities/9 hyperedges.
- AST 124 nodes/276 relations; semantic 112 nodes/224 relations.
- Manifest 555 sources; 20 refreshed sources, not a whole-repository rebuild.
- Non-scope edges preserved: 9393; missing endpoint/member/source path: 0/0/0.
- EXTRACTED 8803; INFERRED 139; AMBIGUOUS 951; fresh ambiguous calls 74.
- External/unresolved source-less symbols: 374; scoped placeholders resolved: [].
- Parallel endpoint pairs 146; extra parallel edges 149.

## Communities — retained, raw cohesion recomputed

| ID | Name | Nodes | Raw cohesion |
|---|---|---:|---:|
| 0 | Filament Form Schemas | 78 | 0.061605 |
| 1 | Filament Tables and Actions | 108 | 0.050883 |
| 2 | Tidal Data Reader Tests | 30 | 0.200000 |
| 3 | Filament Tables and Actions | 64 | 0.069444 |
| 4 | Admin List Pages | 78 | 0.038961 |
| 5 | Admin Edit Pages | 71 | 0.038229 |
| 6 | Model Permission Policies | 71 | 0.051509 |
| 7 | Feature Test Infrastructure | 68 | 0.109745 |
| 8 | Queued Notification Mail | 67 | 0.074175 |
| 9 | Learning Material Admin | 62 | 0.040190 |
| 10 | Private Proposal Attachments | 21 | 0.219048 |
| 11 | Proposal Wizard and Access | 43 | 0.085271 |
| 12 | Edit Approval Regression Tests | 20 | 0.194737 |
| 13 | Akun Pemohon dan Approval | 59 | 0.056108 |
| 14 | Proposal Document Rendering | 255 | 0.014482 |
| 15 | Encrypted Client Records | 40 | 0.093590 |
| 16 | Akun Pemohon dan Approval | 40 | 0.107692 |
| 17 | Proposal Wizard and Access | 32 | 0.074597 |
| 18 | KkprlChapterThreeEvidenceTest | 37 | 0.100601 |
| 19 | HTTP Request Boundaries | 43 | 0.128461 |
| 20 | Encrypted Client Records | 20 | 0.142105 |
| 21 | Eloquent Model Infrastructure | 36 | 0.142857 |
| 22 | KkprlTideEvidenceTest | 33 | 0.160985 |
| 23 | Staff Edit Approval Service | 27 | 0.207977 |
| 24 | Client Admin Pages | 51 | 0.048627 |
| 25 | Encrypted Consultation Records | 23 | 0.142292 |
| 26 | Proposal Progress and Fixtures | 42 | 0.068525 |
| 27 | Staff Edit Approval Service | 21 | 0.214286 |
| 28 | Coordinate Reset Regression Tests | 28 | 0.121693 |
| 29 | Staff Assignment Workflow | 16 | 0.225000 |
| 30 | Proposal Progress and Fixtures | 24 | 0.123188 |
| 31 | Mobile Client and Scheduling | 15 | 0.276190 |
| 32 | Learning Material Downloads | 25 | 0.156667 |
| 33 | Regulation Document Chunking | 54 | 0.057303 |
| 34 | Proposal and Subject Admin | 22 | 0.116883 |
| 35 | Proposal Wizard and Access | 30 | 0.078161 |
| 36 | Encryption Backfill Command | 25 | 0.230000 |
| 37 | Staff Edit Approval Service | 28 | 0.132275 |
| 38 | Mobile Security and Richtext | 21 | 0.119048 |
| 39 | Encrypted Client Records | 25 | 0.110000 |
| 40 | Private Proposal Attachments | 42 | 0.081301 |
| 41 | Consultation Signatures and Records | 24 | 0.112319 |
| 42 | Activity Statistics Widgets | 15 | 0.219048 |
| 43 | Model Permission Policies | 13 | 0.153846 |
| 44 | Institution Schema Repair | 49 | 0.063776 |
| 45 | GMRT Profiles and Coordinates | 15 | 0.133333 |
| 46 | Private File Downloads | 23 | 0.154150 |
| 47 | Proposal Document Rendering | 49 | 0.073980 |
| 48 | Map Measurement and Manual Values | 23 | 0.126482 |
| 49 | Consultation Booking Wizard | 61 | 0.052459 |
| 50 | FAQ Permissions and Records | 7 | 0.666667 |
| 51 | Tidal Evidence and Provider Limits | 22 | 0.108225 |
| 52 | Application Logging and Jobs | 22 | 0.134199 |
| 53 | Edit Approval Admin | 22 | 0.116883 |
| 54 | Model Permission Policies | 21 | 0.138095 |
| 55 | Filament Panel Configuration | 21 | 0.180952 |
| 56 | Staff Edit Approval Service | 27 | 0.105413 |
| 57 | Proposal Guide Administration | 83 | 0.049956 |
| 58 | Survey Result Admin | 10 | 0.244444 |
| 59 | Filament Form Schemas | 21 | 0.128571 |
| 60 | Bathymetry PNG Rendering | 20 | 0.100000 |
| 61 | KkprlBathymetryTest | 20 | 0.205263 |
| 62 | Mobile Authentication and Devices | 27 | 0.122507 |
| 63 | Learning Access Permissions | 24 | 0.126812 |
| 64 | Edit Approval Regression Tests | 28 | 0.111111 |
| 65 | Survey Result Permissions | 19 | 0.140351 |
| 66 | Performance Result Permissions | 19 | 0.140351 |
| 67 | Harmonics and Artifact Identity | 12 | 0.287879 |
| 68 | Chapter Three Image Analysis | 53 | 0.064586 |
| 69 | FAQ Permissions and Records | 24 | 0.112319 |
| 70 | Consultation Service Permissions | 19 | 0.140351 |
| 71 | Akun Pemohon dan Approval | 18 | 0.130719 |
| 72 | Private Artifact Integrity | 66 | 0.049417 |
| 73 | Shared Hosting Image Evidence | 18 | 0.111111 |
| 74 | KkprlProposalWizardTest | 18 | 0.111111 |
| 75 | KkprlSeabedProfileTest | 18 | 0.241830 |
| 76 | Admin Login Security | 14 | 0.230769 |
| 77 | Filament Form Schemas | 27 | 0.113960 |
| 78 | Consultation Location Permissions | 19 | 0.140351 |
| 79 | Filament Tables and Actions | 23 | 0.094862 |
| 80 | Model Permission Policies | 18 | 0.150327 |
| 81 | Proposal Wizard Regression Tests | 17 | 0.125000 |
| 82 | Learning Session Analytics | 27 | 0.102564 |
| 83 | Model Permission Policies | 21 | 0.138095 |
| 84 | Mobile Notifications and Schedules | 8 | 0.285714 |
| 85 | Akun Pemohon dan Approval | 17 | 0.147059 |
| 86 | Private Proposal Attachments | 32 | 0.118952 |
| 87 | Consultation Report Permissions | 29 | 0.093596 |
| 88 | Learning Activity Logs | 18 | 0.156863 |
| 89 | Proposal Wizard and Access | 29 | 0.078818 |
| 90 | Tidal Data Reader Tests | 17 | 0.227941 |
| 91 | Satisfaction Survey Records | 18 | 0.163399 |
| 92 | Conditional Chapter Five Decisions | 15 | 0.161905 |
| 93 | Tidal Evidence and Provider Limits | 11 | 0.181818 |
| 94 | Akun Pemohon dan Approval | 15 | 0.142857 |
| 95 | Private Artifact Integrity | 21 | 0.071429 |
| 96 | KkprlBathymetryTest | 16 | 0.225000 |
| 97 | GMRT Profiles and Coordinates | 16 | 0.133333 |
| 98 | Holiday Permissions | 14 | 0.208791 |
| 99 | Role Permissions | 14 | 0.208791 |
| 100 | Admin Edit Pages | 13 | 0.153846 |
| 101 | Model Permission Policies | 13 | 0.153846 |
| 102 | Mobile Architecture Plan | 13 | 0.243590 |
| 103 | Public Learning Regression Tests | 13 | 0.384615 |
| 104 | KkprlChapterFiveTest | 13 | 0.282051 |
| 105 | Proposal Field Catalog | 13 | 0.205128 |
| 106 | Chapter Rules and Snapshots | 15 | 0.171429 |
| 107 | Model Permission Policies | 13 | 0.153846 |
| 108 | Firebase Push Delivery | 12 | 0.257576 |
| 109 | Admin Login Security | 13 | 0.320513 |
| 110 | Private Proposal Attachments | 12 | 0.272727 |
| 111 | Chapter Rules and Snapshots | 11 | 0.309091 |
| 112 | Learning Access Admin | 11 | 0.200000 |
| 113 | Learning Material Admin | 16 | 0.141667 |
| 114 | Model Permission Policies | 13 | 0.153846 |
| 115 | Learning Material Admin | 11 | 0.181818 |
| 116 | Harmonics and Artifact Identity | 13 | 0.153846 |
| 117 | Tidal Data Reader Tests | 12 | 0.272727 |
| 118 | Audited Staff Edit Decisions | 11 | 0.181818 |
| 119 | Akun Pemohon dan Approval | 11 | 0.418182 |
| 120 | Tidal Data Reader Tests | 12 | 0.166667 |
| 121 | Encrypted Client Records | 10 | 0.400000 |
| 122 | Mobile Client and Scheduling | 16 | 0.208333 |
| 123 | Admin Login Security | 10 | 0.222222 |
| 124 | Tidal Data Reader Tests | 10 | 0.288889 |
| 125 | Public Livewire Pages | 10 | 0.222222 |
| 126 | Proposal Interface Validation | 10 | 0.266667 |
| 127 | Akun Pemohon dan Approval | 10 | 0.200000 |
| 128 | Chapter One Input Reliability | 10 | 0.266667 |
| 129 | Private Proposal Attachments | 11 | 0.290909 |
| 130 | Proposal Progress and Fixtures | 9 | 0.361111 |
| 131 | Chapter Export Decisions | 9 | 0.222222 |
| 132 | Tide Chart Unit Tests | 9 | 0.555556 |
| 133 | Chapter Three Evidence Interface | 8 | 0.250000 |
| 134 | Mobile Security and Richtext | 15 | 0.247619 |
| 135 | Public Status Lookup | 8 | 0.285714 |
| 136 | Proposal AI Chat Interface | 8 | 0.250000 |
| 137 | kkprl-proposal-wizard.blade.php | 11 | 0.127273 |
| 138 | Immutable Proposal Revision Decisions | 8 | 0.250000 |
| 139 | Tidal Data Reader Tests | 8 | 0.500000 |
| 140 | Staff Assignment Workflow | 5 | 0.400000 |
| 141 | Feature Test Infrastructure | 5 | 0.400000 |
| 142 | Proposal Progress and Fixtures | 3 | 0.666667 |
| 145 | Akun Pemohon dan Approval | 7 | 0.333333 |
| 146 | Akun Pemohon dan Approval | 7 | 0.285714 |
| 147 | KkprlProposalSchemaTest | 7 | 0.285714 |
| 148 | Tidal Attachment Interface | 6 | 0.333333 |
| 149 | Phone Normalization and Hashes | 6 | 0.466667 |
| 150 | Akun Pemohon dan Approval | 6 | 0.333333 |
| 151 | Bathymetry PNG Rendering | 6 | 0.666667 |
| 153 | KkprlProposalDocumentDownloadTest | 6 | 0.600000 |
| 155 | Proposal Field Catalog | 5 | 0.600000 |
| 161 | kkprl-applicant-portal.blade.php | 5 | 0.300000 |
| 162 | Dependency Security Baseline | 4 | 0.500000 |
| 163 | Schedule Preview Editor | 3 | 0.666667 |
| 164 | Learning Search Filters | 2 | 1.000000 |
| 165 | Historical Release Evidence | 2 | 1.000000 |
| 166 | Bathymetry Builder Interface | 2 | 1.000000 |
| 167 | Profile Builder Interface | 2 | 1.000000 |
| 168 | Tidal Upload Interface | 2 | 1.000000 |
| 169 | Staff Edit Approval Service | 1 | 1.000000 |
| 170 | Proposal Configuration | 3 | 0.000000 |
| 171 | External Service Configuration | 1 | 1.000000 |
| 172 | kkprl-applicant.blade.php | 1 | 1.000000 |
| 173 | Layout akun responsif dengan CSRF Vite | 1 | 1.000000 |
| 174 | Proposal Guide Interface | 8 | 0.392857 |
| 175 | Learning Group Interface | 1 | 1.000000 |
| 176 | kkprl-applicant-login.blade.php | 1 | 1.000000 |
| 177 | Proposal Access Interface | 1 | 1.000000 |
| 178 | kkprl-proposal-access-expired.blade.php | 1 | 1.000000 |
| 179 | Chapter Five Options Interface | 1 | 1.000000 |
| 180 | Profile Map Script | 1 | 1.000000 |
| 181 | Tidal Harmonic Summary Interface | 1 | 1.000000 |
| 182 | Proposal PDF Template | 6 | 0.600000 |

## Actual evidence and refreshed sources

Parent application test/build/browser evidence: docs/kkprl-osm-main-map-validation.md. Graph extraction verifies provenance, hashes and graph structure; it does not rerun application tests. All source/five artifact SHA-256 values are in osm-main-map-audit.json and .graphify_update.json.

- `app/Domain/Kkprl/ProposalLocationMapViewport.php`
- `app/Services/KkprlLocationMapRenderer.php`
- `app/Services/KkprlOpenStreetMapTiles.php`
- `app/Services/KkprlProposalSnapshotService.php`
- `config/kkprl.php`
- `docs/decisions/ADR-033-print-scale-and-location-inset.md`
- `docs/decisions/ADR-035-openstreetmap-main-map-with-satellite-inset.md`
- `docs/kkprl-chapter-one-map-spec.md`
- `docs/kkprl-map-scale-inset-spec.md`
- `docs/kkprl-osm-main-map-spec.md`
- `docs/kkprl-osm-main-map-validation.md`
- `docs/kkprl-proposal-builder-spec.md`
- `docs/kkprl-security-review.md`
- `docs/kkprl-template-field-map.md`
- `docs/kkprl-user-and-staff-guide.md`
- `phpunit.xml`
- `resources/views/livewire/kkprl-proposal-wizard.blade.php`
- `tests/Feature/KkprlLocationMapTest.php`
- `tests/Feature/KkprlOpenStreetMapTest.php`
- `tools/kkprl-location-map-preview.php`

## Client Bulk Document Download Refresh Audit

- Counterpart feature source commit `93d6616`; 4 scoped sources. Applied onto existing uncommitted user root graph/report/manifest; root outputs stay in the working tree and do not represent a full committed corpus snapshot.
- Baseline → current: 3855 → 3895 nodes; 9893 → 10025 relations; 174 retained communities; 9 preserved hyperedges. All report sections above and their analytics describe historical snapshots; no reclustering or centrality recomputation occurred here.
- Three Clients bulk actions select latest reports, berita acara or both; missing requested documents are skipped and no matches produce a notification without ZIP. Both Filament and service enforce Client viewAny/per-record view. Existing PDF templates, signatures and document status are preserved.
- PDFs are generated into private UUID directories, kept through ZipArchive close, and cleaned after response or generation failure. Safe slug/length-limited names include Client IDs. Livewire buffers/base64-encodes downloads, so this is not unbuffered browser streaming.
- Integrity: every unrelated node/edge/confidence/metadata/old community assignment and hyperedge retained; no dangling/missing endpoints or source files. 150 parallel endpoint pairs. Source SHA-256 and manifest hashes verified only for scope.
- Diagnostic warnings: baseline 24 parser self loops/951 ambiguous relations; current 24/954. No new self-loop identities; 3 new unresolved AST relation candidates. Unresolved receivers are AMBIGUOUS, not proven calls or recursion.
- Validation: Implementation agent/final source document: RED9tests failed before action/service existed; focusedGREEN9tests104assertions PASS21.86s; strengthened missing-type test with opposite document available; finalfullsuite551tests3387assertions PASS382.71s without skipped tests/warnings; Pint2newPHPfiles PASS; architecture/security/query review and gitdiffcheck PASS. Isolated browser redirects to CAPTCHA login, so no authenticated browser-action verification is claimed; Filament/Livewire components, real PDF generation/parser/ZIP contents, per-client authorization, deleted documents, temporary cleanup and render-failure cleanup are verified using isolated users/database/local storage. No frontend/package changes, user database mutation, deployment or graph-agent test rerun.
- Browser limit: isolated admin URL redirected to CAPTCHA login; no credential/login bypass and no authenticated browser-action claim. Test components use isolated users/database/storage. Graph update does not rerun tests, mutate user data or perform Git actions.
- Provenance: `client-bulk-download-audit.json`; prior audits retained. Host semantic token usage unknown, not zero.
