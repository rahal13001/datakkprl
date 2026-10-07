# Validasi akun pemohon KKPRL

Tanggal: 2026-10-07. Status: implementasi akun/approval, cutover, tes/format/build, browser dan refresh Graphify final selesai pada lingkungan pengembangan. Migration/seeder/deployment database pengguna dan UAT operasional belum dijalankan.

## Lingkungan dan batas

PHP Windows 8.4.8 melalui wrapper WSL yang sudah tersedia. Interop Windows memerlukan eksekusi di luar sandbox; automatic approval review mengizinkan tes. PHPUnit memakai SQLite :memory:, session array, cache array. Database/.env pengguna tidak diubah. Tidak ada API Gemini/GMRT berbayar maupun layanan baru.

Working tree awal mengandung perubahan lama, untracked, dan hasil build sebelumnya; dipertahankan. Tidak ada commit/push/deploy. AGENTS.md tidak ditemukan pada filesystem proyek/ancestor; instruksi AGENTS.md dari pesan pengguna dan /home/rahal/.codex/RTK.md dibaca. Semua shell memakai awalan rtk.

## Fondasi

- RED: `rtk proxy php vendor/bin/phpunit --filter=KkprlApplicantIdentityTest` menghasilkan 4 error dan 1 failure karena service belum ada.
- GREEN: perintah sama menghasilkan **5 tests, 17 assertions**, lulus.
- Registrasi email/password pending tanpa draft, input approval/role/owner tidak diterapkan, password hash tersembunyi dari serialisasi, email unik dinormalisasi. Guard dan logout pemohon tidak mengeluarkan petugas. Versi sesi fresh menolak guard cached setelah perubahan versi.

## Administrasi

- RED: `rtk proxy php vendor/bin/phpunit --filter=KkprlApplicantAdministrationTest` menghasilkan 4 error dan 3 failure sebelum service/seeder ada.
- GREEN identity + administration: `rtk proxy php vendor/bin/phpunit --filter=KkprlApplicant` menghasilkan **12 tests, 44 assertions**, lulus sebelum penambahan tes panel.
- Dua assertion awal diperbaiki agar membaca default DB dari model fresh, bukan model hasil create yang belum memuat default kolom.
- Role yang disepakati saja menerima permission. ApproveEdit tidak memberi approval akun. Reset mengganti password hash dan versi sesi tanpa mencatat secret. Penetapan owner menjaga seluruh atribut proposal locked selain applicant_id, mewarisi seluruh rantai, dan menolak owner lain.
- RED panel: resource belum ada; setelah resource tersedia, fixture host dan app.env diselaraskan dengan pola tes panel proyek. Pengujian action tanpa permission memakai panggilan mountAction/callMountedAction langsung, bukan helper yang mensyaratkan tombol visible.

## Catatan tahap fondasi historis

Tahap identity/admin setelah tes panel diperbaiki: `rtk proxy php vendor/bin/phpunit --filter=KkprlApplicant` **15 tests, 64 assertions, lulus**. Pint targeted 15 file lulus setelah memperbaiki 4 masalah format. Graphify fondasi selesai: 3254 node, 8445 relasi, 159 komunitas, nol endpoint hilang; 701 relasi ambigu dan 20 self-loop kandidat dicatat. Ini hanya tahap fondasi, bukan cutover final.

Daftar tindak lanjut fondasi—penggantian entry point tiket/HP, dokumen pending, owner revisi, service/checkpoint, matriks negatif Livewire, migrasi fixture legacy, format/build, regresi penuh, browser, review dan Graphify—diselesaikan pada tahap berikutnya. Hasil final tercatat di bawah. Baseline historis 394 tests/2052 assertions tetap bukan bukti perubahan akun.

## Cutover dan operasi panjang

- RED `rtk proxy php vendor/bin/phpunit --filter=KkprlApplicantAccessTest`: 6 error/3 failure ketika portal belum ada dan akses legacy masih aktif. GREEN: **9 tests, 33 assertions**.
- Akses pemohon memakai provider/model/guard terpisah, owner backend dan selection akun. Verifier, service resume dan action Livewire legacy fail-closed. Route lama redirect tanpa grant.
- Service draft/lampiran/submit/ekspor mengecek approval/owner sebelum operasi serta akun fresh dalam transaksi final. Hydration menolak seluruh action stale sebelum AI/GMRT atau perubahan data. Upload sementara memakai signed route khusus, approval/owner/lock/version diperiksa saat penerimaan file.
- PDF dirender sebelum transaksi persistence; snapshot/manifest dan izin diperiksa ulang, termasuk sebelum menulis status failed. Revocation selama render tidak membuat dokumen atau binary. Reuse hasil generated tetap idempotent.
- Perubahan pengecekan chat sempat menyebabkan rollback ketika Bab 5 baru dinonaktifkan. Checkpoint chat sekarang memeriksa owner/approval/lock, tanpa menganggap bab yang sengaja dinonaktifkan masih wajib aktif. Regresi pilihan izin kembali lulus.
- Fixture tes lama sekarang membuat/login akun approved dan menetapkan owner secara eksplisit. Pengujian cross-account mengubah akun, bukan sekadar memilih proposal lain milik akun yang sama. Coverage normalisasi kontak, lock, snapshot, parser, quota, chapter, revision dan staff edit dipertahankan. Tes tiket/HP obsolete diganti pembuktian fail-closed.

