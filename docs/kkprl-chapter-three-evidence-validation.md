# Validasi bukti gambar Bab 3

> Regresi akses akun tanggal 2026-10-07: suite final **461 tests, 2429 assertions, lulus**, mencakup fitur pada laporan ini. Fixture kini memakai akun approved/owner; tiket/HP tidak lagi autentikasi. Hasil dan batas browser baru ada pada [validasi akun](kkprl-applicant-account-validation.md). Angka dan screenshot tahapan sebelumnya di bawah tetap bukti historis; batas ilmiah/validasi fitur dipertahankan.

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

## Data tabel pasang surut — 2026-10-07

Pada topik Pasang surut, pengguna dapat memakai gambar, CSV/XLS/XLSX, atau keduanya. Isi sumber bukti, pilih file tabel, periksa kolom tanggal/waktu dan tinggi, pilih kolom jam terpisah bila ada, format tanggal, satuan, zona waktu serta acuan tinggi. Simpan data, kemudian klik **Hitung & susun narasi pasut**. Tabel yang terakhir disimpan diprioritaskan, termasuk saat analisis dimulai dari tombol pada gambar pasut. Pengguna dapat menghapus tabel yang tidak diperlukan.

CSV harus UTF-8 dengan pemisah koma/titik koma/tab; gunakan judul kolom pada baris pertama. Excel membaca worksheet pertama dan mendukung tanggal serial kalender 1900/1904. Nilai jam tengah malam berupa angka nol dibaca sebagai 00:00. Gunakan nilai angka tanpa pemisah ribuan; rumus harus dikonversi menjadi nilai sebelum unggah. Maksimal 5 MiB, 50.000 baris data di luar judul kolom, 20 kolom, area data Excel 200.000 sel dan isi ZIP 32 MiB. Data per menit satu bulan didukung tanpa pengurangan sampel. Lampiran berbagi batas Bab 3 yang sudah ada.

Aplikasi menghitung statistik secara lokal; AI menerima ringkasan untuk menyusun narasi. Rata-rata adalah rata-rata aritmetika sampel, bukan penetapan MSL. Minimum/maksimum hanya mewakili sampel pada periode yang diunggah. Tidak dilakukan interpolasi, analisis harmonik atau penetapan tipe pasut/HHWL/LLWL/HAT/LAT. Keputusan: [ADR-018](decisions/ADR-018-tidal-table-evidence.md).

### Hasil pemeriksaan

- Sebelas tes parser awal dan enam tes fitur awal gagal sebelum implementasi. Tes area sel Excel serta file tanpa judul kolom juga terbukti gagal sebelum perbaikannya.
- Suite akhir: **291 tes, 1.428 assertion, 0 failure, 0 error, 0 skipped**. Pint pada 11 file PHP lulus; build Vite berhasil.
- Kasus: CSV desimal koma/BOM, Excel XLS/XLSX asli, kalender 1904, tanggal dan jam terpisah, unit cm/mm, waktu duplikat, angka/waktu invalid, formula, HTML berganti ekstensi, batas baris/ZIP/sel, judul kolom, isolasi akses, kuota lampiran bersama, checksum/koordinat, kegagalan dan klarifikasi AI, patch lintas field, tabel baru/koreksi/lock selama request serta Word/PDF.
- Browser terisolasi dengan SQLite dan file sintetis berhasil mengunggah CSV, XLS, XLSX serta gambar melalui kontrol Livewire. Nama file dan kolom terdeteksi tampil. Konversi 150 cm menjadi 1,500 m, periode/zona waktu dan acuan tinggi tampil benar.
- Analisis dari gambar ketika tabel tersedia mengirim satu permintaan teks berisi ringkasan tabel ke provider simulasi; gambar tidak dipakai untuk mengganti angka tabel. Konfirmasi dan refresh mempertahankan narasi, gambar serta ketiga format file.
- Pada viewport 320, 768, 1024 dan 1440 px, tidak ada kontrol yang keluar viewport atau overflow horizontal. Pada 320 px, kedua input file memiliki lebar 218 px dan tampil sebagai blok dengan tombol berwarna, label, batas format serta status. ID elemen tidak duplikat dan input memiliki label. Console tanpa error/warning.
- Respons Gemini pada uji ini disimulasikan; API berbayar tidak dipanggil. Parser dan perhitungan memakai file Excel/CSV asli, bukan hasil simulasi angka.

