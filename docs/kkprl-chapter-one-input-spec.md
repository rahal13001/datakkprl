# Perbaikan input dan kelengkapan Bab 1

Tanggal: 2026-10-08. Lingkup: wizard Bab 1, evaluasi kelengkapan, dan editor rencana tapak. Autentikasi, approval, kepemilikan, private storage, lock/revisi dan batas ilmiah tetap berlaku. Tidak ada migration, dependency atau layanan baru. Metadata daftar proposal dibaca dari payload sanitized baik flat maupun nested. Scaffold NULL hanya state UI; format storage flat legacy tetap dipertahankan agar snapshot/dokumen lama tidak berubah ketika tidak ada edit isi.

## Masalah dan bukti awal

Browser fixture SQLite terisolasi di localhost:8766 memakai delay respons Livewire 1,5 detik. Memilih hubungan daratan lalu segera mengisi jabatan melalui fill_form mereproduksi hilangnya input: teks baru `kamu kemana saat server menyimpan` kembali ke nilai sebelumnya ketika autosave selesai. Sanitizer menempatkan condition sebelum chapter; echo payload pada wizard mengubah urutan key. Livewire 4.4.1 diffAndPatchRecursive mengganti object target ketika urutan key berubah atau struktur array kosong menjadi object, sehingga perubahan lokal selama request dapat tertimpa. Uji 6 detik pada penyimpanan awal mengungkap kedua kondisi; menjaga key order saja belum cukup.

Progress Bab 1 memakai 36 field katalog, termasuk depth_unit yang tidak mempunyai input. Progress bar atas mencakup seluruh bab aktif, bukan persentase Bab 1 saja. Editor CKEditor memakai teks elemen sumber sebagai konten awal dan mengirim request pada setiap perubahan data.

## Perubahan yang disepakati oleh lingkup permintaan

1. Tetap autosave saat keluar field; state UI memakai struktur object yang stabil sejak mount dan respons wizard mempertahankan urutan key payload masukan setelah sanitasi allowlist. Isian lokal yang masih diketik tidak boleh tertimpa respons sebelumnya.
2. Tambahkan input satuan kedalaman/datum tanpa menebak datum. Tampilkan jumlah isian wajib Bab 1 serta label field kosong dan masalah koordinat. Semua field wajib yang sudah ditetapkan katalog tetap berlaku; tidak mengurangi persyaratan diam-diam. Nilai 0 dan pilihan Tidak diterima. Bila uraian tidak relevan, pengguna dapat menyatakan Tidak berlaku sesuai panduan yang sudah ada; koordinat tetap mengikuti pemeriksaan backend.
3. Narasi rencana tapak dimulai kosong untuk draft baru. Petunjuk menjadi placeholder/helper, bukan data. UI memakai judul, border sederhana dan panduan konkret tentang bangunan, ukuran, letak, akses serta hubungan antarfasilitas; tanpa narasi promosi editor atau dekorasi berlebihan.
4. Perubahan editor masuk state lokal secara langsung, tetapi request disimpan saat keluar editor atau melalui action wizard. Data existing tidak dibersihkan otomatis, termasuk narasi lama. Bersihkan instance ketika keluar bab; kembali ke bab memuat nilai tersimpan.
5. Konten HTML yang tidak mempunyai teks tidak menyelesaikan field narasi rencana tapak. Missing-field report berasal dari evaluator backend yang sama dengan submit/export.

## Acceptance criteria

- Reproduksi browser delay 1,5 detik tidak menghapus teks yang baru diketik ketika pilihan condition atau field lain disimpan.
- Respons autosave mempertahankan key order masukan, menyaring field asing, dan menyimpan field yang sah. Tetap menolak pending, cross-account serta locked.
- Satuan kedalaman/datum dapat diisi; seluruh 36 field sah menyelesaikan Bab 1. Missing report menyebut field yang tepat, boolean false/angka 0 bukan field kosong, narasi HTML kosong tetap incomplete.
- Bab 1 selesai tidak mengklaim seluruh proposal selesai. Status error koordinat tetap mencegah generate/submit.
- Draft baru mempunyai editor kosong; narasi tersimpan tetap utuh setelah reload/navigasi dan perubahan editor terakhir ikut save.
- Regresi PHPUnit, Pint targeted, build dan browser desktop/mobile dijalankan; hasil aktual dicatat terpisah dari baseline historis. Graphify utama diperbarui dari kode dan dokumen final.

## API yang diverifikasi

[Livewire 4 wire:model](https://livewire.laravel.com/docs/4.x/wire-model), [JavaScript $set](https://livewire.laravel.com/docs/4.x/javascript), [CKEditor placeholder](https://ckeditor.com/docs/ckeditor5/latest/features/editor-placeholder.html), [CKEditor data](https://ckeditor.com/docs/ckeditor5/latest/getting-started/setup/getting-and-setting-data.html). Implementasi tetap memakai CKEditor 40 yang sudah ada; tidak mengikuti perubahan konfigurasi versi terbaru yang tidak tersedia pada build proyek.
