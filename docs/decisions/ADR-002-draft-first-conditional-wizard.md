# ADR-002: Draft-First Wizard dengan Bagian Kondisional

## Status

Accepted for MVP

## Date

2026-08-24

## Context

Template proposal terdiri dari beberapa bagian dan sebagian besar data pendukung tidak selalu tersedia saat pertama kali pengisian. Bag 4 hanya berlaku untuk reklamasi. Bag 5 hanya berlaku jika pemohon membutuhkan bukti lahan atau memiliki perizinan pendukung.

Form panjang yang dipaksa selesai dalam satu sesi akan menyulitkan pengguna HP dan meningkatkan risiko kehilangan data.

## Decision

Gunakan wizard Livewire bertahap dengan penyimpanan draft.

- Bag 1–3 selalu tersedia.
- Bag 4 ditampilkan jika kegiatan reklamasi dipilih.
- Bag 5 ditampilkan berdasarkan pilihan kebutuhan lahan/perizinan.
- Draft boleh disimpan sebelum semua field final lengkap.
- Setelah status `submitted`, payload, lampiran, dan snapshot dokumen dikunci.
- Validasi draft dan validasi final dipisahkan.
- Aturan kondisional dipusatkan agar dipakai bersama oleh form, validasi, generator, dan Filament.
- Progress menjadi indikator UX, bukan klaim kelengkapan hukum.
- Wizard menyediakan ruang bawah ekstra pada viewport mobile agar kontrol bantuan fixed global tidak menutupi tombol atau field terakhir.

## Alternatives Considered

### Satu halaman panjang

- Pro: implementasi awal terlihat sederhana.
- Kontra: buruk di HP, sulit dipulihkan, dan menyembunyikan dependensi kondisional.
- Ditolak: tidak sesuai pola pengisian bertahap.

### Semua bagian selalu tampil

- Pro: struktur form seragam.
- Kontra: membingungkan pemohon dan menghasilkan dokumen dengan bagian yang tidak relevan.
- Ditolak: template sudah menunjukkan bagian kondisional.

### Draft hanya disimpan saat submit final

- Pro: model status lebih sedikit.
- Kontra: risiko kehilangan data tinggi dan tidak mendukung pengisian dicicil.
- Ditolak: bertentangan dengan kebutuhan utama pengguna.

## Consequences

Positif:

- Form lebih ringan dan fokus.
- Data dapat diisi ketika dokumen pendukung tersedia.
- Aturan proposal lebih dekat dengan struktur template.
- Kontrol form terakhir tetap dapat diakses pada layar HP meskipun widget bantuan global aktif.

Negatif dan mitigasi:

- State dan validasi lebih kompleks; mitigasi dengan schema/version dan test untuk setiap kondisi.
- Perubahan pilihan dapat membuat data bagian kondisional menjadi tidak relevan; sistem harus menentukan apakah data disembunyikan, dipertahankan, atau dihapus setelah konfirmasi.
- Koreksi pasca-submit harus membuat salinan revisi eksplisit dan tercatat; proposal `submitted` asli tidak boleh ditimpa atau dibuka kembali secara diam-diam.
- Generator dokumen wajib memakai rule yang sama dengan Livewire.
