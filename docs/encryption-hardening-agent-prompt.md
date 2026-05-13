# Introductory Prompt for the Next Agent

You are working in the Laravel 13 / Filament 5 project at `C:\laragon\www\listdatakkprl`.

The app has already been upgraded and sensitive client data has encrypted companion columns plus HMAC lookup columns. The current security objective is to finish deprecating legacy plaintext storage.

Important context:

- Plaintext columns were intentionally kept during the first encryption release for rollback safety.
- The secure target state is encrypted columns as the read/write source of truth, with legacy plaintext columns retained only as empty compatibility columns until a later schema-removal release.
- Do not drop plaintext columns yet unless the user explicitly asks for a later cleanup release.
- Do not rotate `APP_KEY` or `DATA_HASH_KEY`.
- Do not log decrypted names, emails, phone numbers, tokens, signatures, links, file paths, or message bodies.

Encryption impact to remember:

- Laravel encrypted values are non-deterministic ciphertext. Do not compare encrypted columns directly.
- Exact lookup must use HMAC columns such as `access_token_hash`, `email_hash`, `whatsapp_hash`, `attendance_url_token_hash`, and `token_hash`.
- Partial search over encrypted personal fields is not available without a separate search index.
- Public links must keep working by validating the user-supplied token through the hash column, then reading the decrypted token through model accessors when a URL/email needs to display it.
- Models should read encrypted columns first and fall back to legacy plaintext only for old, not-yet-backfilled rows.
- New writes must populate encrypted columns and hashes, not legacy plaintext columns.
- Existing legacy plaintext should be redacted only after `php artisan data-protection:backfill --dry-run` reports zero missing encrypted/hash values.

Useful commands:

```bash
php artisan data-protection:backfill --dry-run
php artisan data-protection:backfill
php artisan data-protection:deprecate-plaintext --dry-run
php artisan data-protection:deprecate-plaintext
php artisan test
php artisan route:list
php artisan migrate:status
npm run build
```

Acceptance expectations:

- New client, schedule, report, Berita Acara, attendee, survey, and notification writes do not persist sensitive values in legacy plaintext columns.
- Existing ticket/token, attendance, and signing links continue to work through hash lookup.
- PDFs and admin/public pages read decrypted values through model accessors.
- Legacy plaintext redaction is idempotent and does not remove operational plaintext such as IDs, ticket numbers, statuses, dates, service IDs, or location IDs.
