# Penghitung pedoman KKPRL

Setiap dokumen pedoman memiliki `view_count` dan `download_count` berupa unsigned bigint dengan nilai awal nol. Keduanya tampil sebagai kolom **Dilihat** dan **Diunduh** di **Konten Publik > Pedoman KKPRL**, dapat diurutkan, dan tidak dapat diubah dari form judul/PDF. Penggantian judul atau PDF mempertahankan statistik pada ID dokumen yang sama. Landing page tidak berubah.

Halaman publik `/pedoman-kkprl` menampilkan **Telah dilihat … kali** dan **Telah diunduh … kali** tepat di atas PDF setiap dokumen, di bawah tombol Buka PDF dan Unduh PDF. Angka diambil dari database saat halaman dimuat, memakai pemisah ribuan Indonesia, dan diperbarui saat halaman dimuat kembali. Baris statistik dapat membungkus pada layar kecil; ikon dekoratif tidak dibacakan pembaca layar. Membaca statistik tidak menambah angka; PDF yang dimuat melalui iframe tetap mengikuti aturan pencatatan di bawah.

`ProposalGuideController::pdf()` menambah `view_count` satu kali untuk permintaan `GET` tanpa header `Range` setelah file tersedia dan respons file berhasil dibuat. `download()` melakukan hal yang sama terhadap `download_count`. Penambahan menggunakan operasi `increment` database agar dua akses tidak menimpa angka satu sama lain. `HEAD`, potongan PDF (`Range`), dokumen draft, file hilang, halaman daftar, dan redirect lama tidak menambah angka. Tujuan redirect mencatat akses saat PDF/unduhan benar-benar diminta.

