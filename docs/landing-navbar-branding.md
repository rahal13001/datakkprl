# Identitas navbar landing page

Navbar menampilkan logo KKP, logo DJPRL, kemudian tulisan **LPRL Sorong** dalam satu baris. Aset yang digunakan adalah `public/img/logokkp.png` dan `public/img/djprl.png`.

Logo KKP tetap menggunakan `object-contain`. Logo DJPRL memiliki kanvas transparan 2.000 × 2.000 px; artwork berada pada koordinat x 153–1846 dan y 608–1285. Bingkai CSS dengan rasio 1694/678 menampilkan seluruh artwork tanpa mengubah file PNG atau meregangkan gambar. Lebar bingkai DJPRL adalah 80 px pada layar kecil dan 96 px mulai breakpoint `sm`; tinggi visualnya sekitar 32/38,4 px, seimbang dengan logo KKP.

Teks identitas tetap satu baris. Subjudul `Official Platform V2` tampil mulai breakpoint `sm` agar layar kecil memiliki ruang untuk kedua logo dan tombol menu. Menu desktop tampil mulai breakpoint `xl` (1.280 px); layar di bawahnya memakai dropdown yang sama. Tombol menu memiliki ukuran 40 × 40 px, ikon SVG lokal, nama aksesibel, serta `aria-expanded` dan `aria-controls`.

## Verifikasi

- Kedua PNG berhasil dimuat dan memiliki nama alternatif yang sesuai pada accessibility tree.
- Pemeriksaan browser pada lebar 320, 768, 1.024, 1.280, dan 1.440 px: urutan logo dan teks benar; identitas tidak bertabrakan dengan menu.
- Screenshot desktop dan ponsel diperiksa secara visual; artwork DJPRL lengkap, rasio gambar dipertahankan, dan teks identitas terbaca.
- Menu ponsel dapat dibuka dan ditutup; status aksesibilitas mengikuti visibilitas dropdown.
- Tes landing page (`ExampleTest`): 3 tes lulus, 12 assertion.
- Build Vite dan `git diff --check` lulus. CSS produksi dan manifest diperbarui.
- Error Chart.js yang sebelumnya dicatat pada landing page masih berada di luar perubahan identitas navbar. Pada viewport 320 px, konten halaman di luar navbar juga masih menunjukkan overflow horizontal; navbar sendiri berada dalam viewport. Hasil verifikasi tidak dinyatakan sebagai bebas masalah untuk seluruh landing page.

Perubahan navbar dan relasi asetnya ditambahkan ke graphify root. Relasi yang sebelumnya ada, termasuk penanda ambigu, dipertahankan. Graph `app/graphify-out` tidak perlu diekstrak ulang karena perubahan ini hanya melibatkan view dan aset publik.
