# ADR-019: Perhitungan harmonik pasut di backend dan narasi AI opsional

Status: Accepted — 2026-10-07.

Timeout Gemini masih terjadi walaupun aplikasi hanya mengirim ringkasan tabel. Pengguna memerlukan hasil perhitungan dan narasi awal yang dapat ditinjau serta disimpan tanpa menunggu provider. Seluruh perhitungan dilakukan dalam PHP dengan dependency yang sudah tersedia; tidak memerlukan program sistem, Python atau paket tambahan di shared hosting.

## Metode dan ruang lingkup

Model kuadrat terkecil menggunakan konstanta, tren linear dan pasangan sinus/kosinus untuk M2, S2, K1, O1, N2, Q1, M4 dan MS4. Waktu dinormalisasi ke jam sejak sampel pertama dalam UTC; tinggi ke meter. Tren dipusatkan dan diskalakan, serta tinggi dikurangi rata-rata untuk stabilitas numerik. Penyelesaian memakai QR inkremental dengan rotasi Givens, tanpa menyimpan matriks desain seluruh sampel atau menyelesaikan persamaan normal. Seluruh sampel valid digunakan; tidak ada interpolasi, pengurangan frekuensi atau penghapusan sampel invalid.

Kecepatan sudut dalam derajat/jam: M2 28,9841042; S2 30; K1 15,0410686; O1 13,9430356; N2 28,4397295; Q1 13,3986609; M4 57,9682084; MS4 58,9841042. Amplitudo adalah akar jumlah kuadrat koefisien sinus dan kosinus. Formzahl dihitung sebagai `F = (A_K1 + A_O1) / (A_M2 + A_S2)`.

Hasil berupa **amplitudo efektif dan estimasi Formzahl untuk periode data**, bukan konstanta harmonik astronomis stasiun. Model tidak memisahkan P1 dari K1 maupun K2 dari S2, tidak menerapkan koreksi nodal, inferensi astronomis atau interval kepercayaan. Data sebulan tidak menyelesaikan semua komponen berdekatan; kecocokan model tinggi tidak membuktikan ketepatan klasifikasi. Hasil tidak dipakai untuk membuat prediksi baru atau menetapkan datum regulasi. Analisis lengkap konstanta astronomis memerlukan metode, periode dan validasi tambahan.

Klasifikasi memakai F sebelum pembulatan: `[0, 0.25)` harian ganda; `[0.25, 1.5)` campuran condong harian ganda; `[1.5, 3)` campuran condong harian tunggal; `[3, ∞)` harian tunggal. Nilai dalam 5% dari batas 0,25/1,5/3 diberi arahan peninjauan. Ini penanda kehati-hatian, bukan interval kepercayaan atau probabilitas klasifikasi.

## Pemeriksaan mutu

Kebijakan konservatif aplikasi untuk estimasi awal:

- Minimal 200 sampel dan rentang antara sampel pertama–terakhir minimal 28 hari. Selisih frekuensi M2–N2 dan O1–Q1 memerlukan sekitar 27,55 hari menurut kriteria Rayleigh; 28 hari bukan ketentuan resmi KKPRL atau jaminan konstanta astronomis.
- Cakupan minimal 90% terhadap jumlah yang diharapkan dari median interval sampling; tidak ada jeda lebih dari tiga jam. Batas jeda juga menahan sampling terlalu lambat untuk komponen M4/MS4. Tidak mengisi data yang hilang.
- Variasi tinggi harus memadai; diagonal QR tidak mendekati singular; jumlah amplitudo M2+S2 tidak mendekati nol. Pembagian dan nilai hasil harus hingga.
- R² pasut setelah pemisahan konstanta/tren minimal 0,90. R² seluruh model juga disimpan, tetapi tidak dipakai sendirian: drift besar dapat membuat R² total tinggi walaupun pasut tidak terjelaskan. RMSE dihitung sebagai akar rata-rata kuadrat residual dalam meter, bukan ketidakpastian amplitudo.

Batas sampel/cakupan/jeda/R² adalah kebijakan mutu aplikasi, bukan standar otoritas atau estimasi ketidakpastian. Jika syarat tidak terpenuhi, Formzahl dan tipe bernilai null dengan alasan; statistik dan narasi awal tetap tersedia. Parser tetap menolak waktu duplikat, nilai invalid serta rumus tanpa melewati baris diam-diam.

CSV SRGI BIG diberi label prediksi, bukan pengamatan. Rata-rata sampel bukan MSL; ekstrem periode bukan HHWL/LLWL/HAT/LAT. Datum dari file hanya acuan sumber. Nama/koordinat stasiun tidak membuktikan kesesuaian lokasi dengan Bab 1.

## Alur dan integritas

`Hitung data pasut` memverifikasi file privat/checksum, akses, Bab 3, lock, mapping dan koordinat sebelum perhitungan. Tabel terbaru diprioritaskan. Hasil diperiksa lagi di transaksi sebelum disimpan dalam `source_metadata.tide_summary.harmonic`, dengan versi `window_harmonic_ols_qr_v1`. Hasil tersimpan dapat dipakai kembali setelah byte file dan konteks diverifikasi. Perubahan algoritme harus menaikkan versi agar hasil lama dihitung ulang. Metadata mengikuti snapshot dan ekspor Word/PDF, termasuk keterbatasan metode.

Narasi awal deterministik tersedia di pratinjau dan checkpoint chat, tanpa request AI. Data proposal berubah hanya melalui `Konfirmasi & Simpan Draft`. Menghitung ulang saat narasi sudah terisi meminta konfirmasi UI karena mengganti narasi pasut di pratinjau. Bab dan field lain dipertahankan; checkpoint terbaru tidak ditimpa oleh hasil yang sudah usang.

`Rapikan narasi dengan AI` adalah opsional. Aplikasi lebih dahulu menyediakan narasi awal bila pratinjau kosong, mempertahankan koreksi manual yang sudah terisi, lalu meminta AI menarasikan ringkasan backend tanpa menghitung ulang angka atau tipe. Ringkasan deterministik dan sitasi menyertai hasil. Timeout atau respons tidak valid mempertahankan file, hasil perhitungan dan narasi. AI memiliki batas waktu/kuota yang sudah berlaku; tidak ada retry otomatis atau pemanggilan berbayar pada tombol hitung.

## Referensi

- [NOAA — Tidal Analysis and Predictions](https://tidesandcurrents.noaa.gov/publications/Tidal_Analysis_and_Predictions.pdf): frekuensi komponen, analisis harmonik dan keterbatasan panjang rekaman.
- [NOAA — About Harmonic Constituents](https://tidesandcurrents.noaa.gov/about_harmonic_constituents): amplitudo/fase dan kebutuhan periode untuk konstanta harmonik lengkap.
- [Uptide — README](https://github.com/stephankramer/uptide/blob/master/README.md): least squares dan kriteria Rayleigh. Library ini tidak ditambahkan sebagai dependency.
- [pyTMD — Tide Form Factor](https://pytmd.readthedocs.io/en/stable/notebooks/Plot-Tide-Form-Factor.html): formula Courtier, rentang klasifikasi dan keterbatasan tipe dominan.

Validasi numerik, alur browser, ekspor dan regresi dicatat pada [laporan pengujian Bab 3](../kkprl-chapter-three-evidence-validation.md).
