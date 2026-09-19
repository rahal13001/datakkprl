# KKPRL MVP — Release Evidence Manifest

Status: teknis lulus, release eksternal belum disetujui.

Tanggal evidence terakhir: 2026-08-28.

## Automated evidence

- PHPUnit serial: 167 tests, 886 assertions, exit code 0.
- Post-hardening 2026-08-28 added nineteen regression tests for document manifest identity/path, business-permission resource access, nested edit-scope rejection, timeout-safe Livewire rendering, replacement-file cleanup, revision-copy cleanup, single-active approval enforcement, expired-approval rollover, locked-row self-approval and self-rejection, missing-binary render rejection, private-disk model guards, fail-closed legacy attachment handling, append-only document snapshots, generated-path immutability, retry no-overwrite behavior, and session-cookie preflight. Full PHPUnit rerun remains pending on a host with PHP available.
- Strict preflight: 9/9 checks PASS sebelum gate session-cookie ditambahkan; set saat ini 10 checks dan rerun host masih pending.
- Frontend build: `npm run build` PASS.
- Composer audit: no security vulnerability advisories.
- npm audit offline: 0 vulnerabilities.
- Targeted Pint: PASS.
- `git diff --check`: PASS.
- Download authorization: reviewer-only is denied; separate document/attachment download permissions are accepted, covered by 7 targeted tests.

Commands yang harus diulang pada environment release:

    rtk php artisan test
    rtk php artisan kkprl:preflight --strict
    rtk npm run build
    rtk composer audit --no-interaction

Pada host Laragon yang tidak menambahkan Composer/PHP ke `PATH`, jalankan audit dengan executable eksplisit:

    rtk cmd.exe /c "cd /d C:\laragon\www\listdatakkprl && C:\laragon\bin\php\php-8.4.15-nts-Win32-vs17-x64\php.exe C:\laragon\bin\composer\composer.phar audit --no-interaction"

## Browser evidence

- Chrome host UAT: create draft, resume tiket + HP, autosave, conditional Bag 4/5, submit lock, private download, dan inline JPG anchor/caption lulus.
- Android Chrome-like emulation: Pixel 7 UA, 412×915, touch, Slow 3G; create/resume/wizard lulus; no horizontal overflow; console bersih; Lighthouse Accessibility, Best Practices, SEO, Agentic Browsing masing-masing 100.
- Deep-link `/proposal-kkprl` tanpa session access mengembalikan 403 sesuai policy.
- Filament proposal route tanpa session mengarah ke login. Role matrix browser memerlukan Summary identity service dan akun resmi.

Screenshot viewport temporary:

    /tmp/kkprl-android-like-wizard-padding-2026-08-25.png

## Synthetic viewer artifacts

Artefak berikut dibuat ulang 2026-08-26 pada host dan bersifat temporary, bukan golden approval resmi:

| Artifact | Size | SHA-256 |
|---|---:|---|
| `C:\Temp\kkprl-golden-smoke-bag-1.docx` | 14,483 bytes | `7414d19980c248e0f84dc26e8cda8ffb5cc9a9243e05336ccb453c5ae704da4b` |
| `C:\Temp\kkprl-golden-smoke-bag-1.pdf` | 1,302,337 bytes | `f0d3786fe0da586be96bc6e9c17bd5406b80028bdec21028d29e720c0fe9c69e` |

Viewer smoke lulus pada Word 16.0 dan Chrome PDF viewer. Artefak harus disalin ke release evidence store yang disetujui sebelum signoff.

## Open release gates

- UAT Android Chrome pada perangkat fisik, jaringan lambat, dan Word/PDF viewer perangkat.
- Perbandingan visual Word/LibreOffice/PDF terhadap template resmi/golden yang disetujui pemilik proses.
- Role matrix petugas dengan identity service aktif dan akun resmi.
- Approval alur review petugas.
- Retention, backup, filesystem, HTTPS, `APP_DEBUG=false`, permission mapping, dan release signoff operasional.

Gate tersebut sengaja tidak ditandai lulus berdasarkan automated test atau host emulation saja.
