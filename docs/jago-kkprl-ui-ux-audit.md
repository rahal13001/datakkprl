# Audit Awal UI/UX Jago KKPRL

Tanggal audit: 28 Juli 2026 (Asia/Jayapura)  
Target: Android, Ionic Vue 8, Vue 3, Capacitor 8  
Status: audit awal selesai; belum ada redesign source yang diterapkan saat dokumen ini dibuat

## Ringkasan Eksekutif

Aplikasi mobile sudah berfungsi, memiliki bottom navigation, permission-aware UI,
safe navigation, loading dasar, dan visual yang lebih baik daripada UI Ionic
default. Masalah utama bukan kerusakan fungsi, melainkan kedalaman UX. Pola modern
baru terlihat di permukaan, sedangkan alur kerja inti masih padat dan terasa
seperti form administrasi web yang dipindahkan ke layar kecil.

Temuan paling penting:

- `RequestDetailPage.vue` memuat detail, status, identitas, jadwal, penugasan,
  laporan, Berita Acara, tanda tangan, lampiran, dan umpan balik dalam satu file
  1.203 baris.
- API sudah memakai cursor pagination, tetapi UI mengabaikan metadata cursor.
  Daftar permohonan hanya mengambil 40 data, notifikasi 50 data, dan masukan
  hanya halaman pertama tanpa kontrol lanjut/sebelumnya.
- Pemilihan pegawai masih memakai `IonSelect` panjang. Endpoint staff mendukung
  pencarian nama/NIP/jabatan, tetapi UI selalu mengambil maksimal 50 pegawai dan
  tidak menyediakan pencarian.
- Form laporan dan Berita Acara belum memiliki navigasi section, ringkasan,
  dirty-state warning, preview, retry per aksi, progres unggahan, atau sticky
  actions.
- State loading/error/empty tersedia secara tidak merata. Beberapa error hanya
  berupa kotak teks tanpa tombol retry dan beberapa daftar tidak memiliki
  skeleton.
- Design tokens masih terlalu sedikit sehingga radius, warna netral, shadow,
  tinggi kontrol, ukuran ikon, dan touch target ditulis berulang per halaman.
- Nama lama `ServiceKKPRL` masih muncul di konfigurasi native dan beberapa
  halaman.

Redesign akan dilakukan bertahap tanpa mengubah endpoint, payload, autentikasi,
otorisasi, policy, struktur data, routing yang dipakai, atau lifecycle bisnis.

## Ruang Lingkup yang Diaudit

| Halaman/area | File utama | Kondisi saat ini | Prioritas |
|---|---|---|---|
| Login | `mobile/src/pages/LoginPage.vue` | Visual cukup rapi, tetapi brand lama, hero gradient besar, dan kartu bertumpuk memakan layar kecil | Medium |
| Beranda | `mobile/src/pages/DashboardPage.vue` | Ringkasan jelas, tetapi kartu metrik dominan dan action penting belum diprioritaskan sebagai pekerjaan yang perlu ditindak | Medium |
| Daftar permohonan | `mobile/src/pages/RequestsPage.vue` | Pilot visual terbaik, tetapi search hanya tiket lengkap, filter tidak dipersistenkan ke URL, cursor pagination diabaikan, error tanpa retry | Critical |
| Detail permohonan | `mobile/src/pages/RequestDetailPage.vue` | Semua workflow digabung; tiga accordion terbuka sekaligus; form administratif panjang | Critical |
| Jadwal | bagian detail permohonan | Edit dan tambah berada dalam aliran yang sama; tidak ada mode edit yang fokus atau ringkasan konflik | High |
| Penugasan/disposisi | bagian detail permohonan | `IonSelect` panjang, tidak searchable, informasi pegawai terbatas, tidak ada review sebelum submit | Critical |
| Laporan konsultasi | bagian detail permohonan | Rich text minimal, status dan upload berada pada form datar, tidak ada preview/draft indicator/progress | Critical |
| Berita Acara | bagian detail permohonan | Form sangat panjang, peserta dan signature berulang, upload native polos, tidak ada section validation atau sticky action | Critical |
| Masukan & penilaian | `mobile/src/pages/FeedbackPage.vue` | Tidak ada pagination control, loading skeleton, retry, search, atau pemisahan ringkasan/detail yang kuat | High |
| Notifikasi | `mobile/src/pages/NotificationsPage.vue` | Mengambil 50 data satu kali, tanpa pagination/loading skeleton/retry dan “baca semua” tanpa state proses | High |
| Profil | `mobile/src/pages/ProfilePage.vue` | Cukup jelas; brand lama, aksi logout perlu hierarchy dan konfirmasi lebih kuat | Medium |
| Update/maintenance | `mobile/src/pages/UpdateRequiredPage.vue` | Fokus dan sederhana; brand lama dan tidak ada retry pemeriksaan konfigurasi | Cosmetic |
| Bottom navigation | `mobile/src/pages/TabsPage.vue` | Struktur empat tab tepat untuk Android; styling, safe area, active indicator, dan badge notifikasi dapat diperkuat | Medium |
| Tema global | `mobile/src/theme/*.css` | Token dasar ada, tetapi belum menjadi design system yang lengkap | High |
| Komponen bersama | `mobile/src/components/*` | Baru empat komponen; error/loading/form/list/pagination/selector masih terduplikasi | High |

