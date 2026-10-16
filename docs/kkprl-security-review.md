# Security Review KKPRL

## Scope

Review ini mencakup boundary pemohon publik, data proposal, upload/download, status immutable, Filament Shield, approval edit, dan artefak dokumen. Review tidak menggantikan penetration test atau sign-off operasional organisasi.

Batas akun/approval baru dan hasil tes aktual: [laporan validasi akun](kkprl-applicant-account-validation.md). Entri smoke Word/PDF 2026-08-25 tetap bukti historis; bukan pengujian viewer baru.

Pembaruan renderer 11 Oktober 2026 mengikuti [ADR-030](decisions/ADR-030-versioned-professional-document-layout.md): HTML editor tidak diteruskan sebagai resource/markup executable, renderer membaca snapshot dan verifikasi artefak mengenali versi hash legacy. Approval/account metadata tetap di luar hash; owner pending tetap hanya membaca artefak generated valid. Checkpoint setelah render dan private storage tidak dilonggarkan. Bukti regresi, mutation check, pemeriksaan visual dengan fixture sintetis serta batas native Word tercatat pada [validasi layout](kkprl-document-layout-validation.md). Contoh asli pengguna tidak diindeks Graphify atau dimuat ke browser connector.

## Evidence dan hasil

Penghapusan berkas hasil generate mengikuti [ADR-034](decisions/ADR-034-audited-generated-document-file-deletion.md). Sesi approved, owner dan lock diperiksa kembali dalam transaksi; pending, lintas akun, staff guard serta arsip locked ditolak. Path artefak diturunkan dari identitas root/revisi/hash/format, dengan private disk, realpath, penolakan symlink dan shared reference. Snapshot/audit tetap ada; file dipilih benar-benar dilepas. Tombstone ditolak pada download. Marker audit batch mencegah render yang berjalan menghidupkan berkas kembali setelah hapus. Kegagalan unlink membatalkan metadata/audit. Bukti aktual dan batas transaksi filesystem/DB ada pada [validasi penghapusan](kkprl-document-deletion-validation.md). Tidak ada penghapusan otomatis atau permission petugas baru.

| Area | Kontrol | Evidence | Hasil |
|---|---|---|---|
| Credential pemohon | Email/password hash, guard/model terpisah, registrasi allowlist/throttle, login generic/throttle, tanpa reset mandiri | `KkprlApplicantIdentityTest`, public UI tests, ADR-024–026 | Bukti aktual pada laporan akun |
| Session/approval/owner | session_version fresh, approval fresh, owner pada service/action/hydrate, idle selection, signed upload terikat akun, checkpoint setelah operasi panjang, logout tidak mengganggu guard petugas | boundary/identity/access tests | Bukti aktual pada laporan akun |
| Data pribadi | HP/email kontak/nama/institusi/payload proposal terenkripsi; email akun login unik terindeks pada tabel pemohon; kolom hash untuk lookup; kolom protection disembunyikan | `KkprlProposalSchemaTest`, model casts/hidden | PASS |
| Artefak | Model memaksa disk `kkprl_private`, root non-public, `serve=false`, path dokumen memuat snapshot+manifest, controller/snapshot/revisi/renderer menolak disk lain, download terotorisasi | `KkprlPreflightTest`, strict preflight, download tests, ADR-016 | PASS teknis; regression terbaru perlu dijalankan pada host PHP |
| Upload | MIME actual + file signature, nama sanitized, UUID path, model memaksa private disk, atomic batch, quota 10 file/15 MiB, conditional chapter relevance and inline anchor rechecked under proposal row-lock on upload/replace, cleanup file saat persistence gagal | attachment service/unit-feature tests | PASS teknis; regression terbaru perlu dijalankan pada host PHP |
| Export | Snapshot/manifest bersama, identitas document mencakup manifest, path binary immutable, retry idempotent, missing binary ditolak, stale snapshot guard, PDF renderer fail-closed, PDF lampiran terbaca pada Word/PDF smoke, credential HP tidak masuk DOCX/PDF | document generation/download tests + Word/PDF viewer smoke 2026-08-25 | PASS teknis; regression terbaru dan golden template eksternal tetap terbuka |
| State | Submitted/needs_revision immutable, stale autosave row-lock, original revision tidak ditimpa, review event dan snapshot dokumen append-only, path dokumen generated immutable | submission/draft/revision/document tests | PASS teknis; regression terbaru perlu dijalankan pada host PHP |
| Staff authorization | Permission Shield terpisah untuk review, request, approve, run session, download; policy resource memakai permission bisnis, bukan nama role/CRUD implisit | Shield/Filament tests | PASS teknis; regression terbaru perlu dijalankan pada host PHP |
| Separation of duties | Petugas A tidak self-approve; satu approval/sesi aktif per revisi; approval scoped, expiry, one-use, known-path validation termasuk penolakan nested path, payload sanitization, session audit before/after | edit approval tests/service, ADR-015 | PASS teknis; regression terbaru perlu dijalankan pada host PHP |
| Administrasi akun | Permission daftar/detail/approve/reset/assign terpisah; reset hanya KkprlApplicant; audit tanpa secret, semua sesi lama gugur | administration/Filament/boundary tests, ADR-025 | Bukti aktual pada laporan akun |
| Logging privacy | Event menyimpan actor/request ID dan metadata audit; credential mentah tidak ditulis ke artefak | review event model + privacy assertions | PASS teknis; verifikasi konfigurasi log production tetap diperlukan |

