# Security Review KKPRL MVP

## Scope

Review ini mencakup boundary pemohon publik, data proposal, upload/download, status immutable, Filament Shield, approval edit, dan artefak dokumen. Review tidak menggantikan penetration test atau sign-off operasional organisasi.

## Evidence dan hasil

| Area | Kontrol | Evidence | Hasil |
|---|---|---|---|
| Credential pemohon | Tiket + HP, HMAC lookup, verifier boundary, generic error, rate limit pasangan/IP | `KkprlProposalAccessTest`, `KkprlTicketPhoneAccessVerifier`, ADR-001/012 | PASS |
| Session | Session access setelah verifikasi, idle timeout, timeout-safe wizard denial, proposal/root binding, Livewire proposal scope lock, timeout render tanpa data, cross-proposal denial, preflight cookie `http_only`/`same_site` dan secure-cookie production | `KkprlProposalAccessTest`, wizard/download/preflight tests | PASS teknis; regression terbaru perlu dijalankan pada host PHP |
| Data pribadi | HP/email/nama/institusi/payload terenkripsi; kolom hash untuk lookup; kolom protection disembunyikan | `KkprlProposalSchemaTest`, model casts/hidden | PASS |
| Artefak | Model memaksa disk `kkprl_private`, root non-public, `serve=false`, path dokumen memuat snapshot+manifest, controller/snapshot/revisi/renderer menolak disk lain, download terotorisasi | `KkprlPreflightTest`, strict preflight, download tests, ADR-016 | PASS teknis; regression terbaru perlu dijalankan pada host PHP |
| Upload | MIME actual + file signature, nama sanitized, UUID path, model memaksa private disk, atomic batch, quota 10 file/15 MiB, conditional chapter relevance and inline anchor rechecked under proposal row-lock on upload/replace, cleanup file saat persistence gagal | attachment service/unit-feature tests | PASS teknis; regression terbaru perlu dijalankan pada host PHP |
| Export | Snapshot/manifest bersama, identitas document mencakup manifest, path binary immutable, retry idempotent, missing binary ditolak, stale snapshot guard, PDF renderer fail-closed, PDF lampiran terbaca pada Word/PDF smoke, credential HP tidak masuk DOCX/PDF | document generation/download tests + Word/PDF viewer smoke 2026-08-25 | PASS teknis; regression terbaru dan golden template eksternal tetap terbuka |
| State | Submitted/needs_revision immutable, stale autosave row-lock, original revision tidak ditimpa, review event dan snapshot dokumen append-only, path dokumen generated immutable | submission/draft/revision/document tests | PASS teknis; regression terbaru perlu dijalankan pada host PHP |
| Staff authorization | Permission Shield terpisah untuk review, request, approve, run session, download; policy resource memakai permission bisnis, bukan nama role/CRUD implisit | Shield/Filament tests | PASS teknis; regression terbaru perlu dijalankan pada host PHP |
| Separation of duties | Petugas A tidak self-approve; satu approval/sesi aktif per revisi; approval scoped, expiry, one-use, known-path validation termasuk penolakan nested path, payload sanitization, session audit before/after | edit approval tests/service, ADR-015 | PASS teknis; regression terbaru perlu dijalankan pada host PHP |
| Logging privacy | Event menyimpan actor/request ID dan metadata audit; credential mentah tidak ditulis ke artefak | review event model + privacy assertions | PASS teknis; verifikasi konfigurasi log production tetap diperlukan |

## Residual risk dan tindakan sebelum production

- Credential tiket + HP tetap lebih lemah daripada OTP. Boundary verifier siap diganti/ditambah OTP.
- `APP_DEBUG=false`, HTTPS, session driver, permission role mapping, backup, dan akses filesystem harus diverifikasi pada environment production.
- Retensi/penghapusan belum otomatis; kebijakan organisasi dan approval operasional diperlukan sebelum scheduler penghapusan dibuat.
- Android/device UAT, jaringan lambat, viewer Word/PDF, dan golden comparison eksternal masih wajib.
- Generasi dokumen MVP sinkron berbatas; monitor durasi, memory, timeout renderer, dan failure rate sesuai ADR-013 sebelum menentukan migrasi queue.

## Kesimpulan

Security/privacy review teknis untuk MVP lulus berdasarkan evidence otomatis dan inspeksi route/storage/domain. Release belum boleh dinyatakan final sampai residual risk operasional dan UAT eksternal ditandatangani.
