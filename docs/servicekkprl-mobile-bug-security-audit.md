# ServiceKKPRL Mobile Navigation Bug and Security Audit

Audit date: 2026-07-27 (Asia/Jayapura)  
Repository branch: `v2.1_kawanruanglaut`  
Mobile package: `com.timurbersinar.servicekkprl`  
Local mobile API routes reviewed: 38

## Executive summary

The undefined-detail failure was caused by missing route-parameter validation. The detail page converted a missing Vue Router parameter with `String(route.params.ticket)`, which turns `undefined` into the literal string `"undefined"`, and immediately issued `GET /clients/undefined`. Notification routes, post-login redirects, and native app URLs also accepted untrusted route strings directly. Ionic history could therefore preserve or revisit an invalid detail location during Back or tab transitions.

The fix centralizes route and identifier validation, uses named routes, prevents invalid API requests, handles unavailable records with an authorized fallback and notice, resets detail drafts when the ticket changes, and allowlists notification/deep-link destinations. Runtime testing also exposed an Ionic transition failure because the parent tabs view rendered without an `IonPage`; wrapping `TabsPage` removed the `classList` exception and missing-page warning. The existing Capacitor 8 direct native secure-storage bridge and the previous white-screen fixes were preserved.

The audit also resolved security issues involving stored rich-text XSS, inappropriate storage of a derivative of the upstream Summary password, Sanctum token scope confusion, incomplete device-token revocation, unbounded/invalid pagination, broad Android FileProvider paths, missing authenticated API rate limits, and non-JSON authentication failures.

Automated tests, TypeScript validation, production Vite build, Capacitor sync, Android unit tests, `assembleDebug`, mocked-browser runtime assertions, and real-device navigation checks pass. The rebuilt APK was installed successfully on Samsung SM-A325F `RR8T4026R9F`. Hardware Back, in-app Back, tabs, valid/invalid deep links (including cold start), notification-to-detail navigation, rotation, and background restore were exercised without a native crash, WebView JavaScript exception, `/undefined` request, or resource-not-found screen.

## Reproduction and root cause

### Deterministic pre-fix reproduction

1. Enter or restore `/requests/undefined`, or deliver a database/FCM notification whose `route` is `/requests/undefined`.
2. The router accepted the route because it did not validate `:ticket`.
3. `RequestDetailPage.vue` evaluated `String(route.params.ticket)`.
4. A missing value became the literal `"undefined"`.
5. TanStack Query ran immediately and requested `/api/mobile/v1/clients/undefined`.
6. Laravel route-model binding returned a 404 with `The requested resource was not found.`
7. The invalid location could remain in Ionic history and be revisited by Android Back, an in-app Back button, or a tab transition.

The pre-fix source was introduced in commit `6765239` and contained:

```ts
const ticket = computed(() => String(route.params.ticket))

const clientQuery = useQuery({
  queryKey: ['client', ticket],
  queryFn: async () =>
    (await api.get(`/clients/${ticket.value}`)).data.data,
})
```

### Contributing causes

- Request links manually concatenated path strings rather than using one validated route builder.
- Push-notification and notification-inbox routes were passed directly to `router.push`.
- The post-login `redirect` query was accepted without an internal-route allowlist.
- Native custom-scheme and HTTPS app links were declared in Android but did not have a controlled Capacitor `appUrlOpen`/terminated-launch handler.
- Reusing a detail component for another ticket retained status, schedule, report, and Berita Acara drafts.
- A valid but deleted, stale, or newly inaccessible resource displayed the raw detail error instead of returning to the authorized list.

### Corrected behavior

- `undefined`, `null`, `NaN`, empty, multi-valued, overlong, control-character, slash, query, fragment, and malformed identifiers are rejected before page loading.
- Positive numeric IDs are validated for feedback and nested resources.
- Invalid links fall back to Requests, Feedback, Notifications, or Dashboard with a useful Indonesian notice.
- A 403/404/410 for a previously valid detail redirects to the authorized list and never creates another invalid request.
- FCM, inbox notifications, login redirects, custom-scheme URLs, and production HTTPS mobile links use one allowlist.
- Deep links from cold start are handled with `App.getLaunchUrl`; foreground/background app links use `appUrlOpen`.
- Detail drafts are cleared when the ticket changes.
- PDF/document paths use the same guarded client-path constructor.
- The tabs parent now renders as an Ionic page, so returning from a root detail view no longer throws during the transition.

## Functional and reliability audit