Iframe, tombol Buka PDF, dan pratinjau admin masing-masing mendapat UUID `view_token` saat halaman dirender. Permintaan PDF berulang dengan token yang sama dihitung sekali selama 30 menit melalui operasi atomik `Cache::add`. Ini mencegah viewer browser menggandakan hitungan ketika meminta PDF ulang. Render halaman berikutnya menghasilkan token baru. Cache memakai konfigurasi cache aplikasi yang sudah ada; tidak memerlukan tabel atau layanan tambahan. Jaminan deduplikasi mengikuti penyimpanan cache tersebut, dan penghapusan cache dapat membuat token lama dihitung ulang. Endpoint PDF tanpa token valid tetap menghitung setiap permintaan GET penuh untuk kompatibilitas tautan langsung dan redirect lama. Dasar API: [Laravel 13 — Store if Not Present](https://laravel.com/docs/13.x/cache#store-if-not-present).

**Dilihat** menghitung pembukaan PDF melalui iframe, tombol Buka PDF, atau endpoint PDF; membuka halaman kembali dapat menambah angka. Pratinjau PDF terbit melalui admin juga memakai endpoint tersebut. **Diunduh** menghitung permintaan melalui tombol Unduh PDF atau endpoint unduh, bukan konfirmasi seluruh byte selesai diterima. Ini bukan penghitung pengunjung unik. Tombol simpan bawaan viewer browser dan tautan file storage langsung tidak memanggil endpoint unduh sehingga tidak tercatat sebagai unduhan.

## Pemulihan angka lama

Migrasi baru `2026_10_10_000001_add_counters_to_proposal_guides_table.php` memulihkan statistik pedoman yang dipindahkan dari Belajar KKPRL. Record arsip tetap dapat dibaca melalui `legacy_material_id`. Nilai sumber yang lebih besar dipertahankan; pedoman lama memperoleh paling sedikit **96 dilihat / 65 diunduh**, sesuai angka terakhir yang diberikan client. Record lama dikenali lewat slug materi asal atau slug judul lengkap pedoman yang sudah diketahui. Pedoman baru lainnya mulai dari nol, dan statistik arsip Belajar KKPRL tidak ditimpa.

Migrasi tidak mengubah file migrasi awal yang sudah pernah dijalankan. Laravel menjalankan migrasi tambahan satu kali sehingga menjalankan `migrate` kembali tidak mereset angka yang sudah bertambah.

```bash
rtk proxy php artisan migrate --path=database/migrations/2026_10_10_000001_add_counters_to_proposal_guides_table.php --force
```

Saat implementasi penghitung awal, database lokal tidak memuat record pedoman atau materi asal. Pemulihan lokal dilakukan pada draft **Pedoman Perolehan Data Proposal KKPRL Skala Kecil** dengan angka awal 96/65 dan tanpa mengarang file PDF. PDF dapat diunggah pada draft tersebut melalui admin, lalu diterbitkan. Dokumen tidak tersedia untuk publik sebelum PDF diisi dan status publikasi diaktifkan.

## Validasi implementasi penghitung

Tes baru membuktikan nilai awal nol, penambahan terpisah, kelanjutan 96/65 menjadi 97/66, pengecualian HEAD/Range/404/draft, redirect tanpa pencatatan ganda, statistik yang bertahan saat PDF diganti, tampilan kolom admin, perlindungan form konten, pemulihan minimum 96/65, serta statistik sumber yang lebih besar tetap terjaga. Regresi viewer menguji dua permintaan PDF dari satu iframe tetap satu hitungan dan render halaman berikutnya menambah satu hitungan baru. Semua tes memakai database SQLite dan storage fake terisolasi.

- RED: tes awal gagal karena nilai penghitung belum tersedia (`null` dibanding `0`).
- RED regresi viewer: dua permintaan dari iframe menghasilkan `2`, padahal seharusnya `1`.
- GREEN: pedoman, penghitung, Belajar KKPRL, dan landing page: **30 tes lulus, 299 assertion**.
- Pint pada lima file PHP terkait: lulus.
- Browser lokal: PDF tampil, satu render menambah satu lihat, tombol Unduh PDF menambah satu unduh, kolom admin memperlihatkan 96/65, dan console publik tanpa error/warning.
- Suite lengkap setelah deduplikasi viewer: **483 tes lulus, 2.714 assertion**, 189,34 detik.
- Dokumen dan akun verifikasi sementara sudah dihapus; draft pedoman asli tetap 96/65, belum terbit, dan belum memiliki PDF.
- Review perubahan dan `git diff --check`: lulus. Tidak ada perubahan CSS atau asset frontend.

## Validasi tampilan statistik publik

Pembaruan tampilan diuji setelah pedoman lokal memiliki PDF dan berstatus terbit. Browser menampilkan **Telah dilihat 99 kali** dan **Telah diunduh 65 kali**, sesuai snapshot database saat halaman dimuat. Kedua statistik berada di atas iframe pada lebar 320, 768, 1024, dan 1440 piksel, tanpa overflow horizontal. Pada lebar 320 piksel, statistik tersusun pada dua baris. PDF 90 halaman tetap tampil, label dapat dibaca dari accessibility tree, dan console tanpa error/warning. Verifikasi memakai dokumen yang sudah terbit; tidak membuat fixture atau mereset angka.

`npm run build` lulus dan menghasilkan CSS baru beserta manifest Vite. Asset terkompilasi sebelumnya tetap dipertahankan. Tes pedoman, penghitung, dan landing yang sudah ada lulus: **18 tes, 174 assertion**. Perubahan ini hanya menampilkan angka yang sudah tersedia; tidak menambahkan migrasi atau mengubah logika penghitung. Suite lengkap 483 tes di atas adalah hasil implementasi penghitung sebelumnya, bukan rerun untuk perubahan tampilan ini.

## Graphify dan pencatatan perubahan

Graph root dan graph aplikasi diperbarui untuk kode, migrasi, tes, dan dokumentasi penghitung sesuai cakupan masing-masing. Pembaruan mempertahankan relasi di luar cakupan, komunitas lama, dan hyperedge. Hasil tersedia sebagai `graph.json`, `graph.html`, dan `GRAPH_REPORT.md`, dengan hash sumber serta audit pada [graph root](../graphify-out/proposal-guide-counters-audit.json) dan [graph aplikasi](../app/graphify-out/proposal-guide-counters-audit.json). Analisis komunitas lama ditandai sebagai snapshot sebelumnya; token model untuk ekstraksi semantik tidak tersedia, bukan nol. Perubahan fitur dan keluaran graph dicatat pada commit terpisah.

Pembaruan berikutnya untuk statistik publik mencakup Blade halaman pedoman dan dokumentasi ini pada graph root. Provenance tersedia pada [audit statistik publik](../graphify-out/public-guide-statistics-audit.json); audit penghitung sebelumnya tetap menjadi catatan historis. Graph aplikasi tetap memakai sumber sebelumnya karena tidak ada perubahan kode di direktori `app` pada pembaruan tampilan.
