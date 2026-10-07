# ADR-021 — Batimetri GMRT sebagai default Bab 3

Status: diterima, 7 Oktober 2026.

Pengguna membuat peta batimetri langsung dari koordinat Bab 1. GMRT menjadi sumber default `bathymetry`; Garmin, GEBCO, foto dan sumber lain tetap tersedia untuk unggah manual. Profil A–B tetap memakai ProfileServer yang sudah tersedia.

## Kontrak

- Koordinat Bab 1 yang valid merupakan referensi lokasi. Batas peta mencakup semua titik dan tambahan area 0,5/1/2/5 km (default 1 km). Luas ini adalah konteks peta, bukan perubahan batas permohonan. Area lintang ±85°, bujur ±180°, maksimal 50 km per sumbu; lintasan yang melintasi antimeridian tidak didukung.
- Backend memakai HTTPS `https://www.gmrt.org/services/GridServer`, `layer=topo`, `format=esriascii`, dan jarak grid permintaan minimal 100 m yang disesuaikan dengan ukuran area. Tidak memakai URL pengguna, API key, program GIS, retry otomatis atau Gemini. Timeout koneksi 5 detik, request 20 detik; respons diterima maksimal 4 MiB / 65.536 sel.
- Probe publik menghasilkan header `ncols`, `nrows`, `xllcorner`, `yllcorner`, `cellsize` dalam derajat, serta `nodata_value -2147483648`. Header, dimensi, lokasi/cakupan, jumlah nilai, angka finite, rentang elevasi dan nilai NoData harus divalidasi. Baris pertama adalah utara; kolom pertama barat. Cakupan header aktual dapat sedikit berbeda karena penyelarasan grid (toleransi maksimal dua sel pada setiap sisi); penanda dihitung dari header aktual, bukan bbox permintaan.
- PNG dibuat oleh PHP GD: raster warna, kontur terhitung dari sel valid, legend elevasi meter, penanda/bentuk lokasi Bab 1, arah utara, koordinat, skala jarak perkiraan, waktu akses, sumber dan kredit CC BY 4.0. Rasio peta mengikuti jarak horizontal/perbedaan lintang. NoData ditampilkan kosong dan tidak diinterpolasi. Kontur merupakan interpolasi di antara pusat sel, bukan tambahan pengukuran.
- Ringkasan deterministik menyebut ekstrem seluruh area peta, jumlah sel valid/kosong, ukuran sel keluaran dan keterbatasan. Nilai positif tetap elevasi positif; tidak dinyatakan sebagai kedalaman laut. Jarak grid keluaran bukan ketelitian/resolusi sumber asli dan bukan datum pasut.
- Satu PNG privat `ai_evidence`, inline pada `bathymetry`, mengikuti kuota Bab 3, checkpoint, manual confirmation, revisions, snapshot dan export. Metadata memuat permintaan, header aktual, ringkasan, identitas koordinat/bentuk, checksum respons serta arsip grid asli terkompresi untuk audit. Grid mentah/arsip tidak dikirim ke Gemini; AI mendapat hanya gambar dan ringkasan backend.
- Setelah request, periksa lagi akses/kunci, koordinat/bentuk Bab 1 dan checkpoint. Kegagalan mempertahankan peta dan narasi sebelumnya. Membuat ulang saat narasi terisi meminta konfirmasi UI.

## Keilmuan, hosting dan izin

GMRT merupakan kompilasi data sekunder, termasuk GEBCO sebagai pengisi area yang tidak mempunyai data rinci. Tidak menjamin ketepatan pada keramba kecil/dekat pantai, jenis sedimen, datum pasut atau navigasi. Tidak mengunduh seluruh grid global atau memakai banyak PointServer untuk membentuk peta. Pembatasan area/grid menjaga request PHP tetap ringan; PHP GD sudah dipakai untuk penampang, tanpa dependensi atau migrasi baru.

Sumber primer: [GridServer](https://www.gmrt.org/services/gridserverinfo.php), [overview](https://www.gmrt.org/about/index.php), [CC BY 4.0](https://www.gmrt.org/about/terms_of_use.php), [data DOI](https://doi.org/10.1594/IEDA.100001). Metadata resmi tersedia melalui `GridServer?layer=metadata&mformat=json` (bukan subpath `/GridServer/metadata`). Pembuatan peta memakai satu request ASCII dan header aktualnya; versi, tanggal survei dan resolusi sumber asli tidak ditebak. Metadata grid memiliki perbedaan dimensi pada probe, sehingga header ASCII merupakan acuan raster.

## Acceptance checks

Test gagal sebelum implementasi, angka dan orientasi grid dibandingkan fixture yang diketahui, NoData/daratan/kontur datar, bbox/header/respons terlalu besar atau salah, default sumber dan preservasi manual/AI gagal, checkpoint/akses/kunci/kuota/koordinat, metadata tidak membengkakkan prompt, snapshot/Word/PDF, probe PHP langsung, browser desktop/mobile dan fallback upload.

## Verifikasi data publik

Probe backend PHP memakai titik sintetis (-0,48; 130,88), tambahan 1 km dan mresolution=100. GMRT mengembalikan 34 × 33 sel, jarak keluaran sekitar 61,15 m dan elevasi -377,20 sampai -156,04 m dalam 3,27 detik. Parser Python independen cocok untuk jumlah sel, ekstrem dan SHA-256. Angka tersebut hanya contoh pengujian, bukan data lokasi proposal pengguna.
