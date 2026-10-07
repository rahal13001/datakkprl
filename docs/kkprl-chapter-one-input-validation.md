# Validasi input Bab 1 — 8 Oktober 2026

Lingkup: hilangnya ketikan ketika autosave merespons, transparansi kelengkapan Bab 1, dan editor rencana tapak. Implementasi dan keputusan: [spesifikasi](kkprl-chapter-one-input-spec.md), [ADR-027](decisions/ADR-027-chapter-one-input-state-and-completion.md). Working tree berisi perubahan lama dan berkas untracked; tidak dilakukan reset, clean, commit, push atau deploy.

## Bukti masalah dan koreksi

Pada fixture SQLite terisolasi, respons update Livewire ditunda 1,5 detik. Mengubah pilihan hubungan daratan lalu mengetik jabatan segera mereproduksi hilangnya teks baru setelah respons. Pemeriksaan Livewire terpasang menemukan penggantian object ketika key order berubah atau array kosong berubah menjadi object. Menjaga urutan key saja masih gagal pada penyimpanan awal; scaffold key Bab 1 sejak mount mengatasi perubahan bentuk tersebut. Uji respons 6 detik kemudian mempertahankan `kamu kemana saat server menyimpan` selama dan setelah respons.

Kelengkapan berasal dari 36 field katalog. `depth_unit` diwajibkan tetapi tidak memiliki input; sekarang tersedia. Missing report dan label berasal dari evaluator/katalog backend yang sama dengan export/submit. Nilai 0/false tetap dihitung. Konten editor yang hanya markup/spasi tidak menyelesaikan narasi.

Editor sebelumnya menganggap teks elemen sumber sebagai data dan mengirim setiap ketikan. Elemen sumber sekarang kosong, helper di luar editor, dan perubahan masuk state lokal sebelum dikirim saat blur/action. Uji navigasi menemukan CKEditor instance yang disimpan dalam proxy Alpine menimbulkan error ketika destroy; cleanup sekarang memakai closure yang memegang instance asli. Navigasi ulang tidak menghasilkan error atau editor ganda.

Scaffold null tidak ditulis ke storage. Data chapter lain dari payload flat tetap utuh. Uji snapshot awal mengungkap normalisasi storage flat menjadi nested dapat mengubah identitas dokumen; storage flat existing sekarang tetap dipertahankan. Save tanpa edit isi mempertahankan snapshot hash dan manifest hash. Input flat yang dikirim melalui jalur lama dinormalisasi sebelum sanitizer, sehingga submit lengkap tidak kehilangan data. Review tambahan menemukan metadata daftar proposal diambil service hanya dari payload nested: RED 1 test/5 assertions gagal dengan applicant_name null. Fallback metadata ke payload flat yang telah disanitasi memperbaikinya; nama, instansi dan provinsi kini ikut diuji.

## Perintah dan hasil aktual

Semua perintah shell memakai awalan `rtk`. PHP memakai wrapper Laragon PHP 8.4.8, bukan instalasi sistem baru. Versi lock aktual: Laravel 13.26.1, Livewire 4.4.1, Filament 5.7.6, Spatie Permission 7.4.1.

| Pemeriksaan | Perintah | Hasil |
|---|---|---|
| RED sebelum implementasi | `rtk proxy php vendor/bin/phpunit --filter=KkprlChapterOneInputTest` | 5 tests/7 assertions; 1 error, 3 failures. Memperlihatkan urutan echo, missing report, HTML kosong dan input kedalaman yang hilang |
| Tes fitur setelah perbaikan storage/snapshot | `rtk proxy php vendor/bin/phpunit --filter=KkprlChapterOneInputTest` | 8 tests/33 assertions lulus |
| Target submit dan Bab 1 setelah normalisasi input flat (sebelum assertion metadata tambahan) | `rtk proxy php vendor/bin/phpunit --filter='KkprlChapterOneInputTest\|complete_wizard_can_submit_and_then_becomes_locked'` | 9 tests/35 assertions lulus |
| Format | `rtk proxy php vendor/bin/pint --test app/Livewire/KkprlProposalWizard.php app/Domain/Kkprl/ProposalProgress.php app/Domain/Kkprl/ProposalProgressResult.php app/Services/KkprlProposalDraftService.php tests/Feature/KkprlChapterOneInputTest.php` | 5 files PASS |
| Build UI | `rtk proxy npm run build` | Vite 7.3.6; 58 modules; 23,91 detik; exit 0 |
| Regresi seluruh aplikasi | `rtk proxy php vendor/bin/phpunit` | 469 tests/2462 assertions lulus |