## Perintah dan hasil aktual

| Perintah | Hasil |
|---|---|
| `rtk proxy php vendor/bin/phpunit --filter='Kkprl'` pada cutover awal | 379 tests, 1802 assertions; 11 error/12 failure. Sebagian besar fixture/ekspektasi legacy, satu rollback Bab 5. Semua ditelusuri dan diperbaiki. |
| `rtk proxy php vendor/bin/phpunit --filter='KkprlApplicant\|KkprlProposalAccessTest\|KkprlProposalWizardTest\|KkprlProposalPublicAccessUiTest\|KkprlChapterFiveTest\|ProposalSnapshotTest\|KkprlProposalEditApprovalTest\|KkprlProposalSchemaTest\|KkprlProposalDocumentDownloadTest'` | **95 tests, 337 assertions, lulus**. |
| `rtk proxy php vendor/bin/phpunit --filter=KkprlApplicantBoundaryTest` pertama | 40 tests, 233 assertions; satu mock PDF salah tipe dan satu ekspektasi API login salah status. Fixture diperbaiki; API petugas tetap mengembalikan validation_failed 422 untuk kredensial salah. |
| `rtk proxy php vendor/bin/phpunit --filter=KkprlApplicantMigrationTest` | **1 test, 11 assertions, lulus**. Up additive mempertahankan row legacy, file, encryption dan snapshot; owner legacy null. |
| `rtk proxy php vendor/bin/phpunit` sebelum penguatan review akhir | **457 tests, 2406 assertions, lulus**, 03:01.265, memory 384 MiB. |
| `rtk proxy php vendor/bin/phpunit --filter='KkprlApplicant\|KkprlProposalWizardTest\|KkprlProposalDocumentDownloadTest'` setelah penguatan | **91 tests, 431 assertions, lulus**. |
| `rtk proxy php vendor/bin/pint <66 file akun/cutover/fixture terkait>` | Exit 0; daftar file `/tmp/kkprl-account-format-files.txt`, hasil `storage/logs/kkprl-account-pint.txt`. Tidak memakai --dirty yang akan memformat seluruh pekerjaan lama. |
| `rtk proxy npm run build` pertama | Lulus, Vite 7.3.6; 58 modul, 8.62 detik. Build terakhir setelah link Akun & dokumen dicatat di bawah. |

Regresi final setelah format dan perubahan download/portal: `rtk proxy php vendor/bin/phpunit` **461 tests, 2429 assertions, lulus**; 01:22.801, memory 390 MiB. Output `storage/logs/kkprl-account-final-suite.txt`. Build final `rtk proxy npm run build` juga **lulus**, 58 modul, 3.96 detik; CSS app-BXNFfi7M.css dan JS app-DMsN-rLE.js. Tidak menganggap hasil historis 394/2052 sebagai bukti perubahan ini.

Pemeriksaan format final `rtk proxy php vendor/bin/pint --test <66 file akun/cutover/fixture pada /tmp/kkprl-account-format-files.txt>` **lulus, 66 file**. Pemeriksaan `rtk git diff --check -- <87 sumber scope final>` **lulus, exit 0**; hasil `/tmp/kkprl-account-diff-check.txt`. Pemeriksaan seluruh working tree juga **lulus, exit 0**, hasil `/tmp/kkprl-account-whole-diff-check.txt`. Seluruh sumber final dibekukan sebelum ekstraksi Graphify. Pemeriksaan whitespace diulang sesudah finalisasi dokumentasi dan artefak graf.