Screenshot sementara: `/tmp/kkprl-tide-mobile-final.png`. Laporan JUnit disimpan di `/tmp/kkprl-tide-validation/`. Server, router, database dan file uji dibersihkan setelah pemeriksaan. Tidak ada migrasi atau dependency baru untuk penerapan; sertakan kode dan aset build melalui alur deployment biasa.

### Penyesuaian kapasitas data BIG satu bulan — 2026-10-07

Batas awal 20.000 baris/60.000 sel Excel tidak mencakup data per menit satu bulan. Batas menjadi 50.000 baris data dan 200.000 sel Excel. Baris judul tidak mengurangi kapasitas sampel. Pembacaan XLS/XLSX memakai read filter maksimal 60.000 posisi sel per tahap. Worksheet diputus, objek dilepas dan referensi siklik PhpSpreadsheet dibersihkan pada akhir setiap tahap, termasuk jalur gagal. Tabel mentah dilepas sesudah perhitungan sebelum pembacaan ulang saat penyimpanan atau request AI. Seluruh sampel tetap divalidasi dan dihitung; tidak dilakukan downsampling.

Fixture writer Excel dibersihkan antar-tes. Tes tabel bulanan lengkap dan unggah bulanan dijalankan dalam proses PHPUnit terpisah agar alokasi besar tidak menumpuk bersama tes render dokumen lainnya. Semua tes tetap dijalankan; batas memori suite tidak dinaikkan dari 512 MiB.

- Tes regresi 30 hari CSV (43.201 sampel) terbukti gagal karena batas baris sebelum perbaikan. Setelah perbaikan, 30 hari CSV dan 31 hari CSV/XLS/XLSX dengan empat kolom menghasilkan jumlah sampel penuh, periode tepat, interval minimum/maksimum 60 detik, ekstrem dan rata-rata yang sesuai.
- Batas 50.000 baris di luar judul diterima; sampel berikutnya ditolak. Rumus di bagian Excel setelah tahap pertama tetap ditolak pada XLS maupun XLSX. Batas total sel dan isi ZIP tetap diperiksa sebelum workbook dimuat.
- Livewire berhasil mengunggah CSV 44.641 sampel, menyimpan file asli secara privat, menghitung periode 744 jam dan menyusun pratinjau menggunakan satu request AI simulasi. Prompt memuat ringkasan lengkap dengan jumlah sampel, bukan seluruh tabel, berukuran kurang dari 20.000 byte. Payload proposal belum berubah sebelum konfirmasi.
- Pengukuran CLI terpisah, fixture sintetis empat kolom dan 44.641 sampel, batas memori PHP **128 MiB**: CSV puncak **62 MiB**, XLS **90 MiB**, XLSX **92 MiB**. Ukuran file sekitar 1,25 MB, 2,72 MB dan 0,74 MB, semuanya di bawah batas 5 MiB. Angka ini mengukur reader dan analyzer pada mesin pengembangan; bukan pengukuran seluruh request Laravel atau jaminan konfigurasi shared hosting.
- API berbayar tidak dipanggil. Tidak ada dependency, instalasi program sistem atau migrasi baru.
- Suite akhir penyesuaian kapasitas: **299 tes, 1.485 assertion, 0 failure, 0 error, 0 skipped**, batas 512 MiB, puncak proses suite 350 MiB. Pint pada empat file PHP yang berubah lulus. Laporan JUnit: `/tmp/kkprl-tide-monthly-validation/suite.xml`. Fixture pengukuran CSV/XLS/XLSX dan skrip sementara dibersihkan setelah pemeriksaan.

### CSV asli SRGI BIG — 2026-10-07

File contoh `docs/pasut/data_srgi.csv` memuat tujuh baris informasi instansi/stasiun, dua baris kosong dan header tabel pada baris 10. Format tersebut sebelumnya ditolak karena baris informasi pertama diperlakukan sebagai judul kolom. Header juga memuat UTC dan waktu lokal berakhiran `WIT`, sehingga mapping kolom dan zona waktu harus dibedakan.

