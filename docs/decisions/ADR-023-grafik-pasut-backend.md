# ADR-023: Grafik deret waktu pasut dari file sumber di backend

Status: Accepted — 2026-10-07.

Pengguna membutuhkan gambar grafik pasang surut dari CSV yang sudah diunggah, tanpa screenshot atau pembuatan gambar melalui AI. Tombol **Buat grafik pasut** tersedia bersama tombol hitung dan narasi. CSV, XLS dan XLSX memakai reader, mapping dan validasi yang sama. PHP GD dan font DejaVu dari dependency Dompdf yang sudah tersedia menghasilkan PNG; tidak menambahkan program sistem atau paket.

## Penyajian ilmiah

- Seluruh sampel valid digambar, termasuk 43.201/44.641 sampel bulanan. Tidak ada pengurangan sampel, pemulusan, pemasangan kurva harmonik atau prediksi baru. `KkprlTideDataAnalyzer::series` menyediakan seri kanonis dan ringkasan; `analyze` tetap kompatibel dengan pemanggil lama.
- Sumbu X memakai selisih waktu sebenarnya dari epoch UTC, bukan nomor baris. Label menampilkan tanggal, jam sampai detik dan UTC/WIB/WITA/WIT sesuai mapping file. Sumbu Y menampilkan tinggi dalam meter terhadap **datum pilihan pengguna**, dengan rentang dari seluruh sampel dan margin; nol tidak dipaksakan menjadi MSL. Series negatif/konstan tetap mempunyai skala hingga.
- Garis lurus antar sampel merupakan penghubung visual, bukan pengisian data atau model. Jeda lebih dari 1,5 kali median interval antar sampel memutus garis; sampel terisolasi tetap digambar. Ambang dan jumlah jeda dicantumkan. Ambang ini **kebijakan penyajian aplikasi**, bukan standar resmi KKPRL atau bukti jumlah sampel hilang pada data tidak beraturan.
- Minimum/maksimum periode diberi tanda tanpa disebut HHWL/LLWL/HAT/LAT. Prediksi SRGI BIG diberi label prediksi, bukan pengukuran. Jenis data lain belum dianggap sebagai observasi tervalidasi. Datum yang tidak diketahui tetap disebut belum diketahui. Grafik tidak melakukan quality control lapangan atau membuktikan kecocokan lokasi stasiun dan proposal.
- Gambar memuat periode, zona waktu, satuan, datum, jumlah sampel, kebijakan jeda, sumber/kredit, tanggal akses, nama file, checksum SHA-256 asli dan stasiun/koordinat bila ada di file. Caption mencantumkan sitasi lengkap, termasuk URL sumber. Kredit/lisensi tidak diasumsikan mengikuti GMRT.

## Alur dan integritas

Grafik merupakan lampiran PNG privat `ai_evidence`, field/anchor `tide`, sehingga mengikuti snapshot dan ekspor Word/PDF Bab 3. Narasi yang sedang dikoreksi tidak diganti; payload baru disimpan melalui konfirmasi draft. Grafik memakai satu slot pada batas 10 lampiran / 15 MB per bab. Grafik identik tidak diduplikasi.

Tabel terbaru diprioritaskan, sebagaimana perhitungan pasut. Akses, bab, kunci, checksum file, mapping, koordinat, identitas tabel terbaru dan checkpoint diperiksa sebelum dan setelah pembuatan gambar, di transaksi dengan kunci proposal. File sementara dibersihkan; kegagalan/kuota tidak menghapus sumber, gambar lama atau narasi manual. Metadata menyimpan versi metode, identitas/hash sumber, mapping, ringkasan dan kebijakan garis/jeda; tidak menyimpan atau mengirim puluhan ribu sampel ke AI. Perhitungan Formzahl yang sudah ada tidak diubah.

Grafik dari file lama tetap memiliki nama file dan periode sendiri. Pengguna dapat menghapus grafik yang tidak dipakai. Penghapusan tabel tidak otomatis menghapus grafik yang telah dibuat, sehingga caption dan identitas sumber tetap diperlukan untuk meninjau bukti yang dipakai dalam draft.

## Referensi

- [NOAA CO-OPS Data Retrieval API](https://api.tidesandcurrents.noaa.gov/api/dev): pemisahan produk prediksi/pengamatan serta datum, satuan dan zona waktu.
- [NOAA Tide Predictions User Guide](https://www.tidesandcurrents.noaa.gov/education/tech-assist/training/user-guides/assets/pdfs/Tide_Predictions_User_Guide_v4.pdf): pilihan datum, zona waktu, satuan dan interval dalam grafik prediksi. Ini referensi penyajian; tidak berarti grafik memenuhi ketentuan hukum KKPRL atau mutu survei yang belum diverifikasi.
- [ADR-019](ADR-019-backend-tidal-harmonics.md): batas statistik/harmonik yang sudah berlaku.