## Temuan Berdasarkan Prioritas

### Critical UX Issues

1. **Pagination server tidak diteruskan ke UI.**
   `ClientController` mengembalikan `next_cursor` dan `previous_cursor`, tetapi
   `RequestsPage.vue` hanya membaca `data`. Pengguna tidak dapat mengetahui atau
   membuka data di luar batch pertama. Feedback dan notifikasi memiliki masalah
   serupa. Cursor API tidak menyediakan total global, sehingga UI tidak boleh
   mengarang total data.

2. **Alur detail menjadi satu halaman super-form.**
   Satu halaman menangani tujuh domain operasional. Banyak accordion dibuka
   default dan action edit selalu terlihat. Ini meningkatkan scroll, cognitive
   load, risiko salah simpan, dan kemungkinan keyboard menutupi field.

3. **Pemilihan pegawai tidak cocok untuk daftar besar.**
   Dua `IonSelect` merender daftar staff yang dibatasi 50. Endpoint sudah menerima
   `search`, tetapi UI tidak menggunakannya. Pegawai di luar 50 hasil pertama
   tidak dapat dipilih.

4. **Form Laporan dan Berita Acara belum mobile-first.**
   Form datar tidak memiliki progress/section summary, validasi per section,
   dirty guard, preview, sticky actions, atau feedback upload yang memadai.

### High Priority

1. Error state tidak konsisten dan sebagian besar tidak memiliki retry action.
2. Loading skeleton tidak tersedia pada feedback, notifikasi, dan detail lengkap.
3. Search permohonan dilabeli “nomor tiket lengkap” dan backend melakukan exact
   match; UI belum menjelaskan keterbatasan ini dengan baik atau menjaga query
   saat kembali dari detail.
4. Filter status/scope hanya tersimpan di state komponen. Perubahan pengguna
   tidak disinkronkan kembali ke URL.
5. `RichTextEditor` menggunakan `document.execCommand`, toolbar hanya berisi
   bold/italic/bullet, label hardcoded, dan belum memiliki numbered list,
   heading sederhana, undo/redo, pressed state, counter, atau helper/error text.
6. Upload masih berupa `<input type="file">` tanpa daftar file yang jelas,
   penghapusan pilihan, progres, retry, atau opsi kamera yang eksplisit.
7. Tombol kecil (`size="small"`) sering dipakai untuk action penting sehingga
   touch target tidak konsisten.
8. Beberapa string source menunjukkan mojibake seperti `â€”`, `Â·`, dan `â€¢`.

### Medium Priority

1. Hierarki toolbar berbeda per halaman.
2. Gradient hero muncul di Login, Dashboard, Detail, dan Profil sehingga identitas
   visual terasa dekoratif dan tidak konsisten dengan arahan sederhana.
3. Radius, shadow, dan border ditulis sebagai angka langsung di banyak scoped CSS.
4. Layout dua kolom tetap dipakai pada field waktu dan peserta; pada layar sempit
   atau font besar dapat terasa sesak.