Reader sekarang mengenali informasi awal SRGI, mempertahankan nama/koordinat stasiun serta datum, dan mengambil seluruh tabel. Form memilih UTC, meter dan datum file; pengguna dapat memilih waktu lokal dengan zona yang cocok. Rumus, koordinat tidak valid, header hilang dan data tidak valid tetap ditolak. Penanda zona waktu tidak dibuang tanpa pemeriksaan. Label `predicted_height_m` menjadi konteks prediksi pada ringkasan dan prompt AI.

- Dua tes regresi SRGI awal gagal sebelum implementasi. Tes terfokus akhir: **9 tes, 51 assertion**, termasuk upload Livewire, metadata privat, pilihan otomatis, narasi prediksi, ISO/waktu lokal, empat penanda zona, konflik satuan/zona serta informasi awal tidak valid.
- File asli pengguna terbaca penuh: **41.760 sampel**, interval minimum/maksimum **60 detik**, stasiun PAAM, datum MSL. Periode UTC **2026-09-01 00:00:00–2026-09-29 23:59:00**; periode WIT **2026-09-01 09:00:00–2026-09-30 08:59:00**. Jumlah sampel sesuai isi file; tidak ditambah agar menjadi sebulan penuh.
- Minimum **−0,900 m**, maksimum **0,845 m**, rentang **1,745 m**, rata-rata sekitar **−0,0002417624521 m**. Perhitungan UTC dan WIT menghasilkan statistik sama dan cocok dengan pemeriksaan independen CSV menggunakan Python.
- Pembacaan dan perhitungan file asli dalam CLI dengan batas **128 MiB** memakai puncak **60 MiB**. Angka ini bukan pengukuran seluruh request Laravel. AI diuji dengan respons simulasi; API berbayar tidak dipanggil.
- Suite akhir: **308 tes, 1.536 assertion, 0 failure, 0 error, 0 skipped**, puncak proses suite 350 MiB. Pint pada lima file PHP yang berubah lulus. Laporan JUnit: `/tmp/kkprl-tide-srgi-validation/suite.xml`. Skrip verifikasi sementara dibersihkan; CSV contoh pengguna tetap tersedia dan tidak diubah.

### Sinkronisasi pilihan zona waktu dan kolom — 2026-10-07

Validasi pasangan kolom/zona sebelumnya belum menyinkronkan perubahan form: memilih WIT masih mempertahankan `timestamp_utc`; memilih UTC saat memakai `timestamp_local` juga ditolak. Untuk file dengan kedua timestamp dan penanda zona lokal, form kini menghubungkan keduanya secara langsung dengan update Livewire live. Pemilihan kolom juga memperbarui zona. Mapping jam terpisah selalu kosong, karena setiap kolom sudah memuat tanggal dan jam lengkap; kontrolnya dinonaktifkan. Pasangan diperiksa kembali sebelum simpan. Validasi analyzer tetap menolak penanda zona yang tidak cocok; tidak ada reinterpretasi jam dari zona lain.

- Dua regresi pemilihan WIT/perubahan dua arah terbukti gagal sebelum perbaikan. Tes memastikan UTC dan WIT menyimpan kolom serta zona yang sesuai, pergantian bolak-balik tidak meninggalkan state lama, dan pilihan jam terpisah tidak menggabungkan dua timestamp lengkap.
- Browser terisolasi berhasil mengunggah file asli **41.760 sampel**, mengganti UTC ke WIT dan kembali ke UTC, serta memilih kolom lokal untuk mengubah zona ke WIT. Penyimpanan file dan narasi AI simulasi berhasil untuk **kedua zona**. Periode WIT mulai 2026-09-01 09:00:00; periode UTC mulai 2026-09-01 00:00:00. Masing-masing mempertahankan seluruh sampel dan interval 60 detik. Tidak ada alert maupun error/warning console.
- Suite akhir: **311 tes, 1.563 assertion, 0 failure, 0 error, 0 skipped**; Pint pada kedua file PHP yang berubah lulus. API berbayar tidak dipanggil; database, sesi dan penyimpanan browser memakai fixture terisolasi.
- Laporan JUnit: `/tmp/kkprl-tide-timezone-validation/suite.xml`; screenshot: `/tmp/kkprl-timezone-browser.png`. Server, database, skrip dan salinan CSV sementara dibersihkan setelah pemeriksaan. CSV asli pengguna tidak diubah.