| Area | Review/result |
| --- | --- |
| Startup and white-screen prevention | `main.ts` still mounts before async initialization; direct native secure-storage calls remain unchanged. Production build passes. |
| Login | Summary authentication, inactive account, missing mobile role, token issuance, safe redirect, and login throttling reviewed/tested. |
| Logout/logout-all/device disable | Current/all mobile tokens are revoked and devices disabled. Explicit device disable now deletes the linked token. Push delivery filters disabled devices. |
| Dashboard | Counts/recent requests are bounded; recent-detail links now use validated named routes. |
| All/My Requests | Filters are normalized, API page size is bounded, ticket links are validated, and pagination is cursor-based. |
| Request detail | Valid ticket required before query; stale/deleted/inaccessible records have a controlled fallback. |
| Schedules | Nested ownership is checked on update; validation enforces date/time/online-link rules and optimistic concurrency. |
| Assignments | Nested schedule ownership, active users, deduplicated creation, conflict warnings, transactions, policy checks, and optimistic concurrency reviewed. |
| Consultation reports | Policy/nested ownership, image MIME/size limits, optimistic concurrency, protected documents, and rich-text sanitization reviewed. |
| Berita Acara | Policy/nested ownership, transaction, attendee ownership, signatures, attachment limits, signing links, PDF authorization, and rich-text sanitization reviewed. |
| Satisfaction/public feedback | List/detail permissions, positive IDs, pagination bounds, stale-detail fallback, and text-safe Vue rendering reviewed. |
| Notifications/FCM | Per-user inbox lookup, unread handling, deterministic IDs, after-commit jobs, retry backoff, delivery dedupe, privacy-safe payloads, disabled devices, and route allowlisting reviewed. |
| Profile/capabilities | Capabilities derive from Shield permissions; inactive/no-access users have their current token revoked. |
| Update/maintenance | Public app-config verified in production; local routing to update-required remains guarded. |
| Offline/reconnect | Writes are blocked before transmission while offline; reconnect refreshes profile and queries. No unsafe automatic replay of mutations was added. |
| Upload interruption/retry | Failure is surfaced and no destructive endpoint exists. Automatic mutation replay remains intentionally disabled to avoid duplicate writes. |
| Error states | 401, 403, 404, 409, 422, 429, offline, and generic failures reviewed. Mobile API requests now always negotiate safe JSON and carry request IDs. |
| Rotation/background/restart | Router/deep-link state is validated on every entry. Samsung testing confirmed detail survives background/restore and landscape rotation, and an invalid cold-start URL reaches the controlled Notifications fallback. |

## Security findings

### Critical

No reproducible Critical issue was found in the reviewed mobile/API scope.

### High — resolved

1. **Stored rich-text XSS in the mobile editor and raw public/PDF rendering**
   - Evidence: stored report HTML was assigned directly to `contenteditable.innerHTML`; Berita Acara and report Blade templates rendered stored HTML with raw Blade output.
   - Resolution: client and server allowlist sanitizers remove scripts, SVG/object/iframe/style content, comments, event handlers, URLs, styles, and all other attributes while preserving basic formatting and safe tables. Paste is plain text. Public attendee and PDF rendering sanitize legacy data at render time.
   - Regression: `sanitizeRichText.spec.ts`, `SafeRichTextTest.php`, and the existing PDF table-style test.

2. **Upstream Summary password derivative persisted locally**
   - Evidence: every Summary login executed `bcrypt($password)` and overwrote `users.password`; it also copied an upstream FCM token into a legacy plaintext column.
   - Resolution: existing local passwords are no longer overwritten. New brokered users receive a random unusable local password, and the upstream FCM token is no longer ingested. Authentication continues to rely on the Summary response.
   - Regression: mobile login test asserts the supplied Summary password cannot verify against the local hash.

3. **Non-mobile Sanctum tokens accepted by the mobile API and disabled-device tokens not always revoked**
   - Evidence: protected routes used only `auth:sanctum`; explicit device disable cleared the device association but left its personal access token usable.
   - Resolution: protected routes require the `mobile` token ability, and device disable deletes the linked token.
   - Regression: non-mobile-token rejection and device-revocation feature tests.

### Medium — resolved

1. Invalid/stale identifiers could create `/clients/undefined` and similar requests.
2. Notification, login-redirect, and deep-link routes were not allowlisted.
3. Missing `Accept: application/json` could cause unauthenticated mobile endpoints to redirect to a nonexistent `login` route and return HTML 500.
4. Notification/feedback pagination accepted invalid page sizes that could produce server errors.
5. Authenticated mobile routes had no group-wide rate limit; they now use 120 requests/minute in addition to the stricter login limit.
6. Android FileProvider exposed the full external-storage root; it now exposes only app-specific external files and cache.
7. Permission removal/inactive-account 403 responses did not immediately clear the client session; the mobile interceptor now clears only explicit revocation codes.
8. Detail drafts could cross ticket changes in a reused Ionic page.
9. Returning from a root detail view to the tabs parent produced an Ionic missing-`IonPage` warning and `classList` exception; `TabsPage` now has the required page wrapper.