Smoke positif tambahan pada harness ignored: `rtk proxy php vendor/bin/phpunit storage/framework/testing/kkprl-account-browser-20261007/KkprlApplicantUploadSmokeTest.php` **1 test, 6 assertions, lulus**, 00:00.605, memory 82 MiB. Handler signed menerima akun approved/owner dengan HTTP 200; reference signed valid dan binary `TemporaryUploadedFile` tersedia, belum membuat attachment proposal, tanpa HTTP eksternal. Penyesuaian awal hanya fixture/harness: hook ganda, default versi sesi belum fresh, disk test belum fake dan ekspektasi metadata/reference Livewire 4. Tidak ada perubahan aplikasi dari smoke tersebut. Hasil ini terpisah dari full suite final **461/2429**.

## Matriks pembuktian

| Acceptance | Evidence |
|---|---|
| Pending tanpa draft, input role/approval/owner diabaikan | identity, access, public UI |
| Pending hanya dokumen sendiri/empty; tanpa payload/chat/attachment/progres | access, browser DOM/snapshot, private download |
| Seluruh action stale ditolak tanpa HTTP/mutasi | boundary data provider: wizard/chat, upload start/finish/remove, draft, map, submit, generate, evidence, tide, chart, GMRT |
| Service pending dan cross-account ditolak | boundary/service tests: draft, store/delete/replace, export/submit, AI/image/GMRT |
| Approved own builder dan lock tetap berlaku | access, wizard/draft/attachment/submission, chapter 1–5 regression |
| Tiket/HP/sesi lama fail-closed | access, proposal-access, public UI |
| Menu/list/detail/direct action/service admin memakai permission | Filament dan administration, termasuk approve/revoke/reset/assign tanpa permission |
| Pemohon tidak masuk panel/API petugas; reset tidak menarget User | identity/boundary/administration, nol API token |
| Password lama dan semua versi sesi lama ditolak; audit tanpa secret | administration/boundary; reset selama AI juga membatalkan persistence |
| Legacy terjaga, assignment diaudit, revision owner diwariskan | migration/administration/revision/boundary |
| Revocation saat AI/GMRT/PDF tidak menulis hasil | access/boundary mock/fake, nol chat/lampiran/dokumen/binary baru |
| Metadata akun tidak mengubah snapshot; private/hash/manifest tetap berlaku | boundary, snapshot/document/download/preflight regression |
| Pasut/grafik/GMRT/Word/PDF/staff edit tetap lulus | full suite dan laporan historis fitur yang dipertahankan |

## Browser aktual

Chrome DevTools MCP, context `kkprl-account-20261007`, localhost 127.0.0.1:8765. Harness ada di `storage/framework/testing/kkprl-account-browser-20261007`; SQLite/session/cache/log/storage/APP_KEY sintetis terpisah. Http preventStrayRequests; tidak memakai DB/data/credential pengguna atau Gemini berbayar. Setup/server: `rtk proxy php storage/framework/testing/kkprl-account-browser-20261007/setup.php`, `rtk proxy php -S 127.0.0.1:8765 -t public storage/framework/testing/kkprl-account-browser-20261007/router.php`.

