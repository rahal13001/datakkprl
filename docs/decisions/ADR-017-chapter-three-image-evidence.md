# ADR-017: Bukti Gambar Bab 3 pada Shared Hosting

## Status

Accepted for MVP — 2026-10-05.

## Context

Pelaku usaha belum tentu dapat menjelaskan mangrove, lamun, karang, arus, gelombang, pasang surut, batimetri dan profil dasar laut. Koordinat sudah tersedia di Bab 1. Hosting sementara tidak mengizinkan instalasi program atau paket tambahan; pengolahan raster lokal dengan GDAL/Python tidak dapat dijadikan prasyarat.

## Decision

1. Bab 3 menyediakan panduan sumber per topik dan koordinat Bab 1. Pengguna membuka situs sumber secara manual, memilih lokasi/lapisan/periode, lalu mengunggah screenshot atau foto miliknya. Bukti gambar opsional; percakapan biasa tetap tersedia.
2. Gambar JPG/PNG/WebP maksimal 5 MiB disimpan melalui service lampiran privat yang sudah ada. Batas bersama per bab tetap 10 file dan 15 MiB. Gambar dikaitkan dengan satu field Bab 3 sebagai lampiran inline berperan `ai_evidence`.
3. `source_metadata` menyimpan nama sumber, URL, periode, tanggal akses/dokumentasi, atribusi, keterangan dan koordinat saat unggah. Caption membawa sumber ke Word/PDF. Metadata ikut snapshot/manifest dan salinan revisi. Penggantian melalui uploader umum membuang provenance lama dan peran analisis agar gambar berbeda tidak dianggap berasal dari sumber sebelumnya.
4. Hanya gambar yang sengaja diunggah dalam alur ini yang dikirim sebagai inline bytes ke Gemini `gemini-3.8-flash`, memakai HTTP Laravel yang sudah tersedia. Tidak ada pengambilan URL pengguna, scraping portal, instalasi dependency, queue worker atau pipeline geospasial baru.
5. AI membaca legenda/label yang terlihat, menggunakan metadata sebagai klaim pengguna yang belum diverifikasi, dan dapat meminta klarifikasi melalui chat. Delapan pesan terakhir ikut analisis ulang. Patch wajib memuat tepat satu field yang terkait dengan gambar; field lain dan koreksi manual dipertahankan.
6. Narasi ditambah sitasi sumber secara deterministik dan disimpan sebagai checkpoint pratinjau. Payload proposal hanya berubah setelah **Konfirmasi & Simpan Draft**. Respons tidak valid atau kegagalan API mempertahankan gambar dan narasi sebelumnya.
7. Lokasi berubah, checksum berbeda, bukti dihapus, akses berakhir atau proposal terkunci mencegah analisis. Akses/penguncian/identitas bukti diperiksa kembali setelah API selesai. Perubahan checkpoint selama analisis menyebabkan hasil ditolak dan koreksi terbaru dimuat kembali.
8. Panduan sumber menjelaskan batas cakupan, resolusi dan izin penggunaan. Akses gratis tidak diartikan sebagai izin memakai seluruh screenshot untuk proposal usaha. Foto sendiri dan sumber lain tersedia sebagai alternatif.
9. Kegagalan Gemini diklasifikasikan agar pengguna mendapat arahan yang sesuai. HTTP 429 menahan permintaan chat/gambar melalui cache bersama mengikuti waktu tunggu provider; gambar, jawaban dan pratinjau dipertahankan. Tidak ada perpindahan otomatis ke layanan berbayar. Setelah kuota proyek ditingkatkan, pengelola dapat menghapus penundaan lokal melalui `php artisan kkprl:ai-reset-cooldown`; batas Google tetap berlaku. Key/model dibaca melalui konfigurasi layanan dan tidak dicatat dalam log.

## Consequences

Pengguna mendapat bantuan yang dapat berjalan pada shared hosting dengan stack aplikasi saat ini. Sumber dan gambar dapat ditelusuri dalam proposal, tanpa mengisi fakta teknis yang belum diketahui secara otomatis.

Pemilihan lokasi, data dan izin gambar tetap membutuhkan pengguna. Screenshot tidak cukup untuk pengukuran luas/kedalaman presisi, penampang terukur, kesehatan karang, jenis sedimen atau pembuktian ketiadaan habitat. Kecocokan spasial dan kualitas narasi tetap perlu ditinjau; aplikasi tidak menjamin penerimaan dokumen oleh instansi.

Permintaan analisis bersifat sinkron dengan timeout HTTP 30 detik dan satu gambar per permintaan. Hosting memerlukan koneksi HTTPS keluar ke Gemini dan limit upload/request yang sesuai. Jika timeout tercapai, pengguna dapat mengulang tanpa kehilangan bukti atau koreksi.

## Verification

Tes fitur mencakup provenance, akses privat, isolasi proposal/bab, kuota, jenis file/URL, perubahan koordinat, penguncian selama analisis, koreksi bersamaan, kegagalan/clarification AI, konfirmasi, checksum, penghapusan, replacement dan manifest. Tes revisi memeriksa pelestarian metadata. Pengujian browser menggunakan database dan peta sintetis serta respons Gemini simulasi; lihat [laporan validasi](../kkprl-chapter-three-evidence-validation.md).

## References

- [Gemini image understanding](https://ai.google.dev/gemini-api/docs/image-understanding): inline image input, tanpa SDK baru.
- [Gemini rate limits](https://ai.google.dev/gemini-api/docs/rate-limits): kuota berlaku pada proyek dan reset harian mengikuti tengah malam Pacific.
- [Livewire 4 uploads](https://livewire.laravel.com/docs/4.x/uploads): `WithFileUploads`.
- [Allen Coral Atlas FAQ](https://allencoralatlas.org/resources/): habitat CC BY 4.0, citra Planet dengan batasan nonkomersial, lamun dalam wilayah terumbu yang dipetakan dan kelas gabungan coral/algae.
- [Allen Coral Atlas Terms of Use](https://allencoralatlas.org/tou/): pembatasan komersial dan reproduksi konten situs tanpa izin tertulis. Panduan tidak menganggap screenshot seluruh situs otomatis berlisensi CC BY.
- [Global Mangrove Watch](https://www.globalmangrovewatch.org/): sumber mangrove dengan versi dan kredit dataset yang harus dipilih pengguna.
- [Copernicus Marine license](https://marine.copernicus.eu/user-corner/service-commitments-and-licence): penggunaan data dengan atribusi dan ketentuan layanan.
- [BIG pasang surut](https://srgi.big.go.id/tides): login, persetujuan ketentuan, serta kontak BIG untuk lebih dari tiga hari.
- [GEBCO download app](https://download.gebco.net/) dan [WMS](https://www.gebco.net/data-products/gebco-web-services/web-map-service): gambaran batimetri global.
