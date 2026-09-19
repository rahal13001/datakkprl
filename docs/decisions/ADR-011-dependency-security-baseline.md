# ADR-011: Dependency Security Baseline

## Status

Accepted for MVP release preparation

## Date

2026-08-24

## Context

Composer audit pada lock awal menemukan advisory pada Dompdf, Filament, Guzzle, PhpSpreadsheet, dan komponen Symfony. Dependency tersebut dipakai oleh renderer dokumen, panel review, dan boundary HTTP aplikasi.

## Decision

- Terapkan targeted update yang masih sesuai constraint `composer.json`; tidak menambah dependency baru dan tidak menjalankan `composer audit fix` yang mengubah constraint secara otomatis.
- Baseline lock saat review: Dompdf `3.1.6`, Filament `5.7.6`, Guzzle `8.0.2`, Guzzle PSR-7 `3.0.0`, PhpSpreadsheet `1.30.6`, dan Symfony `8.1.5`.
- Composer audit terhadap `composer.lock` wajib menjadi quality gate release.
- Setelah update, jalankan full PHPUnit dan build frontend sebelum handoff.

## Verification

- `composer audit --locked --format=summary`: `No security vulnerability advisories found.`
- `artisan test --without-tty`: 151 tests, 648 assertions passed.
- `npm run build`: passed.

## Consequences

- Lock file berubah bersama dependency transitive yang diperlukan untuk patch keamanan.
- Release masih membutuhkan UAT browser/mobile dan golden-file Word/PDF manual.
- Composer post-update scripts yang membutuhkan koneksi aplikasi harus dijalankan pada environment release yang memiliki database siap; autoload dapat diregenerasi dengan `--no-scripts` bila script operasional tidak tersedia.