5. Aksi penting dan informasi read-only sering bercampur dalam card yang sama.
6. Android safe area dan keyboard handling belum dinyatakan sebagai pola global.
7. Konfirmasi logout, keluar dari form kotor, dan destructive action belum menjadi
   komponen bersama.

### Cosmetic Improvements

1. Brand lama `ServiceKKPRL` dan istilah campuran seperti “Service desk” masih ada.
2. Status badge belum memiliki fallback visual yang konsisten untuk status baru.
3. Empty state hanya memakai satu ikon dan belum membedakan empty, no-result,
   dan permission-limited state.
4. Active state bottom navigation masih bergantung pada warna teks/ikon.

## Penyebab Desain Terasa Kuno atau Kaku

- UI mengikuti struktur resource/backend, bukan urutan keputusan pengguna.
- Komponen Ionic dipakai langsung di halaman tanpa lapisan design system yang
  menetapkan tinggi, radius, label, helper, dan feedback.
- Form edit selalu tampil, sementara aplikasi mobile lebih nyaman memakai
  progressive disclosure: ringkasan dahulu, edit saat dibutuhkan.
- State jaringan dan mutasi ditangani global, bukan dekat dengan tindakan yang
  gagal.
- Daftar besar diperlakukan sebagai batch statis, bukan alur search-filter-page.
- Scoped CSS per halaman menghasilkan banyak angka dan pola serupa yang tidak
  benar-benar reusable.

## Arah Desain

Nama produk: **Jago KKPRL**  
Arah visual: **Maritime civic utility** — tenang, tepercaya, bersih, dan cepat
dibaca oleh petugas lapangan maupun pengelola.

- Primary: biru laut gelap untuk navigasi dan action utama.
- Secondary: teal untuk informasi dan state aktif sekunder.
- Accent: emas hangat hanya untuk perhatian/brand, bukan sebagai dekorasi luas.
- Background: abu-biru sangat muda; surface putih.
- Typography: system/Inter dengan skala 12, 14, 16, 20, 24, dan 32 px.
- Layout: satu kolom pada ponsel, lebar baca dibatasi pada tablet.
- Card: radius 16 px, border lembut, shadow minimal.
- Control: tinggi minimum 48 px; touch target minimum 48 × 48 px.
- Navigation: empat bottom tabs dipertahankan; active indicator memiliki bentuk,
  bukan hanya perubahan warna.
- Forms: label selalu terlihat, helper/error spesifik, section summary, sticky
  actions, dan mode edit yang fokus.
- Motion: 120–200 ms untuk feedback ringan; hormati `prefers-reduced-motion`.

Design tidak akan bergantung pada gradient besar, shadow berat, nested card
berlebihan, atau tabel desktop.

## Design Tokens yang Akan Dibangun

Token akan mencakup:

- semantic colors: primary, secondary, background, surface, text, border,
  success, warning, danger, information;
- typography scale dan line-height;
- spacing 4/8/12/16/20/24/32/40/48;
- radius 8/12/16/20/999;
- elevation rendah;
- icon size 18/20/24/28;
- button/field height 48/52;
- touch target 48;
- content max width;
- safe-area spacing;
- card, list item, modal, bottom sheet, chip, badge, dan focus ring.

## Komponen Reusable yang Direncanakan

1. `AppHeader`
2. `PageContainer`
3. `SearchToolbar`
4. `FilterBottomSheet`
5. `PaginationControl`
6. `DataListCard`
7. `StatusBadge` (peningkatan komponen yang ada)
8. `EmptyState` (mendukung empty/no-result)
9. `ErrorState`
10. `LoadingSkeleton`
11. `EmployeeSearchSelector`
12. `FormSection`
13. `StickyFormActions`
14. `AttachmentUploader`
15. `ConfirmationDialog`
16. `RichTextEditor`/`RichTextField` (peningkatan komponen yang ada)
17. `DetailInformationRow`

Komponen hanya akan menerima/emit data UI. Payload API tetap disusun oleh flow
yang ada agar kontrak tidak berubah.

## Urutan Implementasi Delapan Tahap

