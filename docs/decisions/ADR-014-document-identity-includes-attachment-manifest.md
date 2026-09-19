# ADR-014: Identitas Dokumen Mencakup Manifest Lampiran

## Status

Accepted for MVP.

## Context

Snapshot payload dan manifest lampiran adalah dua input berbeda untuk generator Word/PDF. Payload dapat tetap sama ketika pemohon menambah, mengganti, atau menghapus lampiran pada bab yang sama. Jika identitas dokumen hanya memakai hash snapshot payload, proses generate berikutnya dapat memakai record lama dengan manifest metadata yang sudah tidak sesuai dengan binary baru.

Akibatnya, dokumen lama dapat tertimpa secara logis, metadata audit tidak akurat, dan pemeriksaan stale snapshot dapat menolak unduhan hasil terbaru.

## Decision

Identitas unik dokumen per bab dan format memakai:

- proposal;
- chapter;
- format;
- snapshot hash; dan
- attachment manifest hash.

Perubahan manifest membuat document record baru. Retry dengan pasangan hash yang sama memakai record yang sama dan tetap idempotent. Record lama tidak diubah; artefak lama boleh tetap tersimpan sebagai riwayat, tetapi pemeriksaan snapshot menentukan apakah masih dapat diunduh sebagai hasil terkini.

Nama path storage juga memuat kedua hash tersebut agar artefak binary versi lama tidak tertimpa ketika hanya manifest lampiran berubah.
Setelah status `generated`, pointer storage path tidak dapat diubah; retry hanya boleh menjalankan lifecycle yang sama
dan memakai path deterministik tersebut.

## Alternatives Considered

### Mengubah record lama

Ditolak karena field snapshot dan manifest bersifat immutable, serta merusak keterlacakan hasil yang pernah dibuat.

### Menghapus dokumen lama sebelum generate

Ditolak karena menghilangkan riwayat artefak dan memperbesar risiko kehilangan hasil ketika render baru gagal.

## Consequences

- Database dapat menyimpan beberapa versi hasil export untuk payload yang sama tetapi manifest berbeda.
- Artefak binary setiap versi memiliki path privat yang berbeda.
- Retry tetap tidak menggandakan record tanpa perubahan input.
- Retry tidak menulis ulang binary yang sudah generated selama artifact masih tersedia pada path privat yang sama.
- Retensi artefak lama perlu mengikuti kebijakan retensi operasional yang sudah terdokumentasi.
- Download tetap fail-closed bila manifest dokumen tidak sama dengan keadaan proposal saat ini.

## Verification

Regression test memastikan perubahan manifest membuat record baru dengan snapshot hash sama, manifest hash berbeda, dan record pertama tetap tidak berubah.
