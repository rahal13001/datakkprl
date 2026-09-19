# Panduan Pengguna dan Petugas — Proposal KKPRL

Dokumen ini menjelaskan penggunaan sistem pendukung penyusunan dan review proposal KKPRL. Sistem ini bukan OSS, e-Sea, sistem perizinan utama, penerbit izin, atau sumber keputusan hukum/administratif.

## Pemohon

### Membuat dan melanjutkan draft

1. Buka route **`/buat-proposal-kkprl`** (**Buat Proposal Baru**), masukkan nomor HP, lalu simpan nomor tiket yang ditampilkan.
2. Isi Bab 1–3 secara bertahap. Gunakan **Simpan Draft**; autosave berjalan ketika data berubah atau langkah berpindah.
3. Untuk kembali, buka route **`/lanjutkan-proposal-kkprl`** (**Lanjutkan Proposal**) dan masukkan pasangan nomor tiket + nomor HP. Nomor tiket saja tidak cukup.
4. Setelah session aktif, nomor HP tidak perlu dimasukkan pada setiap langkah. Session berakhir setelah idle timeout.

Pesan credential yang salah bersifat generik. Jangan membagikan nomor tiket dan nomor HP melalui kanal publik.

### Bab dan validasi

- Bab 1–3 selalu tersedia dan wajib lengkap saat dikirim.
- Bab 4 hanya muncul bila reklamasi dipilih.
- Bab 5 hanya menampilkan subbagian lahan darat dan/atau perizinan yang dipilih.
- Gunakan keterangan eksplisit **Tidak berlaku** bila field yang tampil memang tidak berlaku.
- Koordinat diisi manual sesuai format dan rentang yang diminta.

Bab yang belum lengkap tidak dapat diekspor dan proposal tidak dapat dikirim sebagai `submitted`.

### Lampiran dan dokumen

Format lampiran: PDF, JPG/JPEG, PNG, dan WebP. Setiap bab memiliki batas maksimal 10 lampiran aktif dan total 15 MiB. Batas mencakup gambar inline, appendix, peta, site plan, foto, dan PDF.

Lampiran dapat ditempatkan sebagai **Lampiran bab** atau **Di bawah teks acuan**. Untuk posisi inline, pilih field/teks acuan dan isi caption. WebP asli tetap disimpan; normalisasi hanya digunakan pada salinan render.

Setelah bab lengkap, gunakan **Buat Word + PDF**. Hasil hanya memuat bab dan lampiran bab tersebut. Export tidak mengubah status proposal. Download hanya tersedia melalui session proposal yang benar.

### Mengirim proposal

Klik **Kirim untuk Ditinjau** setelah semua bab relevan lengkap. Sistem membuat snapshot, Word, dan PDF per bab aktif sebelum mengunci proposal.

Status `submitted` berarti dokumen pendukung telah dikirim untuk review. Status ini bukan penerbitan izin. Setelah terkunci, payload dan lampiran tidak dapat diubah.

Jika petugas meminta revisi, lanjutkan dengan nomor tiket utama + nomor HP. Sistem membuka salinan baru berlabel `rev-1`, `rev-2`, dan seterusnya; proposal submitted asli tetap tidak berubah.

## Petugas Filament

Panel: `Layanankkprl` (`/layananruanglaut`). Setelah login, buka menu **Proposal KKPRL > Review Proposal**. Menu approval berada pada **Proposal KKPRL > Approval Edit Darurat**. Hak akses berasal dari Filament Shield/Laravel Authorization, bukan nama role.

Pada host lokal, halaman login biasanya tersedia di **`/layananruanglaut/login`**. Pada production, gunakan host resmi yang dikonfigurasi untuk panel tersebut. Jika menu tidak terlihat, akun belum memiliki permission yang diperlukan.

Permission minimum:

- `Review:KkprlProposal` — melihat dan meninjau proposal.
- `RequestEdit:KkprlProposal` — meminta edit darurat scoped.
- `ApproveEdit:KkprlProposal` — menyetujui/menolak approval edit dan menentukan kedaluwarsa.
- `RunEditSession:KkprlProposal` — menjalankan sesi edit yang telah disetujui.
- `Download:KkprlProposalDocument` — mengunduh Word/PDF.
- `Download:KkprlProposalAttachment` — mengunduh lampiran.

Role apa pun dapat menjadi Petugas B bila memiliki permission approval. Petugas A tidak boleh menyetujui atau menolak permintaannya sendiri, termasuk saat memiliki akses super-admin.

### Review normal

1. Buka **Review Proposal**, cari nomor tiket atau field review yang tersedia, lalu periksa Bab aktif, lampiran, dokumen, dan riwayat review.
2. Gunakan **Minta revisi** untuk memberi catatan. Sistem membuat salinan revisi; proposal submitted asli immutable.
3. Download hanya memakai action privat dan permission download yang sesuai.

### Edit darurat scoped

1. Petugas A mengajukan edit pada revisi draft dengan alasan dan field/section yang diizinkan.
2. Petugas B dengan `ApproveEdit:KkprlProposal` menyetujui atau menolak dan menentukan `expires_at`.
3. Setelah disetujui, hanya Petugas A yang dapat memulai satu sesi. Approval menjadi one-use.
4. Setiap perubahan wajib memiliki alasan dan dicatat append-only dengan before/after, actor, role, permission, waktu, dan request ID.
5. Sesi berakhir saat dibatalkan, kedaluwarsa, atau revisi dikirim. Approval baru diperlukan untuk sesi berikutnya.

Satu approval atau sesi edit aktif hanya boleh ada untuk satu revisi. Permintaan paralel ditolak agar scope dan riwayat perubahan tetap deterministik.

## Operasional

Artefak dan lampiran baru berada pada disk privat `kkprl_private` tanpa serving route. Retensi tidak menghapus data secara otomatis pada MVP; penghapusan/arsip harus mengikuti kebijakan organisasi. Jalankan preflight sebelum release:

```text
rtk php artisan kkprl:preflight --strict
```

Untuk UAT manual, gunakan [checklist UAT](kkprl-uat-checklist.md). Untuk keputusan arsitektur, lihat [ADR renderer dan artefak privat](decisions/ADR-009-kkprl-renderer-and-private-artifacts.md), [keputusan generasi sinkron berbatas](decisions/ADR-013-bounded-synchronous-document-generation.md), dan [kebijakan retensi](kkprl-data-retention.md).
