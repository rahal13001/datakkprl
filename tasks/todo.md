# Task Checklist: Pembuat Proposal KKPRL

Task detail dan acceptance criteria ada di [`tasks/plan.md`](plan.md).

## Gate 0 — Review sebelum coding

- [x] 0.1 Review mapping template, field wajib, satuan, format koordinat, dan aturan PKKPRL/KKRL.
- [x] Konfirmasi status final proposal: `submitted` dan proposal terkunci.
- [x] Konfirmasi hak edit petugas dan aturan revisi berbasis Filament Shield.
- [x] Konfirmasi lampiran ikut digabung ke Word/PDF per bab.
- [x] Konfirmasi tipe lampiran MVP: PDF, JPG/JPEG, PNG, WebP.
- [x] Konfirmasi gambar dapat ditempatkan inline di bawah teks/field acuan.
- [x] Konfirmasi batas lampiran: maksimal 10 file dan total 15 MB per bab.
- [x] Strategi generator Word/PDF MVP memakai renderer terkontrol dan snapshot manifest bersama.

## Gate 1 — Access dan fondasi

- [x] 1.1 Spike generator Word/PDF.
- [x] 1.2 Schema proposal, encrypted lookup, dan attachment metadata.
- [x] 1.3 Create/resume draft dengan session access.
- [x] Checkpoint 1 lulus.

## Gate 2 — Wizard

- [x] 2.1 Wizard shell, autosave, progress, dan draft validation.
- [x] 2.2 Bag 1.
- [x] 2.3 Bag 2–3.
- [x] 2.4 Bag 4–5 kondisional.
- [x] Checkpoint 2 lulus.

## Gate 3 — Output dokumen

- [x] 3.1 Final validation dan snapshot.
- [x] 3.2 Generate Word/PDF per bab.
- [x] Checkpoint 3 lulus.

## Gate 4 — Petugas

- [x] 4.1 Filament resource proposal.
- [x] 4.2 Status review, `needs_revision`, dan salinan revisi immutable setelah workflow disetujui.

## Gate 5 — Release

- [ ] 5.1 Security, privacy, dan retention review (security/privacy teknis lulus; sign-off retensi operasional masih terbuka).
- [ ] 5.2 Mobile QA dan release readiness.
- [ ] Semua success criteria MVP terpenuhi.

## Status implementasi 2026-08-25

- Baseline PHPUnit sebelum hardening: 167 test lulus dengan 886 assertion (`memory_limit=512M`, PHP 8.4.15); sembilan belas regression test tambahan wajib diulang pada host PHP.
- UAT host dengan Chrome Windows headless localhost-only lulus untuk create draft, resume tiket + HP, autosave 47/47, aktivasi Bab 4/5, submit 5 bab, lock, dan download DOCX/PDF; Android/device UAT manual masih terbuka.
- Chrome DevTools isolated mobile 390px/Fast 3G lulus: Lighthouse 100 untuk Accessibility, Best Practices, SEO, dan Agentic Browsing; console bersih; tidak ada horizontal overflow atau unnamed form field.
- UAT browser 2026-08-25 menambahkan bukti upload JPG inline dengan anchor/caption; file tersimpan di `kkprl_private`, URL public ditolak, dan mobile emulation 390px tetap tanpa overflow serta console bersih. Lighthouse Accessibility/Best Practices/SEO/Agentic Browsing semuanya 100 setelah `public/llms.txt` ditambahkan.
- Android Chrome-like emulation 2026-08-25 dengan Pixel 7 UA/412×915/Slow 3G berhasil membuat draft, resume tiket + HP, dan membuka wizard dengan session access; deep-link tanpa session tetap 403 sesuai policy. UAT Android fisik tetap terbuka.
- Patch UX mobile menambahkan ruang bawah 128px pada wizard agar widget bantuan fixed tidak menutupi kontrol akhir; browser check 412×915 mengonfirmasi computed padding dan tidak ada overlap pada screenshot.
- Viewer smoke sample lulus: Word 16.0 membuka DOCX read-only 3 halaman; Chrome PDF viewer membuka PDF sample 3 halaman. Pembandingan terhadap template resmi/golden release tetap manual.
- Smoke artifact synthetic 2026-08-25 lulus: Word 16.0 membuka DOCX 5 halaman dengan cover + label Indonesia; Chrome PDF viewer membuka PDF 8 halaman tanpa blank page setelah cover, dengan toolbar/thumbnail. Artifact hanya temporary, bukan approval golden resmi.
- Generator menguji cover/year, heading section resmi beserta prefix Roman, label field Indonesia, dan judul resmi Bab 1–5 pada DOCX/PDF; ini memperkuat automated sample check tetapi tidak menggantikan review visual golden release.
- Preflight runtime: 9 check lulus sebelum gate session-cookie ditambahkan; set saat ini 10 check dan strict preflight wajib diulang pada host.
- Checklist UAT manual tersedia di [docs/kkprl-uat-checklist.md](../docs/kkprl-uat-checklist.md).
- Manifest evidence release tersedia di [docs/kkprl-release-evidence.md](../docs/kkprl-release-evidence.md); gate eksternal tetap terbuka sampai evidence disimpan di release store dan disetujui.
- Panduan pengguna dan petugas tersedia di [docs/kkprl-user-and-staff-guide.md](../docs/kkprl-user-and-staff-guide.md).
- UI Filament proposal, approval/reject, request revisi, dan request edit darurat tersedia; sesi edit tetap dibatasi service scoped.
- Fidelity terhadap seluruh template DOCX, tabel panjang, orientasi landscape, dan sample comparison eksternal masih perlu QA manual Word/LibreOffice/PDF viewer.
- Generasi MVP sinkron berbatas; threshold pemindahan ke queue dan monitoring produksi didokumentasikan di [ADR-013](../docs/decisions/ADR-013-bounded-synchronous-document-generation.md).
- Composer audit terkini lulus tanpa advisory setelah targeted security update; lihat [ADR-011](../docs/decisions/ADR-011-dependency-security-baseline.md).
- `npm run build` lulus setelah source scan Tailwind dibatasi ke `app`/`resources` dan source framework yang diperlukan. `npm audit --offline --audit-level=moderate` lulus; audit online tetap perlu dijalankan pada mesin release.
