# Checklist UAT KKPRL

Checklist ini untuk environment yang memiliki browser nyata, database aplikasi, dan Word/LibreOffice/PDF viewer.

## Preflight

Jalankan sebelum UAT dan sebelum release:

    rtk php artisan kkprl:preflight --strict

Perintah harus melaporkan PASS untuk private storage, session-cookie security, ZIP/DOCX, PDF generator/parser, PDF renderer format, PDF attachment renderer, image normalization (WebP dan BMP bila mode BMP), dan seluruh template KKPRL. Binary dan mode dikonfigurasi melalui `KKPRL_PDF_RENDERER_BINARY` serta `KKPRL_PDF_RENDERER_FORMAT`; host audit Windows memakai Poppler `pdftobmp.exe` dengan mode `bmp` dan strict preflight lulus.

Sebelum UAT fresh environment, jalankan migration lalu `php artisan db:seed --class=KkprlPermissionSeeder --force`; seeder hanya mendaftarkan permission KKPRL dan tidak membuat role baru.

## Evidence host UAT 2026-08-24

- [x] Chrome Windows headless localhost-only: create draft, resume tiket + HP, autosave, conditional Bab 4/5, submit lengkap 5 bab, status lock, dan download DOCX/PDF.
- [x] Credential salah menampilkan pesan generik tanpa indikasi proposal ditemukan/tidak ditemukan.
- [x] Viewport mobile emulasi 390px: `document.documentElement.scrollWidth` tidak melebihi viewport.
- [x] Chrome DevTools isolated mobile 390px/Fast 3G: Lighthouse Accessibility, Best Practices, SEO, dan Agentic Browsing masing-masing 100; console bersih dan seluruh field form memiliki `id`/`name`.
- [x] Hasil PDF privat dibuka di Chrome PDF viewer; halaman Bab 4 dan tabel jadwal tampil sebagai konten PDF, bukan tautan publik.
- [x] Viewer smoke sample: Microsoft Word 16.0 membuka DOCX read-only (3 halaman, teks terbaca); Chrome PDF viewer membuka PDF sample (3 halaman dan toolbar viewer tersedia).
- [x] Smoke artifact synthetic 2026-08-25: Word 16.0 membuka DOCX 5 halaman dengan cover, tahun proposal, dan label field Indonesia; Chrome PDF viewer membuka PDF 8 halaman tanpa blank page setelah cover, dengan toolbar dan thumbnail. Ini evidence viewer, bukan golden approval template resmi.
- [x] Synthetic smoke regenerated 2026-08-26: `C:\Temp\kkprl-golden-smoke-bag-1.docx` (14,483 bytes, SHA-256 `7414d19980c248e0f84dc26e8cda8ffb5cc9a9243e05336ccb453c5ae704da4b`) dan `C:\Temp\kkprl-golden-smoke-bag-1.pdf` (1,302,337 bytes, SHA-256 `f0d3786fe0da586be96bc6e9c17bd5406b80028bdec21028d29e720c0fe9c69e`); temporary evidence, bukan approval golden resmi.
- [x] Word 16.0 roundtrip synthetic 2026-08-25: DOCX diekspor ke PDF 5 halaman; halaman lampiran `support.pdf` menampilkan judul dan teks terbaca. Native PDF halaman lampiran juga terbaca pada Chrome PDF viewer.
- [x] Automated sample comparison: marker fixture, `official-sections.golden.json`, `official-section-numbering.golden.json`, dan direct XML verification terhadap lima DOCX resmi; heading section, prefix Roman (dengan Bag 2 tanpa prefix), dan judul bab cocok pada DOCX/PDF; package DOCX memiliki ZIP entries, XML document/relationships well-formed, media inline, dan PDF memiliki marker serta orientasi yang diharapkan.
- [x] Host browser UAT 2026-08-25: upload JPG inline dengan anchor `site_plan_description` dan caption berhasil; lampiran tampil sebagai `INLINE`, tersimpan pada disk privat, dan URL `/storage/...` tidak menyajikannya.
- [x] Mobile emulation 390px/Fast 3G pada state upload: tidak ada horizontal overflow, seluruh kontrol memiliki `id`/`name`, dan console browser bersih. Lighthouse Accessibility, Best Practices, SEO, dan Agentic Browsing semuanya 100 setelah `public/llms.txt` ditambahkan.
- [x] Android Chrome-like emulation 2026-08-25: Pixel 7 UA, viewport 412×915, dan Slow 3G berhasil membuat draft dari route publik, resume dengan tiket + HP, lalu membuka wizard dengan session access; tidak ada overflow, console bersih, dan Lighthouse Accessibility/Best Practices/SEO/Agentic Browsing 100. Ini bukan pengganti UAT perangkat Android fisik.
- [x] Android Chrome-like emulation setelah patch UX: padding bawah 128px menjaga widget bantuan fixed tidak menutupi kontrol akhir form; screenshot viewport tersimpan sementara di `/tmp/kkprl-android-like-wizard-padding-2026-08-25.png`.
- [ ] Android Chrome perangkat nyata, jaringan lambat, dan pembukaan Word/PDF pada viewer perangkat umum.

