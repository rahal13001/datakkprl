# ADR-022 — Kelengkapan Bab 5 sesuai ketersediaan izin

Status: diterima, 7 Oktober 2026.

Semua field Bab 5 sebelumnya dihitung wajib, walaupun spesifikasi dan template menyatakan dokumen dapat dilampirkan dan sejumlah rincian diisi jika tersedia. Pengguna mengonfirmasi dua kondisi harus didukung: belum memiliki izin sama sekali, serta sudah memiliki izin tetapi dokumennya belum tersedia.

## Keputusan

- Tetap memakai aturan kondisi Bab 1: land_relation=adjacent mengaktifkan bukti lahan; has_existing_permits=true mengaktifkan informasi izin. Bab 5 dilewati jika kedua bagian tidak relevan.
- Bab 5 menampilkan pilihan **Sudah memiliki izin pendukung / Belum memiliki izin pendukung**. Pilihan tersimpan dalam checkpoint chat sebagai pratinjau, tidak mengubah proposal sebelum Konfirmasi & Simpan Draft. Konfirmasi memperbarui has_existing_permits pada akar payload agar semua aturan wizard, progress, snapshot, ekspor dan review tetap memakai sumber kondisi yang sama.
- Pilihan belum memiliki izin menyembunyikan kolom izin dan melewati perhitungan kelengkapannya. Informasi bukti lahan tetap wajib jika lokasi berhimpitan. Pilihan tidak menghapus data/koreksi izin lama; data tidak aktif tidak masuk snapshot maupun dokumen. Lampiran yang dikenali melalui field/anchor bagian nonaktif tetap disimpan privat dan tidak menghalangi ekspor bagian yang aktif. Anchor yang tidak dikenali tetap dilaporkan sebagai kesalahan.
- Rincian wajib izin yang ada: jenis, tahun perolehan, status. Nomor, instansi penerbit, masa berlaku, dokumen dan catatan opsional. Rincian wajib lahan: status, jenis bukti, tahun perolehan; dokumen dan catatan opsional. Informasi pokok yang belum diketahui tetap menjadi draft parsial, tanpa angka/izin karangan.
- ProposalFieldCatalog membedakan daftar seluruh field dengan daftar yang wajib. Progress memakai daftar wajib; form, sanitizer dan snapshot tetap mempertahankan semua field aktif, termasuk rincian opsional yang diisi dan payload legacy datar.
- AI memakai pilihan ketersediaan terbaru, tidak meminta detail izin yang belum ada, tidak mengisi bagian nonaktif, dan tidak menyatakan kelengkapan draft sebagai pemenuhan kewajiban atau penerbitan izin. Pernyataan lewat chat diarahkan untuk memilih kondisi yang benar sebelum konfirmasi; tidak mengubah kondisi berdasarkan tebakan AI.
- Konfirmasi mengunci proposal dan membaca payload terbaru, lalu menyimpan kondisi, data dan checkpoint dalam satu transaksi. Checkpoint pilihan diabaikan jika keputusan Bab 1 berubah sejak dibuat. Akses, bab, nilai pilihan dan kunci proposal tetap diperiksa.

Tidak memerlukan migrasi, perubahan .env, panggilan AI berbayar, atau perubahan data pengguna secara massal. Ini aturan kelengkapan dokumen pendampingan, bukan keputusan hukum tentang izin yang wajib dimiliki.

## Verifikasi

Regresi RED sebelum perubahan: jenis/tahun/status izin terisi, field opsional kosong, tetapi Bab 5 berstatus incomplete. Tes mencakup kedua kondisi pengguna, lahan tetap aktif, preservasi data/koreksi, resume, validasi/akses/kunci, prompt dan patch AI, keputusan Bab 1 dari tab lain, snapshot legacy/nested, Word/PDF serta browser desktop/mobile.
