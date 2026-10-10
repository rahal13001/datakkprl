# Penghitung pedoman KKPRL

Setiap dokumen pedoman memiliki `view_count` dan `download_count` berupa unsigned bigint dengan nilai awal nol. Keduanya tampil sebagai kolom **Dilihat** dan **Diunduh** di **Konten Publik > Pedoman KKPRL**, dapat diurutkan, dan tidak dapat diubah dari form judul/PDF. Penggantian judul atau PDF mempertahankan statistik pada ID dokumen yang sama. Landing page tidak berubah.

`ProposalGuideController::pdf()` menambah `view_count` satu kali untuk permintaan `GET` tanpa header `Range` setelah file tersedia dan respons file berhasil dibuat. `download()` melakukan hal yang sama terhadap `download_count`. Penambahan menggunakan operasi `increment` database agar dua akses tidak menimpa angka satu sama lain. `HEAD`, potongan PDF (`Range`), dokumen draft, file hilang, halaman daftar, dan redirect lama tidak menambah angka. Tujuan redirect mencatat akses saat PDF/unduhan benar-benar diminta.

Iframe, tombol Buka PDF, dan pratinjau admin masing-masing mendapat UUID `view_token` saat halaman dirender. Permintaan PDF berulang dengan token yang sama dihitung sekali selama 30 menit melalui operasi atomik `Cache::add`. Ini mencegah viewer browser menggandakan hitungan ketika meminta PDF ulang. Render halaman berikutnya menghasilkan token baru. Cache memakai konfigurasi cache aplikasi yang sudah ada; tidak memerlukan tabel atau layanan tambahan. Jaminan deduplikasi mengikuti penyimpanan cache tersebut, dan penghapusan cache dapat membuat token lama dihitung ulang. Endpoint PDF tanpa token valid tetap menghitung setiap permintaan GET penuh untuk kompatibilitas tautan langsung dan redirect lama. Dasar API: [Laravel 13 — Store if Not Present](https://laravel.com/docs/13.x/cache#store-if-not-present).

**Dilihat** menghitung pembukaan PDF melalui iframe, tombol Buka PDF, atau endpoint PDF; membuka halaman kembali dapat menambah angka. Pratinjau PDF terbit melalui admin juga memakai endpoint tersebut. **Diunduh** menghitung permintaan melalui tombol Unduh PDF atau endpoint unduh, bukan konfirmasi seluruh byte selesai diterima. Ini bukan penghitung pengunjung unik. Tombol simpan bawaan viewer browser dan tautan file storage langsung tidak memanggil endpoint unduh sehingga tidak tercatat sebagai unduhan.

## Pemulihan angka lama

Migrasi baru `2026_10_10_000001_add_counters_to_proposal_guides_table.php` memulihkan statistik pedoman yang dipindahkan dari Belajar KKPRL. Record arsip tetap dapat dibaca melalui `legacy_material_id`. Nilai sumber yang lebih besar dipertahankan; pedoman lama memperoleh paling sedikit **96 dilihat / 65 diunduh**, sesuai angka terakhir yang diberikan client. Record lama dikenali lewat slug materi asal atau slug judul lengkap pedoman yang sudah diketahui. Pedoman baru lainnya mulai dari nol, dan statistik arsip Belajar KKPRL tidak ditimpa.

Migrasi tidak mengubah file migrasi awal yang sudah pernah dijalankan. Laravel menjalankan migrasi tambahan satu kali sehingga menjalankan `migrate` kembali tidak mereset angka yang sudah bertambah.

```bash
rtk proxy php artisan migrate --path=database/migrations/2026_10_10_000001_add_counters_to_proposal_guides_table.php --force
```

Database lokal sesi ini tidak memuat record pedoman atau materi asal. Pemulihan lokal dilakukan pada draft **Pedoman Perolehan Data Proposal KKPRL Skala Kecil** dengan angka awal 96/65 dan tanpa mengarang file PDF. Unggah PDF pada draft tersebut melalui admin, lalu terbitkan. Dokumen tetap tidak tersedia untuk publik sebelum PDF diisi dan status publikasi diaktifkan.

## Validasi

Tes baru membuktikan nilai awal nol, penambahan terpisah, kelanjutan 96/65 menjadi 97/66, pengecualian HEAD/Range/404/draft, redirect tanpa pencatatan ganda, statistik yang bertahan saat PDF diganti, tampilan kolom admin, perlindungan form konten, pemulihan minimum 96/65, serta statistik sumber yang lebih besar tetap terjaga. Regresi viewer menguji dua permintaan PDF dari satu iframe tetap satu hitungan dan render halaman berikutnya menambah satu hitungan baru. Semua tes memakai database SQLite dan storage fake terisolasi.

- RED: tes awal gagal karena nilai penghitung belum tersedia (`null` dibanding `0`).
- RED regresi viewer: dua permintaan dari iframe menghasilkan `2`, padahal seharusnya `1`.
- GREEN: pedoman, penghitung, Belajar KKPRL, dan landing page: **30 tes lulus, 299 assertion**.
- Pint pada lima file PHP terkait: lulus.
- Browser lokal: PDF tampil, satu render menambah satu lihat, tombol Unduh PDF menambah satu unduh, kolom admin memperlihatkan 96/65, dan console publik tanpa error/warning.
- Suite lengkap setelah deduplikasi viewer: **483 tes lulus, 2.714 assertion**, 189,34 detik.
- Dokumen dan akun verifikasi sementara sudah dihapus; draft pedoman asli tetap 96/65, belum terbit, dan belum memiliki PDF.
- Review perubahan dan `git diff --check`: lulus. Tidak ada perubahan CSS atau asset frontend.

## Graphify dan pencatatan perubahan

Graph root dan graph aplikasi diperbarui untuk kode, migrasi, tes, dan dokumentasi penghitung sesuai cakupan masing-masing. Pembaruan mempertahankan relasi di luar cakupan, komunitas lama, dan hyperedge. Hasil tersedia sebagai `graph.json`, `graph.html`, dan `GRAPH_REPORT.md`, dengan hash sumber serta audit pada [graph root](../graphify-out/proposal-guide-counters-audit.json) dan [graph aplikasi](../app/graphify-out/proposal-guide-counters-audit.json). Analisis komunitas lama ditandai sebagai snapshot sebelumnya; token model untuk ekstraksi semantik tidak tersedia, bukan nol. Perubahan fitur dan keluaran graph dicatat pada commit terpisah.