## Residual risk dan tindakan sebelum production

- Sesi/verifier tiket/HP fail-closed. Pemohon legacy belum terhubung akun sampai verifikasi manual dan audit; tidak ada pencocokan email/HP/tiket otomatis.
- `APP_DEBUG=false`, HTTPS, session driver, permission role mapping, backup, dan akses filesystem harus diverifikasi pada environment production.
- Retensi/penghapusan belum otomatis; kebijakan organisasi dan approval operasional diperlukan sebelum scheduler penghapusan dibuat.
- Android/device UAT, jaringan lambat, viewer Word/PDF, dan golden comparison eksternal masih wajib.
- Generasi dokumen MVP sinkron berbatas; monitor durasi, memory, timeout renderer, dan failure rate sesuai ADR-013 sebelum menentukan migrasi queue.

## Kesimpulan

Security/privacy review teknis untuk MVP lulus berdasarkan evidence otomatis dan inspeksi route/storage/domain. Release belum boleh dinyatakan final sampai residual risk operasional dan UAT eksternal ditandatangani.


## Peta ekspor Bab 1 — 12 Oktober 2026

Pembaruan terakhir menambahkan provider OpenStreetMap tetap pada utama dan mempertahankan WMTS pada inset. Utama maksimum zoom 19/16 tile, inset zoom 14/16 tile, keduanya berbagi deadline fetch 20 detik. Tidak menerima URL pengguna, tidak mengikuti redirect dan tidak menambah route publik proposal. User-Agent/Referer hanya memuat origin aplikasi; cache publik Base64 minimum tujuh hari tanpa identitas/overlay/token. Batas byte/decode penuh tetap berlaku. Approval dicabut saat fetch OSM terbukti tidak menyimpan dokumen/file. Snapshot immutable menyimpan provider agar perubahan konfigurasi tidak mengubah hash unduhan lama; semua versi historis tetap didukung. Tes dan batas tercatat pada [validasi OSM](kkprl-osm-main-map-validation.md), mengikuti [ADR-035](decisions/ADR-035-openstreetmap-main-map-with-satellite-inset.md). Uraian WMTS awal di bawah dipertahankan sebagai sejarah kontrol provider.

Cache tile publik memakai Base64 dalam namespace `base64-v1`, aman untuk kolom teks UTF-8 MySQL. Encoded maksimal 699.052 byte diperiksa sebelum decode strict, dilanjutkan semua validasi PNG existing. Base64 bukan encryption dan tidak dipakai untuk menyimpan overlay, identitas atau credential. Cache lama tidak di-flush dan snapshot dokumen tidak berubah. Diagnosis database pengguna baca saja; verifikasi tulis hanya pada fixture terisolasi. Bukti dan batas tersedia pada [validasi cache](kkprl-chapter-one-map-cache-validation.md), mengikuti [ADR-032](decisions/ADR-032-text-safe-map-tile-cache.md).

Batas kepercayaan baru adalah WMTS publik tetap, tanpa URL pengguna/redirect/token. PNG diverifikasi signature, ukuran byte, 256×256 dan decode pixel GD sebelum dipakai atau disimpan ke cache; cache rusak diabaikan dan tile diambil ulang. Maksimum 16 tile, 20 detik total, 6 detik/request, connect 4 detik dan zoom 14. Koordinat raw maksimal 1 MiB/1.000 titik. Cache hanya berisi tile publik; provider menerima indeks tile wilayah, tidak menerima identitas atau overlay proposal. PNG overlay berada dalam memori dan tertanam pada dokumen private, tanpa endpoint publik. Sumber opsional dibatasi 300 karakter dan dirender sebagai teks. Owner, approval, lock dan snapshot/manifest diperiksa kembali sesudah rendering, sehingga pencabutan approval atau perubahan raw tidak dapat menyimpan hasil stale. Snapshot lama direkonstruksi berdasarkan versi; metadata akun tetap tidak masuk hash. Tes deny/timeout/malformed/redirect/after-fetch dan regresi tercatat pada [validasi peta](kkprl-chapter-one-map-validation.md).
