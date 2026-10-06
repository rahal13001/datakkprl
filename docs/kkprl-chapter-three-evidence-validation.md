# Validasi bukti gambar Bab 3

Tanggal: 2026-10-05.

## Alur pengguna

1. Buka Bab 3 dan pilih bagian kondisi perairan.
2. Baca koordinat Bab 1, panduan lapisan peta serta ketentuan sumber. Buka sumber di tab baru atau gunakan foto sendiri.
3. Unggah JPG/PNG/WebP dengan legenda/lokasi/tanggal yang jelas. Isi periode, tanggal akses, kredit dan keterangan yang diketahui; periode boleh kosong.
4. Klik **Simpan gambar & sumber**, kemudian **Analisis dengan AI** pada gambar terkait.
5. Baca narasi di Pratinjau Draft. Jawab klarifikasi lewat chat atau edit langsung. Analisis ulang menyertakan percakapan terbaru.
6. Klik **Konfirmasi & Simpan Draft** jika narasi sesuai. Bukti gambar sudah tersimpan sebagai lampiran; narasi sebelum konfirmasi hanya menjadi checkpoint chat.

## Pengujian otomatis

- Delapan tes awal gagal sebelum fitur diimplementasikan. Regresi koreksi bersamaan juga terbukti gagal sebelum pelindung checkpoint diterapkan.
- Fitur bukti gambar: **14 tes, 82 assertion**, lulus.
- Suite proyek: **246 tes, 1.175 assertion, 0 error, 0 failure, 0 skipped**, lulus. Termasuk pelestarian metadata pada revisi.
- Pint pada file PHP yang berubah dan build Vite berhasil.
- Kasus teruji: simpan provenance/koordinat, gambar privat, patch satu field, sitasi, checkpoint dan konfirmasi, klarifikasi AI, patch lintas field, kegagalan API, PDF/URL berbahaya, akses lintas proposal/bab, kuota bersama, sesi berakhir, koordinat berubah, proposal terkunci saat API berjalan, checksum rusak, penghapusan/replacement, koreksi bersamaan dan perubahan hash manifest.

## Pengujian browser

Browser memakai context terisolasi, SQLite sementara, gambar peta sintetis dan respons Gemini simulasi. API Gemini aktual tidak dipanggil.

- Unggah melalui input file Livewire dan simpan bukti privat berjalan; thumbnail terotorisasi berhasil dimuat.
- Analisis mengisi mangrove dan sitasi, tanpa mengganti koreksi lamun. Chat menampilkan arahan meninjau dan tidak menampilkan blok JSON.
- Refresh mempertahankan gambar, pratinjau dan koreksi. Konfirmasi menyimpan narasi/sumber serta koreksi pada payload database uji.
- Pemilihan lamun mengubah panduan, sumber rekomendasi, URL dan atribusi menjadi Allen Coral Atlas. Pembatasan komersial dan tautan ketentuan tampil.
- Respons API gagal menampilkan arahan mencoba ulang, mempertahankan kedua gambar serta narasi mangrove dan koreksi lamun.
- Viewport ponsel 320 px tidak memiliki overflow horizontal atau kontrol di luar viewport.
- Console skenario akhir tidak memiliki error/warning.

Evidence sementara: `/tmp/kkprl-evidence-mobile.png` dan laporan JUnit `kkprl-evidence-*.xml`. Router, database, peta dan server uji dibersihkan setelah pemeriksaan.

## Uji Gemini aktual

Di luar browser simulasi, service HTTP Gemini diuji dengan satu peta sintetis melalui konfigurasi API aplikasi. Permintaan pertama mengembalikan HTTP 503. Satu percobaan ulang berhasil: respons berisi narasi dengan field yang diminta dan dapat diproses parser. Tidak ada gambar atau data proposal pengguna yang dikirim dalam uji ini. Uji tersebut memastikan integrasi multimodal; kualitas interpretasi pada screenshot sumber sebenarnya tetap perlu ditinjau pengguna.

## Penerapan