- Desktop 1265/1440 px dan mobile 320×740: pending hanya heading, Keluar dan empty state; nol input, satu komponen Livewire, tanpa chat/wizard/progres/builder. scrollWidth sama viewport.
- Pending dengan dokumen generated: hanya Bab/format/link PDF dan DOCX miliknya. Mobile tanpa overflow. Downloads diuji HTTP pada suite.
- Approved: klik Buat proposal baru membuka wizard Bab 1, input kontak tetap tersedia. Mobile scrollWidth=320. Tidak ada error/warning console pada akun/wizard.
- Registrasi melalui UI email/password/konfirmasi berhasil menuju pending empty state; logout dan login melalui UI berhasil. Password field terhapus setelah submit.
- Panel akun/admin: daftar/detail dan modal reset tersedia untuk fixture role admin. Reset melalui modal berjalan, input password hilang setelah modal ditutup. Mobile hanya tabel yang scroll horizontal di container (288 px/980 px), halaman tetap 320 px.
- Browser menemukan `isTrapActive is not defined` dari public/js/filament/support/support.js lama: tidak memiliki simbol itu, sementara vendor Filament terpasang memilikinya. Generated asset disinkronkan dengan vendor, tanpa mengubah vendor atau menambah paket. File sebelumnya disimpan di `/tmp/kkprl-account-existing-support.js`. Setelah reload tanpa cache, buka/reset/tutup modal diulang; console bersih. Deployment kelak perlu assets Filament yang sesuai paket, mengikuti [pipeline aset resmi](https://filamentphp.com/docs/5.x/advanced/assets).
- Beberapa label bawaan Filament masih berupa key terjemahan (`filament-panels::layout...`, `result_count`); ini konfigurasi bahasa panel sebelumnya, tidak diubah dalam fitur akun. Label input dan action baru berbahasa Indonesia.
- Satu navigation panel sempat timeout 10 detik pada PHP development server Windows; wait lanjutan berhasil, tanpa response error. Bukan pengukuran performa produksi.

Screenshot: `/tmp/kkprl-pending-mobile.png`, `/tmp/kkprl-documents-mobile.png`, `/tmp/kkprl-approved-mobile.png`, `/tmp/kkprl-admin-mobile.png`. Tidak menyimpan password dalam laporan/screenshot/audit/Graphify. Viewer Word/PDF eksternal, perangkat nyata dan production UAT tetap evidence yang berbeda dari tes otomatis/browser ini.

Server browser fixture dihentikan setelah pengujian (Ctrl+C, exit 130), tab terisolasi ditutup. Hanya harness sintetis yang dipakai; tidak ada server/data produksi pengguna yang dihentikan atau diubah.

## Review kode dan batas operasional

Review mengikuti skill code-review-and-quality/security: guard terpisah tanpa HasRoles/token; input allowlist; approval berbeda dari User.status; owner/locked data diperiksa fresh; immutable snapshot tetap; permission action/service; audit tanpa secret; rollback legacy diuji hanya fixture. Portal memakai pagination dan eager-load proposal; view mengirim field dokumen allowlist. Workflow edit darurat tetap memakai actor User dan permission scoped yang lama. Tidak ada dependency Composer/npm atau program sistem baru.

Migration, permission seeder, commit/push/deploy belum dijalankan pada database/application produksi pengguna. Runbook transisi ada di panduan dan ADR-026; jangan memakai migrate:fresh, mengklaim owner dari tiket/email/HP, atau rollback schema akun setelah ada data baru tanpa rencana backup.

## Graphify

Graphify fondasi sebelumnya diekspor dari kode dan lima dokumen. Refresh final cutover mencakup kode, view, tes dan dokumentasi/ADR-001/024–026 yang berubah, bukan hanya query. Hasil final, provenance, manifest, relasi ambigu, endpoint validation dan batas cakupan dicatat pada `graphify-out/GRAPH_REPORT.md` serta `.graphify_update.json`. Cakupan tetap indeks utama/KKPRL, bukan seluruh isi repo; parser ambigu tidak dianggap bukti otorisasi. Rujuk kode aktual dan tes.

Refresh final cutover menghasilkan **3368 node, 8775 relasi, 170 komunitas**. Ekstraksi membaca **87 sumber**: 66 PHP, lima Blade, 15 dokumen Markdown serta satu artefak JavaScript generated yang hanya dicatat provenance. Kode AST dan semantik dokumen/view digabung dengan indeks utama sebelumnya, bukan mengganti cakupan lama dengan subgraf akun.

- Integrity gate: **nol endpoint hilang/dangling**, directed MultiDiGraph mempertahankan **134 pasangan endpoint paralel**, 22 self-loop kandidat. Sebanyak **202 kandidat call PHP baru** yang hanya cocok nama diberi AMBIGUOUS; keseluruhan graf memiliki **686 relasi ambigu**. Self-loop bukan bukti rekursi; kandidat parser perlu diverifikasi pada kode.
- Manifest dan SHA-256 sumber scope final sesuai isi aktual; entri sumber di luar scope tetap dipertahankan dengan provenance sebelumnya, tanpa distamp seolah diekstraksi ulang. Cache AST/semantik, labels, graph.json/html, report, cost dan audit diselaraskan. Simbol portal, signed-upload trait/controller, owner revision, checkpoint dan ADR-026 ditemukan dalam indeks.
- `public/js/filament/support/support.js` identik byte dengan `vendor/filament/support/dist/index.js`: 144989 byte, SHA-256 `7af2205b35427de6ec6dbc3b6a66b19de70eddbf0bbde024fef7a9043672191b`. Graf hanya mencatat artefak/provenance; internal/vendor JavaScript tidak diekspansi ke indeks utama.
- Token semantik host tidak tersedia, sehingga **tidak diketahui**, bukan nol. Tidak ada panggilan API model eksternal untuk refresh ini. Literal password fixture tidak dimasukkan ke graf/report/audit/cache. Template DOCX tidak diekstraksi ulang; coverage template tetap melalui field map/spec sebelumnya.
- Regresi dan browser baru berasal dari hasil aktual laporan ini; grafik tidak melakukan tes aplikasi ulang. Bukti viewer/perangkat nyata/production UAT historis atau belum selesai tidak ditandai lulus baru.