### Kegagalan koneksi saat analisis tabel — 2026-10-07

Log pengguna sebelumnya memuat tiga `ConnectionException` tanpa kode transport atau durasi, sehingga penyebab spesifik kejadian lama tidak dapat dipastikan. Pengguna melaporkan error kurang dari sepuluh detik; implementasi sebelumnya membatasi pembentukan koneksi hanya lima detik dan seluruh request tiga puluh detik. Batas koneksi kini 15 detik, total 60 detik, dengan konfigurasi dan batas maksimum. Log serta pesan membedakan timeout, DNS dan verifikasi sertifikat; tidak mencatat key, prompt atau teks exception.

Prompt tabel kini khusus pasut dan membawa koreksi pasut terbaru tanpa pratinjau field Bab 3 lainnya. Tugas narasi tabel meminta thinking LOW pada Flash yang didukung; percakapan dan gambar mempertahankan pengaturan sebelumnya. Seluruh sampel tetap dihitung lokal. Tidak ada retry otomatis untuk POST berbayar; file serta draft lama dipertahankan saat gagal.

Verifikasi memakai ringkasan CSV asli 41.760 sampel, tanpa mengirim seluruh file atau menyimpan payload pengguna. Satu request langsung sebelum perubahan berhasil HTTP 200 sekitar 11,67 detik. Dua verifikasi berikutnya melalui service aplikasi mengalami timeout cURL 28 sekitar 60 detik, termasuk setelah thinking LOW. Pada verifikasi terakhir koneksi/TLS selesai sekitar 0,61 detik, tetapi tidak ada first byte dari penyedia sebelum batas waktu. Ini membuktikan timeout respons pada uji tersebut; tidak membuktikan penyebab tiga kegagalan lama atau menjamin pemulihan provider. Tiga pemanggilan Gemini nyata dilakukan untuk diagnosis; tes regresi memakai HTTP fake dan tidak memakai API berbayar.

- Tes reproduksi terbukti gagal sebelum perbaikan untuk deadline, klasifikasi timeout, prompt pasut terfokus dan thinking LOW.
- Suite final: **319 tes, 1.599 assertion, 0 failure, 0 error, 0 skipped**, puncak 348 MiB. Pint pada enam file PHP yang berubah lulus. Tes memastikan opsi LOW hanya diterapkan saat diminta pada model yang didukung, statistik dan koreksi dipertahankan, kegagalan tidak menyimpan payload, dan kode transport tidak membocorkan credential/prompt.
- Laporan JUnit: `/tmp/kkprl-ai-connection-validation/suite.xml`. Skrip diagnosis serta laporan sementara di workspace dibersihkan; CSV asli dan konfigurasi `.env` tidak diubah. Tidak ada UI, dependency atau migrasi baru.

### Harmonik backend, estimasi Formzahl dan AI opsional — 2026-10-07

Keputusan: [ADR-019](decisions/ADR-019-backend-tidal-harmonics.md). Aplikasi menghitung statistik dan amplitudo efektif delapan komponen dengan QR/rotasi Givens, konstanta serta tren linear. Formzahl hanya tersedia jika kebijakan mutu aplikasi terpenuhi. Hasil menyatakan keterbatasan rekaman sebulan, komponen berdekatan yang belum dipisahkan dan tidak adanya koreksi nodal/inferensi/interval kepercayaan. Estimasi periode bukan konstanta astronomis stasiun atau penetapan tipe resmi.