### Low — resolved

1. Root ignore rules did not explicitly cover keystores, PKCS files, service-account JSON, Firebase credentials, and nested `google-services.json`; defensive patterns were added.
2. Navigation failures were shown only as generic API errors; controlled Indonesian notices were added.

### Remaining or production-only risks

1. **Medium — production deployment drift.** Safe probes on 2026-07-27 showed `app-config` at version/build `1.0.0/1`, but a request without JSON `Accept` returned HTML 500 and unauthenticated JSON responses did not consistently include the request ID in the body. The local fixes are not deployed yet.
2. **Medium — production transport policy.** HTTPS works, cleartext is disabled in Android, and production returns JSON/CORS headers. The observed response did not include an HSTS header and returned `Access-Control-Allow-Origin: *`. Confirm the reverse-proxy HSTS policy and restrict CORS to required Capacitor/web origins if browser access is needed.
3. **Medium — record-scope business policy.** Nested resources are tied to their parent and private files verify ownership, but `ClientPolicy` grants access to every client to a user with `View:Client`. If officers should see only assigned clients, record-level scoping requires a business decision and matching Filament behavior before changing it.
4. **Medium — legacy `users.fcm_token`.** New brokered logins no longer copy the Summary FCM token, and mobile registrations are encrypted in `user_devices`; historical plaintext values require an approved production data-cleanup migration.
5. **Medium — upload atomicity/idempotency.** Database transactions cannot roll back already-written filesystem objects. Interrupted report/Berita Acara uploads can leave orphan files, and schedule/report creation does not use a client idempotency key. A storage cleanup job and idempotency contract require infrastructure/API-version planning.
6. **Medium — production mutation coverage.** The physical-device pass intentionally avoided changing real service records. Login re-entry, logout/logout-all, schedule/assignment/report/Berita Acara writes, feedback submission, upload interruption, and an actual newly delivered FCM push still need a controlled staging account and staging records for end-to-end acceptance.
7. **Low — update distribution.** Production `distribution_url` is null, so the in-app update banner cannot deliver an APK even though update policy fields exist.
8. **Low — build maintenance.** Vite reports a roughly 1.28 MB main chunk, and Gradle reports plugin/AGP deprecation warnings. Neither blocks this debug build.
9. **Production confirmation required.** Confirm `APP_ENV=production`, `APP_DEBUG=false`, queue workers/retries, Firebase service-account file permissions, log redaction/retention, and database/device cleanup schedules directly on the server. Safe HTTP probes cannot prove environment values.

## Files changed

### Navigation/mobile

- `mobile/src/navigation/safeNavigation.ts`
- `mobile/src/navigation/appLinks.ts`
- `mobile/src/router/index.ts`
- `mobile/src/main.ts`
- `mobile/src/notifications/push.ts`
- `mobile/src/pages/LoginPage.vue`
- `mobile/src/pages/DashboardPage.vue`
- `mobile/src/pages/RequestsPage.vue`
- `mobile/src/pages/RequestDetailPage.vue`
- `mobile/src/pages/NotificationsPage.vue`
- `mobile/src/pages/FeedbackPage.vue`
- `mobile/src/pages/TabsPage.vue`
- `mobile/src/api/client.ts`
- `mobile/src/theme/app.css`

### Rich text and rendering

- `mobile/src/security/sanitizeRichText.ts`
- `mobile/src/components/RichTextEditor.vue`
- `app/Services/SafeRichText.php`
- `app/Http/Controllers/Api/Mobile/V1/ConsultationReportController.php`
- `app/Http/Controllers/Api/Mobile/V1/BeritaAcaraController.php`
- `resources/views/livewire/attendee-sign.blade.php`
- `resources/views/pdf/consultation-report.blade.php`
- `resources/views/pdf/berita-acara.blade.php`

### API/auth/platform hardening

- `app/Actions/Mobile/AuthenticateSummaryUser.php`
- `app/Http/Middleware/AuthenticateMobile.php`
- `app/Http/Middleware/EnsureMobileJsonRequest.php`
- `app/Http/Controllers/Api/Mobile/V1/ProfileController.php`
- `app/Http/Controllers/Api/Mobile/V1/MobileNotificationController.php`
- `app/Http/Controllers/Api/Mobile/V1/FeedbackController.php`
- `bootstrap/app.php`
- `routes/api.php`
- `mobile/android/app/src/main/res/xml/file_paths.xml`
- `.gitignore`

### Tests and documentation

- `mobile/tests/safeNavigation.spec.ts`
- `mobile/tests/sanitizeRichText.spec.ts`
- `tests/Unit/SafeRichTextTest.php`
- `tests/Feature/MobileApiTest.php`
- `docs/servicekkprl-mobile-bug-security-audit.md`

## Regression and build results

