# ADR-033: Skala cetak dan inset untuk detail tapak kecil

## Status

Accepted untuk skala/inset. Keputusan latar utama tanpa peta dasar digantikan [ADR-035](ADR-035-openstreetmap-main-map-with-satellite-inset.md), setelah pengguna memilih OpenStreetMap. Memperbarui penyajian peta baru pada ADR-031; ADR-031 tetap mencatat keputusan geometri, provider, private storage dan format landscape. ADR-032 tetap berlaku untuk cache Base64. Tidak mengubah kewenangan atau workflow revisi. Uraian di bawah mempertahankan keputusan historis tahap ini.

## Date

2026-10-12

## Context

Komposit WorldCover 2021 sekitar 10 m menjadi pecah ketika crop tapak sempit diregangkan ke frame utama. Batas tile zoom 14 membatasi permintaan provider, tetapi tidak membatasi pembesaran setelah crop. Pengguna meminta skala angka dan menyetujui inset; koordinat dan luas permohonan tidak boleh diperluas. Shared hosting tidak dapat menambah GIS/browser runtime.

## Decision

- Peta utama menampilkan grid dan geometri asli pada latar sederhana. `ProposalLocationMapViewport` mengubah hanya viewport dan posisi di kertas. Tidak ada buffer, perubahan koordinat, perubahan pengukuran, penggantian dimensi atau perubahan payload. Titik tetap diberi nomor.
- Denominator utama minimum 500 (pembesaran maksimum 1:500), dipilih otomatis dari deret 1, 2, 2,5, 5 per dekade mulai 500. Margin 1,35 dan rasio frame memuat seluruh titik, termasuk bentuk memanjang. Angka 500 adalah pilihan keterbacaan proyek; bukan aturan KKPRL atau ketelitian survei.
- Denominator menggunakan lebar cetak gambar 700 point, ukuran PNG 1.800×1.100, frame utama 1.190×860, circumference Web Mercator 2π×6.378.137 m dan faktor cos(latitude tengah). Skala berlaku pada tengah frame dan ukuran cetak asli 100%, dengan batas distorsi proyeksi; skala grafis tetap tersedia. Denominator utama dan inset ditampilkan terpisah.
- Inset 415×190 menampilkan cakupan minimal empat kali frame utama, dengan denominator minimum 50.000 untuk citra 10 m. Citra WorldCover hanya pada inset. Mode `none` tetap offline dan berlabel. Kotak biru berarti cakupan frame utama; tanda + berarti pusat cakupan. Simbol tersebut bukan perluasan batas permohonan. Polygon/line tetap diproyeksikan dari geometri asli.
- Lebar Word dan PDF sama 700 point (Word 14.000 twip / 8.890.000 EMU). Word memakai heading `MapTitle` yang ringkas dan batas tinggi 8.600 twip agar ukuran gambar tidak diperkecil oleh batas tinggi lama 8.400 twip. Tidak mengubah lampiran lainnya. Metadata cetak hanya keluaran renderer internal, bukan input pengguna.
- Snapshot baru `2026-10-12-map-inset` menambahkan `location_map.layout=detail-inset-v1`. Rekonstruksi `2026-10-12-map` mempertahankan tiga key metadata historis; `2026-10-11` dan null tidak mendapat metadata peta baru. Download file lama tetap berdasarkan versi dan hash historis. Dokumen baru tidak menimpa file lama.
- Provider tetap HTTPS fixed, budget maksimum 16 tile/20 detik, cache Base64 publik dan validasi PNG existing. Tidak ada request AI, provider baru, dependency baru, instalasi sistem, route preview proposal, atau migration database. Checkpoint sebelum persistence tetap berlaku.

## Alternatives Considered

- Hanya membatasi pembesaran seluruh peta satelit: mengurangi pecah tetapi tapak menjadi terlalu kecil untuk membaca titik/batas.
- Mencetak citra 10 m pada 1:500–1:1.000: memperbesar piksel tanpa menambah informasi spasial.
- OpenFreeMap/vector tiles: kandidat gratis untuk konteks jalan, tetapi memerlukan adaptasi ekspor vector yang belum tersedia pada GD. Tidak ditambahkan pada tahap ini.
- Memperluas polygon atau membuat detail citra dengan AI: mengubah/mengarang data spasial; ditolak.

## Consequences

Detail tapak kecil dapat dibaca dan konteks wilayah tetap tersedia pada inset. Latar utama tidak menunjukkan jalan/bangunan; inset bukan survei dan bukan citra terkini. Tahun, resolusi, atribusi dan sumber pengguna tetap tercantum. Mengubah ukuran gambar Word atau memakai fit-to-page pada printer mengubah skala angka; gunakan skala grafis bila ukuran keluaran berubah.

Hosting aktual dan pagination Microsoft Word native belum diverifikasi; pengujian lokal memakai GD, OOXML, PDF.js dan docx-preview. Cakupan yang terlalu luas untuk frame Mercator menghasilkan validasi yang jelas, bukan pemotongan/perubahan titik.

Lihat [spesifikasi](../kkprl-map-scale-inset-spec.md) dan [validasi](../kkprl-map-scale-inset-validation.md). Referensi: [USGS Map Scales](https://pubs.usgs.gov/fs/2002/0015/report.pdf), [WorldCover](https://esa-worldcover.org/en/data-access).