- Pengujian sintetis memulihkan amplitudo yang diketahui dengan offset, fase, komponen pendukung dan drift, toleransi 1e-9 meter; seluruh empat tipe dan batas F diuji. Konversi cm–m, UTC–WIT dan satu sampel hilang menghasilkan F yang sama. Periode singkat, tinggi konstan, cakupan rendah, jeda besar, kecocokan buruk dan amplitudo M2+S2 mendekati nol tidak menghasilkan tipe. Regresi khusus membuktikan drift besar dapat membuat R² total tinggi; penilaian memakai R² pasut setelah pemisahan tren.
- CSV asli `docs/pasut/data_srgi.csv`: seluruh **41.760 sampel** digunakan, F **0,3191236485273796**, estimasi campuran condong harian ganda; RMSE **0,01747809864 m**, R² pasut **0,9982321264**. Data tetap diberi label prediksi, datum MSL dari sumber dan lokasi stasiun belum diverifikasi terhadap Bab 1.
- Pemeriksaan independen memakai Python standard library untuk membaca CSV lalu menyelesaikan model yang sama melalui persamaan normal dan eliminasi Gaussian dengan pivot parsial, terpisah dari solver PHP QR. Selisih F sekitar **4,05e-15**, selisih maksimum amplitudo delapan komponen sekitar **6,83e-15 m**. Ini verifikasi aritmetika/model implementasi, bukan validasi konstanta stasiun atau jaminan ketepatan tipe astronomis.
- Pengukuran CLI CSV asli, reader dan analyzer harmonik, batas PHP **128 MiB**: sekitar **0,639 detik**, puncak **72 MiB** pada mesin pengembangan. Tidak mengukur seluruh request Laravel atau menjamin waktu shared hosting.
- Browser dengan database SQLite, sesi, storage dan key sintetis terisolasi berhasil mengunggah CSV asli, menghitung F, membaca narasi awal, melakukan konfirmasi dan memuat ulang draft. Timeout AI simulasi tetap menampilkan F dan mempertahankan narasi identik. Database fixture membuktikan payload pasut tersimpan setelah konfirmasi. Viewport **320 px** dan **1280 px** tidak memiliki luapan horizontal; tidak ada error/warning console.
- Pengujian akses lintas proposal/bab, file asli berubah meskipun hasil sudah dicache, koordinat berubah, akses dicabut dan proposal terkunci lulus. Word/PDF memuat Formzahl dan keterbatasan metode. Respons AI tidak valid atau checkpoint baru mempertahankan koreksi pengguna. Hasil cache dinormalisasi sesuai JSON metadata tersimpan sebelum perbandingan setelah request.
- Suite final: **341 tes, 1.715 assertion, 0 failure, 0 error, 0 skipped**, puncak proses **346 MiB**, sekitar **154 detik**. Pint pada lima file PHP lulus; `npm run build` berhasil. Seluruh pengujian AI memakai HTTP fake; API berbayar tidak dipanggil untuk perubahan ini.
- Artefak validasi di `/tmp/kkprl-formzahl-validation/`: `suite.xml`, `original-csv-summary.json`, `independent-calculation.json`, `backend-snapshot.txt`, `timeout-snapshot.txt`, `mobile-320.png` dan `desktop.png`. Server, halaman browser, skrip, database dan salinan unggah sementara dibersihkan setelah pemeriksaan. Tidak ada instalasi, dependency, migrasi baru atau perubahan `.env` pengguna; CSV asli tidak diubah.

### Sumber bawaan batimetri Garmin — 2026-10-07

Sumber bawaan `bathymetry` di katalog berubah dari `gebco` menjadi `garmin`, memakai tautan viewer yang diberikan pengguna. Kredit default Garmin/Navionics dan tautan ketentuan sumber ikut form serta provenance. Panduan meminta lokasi sesuai Bab 1, angka kedalaman/kontur yang terbaca, satuan, skala dan kredit pada screenshot. GEBCO tetap tersedia sebagai alternatif; sumber bawaan profil dasar laut serta provenance lampiran lama tidak diubah.

- Browser localhost dengan database/sesi/storage terisolasi membuktikan pemilihan batimetri mengisi source `garmin`, URL viewer lengkap, kredit dan panduan secara otomatis. Tidak ada error/warning console. Screenshot dan snapshot disimpan di `/tmp/kkprl-garmin-validation/`.
- Halaman viewer Garmin dibuka pada browser terisolasi dan judul serta kontrol pencariannya terdeteksi. Tile peta tidak berhasil diverifikasi pada lingkungan browser otomatis ini; pemeriksaan tersebut tidak menjamin cakupan kedalaman atau ketersediaan data pada lokasi pengguna.
- Tes regresi `KkprlChapterThreeEvidenceTest`: **16 tes, 94 assertion**, seluruhnya lulus. Pint katalog dan pemeriksaan whitespace lulus. Tidak ada perubahan Blade/CSS/JavaScript, sehingga build aset tidak diperlukan. Tidak ada dependency, migrasi atau perubahan `.env`; API AI berbayar tidak dipanggil. Fixture dan server browser sementara dibersihkan setelah validasi.


