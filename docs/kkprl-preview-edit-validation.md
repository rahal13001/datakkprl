# Validasi koreksi draft melalui chat dan editor pratinjau

Tanggal: 2026-10-04.

## Perilaku yang diterapkan

Bab 2–5 menyediakan dua cara koreksi: permintaan revisi melalui chat dan pengeditan langsung pada pratinjau. Pratinjau memakai label serta key dari katalog template. Koreksi manual menjadi konteks permintaan AI berikutnya. Jadwal reklamasi dapat ditambah, diubah, atau dihapus per baris.

Pengeditan menyimpan checkpoint pada riwayat chat, bukan payload proposal. Tombol **Konfirmasi & Simpan Draft** menyimpan payload melalui service draft, memperbarui progress dan waktu simpan, serta mempertahankan bab lain. Bab belum lengkap boleh disimpan sebagai draft; aturan export tidak berubah.

Input pratinjau memakai `wire:model.live.blur`: Livewire 4 memerlukan `.live` untuk mengirim perubahan ke server saat field kehilangan fokus. Referensi: [dokumentasi wire:model Livewire 4](https://livewire.laravel.com/docs/4.x/wire-model).

## Pengujian otomatis

- `php vendor/bin/phpunit --filter=KkprlAiChatTest`: 13 test, 50 assertion, semuanya lulus.
- Test mencakup penyimpanan setelah konfirmasi, pemulihan koreksi, revisi chat menggunakan pratinjau terbaru, draft parsial, jadwal reklamasi, kompatibilitas draft lama, format JSON tidak valid, kegagalan AI, session access, bab aktif, penguncian proposal, dan pembaruan progress wizard.
- Targeted Pint untuk komponen AI dan test baru: lulus.
- `git diff --check` pada perubahan fitur: lulus.
- `npm run build`: lulus.

Sebelum perbaikan baseline, suite menghasilkan 14 error dan 11 failure yang juga muncul pada pembanding Git. Setelah perbaikan 2026-10-04, seluruh suite lulus: **217 test, 1.026 assertion, 0 error, 0 failure, 0 skipped**.

Perbaikan baseline:

- Policy review/approval memakai permission workflow dari katalog KKPRL. Resource dikecualikan dari generator policy Shield untuk mempertahankan aturan tersebut.
- Scope approval dan fixture memakai field katalog aktif. Assertion larangan self-approval, scope, expiry, actor, audit, serta penguncian tetap diuji.
- Koordinat peta/teks divalidasi berdasarkan WGS84, bentuk lokasi, dan jumlah titik. Input rusak tetap boleh menjadi draft, tetapi tidak bisa diekspor atau dikirim.
- Raw koordinat dipertahankan saat konfirmasi draft flat. Widget peta tidak diinisialisasi ulang oleh autosave, membersihkan resource saat keluar bab, dan menyertakan edit terbaru sebelum navigasi.
- Dropdown memulihkan nilai boolean sebagai opsi `0`/`1`. Navigasi memakai nomor bab sebenarnya dan memperbarui scope lampiran. Progressbar menunjukkan persentase field terisi, termasuk setelah konfirmasi AI.
- Assertion UI diselaraskan dengan copy yang tampil sekarang; label upload dapat dibaca melalui accessibility tree. Input Bab 1 memakai pengiriman perubahan saat blur sesuai Livewire 4.

Pint pada file PHP yang diubah, pemeriksaan whitespace diff, konversi DD/DMS (5 kasus batas), serta build frontend semuanya lulus. Test tambahan koordinat dibuktikan gagal sebelum validator diperbaiki.

## Pengujian browser

Chrome memakai context terisolasi, database SQLite sintetis, dan respons Gemini palsu. API Gemini aktual tidak dipanggil.

- Koreksi manual terkirim saat keluar dari field dan tetap tersedia setelah refresh.
- Payload tetap memakai hasil konfirmasi sebelumnya selama koreksi belum dikonfirmasi.
- Permintaan melalui chat memperbarui pratinjau; konfirmasi kemudian menyimpan hasil tersebut.
- Enter mengirim pesan; Shift+Enter membuat baris baru.
- Jadwal reklamasi yang diedit dan dikonfirmasi tetap tersedia setelah refresh.
- Viewport 320, 768, 1024, dan 1440 px tidak memiliki overflow horizontal.
- Console browser pada skenario tersebut tidak memiliki error atau warning; request Livewire berhasil dengan HTTP 200.

Evidence sementara: `/tmp/kkprl-preview-edit-mobile.png` dan laporan JUnit di `/tmp/kkprl-preview-validation/`. Database, router, dan server uji bersifat sementara dan tidak termasuk fitur aplikasi.

## Browser regresi wizard — 2026-10-04

Pengujian memakai SQLite sintetis dan wizard penuh pada server lokal. Nama pemohon dan koordinat tersimpan otomatis; koordinat tetap sama setelah pindah Bab 2 dan kembali. Nilai latitude `999` menandai Bab 1 error dan menghilangkan tombol ekspornya; setelah dikoreksi tombol tersedia lagi. Dropdown boolean menampilkan opsi yang benar. Saat Bab 4 tidak aktif, tombol Bab 5 mempertahankan nomor bab dan hanya menampilkan field serta anchor lampiran lahan yang aktif. Navigasi dan autosave tidak lagi memunculkan error `Map container is already initialized`.

Pada viewport 320 px, scroll width sesuai viewport dan tidak ada input/select/textarea/button yang terpotong keluar viewport. Console pada skenario akhir bersih. Server dan database uji sementara dibersihkan setelah pemeriksaan.

Evidence tambahan: `/tmp/kkprl-regression-wizard-mobile.png` dan `/tmp/kkprl-preview-validation/full-fixed.xml`.

## Reset nomor titik peta — 2026-10-04

Pengosongan manual dua baris DD sebelumnya menyisakan kedua baris dalam array, sehingga klik peta berikutnya diberi label Titik 3. Kasus ini direproduksi pada halaman uji yang memakai markup dan JavaScript widget asli. Setelah perbaikan, semua baris kosong diganti dengan satu titik baru mulai dari Titik 1. Jika masih ada koordinat yang terisi, klik peta mengisi baris kosong terlebih dahulu tanpa menimpa titik parsial.

Tombol Bersihkan Peta menghapus marker langsung dan mereset flag drawing. Pengosongan seluruh komponen DMS juga mengosongkan DD; input nol tetap dianggap koordinat valid.

`node tests/js/kkprl-coordinate-manager.test.js`: 7 test lulus, mencakup reset DD/DMS, pengisian slot kosong, titik parsial, penghapusan semua baris, reset berulang, dan koordinat nol. Test reset DD/slot kosong/DMS terbukti gagal sebelum perbaikan masing-masing. Suite PHP: 217 test dan 1.026 assertion lulus. Build frontend dan pemeriksaan whitespace lulus.

Browser memverifikasi pengosongan DD menghasilkan Titik 1 lalu Titik 2, kemudian Bersihkan Peta menghapus seluruh marker dan klik baru kembali menjadi Titik 1. Pengosongan seluruh komponen DMS di browser juga mengosongkan DD dan marker; klik berikutnya kembali menjadi Titik 1. Console bersih. Halaman uji memakai adapter penyimpanan sintetis, sehingga data proposal pengguna tidak disentuh.

## Percakapan saat draft siap — 2026-10-04

Respons AI sebelumnya hanya mengenali blok dengan penanda `json` huruf kecil dan penutup lengkap. Respons dengan penanda huruf besar, tanpa pagar kode, atau terpotong dapat memperlihatkan data teknis di chat. Pemrosesan respons sekarang memisahkan teks percakapan dan data pratinjau. Riwayat AI lama juga dibersihkan saat ditampilkan kembali, tanpa menimpa checkpoint koreksi manual.

Saat respons hanya berisi data draft atau penjelasan teknis, chat menampilkan arahan membaca Pratinjau Draft, mengoreksi jika perlu, dan menekan **Konfirmasi & Simpan Draft** jika sesuai. Prompt meminta bahasa Indonesia yang mudah dipahami serta arahan yang sama ketika informasi sudah cukup. Pertanyaan lanjutan tetap tampil saat AI memperbarui draft parsial. Respons tidak valid ditolak seluruhnya, mempertahankan pratinjau sebelumnya, dan meminta pengguna mencoba kembali. Indentasi markup tidak lagi ikut tampil sebagai spasi dalam gelembung pesan.

- Delapan regresi awal terbukti gagal sebelum perbaikan dan lulus setelahnya.
- Tes percakapan dan parser: **28 test, 116 assertion**, seluruhnya lulus. Mencakup variasi pagar kode, objek tanpa pagar, respons terpotong, riwayat lama, pertanyaan lanjutan, field di luar katalog, data bersarang, dan gabungan blok valid/tidak valid.
- Suite Laravel: **232 test, 1.092 assertion, 0 error, 0 failure, 0 skipped**. Setelah perubahan whitespace Blade, 23 test percakapan diperiksa kembali dan lulus.
- Pint untuk komponen, parser, dan test baru; pemeriksaan whitespace; serta build frontend: lulus.

Browser memakai context terisolasi, database SQLite sintetis, dan respons Gemini simulasi. Draft tampil di pratinjau tanpa JSON atau key teknis dalam chat; payload kosong sebelum konfirmasi dan berisi narasi setelah konfirmasi. Respons terpotong tidak mengubah narasi sebelumnya. Setelah refresh, pesan ramah dan pratinjau tetap tersedia. Viewport 320 px tidak memiliki overflow horizontal, dan console tidak memiliki error atau warning. API Gemini aktual tidak dipanggil pada pengujian ini.

Evidence sementara: `/tmp/kkprl-friendly-chat-mobile.png` dan laporan `friendly-*.xml` di `/tmp/kkprl-preview-validation/`. Server dan database uji dibersihkan setelah pemeriksaan.