Migrasi `2026_10_05_000000_add_source_metadata_to_kkprl_proposal_attachments.php` berhasil diterapkan pada MySQL lokal. MySQL yang awalnya tidak aktif dijalankan sementara dan dihentikan kembali setelah migrasi. Konfigurasi pengguna tidak diubah.

Saat kode dipindahkan ke hosting, terapkan migrasi tersebut dan sertakan aset hasil build melalui alur deploy biasa. Fitur tidak menambah dependency Composer/npm, paket Python, program sistem, worker queue atau layanan geospasial.

Hosting harus mengizinkan HTTPS keluar ke API Gemini. Untuk gambar 5 MiB, sesuaikan limit PHP/proxy `upload_max_filesize` dan `post_max_size`, serta waktu request di atas timeout API 30 detik. Jika provider membatasi nilai tersebut, pengguna dapat memakai gambar lebih kecil. Kualitas respons pada peta dan foto sumber sebenarnya masih perlu dicoba.

## Perbaikan kegagalan kuota Gemini

Pemeriksaan lanjutan pada 2026-10-05 menemukan HTTP 429 `RESOURCE_EXHAUSTED`. Respons API proyek yang dikonfigurasi menyebut batas free tier **20 permintaan per hari per proyek/model**, dengan waktu tunggu sekitar 23 jam 40 menit. Angka tersebut berasal dari respons proyek ini, bukan batas universal Gemini. Kegagalan berasal dari kuota layanan; gambar yang sudah tersimpan tidak rusak.

- Service membedakan kuota harian, batas penggunaan sementara, kuota model belum tersedia, konfigurasi/akses, model tidak tersedia, layanan sibuk, koneksi dan respons kosong. Chat dan analisis gambar menampilkan pesan yang sesuai sambil mempertahankan input, gambar dan pratinjau sebelumnya.
- HTTP 429 menyimpan penundaan pada cache bersama chat/gambar berdasarkan konfigurasi key/model, menggunakan waktu tunggu provider. Jika informasi waktu kuota harian tidak tersedia, fallback mengikuti tengah malam Pacific. Log hanya memuat klasifikasi, model, status dan durasi; key, prompt serta body provider tidak dicatat.
- Tidak ada perpindahan otomatis ke model atau layanan berbayar. Perbaikan kode tidak menambah kuota Google.
- Tes awal gagal sebelum implementasi; tes command reset juga gagal sebelum command ditambahkan. Suite akhir: **257 tes, 1.225 assertion, 0 error, 0 failure, 0 skipped**. Pint pada PHP yang berubah lulus.
- Browser dengan provider simulasi memverifikasi dua klik analisis dan satu pengiriman chat hanya memicu **satu** request provider. Gambar, narasi dan jawaban chat tetap tersedia; console tanpa error/warning.
- Service dengan API aktual dan gambar sintetis mengenali kuota harian. Percobaan ulang ditahan cache: **satu** request aktual untuk dua pemanggilan. Kuota proyek masih habis saat pemeriksaan; keberhasilan analisis setelah reset belum dapat diverifikasi pada pemeriksaan ini.

Konfigurasi Gemini tersedia melalui `services.gemini`, memakai `GEMINI_API_KEY` dan `GEMINI_MODEL`. Perbarui config cache melalui alur deployment biasa bila digunakan. Gunakan cache persisten seperti `file` atau `database` agar penundaan berlaku antar-request. Perbaikan ini tidak menambah migrasi, dependency atau program sistem.

Jika pengelola sudah meningkatkan kuota proyek dan ingin mencoba sebelum penundaan lokal berakhir, jalankan `php artisan kkprl:ai-reset-cooldown`. Command hanya menghapus penundaan Gemini untuk konfigurasi aktif dan mempertahankan cache lain; command tidak memulihkan kuota Google. Tanpa perubahan kuota, tunggu masa penundaan berakhir. Lihat [ketentuan kuota Gemini](https://ai.google.dev/gemini-api/docs/rate-limits).
