# Pedoman Perolehan Data Proposal KKPRL Skala Kecil

## Kebutuhan dan keputusan

Pedoman dipisahkan dari Belajar KKPRL ke `/pedoman-kkprl`. Landing page mempertahankan seluruh markup, teks, ikon, posisi, warna, dan perilaku tab baru; hanya `href` yang berubah.

Pedoman disimpan dalam tabel `proposal_guides` dan dikelola melalui menu **Pedoman KKPRL** di panel layanan. Admin dapat menambah dokumen, mengganti PDF, mengubah judul/deskripsi, menentukan urutan, dan menerbitkan atau menyembunyikan dokumen. URL publik menggunakan ID agar perubahan judul tidak memutus tautan. PDF dibaca melalui controller yang memeriksa status publikasi dan keberadaan file.

Migrasi memindahkan materi dengan slug persis yang digunakan tautan landing page lama. File PDF dan riwayat pembelajaran dipertahankan. Materi asal diarsipkan dengan soft delete. Status publikasi pedoman mengikuti visibilitas materi, grup, dan kategori asal. Tautan materi lama dialihkan ke pedoman, termasuk endpoint PDF dan unduhan. Rollback memulihkan materi asal tanpa menghapus PDF.

Hak akses pengelolaan menggunakan izin Materi Pembelajaran yang sudah ada. Pengguna tanpa izin tidak dapat membuka menu atau menjalankan aksi admin. Resource dikecualikan dari generator Shield agar kebijakan ini tidak ditimpa.

## Rencana dan penerimaan

1. Uji halaman publik, PDF, pembaruan dinamis, arsip/migrasi, redirect lama, dan otorisasi admin.
2. Tambahkan model, migrasi, controller, dan halaman publik; pastikan tes perilaku lulus.
3. Tambahkan resource admin, pertahankan landing page, dan uji perubahan PDF melalui form admin.
4. Jalankan tes regresi, Pint, build Vite, dan verifikasi browser.
5. Perbarui graphify dan catat bukti hasil serta batasan verifikasi di dokumen ini.

Kode mengikuti konvensi Laravel proyek: controller di `app/Http/Controllers`, model di `app/Models`, resource Filament di `app/Filament/Layanankkprl/Resources`, dan tes PHPUnit di `tests/Feature`.

```php
ProposalGuide::query()->where('is_published', true)->orderBy('sort_order')->orderBy('id')->get();
```

## Perintah verifikasi

```bash
rtk proxy php artisan test --compact --filter 'ProposalGuideTest|BelajarKkprlTest|ExampleTest'
rtk proxy php artisan test --compact
rtk proxy php vendor/bin/pint --test
rtk npm run build
```

## Pengoperasian

Jalankan migrasi setelah perubahan kode tersedia:

```bash
rtk proxy php artisan migrate --path=database/migrations/2026_10_06_000000_create_proposal_guides_table.php --force
```

Buka panel layanan, menu **Konten Publik > Pedoman KKPRL**, kemudian tambah atau edit dokumen. PDF wajib, maksimal 10 MB. Hapus pilihan file sebelumnya pada form untuk menggantinya, unggah PDF baru, lalu simpan. Terbitkan dokumen agar tampil di halaman publik; matikan publikasi untuk menyembunyikannya. File lama dipertahankan agar penggantian PDF tidak menghapus sumber/riwayat secara tidak sengaja. Penghapusan permanen dokumen tidak disediakan; gunakan status publikasi.

Admin memakai izin `ViewAny:LearningMaterial`, `Create:LearningMaterial`, dan `Update:LearningMaterial` yang sudah tersedia. URL halaman admin adalah `/layananruanglaut/proposal-guides`.

Pada database lokal sesi ini tidak ada record `learning_materials`, termasuk pedoman lama. Migrasi lokal berhasil, tetapi halaman menampilkan pesan kosong sampai admin mengunggah PDF. Pada database yang memuat slug pedoman lama, migrasi menyalin PDF secara otomatis. Perilaku impor dan rollback dibuktikan dengan tes database terisolasi.

## Bukti pelaksanaan

- `php artisan test --compact`: **265 tes lulus, 1.315 assertion**; seluruh suite dijalankan setelah kode dan tes terakhir selesai.
- Tes terfokus pedoman, Belajar KKPRL, dan landing page: **23 tes lulus, 227 assertion**.
- Pint pada delapan file PHP implementasi/tes pedoman: lulus. `git diff --check` pada file lama yang diubah: lulus.
- `npm run build`: lulus. Manifest dan CSS produksi diperbarui untuk kelas halaman pedoman.
- Migrasi lokal diterapkan hanya untuk `2026_10_06_000000_create_proposal_guides_table.php`.
- Browser lokal: halaman pedoman dan iframe PDF mengembalikan HTTP 200; PDF satu halaman tampil; tautan buka dan unduh tersedia; console halaman pedoman bersih. Fixture PDF/record sementara dibersihkan setelah verifikasi.
- Empat tombol landing page pada viewport 1265 × 1437: koordinat dan ukuran sebelum/sesudah identik. Lebar tombol 327,90625/327,921875 px, tinggi 88 px; posisi y 570,875/674,875 px. Markup landing page hanya berubah satu `href`.
- Halaman pedoman pada viewport sempit tidak mengalami scroll horizontal. Browser mengukur lebar efektif 500 px saat jendela diminta 390 px.
- Temuan di luar lingkup: landing page mencatat error Chart.js `Canvas is already in use` pada empat grafik. Skrip grafik tidak diubah oleh pekerjaan ini; temuan tersebut tidak dinyatakan terselesaikan.
- Review mencakup pemisahan data, visibilitas draft, keamanan path upload, validasi tipe/ukuran PDF, kestabilan URL, otorisasi aksi admin, riwayat pembelajaran, serta rollback.

## Data graphify

Graph aplikasi pada `app/graphify-out` diperbarui dari source code terbaru. Graph tambahan di `graphify-out` mencakup kode aplikasi serta route, view, migrasi, tes, dan dokumen yang terkait perubahan pedoman. Graph tambahan merupakan peta lingkup pekerjaan ini, bukan indeks seluruh dokumen/mobile proyek. Relasi struktural diekstrak melalui AST; relasi semantik dilengkapi dari sumber dengan penanda confidence.

Perintah pembaruan graph aplikasi dijalankan dari direktori `app` agar lokasi sumber konsisten:

```bash
rtk proxy graphify update . --no-cluster
rtk proxy graphify cluster-only . --no-label
```
