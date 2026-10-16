# Tata letak ekspor proposal KKPRL

Tanggal: 11 Oktober 2026. Lingkup: Word/PDF Bab 1–5, tanpa mengubah isi proposal atau persyaratan form.

## Temuan

Tiga DOCX pada docs/export_sample dibaca lokal sebagai ZIP/XML. Sampul dan body belum memakai paragraph styles; styles tidak memiliki relationship dari document; label dan narasi memakai paragraf default; gambar belum mengikuti margin aktual; HTML editor rencana tapak tercetak sebagai tag; sampul Bab 2/3 memuat placeholder instansi. Sampel mengandung data pribadi dan tidak dimasukkan ke Graphify, dikirim ke layanan eksternal, atau ditimpa. Template asli Bab 1–5 tetap menjadi sumber judul, urutan dan penomoran resmi, bukan sumber identitas contoh.

## Desain

- A4, margin sekitar 2,5 cm, font sans serif 11 pt, warna netral. Sampul terpusat dengan judul berjenjang, nama pemohon/instansi, tahun dan nomor tiket.
- Heading bagian mengikuti katalog/penomoran resmi. Heading dan label tidak terpisah dari isi berikutnya; paragraf body memiliki jarak, line spacing dan widow/orphan control.
- Isian singkat dikelompokkan dalam tabel label/nilai dua kolom. Narasi panjang menjadi paragraf yang dapat berlanjut antarhalaman. HTML editor dikonversi ke teks/format dasar aman, bukan tag mentah; script/resource eksternal tidak dirender.
- Tabel data memiliki lebar eksplisit, header berulang, padding dan border halus; data multiline tetap ada. Tabel lebar mempertahankan dukungan landscape existing.
- Gambar diskalakan proporsional mengikuti ruang cetak; judul lampiran dan caption sumber terbaca tanpa memotong isi. Nama file menjadi fallback jika caption kosong; nama asli tetap tersimpan dalam manifest. Lampiran PDF dan ringkasan pasut tetap mengikuti renderer private existing.
- Word memakai named styles, relationship styles/footer dan field PAGE. PDF memakai footer page number serta aturan pagination yang tidak mengurung seluruh narasi dan lampiran dalam satu blok yang tidak bisa dipotong.

## Versi dan kompatibilitas

Dokumen generated lama immutable dan tidak ditimpa. Snapshot baru memasukkan metadata versi layout dan nama sampul dari data Bab 1 yang disnapshot, sehingga generate ulang pada draft unchanged menghasilkan artefak baru tanpa mengubah payload pengguna atau membutuhkan migration database baru. Retry layout/input yang sama tetap idempotent. Verifikasi dokumen lama memakai format snapshot legacy dari metadata encrypted dokumennya; hash historis dan akses download lama tetap berlaku. Metadata akun/approval tidak masuk hash.

Nama sampul menjadi bagian snapshot baru agar perubahan institusi tidak menghasilkan sampul stale. Rendering memakai snapshot terkunci, bukan membaca live payload di tengah render. Seluruh call site pemeriksaan integritas (download, wizard dan portal) menggunakan resolver versi dokumen. Versi tak dikenal ditolak. Submitted/locked tidak dapat regenerate; revisi mengikuti workflow existing.

## Matriks akses

| Pelaku | Perilaku |
|---|---|
| Pending | Hanya download dokumen generated miliknya yang valid, termasuk legacy; tanpa generate |
| Approved | Generate format baru pada draft miliknya yang lengkap dan belum locked |
| Petugas | Hak review/download/edit tetap berdasarkan permission existing |

Private storage, snapshot/manifest, audit, pemisahan kewenangan dan checkpoint approval/owner setelah render tetap berlaku. Tidak ada AI, layanan baru, instalasi sistem atau converter Word di hosting. Viewer pengujian lokal bersifat tooling pengembangan, bukan dependency aplikasi.

## Acceptance criteria

1. Word/PDF memakai struktur resmi seluruh bab dan tidak kehilangan teks, baris tabel, gambar atau caption.
2. HTML editor tidak tercetak mentah dan resource/script tidak dapat masuk output.
3. Cover, styles, tabel, header/footer, paragraf dan ukuran gambar memiliki bukti struktur serta pemeriksaan visual hasil render.
4. Narasi multipage, gambar tinggi, caption/URL panjang, tabel landscape dan lampiran campuran diperiksa.
5. Retry idempotent; legacy tetap dapat diunduh; regenerate draft membuat artefak layout baru tanpa menimpa file/row lama.
6. Perubahan cover memberi snapshot baru; perubahan approval akun tidak merusak dokumen; revoked approval/lock dan cross-owner tetap ditolak.
7. Fixture/database pengujian terisolasi dan layanan eksternal fake. Jalankan tes relevan, suite penuh, format dan audit Graphify sumber kode+dokumentasi. Build UI hanya jika asset aplikasi berubah.

Hasil aktual tersedia pada [laporan validasi](kkprl-document-layout-validation.md), keputusan pada [ADR-030](decisions/ADR-030-versioned-professional-document-layout.md), dan contoh Word/PDF baru pada `docs/export_sample/rapi`. Contoh asli dipertahankan. Tidak ada commit, push atau deploy.