- Laravel: **40 passed, 309 assertions**.
- Mobile Vitest: **11 passed** across 4 files.
- TypeScript strict validation: passed through `vue-tsc --noEmit`.
- Production Vite build: passed; 366 modules transformed.
- Playwright mobile-viewport runtime: passed for list → valid detail → in-app Back → list and direct `/requests/undefined` fallback; no console warning/error and no invalid API request.
- PHP lint: passed for every changed/untracked PHP file.
- Repository integrity: `git diff --check` passed; no tracked Google service JSON, service-account JSON, keystore, PKCS file, or `.env` was found.
- Mobile API route inventory: 38 routes.
- Capacitor Android sync: passed; 6 native plugins found.
- Android `testDebugUnitTest`: passed (`BUILD SUCCESSFUL`).
- Android `assembleDebug`: passed (`BUILD SUCCESSFUL`).

Final APK:

- Path: `mobile/android/app/build/outputs/apk/debug/app-debug.apk`
- Package: `com.timurbersinar.servicekkprl`
- Version: `1.0.0` (`versionCode` 1)
- Min/target SDK: 29/36
- Debug signing verification: passed
- SHA-256: `95F31F496CDF73C0F015A1ECF84EA60D54087D0EB19EA7D5E8567B9865A49EC1`

## Device verification evidence

Target:

- Serial: `RR8T4026R9F`
- Product/model: `a32xx` / `SM_A325F`
- Package/activity: `com.timurbersinar.servicekkprl/.MainActivity`
- Installation: `adb install -r` returned `Success`
- Rebuilt APK size: 13,920,244 bytes

Physical results:

- Android hardware Back from a valid request detail returned to Requests: **5/5**.
- In-app Back from a valid request detail returned to Requests: passed.
- Dashboard → Requests → Notifications tab cycle rendered each destination and no error screen: **5/5**.
- Valid custom-scheme deep link opened a valid request detail: passed.
- Invalid foreground deep link fell back to Notifications with the safe notice: passed.
- Invalid cold-start deep link, after force-stop, fell back to Notifications with the safe notice: passed.
- Tapping a real inbox notification opened its valid detail; Back returned to Notifications: passed.
- Background/home and relaunch preserved the current detail: passed.
- Landscape rotation rendered the detail hierarchy; the original device rotation setting was restored: passed.
- Final filtered logcat counts: 0 native crash signals, 0 WebView JavaScript exceptions, and 0 `clients/undefined`/resource-not-found matches.
- The installed app reused an existing authenticated session, so no credentials were requested, entered by automation, or printed.

The live screens contained real applicant/notification data. Temporary device screenshots and UI hierarchy files were used only for boolean assertions and then deleted from both the workstation and `/sdcard`; they are intentionally not retained in the audit package.

Non-sensitive mocked-browser evidence is retained locally under ignored test output:

- `output/playwright/navigation-flow-passed.png`
- `output/playwright/navigation-flow-trace.zip`

Before release acceptance, repeat production-write workflows using a controlled staging account: login re-entry, logout/logout-all, schedule and assignment changes, consultation reports, Berita Acara, feedback, uploads/retries, permission revocation, offline/reconnect, and an actual newly delivered FCM push. Do not exercise those mutations against real applicant records.

## Deployment

### Backend

1. Back up the release and confirm production secrets are outside the repository.
2. Deploy the reviewed PHP/routes/views files.
3. Run `composer install --no-dev --prefer-dist --optimize-autoloader`.
4. Run `php artisan optimize:clear`, then rebuild the intended production caches.
5. Confirm `APP_ENV=production`, `APP_DEBUG=false`, HTTPS/HSTS, restricted CORS, Summary HTTPS URL, Firebase credential file permissions, and queue workers.
6. Run the Laravel suite against an isolated testing database.
7. Safely probe `app-config`, unauthenticated `/me` with and without JSON `Accept`, 401/403/404/422/429 envelopes, and `X-Request-Id`.
8. Deploy/restart queue workers after the web release.

No database migration or destructive endpoint was added.

### Android

```powershell
cd mobile
npm.cmd ci
npm.cmd test
npm.cmd run build
npx.cmd cap sync android
cd android
.\gradlew.bat testDebugUnitTest
.\gradlew.bat assembleDebug
```

Install only after validating the final APK hash/signature and confirming the intended environment/base URL.

## Rollback

1. Restore the prior backend release atomically and run `php artisan optimize:clear`.
2. Restart queue workers on the restored release.
3. Reinstall the previously approved APK with `adb install -r <previous-apk>`.
4. If a backend-only rollback is required, retain the new mobile APK only if the old API still supports the same route envelopes; otherwise roll back both together.
5. No schema rollback is required because this change adds no migration.

Do not roll back by deleting user/device data or using destructive Git commands.