1. **Audit dan baseline** — dokumen ini, inventory, test/build awal.
2. **Fondasi** — nama Jago KKPRL, design tokens, global Android styling,
   AppHeader/PageContainer/ErrorState/LoadingSkeleton/StatusBadge/EmptyState.
3. **Pilot daftar dan detail** — daftar permohonan dengan query URL, debounce,
   cursor pagination, loading/empty/no-result/error/retry, lalu detail summary.
4. **Penugasan/disposisi** — searchable employee selector dan flow penugasan
   terstruktur tanpa mengubah `schedule_ids`, `user_ids`, atau status payload.
5. **Laporan** — section form, draft indication, attachment experience, preview,
   rich-text toolbar mobile, dan double-submit protection.
6. **Berita Acara** — sectioned form, participant editor, signatures,
   attachments, sticky actions, dan preview summary.
7. **Konsistensi seluruh halaman** — dashboard, feedback, notification, profile,
   login/update page, Android safe area, keyboard, focus, dan accessibility.
8. **Regression dan handoff** — type check, unit/feature tests, production build,
   UI smoke checks, daftar perubahan, regression, risiko, dan rekomendasi.

Setiap tahap harus lulus validasi sebelum tahap berikutnya dianggap selesai.

## Risiko dan Mitigasi

| Risiko | Dampak | Mitigasi |
|---|---|---|
| Cursor tidak menyediakan total global | Acceptance “jumlah total” tidak bisa dipenuhi akurat dari contract saat ini | Tampilkan jumlah item pada halaman dan posisi halaman; dokumentasikan total global sebagai keterbatasan API tanpa mengubah contract |
| Staff endpoint dibatasi 50 dan belum memiliki cursor | Pegawai di luar hasil pencarian pertama dapat hilang jika query kosong | Wajibkan pencarian server setelah ambang tertentu; jangan mengubah endpoint; tampilkan batas hasil secara jujur |
| Pemecahan `RequestDetailPage` | Risiko payload atau permission berubah | Ekstrak presentational component bertahap; pertahankan fungsi mutation dan payload; tambah test komponen |
| `execCommand` pada Android WebView | Perilaku editor berbeda antar WebView | Pertahankan sanitasi dan format HTML; toolbar ringan; uji focus, paste, undo/redo; hindari dependency besar pada tahap awal |
| Dirty-state dan autosave | Dapat menimbulkan write baru yang tidak diinginkan | Mulai dengan local dirty warning dan manual draft save; jangan menambah autosave API |
| Upload foto besar | Performa dan memory | Preview berbasis object URL, batas file mengikuti backend, progress Axios; kompresi hanya jika aman dan teruji |
| Rename native app | Dapat mengganggu update jika package berubah | Ubah display name menjadi Jago KKPRL; pertahankan `appId` dan route/deep-link identifiers |
| Font scaling/keyboard | Field atau sticky action tertutup | Gunakan responsive grid, `scroll-padding-bottom`, safe-area tokens, dan uji viewport kecil/font besar |

## Baseline Validasi

Hasil sebelum perubahan source:

- `vue-tsc --noEmit`: lulus.
- Vitest: 4 file, 11 test lulus.
- Vite production build: lulus ke
  `output/mobile-ui-audit-baseline`.
- Peringatan baseline: entry chunk sekitar 1,28 MB (gzip sekitar 300 KB).
- `npm test` biasa sempat gagal karena Vite tidak dapat menulis
  `node_modules/.vite-temp` pada environment sandbox. Menjalankan Vitest dengan
  config loader one-shot `runner` lulus; ini bukan failure aplikasi.
- Pemeriksaan singkat server eksternal `http://127.0.0.1:4173/`: HTTP 200.
- Artefak Playwright lama mengonfirmasi fallback route dan bottom navigation
  berfungsi. Artefak tersebut berasal dari audit navigasi sebelumnya, bukan bukti
  bahwa redesign UI/UX baru sudah diterapkan.

## Format Pelaporan Tiap Tahap

Setiap tahap berikutnya akan mencatat:

1. kondisi saat ini;
2. penyebab;
3. rencana perbaikan dan file;
4. implementasi;
5. dampak pengguna;
6. hasil validasi;
7. risiko/regression tersisa.