### Penampang GMRT langsung di Bab 3 — 2026-10-07

Keputusan: [ADR-020](decisions/ADR-020-profil-dasar-laut-gmrt.md). Topik Profil dasar laut sekarang menyediakan peta referensi Bab 1, pemilihan A/B dan alternatif form koordinat desimal. Backend menghasilkan grafik PNG privat beserta narasi awal dari GMRT, tanpa Gemini. Gambar dari sumber lain tetap dapat diunggah. Penggantian pratinjau yang sudah terisi meminta konfirmasi. Grafik memakai kuota dan ekspor inline yang sudah ada.

- Probe publik langsung memakai Python dan service Laravel/PHP berhasil. Lintasan A (-0.48, 130.88) ke B (-0.49, 130.89) menghasilkan **101 sampel**, panjang menurut layanan **1.569,0 m**, ekstrem elevasi sampel **−385,2 m / −258,5 m**. Request PHP dan pembuatan PNG sekitar **6,7 detik** pada mesin pengembangan, bukan jaminan waktu shared hosting. Urutan JSON nyata adalah latitude, longitude, elevasi meter, jarak meter; parser memeriksa posisi dan jarak untuk mencegah pertukaran koordinat.
- Tests memeriksa koordinat/angka tidak valid, titik identik, ukuran/jumlah respons, redirect, jarak tidak berurutan, null/celah dan daratan, timeout, kuota, akses dan status terkunci, perubahan koordinat/checkpoint saat request, AI gagal dan provenance. PNG mempertahankan signed elevation, memutus celah, memberi label jarak/elevasi, A/B, koordinat, kredit, lisensi, waktu akses dan perbedaan skala. Tidak menyimpulkan sedimen, datum, resolusi lokal atau survei.
- Browser terisolasi membuktikan klik peta mengisi form dan menampilkan A/B. Bersihkan lintasan mengosongkan empat nilai, lalu klik berikutnya menghasilkan **A**, tanpa menghapus grafik. Dua grafik berhasil dibuat dari respons GMRT publik yang direkam; narasi tetap di pratinjau sebelum konfirmasi. AI timeout simulasi mempertahankan narasi; konfirmasi dan reload berhasil mempertahankan draft dan gambar.
- Peta memuat tile OSM. Pada pemeriksaan awal beberapa tile mengalami `ERR_NETWORK_CHANGED`; fallback koordinat dan pesan tersedia. Konflik animasi tooltip Leaflet ditemukan dan diperbaiki dengan menyimpan object Leaflet di luar proxy Alpine, menonaktifkan animasi dan zoom roda agar scroll halaman tidak mengganggu peta. Pemeriksaan ulang setelah reload, perubahan topik, pemilihan/reset dan AI tidak memiliki error/warning console.
- Viewport desktop dan **320 px** tidak memiliki luapan horizontal. Peta dan form tampil di ponsel; grafik tersimpan berukuran **1100×580** dan dapat diunduh. Word/PDF memuat grafik serta atribusi CC BY 4.0; metadata sampel bertahan pada snapshot dan perubahan metadata mengubah manifest.
- Suite final: **354 tes, 1.799 assertion, 0 failure, 0 error, 0 skipped**, puncak **350 MiB**, sekitar **98 detik**. Pint tujuh file PHP dan pemeriksaan whitespace lulus. `npm run build` berhasil. Seluruh pengujian Gemini memakai respons simulasi; API berbayar tidak dipanggil.
- Bukti: `/tmp/kkprl-profile-validation/`, termasuk respons publik, `actual-profile.json`, `actual-profile.png`, screenshots, snapshots dan `verification.json`. Server, halaman browser, SQLite, sesi, storage serta skrip fixture sementara dibersihkan. Tidak ada dependency, program sistem atau migrasi baru, maupun perubahan `.env`/database pengguna. PHP memerlukan GD yang sudah tersedia pada mesin pengembangan; jika hosting menonaktifkannya, form menjelaskan kebutuhan aktivasi GD dan alternatif unggah gambar.