## Pemohon mobile

- [ ] Buka `Buat Proposal Baru` pada Android Chrome.
- [ ] Buat draft; salin nomor tiket; pastikan disclaimer bukan sistem perizinan.
- [ ] Isi Bab 1–3 bertahap; refresh; pastikan data dan waktu autosave tetap ada.
- [ ] Coba tiket benar + HP benar, tiket benar + HP salah, tiket saja, dan proposal lain.
- [ ] Verifikasi rate limit dan pesan credential generik.
- [ ] Uji reklamasi aktif/nonaktif; hubungan daratan; izin pendukung; pastikan form dan dokumen hanya merender bagian aktif.
- [ ] Uji upload PDF, JPG/JPEG, PNG, WebP; file signature salah; batas 10 file; batas 15 MiB.
- [ ] Uji daftar lampiran, hapus, dan ganti file; pastikan kuota dihitung ulang dan file lama tidak menjadi file publik.
- [ ] Uji inline anchor, appendix, caption, gambar portrait/landscape, peta/site plan.
- [ ] Generate Bab 1–5 yang relevan; buka Word dan PDF; pastikan tabel panjang, header berulang, landscape, dan PDF lampiran terbaca.
- [ ] Submit; pastikan payload dan lampiran tidak dapat diubah.

## Petugas Filament

- [x] Host browser tanpa session pada `/layananruanglaut/kkprl-proposals` diarahkan ke login; role-matrix browser belum ditandai lulus karena login memakai Summary identity service dan environment lokal mengembalikan `The identity service is temporarily unavailable.`
- [ ] User hanya dengan permission review dapat membuka proposal.
- [ ] Download lampiran/dokumen memerlukan permission masing-masing.
- [ ] Petugas A dapat meminta revisi/edit scoped, tetapi tidak dapat self-approve.
- [ ] Petugas B dari role berbeda dengan permission approval dapat approve/reject dan menentukan expiry.
- [ ] Sesi edit hanya membuka field/section scope; perubahan mencatat before/after, actor, role, permission, waktu, alasan, dan request ID.
- [ ] Sesi berakhir saat dibatalkan, expired, atau revisi dikirim.
- [ ] Revisi memakai tiket utama yang sama, label `rev-1`/`rev-2`, dan proposal submitted asli tetap immutable.

## Artefak

- [ ] Bandingkan hasil dengan sample template resmi yang disamarkan.
- [ ] Pastikan file berada di storage privat dan route download menolak session/proposal lain.
- [ ] Simpan hasil UAT, screenshot viewport HP, dan sample Word/PDF pada artefak release.