Suite penuh awal pada tahap antara menghasilkan 468 tests/2452 assertions dengan satu kegagalan submit fixture flat. Ini diperbaiki pada normalisasi sebelum sanitizer dan diuji ulang; angka tersebut bukan hasil akhir. Setelah perubahan cleanup editor dan koreksi metadata legacy, suite penuh diulang lagi: hasil final 469 tests/2462 assertions, 89,105 detik, exit 0. Pemeriksaan `rtk proxy git -c core.safecrlf=false diff --check` pada seluruh working tree dan scope final lulus; whitespace lama pada Blade tidak dihapus, tidak ada whitespace baru pada diff perubahan ini. Log final disimpan lokal pada `storage/logs/kkprl-chapter-one-final-suite.txt` dan tidak memuat password.

Pengujian otomatis memakai SQLite `:memory:`, session/cache array serta fake/mock layanan eksternal. Tidak menggunakan API Gemini berbayar. Suite mencakup otorisasi akun pending/approved, kepemilikan, lock/revisi, workflow petugas, integritas/private download, Bab 1–5, pasut/grafik, GMRT dan export Word/PDF. Tidak menjalankan migration pada database pengguna atau mengubah `.env`.

## Browser desktop dan mobile

Fixture baru berada di `storage/framework/testing/kkprl-chapter-one-20261008` (ignored), dengan database, session, cache, storage, log, key dan akun sintetis tersendiri. Server localhost:8766 memakai router fixture; `Http::preventStrayRequests` mencegah koneksi AI/GMRT nyata. Interaksi memakai Chrome DevTools pada context terisolasi. Tidak mengakses sesi/data pengguna.

- Reproduksi autosave lambat 1,5 dan 6 detik; teks yang diketik setelah request dimulai bertahan. Semua isian batch tetap berada pada state klien setelah respons selesai.
- Draft baru mempunyai editor kosong tanpa petunjuk menjadi konten. Mengetik narasi tambahan tidak menambah request Livewire per ketikan; keluar editor dan Simpan Draft menyimpan narasi. Reload mempertahankan narasi dan jabatan.
- Seluruh isian terlihat diisi melalui UI, termasuk nilai luas/investasi/tenaga kerja 0, pilihan Tidak, satuan kedalaman/datum, Point dan satu koordinat valid. Tampilan mencapai **36 dari 36 isian wajib**, **Bab 1 selesai**, dan progress keseluruhan **76%** karena Bab 2–3 masih kosong. Kelengkapan seluruh proposal tidak disamakan dengan Bab 1.
- Pindah Bab 2 menghasilkan nol editor CKEditor; kembali Bab 1 menghasilkan tepat satu instance dengan narasi utuh. Console setelah reload dan navigasi final bebas error/warning.
- Desktop dan emulasi mobile **320 × 740** diperiksa. Mobile `innerWidth=320`, `documentElement.scrollWidth=320`; editor/toolbar menu overflow, teks helper dan dropdown terbaca. Resize window saja mempunyai minimum 500 px pada Chrome ini; pemeriksaan 320 px menggunakan device emulation aktual.

Screenshot lokal: `/tmp/kkprl-chapter-one-complete-desktop.png`, `/tmp/kkprl-chapter-one-site-plan-desktop.png`, `/tmp/kkprl-chapter-one-complete-mobile.png`, `/tmp/kkprl-chapter-one-site-plan-mobile.png`. Ini bukti fixture, bukan data pemohon. Keberhasilan tidak menguji koneksi CDN nyata dari jaringan shared hosting tujuan atau konflik editing dari beberapa tab; dependency editor/peta existing tetap sama.

## Review dan Graphify

Review mencakup stabilitas state, sanitizer allowlist, preservasi 0/false/null, payload flat lintas bab, snapshot identity, teardown editor, jumlah request, dan authorization service existing. Tidak ada dependency, route, permission atau layanan baru. Build menyimpan kembali asset generated lama yang masih diperlukan perubahan sebelumnya; tidak membersihkan working tree.

Graphify utama diperbarui dari kode dan dokumentasi perubahan ini serta fixture/test terkait yang berubah sebelumnya. Detail jumlah, hash, manifest, arah relasi, endpoint dan relasi ambigu ada di [GRAPH_REPORT](../graphify-out/GRAPH_REPORT.md) dan audit Bab 1 di direktori yang sama. Cakupan tetap indeks utama proyek/KKPRL; sebagian relasi parser ambigu dan tidak diperlakukan sebagai call graph yang terbukti. Query awal hanya peta dampak; refresh artefak final adalah tahap terpisah.
