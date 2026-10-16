# Unduhan massal dokumen klien

Pada **Admin > Klien**, centang klien yang ingin diunduh, buka menu aksi massal, lalu pilih:

- **Unduh Laporan**: laporan konsultasi terbaru dari setiap klien terpilih.
- **Unduh Berita Acara**: berita acara setiap klien terpilih.
- **Unduh Keduanya**: kedua jenis dokumen dalam satu ZIP.

ZIP memisahkan PDF dalam folder `Laporan/` dan `Berita-Acara/`. Jika hanya satu jenis dipilih, hanya folder jenis tersebut yang berisi PDF. Klien yang tidak memiliki jenis dokumen yang diminta dilewati; PDF klien lainnya tetap diunduh. Jika tidak ada satu pun dokumen yang sesuai, aplikasi menampilkan **Tidak ada dokumen yang tersedia** dan tidak mengunduh ZIP kosong. Pilihan centang dibersihkan sesudah aksi berhasil.

## Perilaku dan batas akses

Implementasi menambahkan tiga bulk action pada `ClientsTable`, tanpa mengubah unduhan satuan, ekspor Excel, atau aksi hapus/pulihkan yang sudah tersedia. Seleksi tetap berasal dari query tabel Filament, termasuk filter dan pencarian. Tidak ada route unduh baru atau migrasi database.

Bulk action memeriksa `viewAny` pada kebijakan `Client` dan `view` untuk setiap klien melalui `authorizeIndividualRecords('view')`. Service arsip memeriksa kebijakan tersebut kembali sebelum membaca dokumen. Dokumen mengikuti akses klien, sesuai alur dokumen pada resource klien. Laporan dan berita acara yang dihapus melalui soft delete tidak ikut karena relasi Eloquent tetap memakai scope normal.

`ClientDocumentArchiveService` memakai `latestConsultationReport` dan `beritaAcara`, lalu merender template yang sama dengan tombol satuan: `pdf.consultation-report` dan `pdf.berita-acara`. Keduanya memakai kertas A4 portrait; penandatangan laporan, peserta, tanda tangan berita acara, dan relasi klien dimuat untuk template. Dokumen draft juga dapat diunduh oleh petugas berizin, mengikuti perilaku unduhan satuan admin. Unduhan tidak mengubah status atau isi dokumen.

Nama PDF menggunakan nomor tiket atau nomor berita acara yang sudah dijadikan slug dan dibatasi panjangnya, ditambah ID klien. Nomor berita acara yang sama pada dua klien tidak menimpa file. Pemisah direktori dari nomor dokumen tidak diteruskan sebagai path ZIP.

## File sementara dan kapasitas

PDF dan ZIP dibuat pada disk `local`, dalam direktori privat `client-document-downloads/{UUID}`. Lokasi berasal dari server, bukan input pengguna. PDF ditulis satu per satu ke file sementara agar tidak ditampung semuanya sebagai string PDF dalam objek ZIP. File tetap tersedia sampai `ZipArchive::close()` selesai membaca sumbernya.

Direktori permintaan dibersihkan sesudah ZIP dikirim atau jika proses render/pembuatan ZIP gagal. File asli, gambar dokumentasi, dan tanda tangan tidak diubah. Respons ZIP menggunakan `Cache-Control: no-store, private`. Fitur memakai ekstensi `zip` yang sudah tersedia pada PHP proyek; tidak menambah paket.

File download melalui Livewire tetap dikumpulkan dan dikodekan Base64 sebelum dikirim ke browser. Ini bukan streaming jaringan tanpa buffer. Jumlah dan ukuran dokumen mengikuti batas memori/waktu proses server; untuk pilihan yang sangat besar, unduh dalam beberapa kelompok. Dasar implementasi: [Filament 5 bulk actions](https://filamentphp.com/docs/5.x/tables/actions#bulk-actions), [Livewire 4 file downloads](https://livewire.laravel.com/docs/4.x/downloads), dan [PHP ZipArchive::addFile](https://www.php.net/manual/en/ziparchive.addfile.php).

## Validasi

- RED: sembilan skenario gagal sebelum implementasi karena bulk action dan service arsip belum tersedia.
- GREEN awal: **9 tes, 104 assertion**, termasuk render PDF asli, pembacaan isi ZIP, pilihan jenis dokumen, laporan terbaru, seleksi klien, nomor berita acara sama, nama file aman, dokumen kosong/terhapus, izin klien, pembersihan file, dan kegagalan render.
- Kasus dokumen kosong diperkuat agar dokumen jenis lain yang tersedia juga tidak ikut terunduh ketika jenis yang diminta tidak ada.
- Pint memformat dua file PHP baru. Perubahan tabel dibatasi pada import, pendaftaran tiga bulk action, dan helper aksi.
- Browser terisolasi membuka URL admin, tetapi diarahkan ke halaman login dengan CAPTCHA. Pengujian aksi admin dilakukan lewat komponen Filament/Livewire dengan user dan database pengujian terisolasi; login browser tidak dilewati.
- Suite lengkap setelah penguatan kasus dokumen jenis lain: **551 tes lulus, 3.387 assertion**, 382,71 detik. Tidak ada tes yang dilewati atau dinonaktifkan.
- Review kebenaran, keterbacaan, arsitektur, keamanan, dan query relasi: lulus. `git diff --check` bersih. Tidak ada perubahan asset frontend, package, atau database pengguna untuk verifikasi.

## Graphify dan pencatatan

Graph root dan graph aplikasi diperbarui sesuai cakupan file tabel, service, tes, dan dokumentasi ini. Audit baru: `graphify-out/client-bulk-download-audit.json` dan `app/graphify-out/client-bulk-download-audit.json`. Relasi di luar cakupan, metadata, komunitas, hyperedge, dan audit sebelumnya dipertahankan.

Graph root sudah memiliki perubahan pengguna sebelum pekerjaan ini. Pembaruan root diterapkan di atas keadaan tersebut dan dibiarkan pada working tree agar perubahan pengguna tidak ikut masuk commit fitur. Kode, tes, dokumentasi, graph aplikasi, dan audit baru dicatat secara terpisah dari pekerjaan pengguna yang sudah ada. Snapshot analisis komunitas lama tetap ditandai sebagai historis; penggunaan token semantik host tidak tersedia dan tidak dilaporkan sebagai nol.
