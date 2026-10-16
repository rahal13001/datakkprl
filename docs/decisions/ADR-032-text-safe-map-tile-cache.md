# ADR-032: Cache tile peta berbasis teks untuk MySQL

## Status

Accepted. Memperbaiki detail penyimpanan cache ADR-031; tidak mengganti mekanisme akun, akses dokumen atau snapshot.

## Date

2026-10-12

## Context

Ekspor Bab 1 gagal di Laragon walaupun bab berstatus complete. Renderer bekerja saat cache array dipakai. Laragon menggunakan `CACHE_STORE=database` dengan MySQL: serializer Laravel `DatabaseStore` mengembalikan PHP serialize raw PNG, yang ditolak kolom teks UTF-8 dengan error 1366. Suite sebelumnya memakai cache array dan preview live juga memakai cache array, sehingga tidak menguji batas ini.

## Decision

- `KkprlWorldCoverTiles` mengubah hanya tile provider publik menjadi Base64 saat cache put. Pada get, maksimum 699.052 byte encoded diperiksa sebelum decode strict; signature PNG, maksimum 512 KiB decoded, 256×256 dan decode pixel GD tetap diperiksa. Renderer tetap menerima byte PNG asli, tidak menerima Base64.
- Namespace baru `kkprl-worldcover-2021:base64-v1:` membedakan format teks baru dari raw binary lama. Entry lama tidak dihapus atau di-flush, melainkan dibiarkan kedaluwarsa sesuai TTL existing tujuh hari. Tidak ada perubahan schema, migration atau konfigurasi cache global. Format snapshot/binary dokumen tidak berubah.
- Invalid Base64, encoded terlalu besar atau PNG rusak dianggap cache miss dan diambil ulang dari host/layer provider tetap dalam budget existing. Base64 merupakan encoding transport, bukan encryption; cache tetap hanya berisi tile publik tanpa identitas/geometri overlay proposal.
- Tes regresi memakai serializer MySQL `DatabaseStore` actual dengan SQLite memory dan constraint UTF-8, ditambah verifikasi provider pada database fixture MySQL baru beridentitas acak dan marker cleanup. Tes Livewire membuktikan Word/PDF berhasil ketika hanya Bab 1 complete, serta payload tidak berubah. Tidak menggunakan database pengguna untuk tes tulis, API Gemini atau jaringan provider otomatis.

## Alternatives Considered

- Mengganti `.env` menjadi file cache: menutupi bug portabilitas dan mengubah konfigurasi sistem lain.
- Mengubah kolom cache global menjadi BLOB: membutuhkan migration dan memengaruhi seluruh cache aplikasi.
- Menonaktifkan cache: mengulang network request yang tidak perlu dan meningkatkan ketergantungan provider.
- Mewajibkan seluruh bab atau lampiran tambahan: tidak berhubungan dengan penyebab teknis dan bertentangan dengan ekspor per bab.

## Consequences

Representasi cache bertambah sekitar sepertiga, tetapi tile/peta tetap dibatasi. Shared hosting MySQL tidak memerlukan instalasi, tabel atau layanan tambahan. Dokumen lama, private storage, approval, ownership, lock dan checkpoint operasi panjang tetap berlaku. Peta masih tertanam dalam Word/PDF; download PNG terpisah belum diimplementasikan.

Diagnosis dan hasil aktual tercatat pada [validasi cache peta](../kkprl-chapter-one-map-cache-validation.md). Perilaku serializer diverifikasi terhadap `vendor/laravel/framework/src/Illuminate/Cache/DatabaseStore.php` versi Laravel 13.26.1 dan MySQL fixture actual, tanpa mengubah vendor.
