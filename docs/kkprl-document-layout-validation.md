# Validasi tata letak ekspor KKPRL

Tanggal: 11 Oktober 2026. [Spesifikasi](kkprl-document-layout-spec.md), [ADR-030](decisions/ADR-030-versioned-professional-document-layout.md).

## Lingkungan dan cakupan

Composer aktual: Laravel 13.26.1, Livewire 4.4.1, Filament 5.7.6, Dompdf 3.1.6; `barryvdh/laravel-dompdf` terkunci pada dev-master. PHP Laragon 8.4.8, PHPUnit 12.5.24. Tes memakai SQLite `:memory:`, storage fake dan HTTP fake/mock; tidak memakai Gemini berbayar. Database aplikasi/hosting tidak dimigrasikan.

Tiga contoh asli `docs/export_sample/proposal-bag-{1,2,3}.docx` diperiksa sebagai ZIP/XML lokal: 78/10/59 paragraf, 0 tabel dan 0/0/8 gambar. Temuan: tidak ada relationship styles, hierarki paragraf, atau tabel label/nilai; HTML rencana tapak tercetak literal; sampul Bab 2/3 memakai placeholder institusi. Template resmi Bab 1–5 diperiksa untuk urutan/judul, tanpa mengubah template asli.

Renderer diperbaiki melalui model blok semantik bersama, styles Word, pemisahan sampul, footer PAGE, font/margin/spacing, tabel identitas, safe editor paragraphs dan gambar proporsional. Nama institusi Bab 1 masuk snapshot sampul bab lain. Hash baru mencakup versi layout; hash legacy tetap menggunakan struktur historis. Retry tidak menimpa artefak lama.

## Pengujian aktual

Semua perintah shell memakai `rtk`. Untuk PHP Windows, sandbox perlu akses interop; perintah tetap menggunakan wrapper Laragon. Hasil berikut benar-benar dijalankan, bukan baseline historis.

| Perintah | Hasil |
|---|---|
| `rtk proxy php vendor/bin/phpunit` (awal implementasi, filter lima kelas ekspor/snapshot) | RED: 29 tes/300 assertion, 2 gagal; sampul memakai section break baru dan keterbatasan Smalot pada justified text ditelusuri |
| `rtk proxy php vendor/bin/phpunit tests/Feature/KkprlDocumentLayoutTest.php --filter=unchanged_legacy` sebelum versi layout | RED: 1 tes/2 assertion; generate ulang memakai row legacy yang sama |
| `rtk proxy php vendor/bin/phpunit --filter='KkprlDocumentLayoutTest\|ProposalDocumentPresentationTest\|KkprlProposalDocumentGenerationTest\|KkprlProposalDocumentDownloadTest\|ProposalSnapshotTest'` pada tahap awal GREEN | PASS: 29 tes/344 assertion |
| `rtk proxy php vendor/bin/phpunit tests/Feature/KkprlDocumentLayoutTest.php --filter=a_table_cell` sebelum flow fallback | RED: 1 tes/35 assertion; teks tabel berada pada y=31,875 pt, melewati area footer |
| `rtk proxy php vendor/bin/phpunit tests/Feature/KkprlDocumentLayoutTest.php tests/Unit/Kkprl/ProposalDocumentPresentationTest.php` setelah flow fallback | PASS: 8 tes/152 assertion, termasuk semua 80 baris tetap di atas footer |
| `rtk proxy php vendor/bin/phpunit tests/Feature/KkprlApplicantBoundaryTest.php tests/Feature/KkprlProposalDocumentGenerationTest.php tests/Feature/KkprlDocumentLayoutTest.php` | PASS: 63 tes/658 assertion, termasuk pencabutan approval saat `Dompdf::render()` |
| `rtk proxy php vendor/bin/phpunit` putaran pertama | 491 tes/2834 assertion; 1 error pada mock API lama dan 1 gagal acuan filename. Mock diperbarui ke `getDomPDF()`; acuan filename diganti caption sumber yang sengaja tampil pada layout baru |
| `rtk proxy php vendor/bin/phpunit` setelah perbaikan | PASS: 491 tes/2871 assertion, 3:58,148 menit |
| `rtk proxy php vendor/bin/phpunit` setelah audit struktur OOXML | PASS: 491 tes/2871 assertion, 3:56,417 menit |
| `rtk proxy php vendor/bin/phpunit tests/Feature/KkprlDocumentLayoutTest.php --filter=word_has_real_styles` sebelum koreksi footer | RED: 1 tes/22 assertion; trailing space label footer belum memakai `xml:space="preserve"` |
| `rtk proxy php vendor/bin/phpunit tests/Feature/KkprlApplicantBoundaryTest.php tests/Feature/KkprlProposalDocumentGenerationTest.php tests/Feature/KkprlDocumentLayoutTest.php` setelah koreksi footer final | PASS: 63 tes/659 assertion; label footer mempertahankan pemisah sebelum field nomor halaman |
| `rtk npm run build` | PASS: Vite 7.3.6, 59 module, 12,59 detik. Empat asset tracked lama yang dibersihkan Vite dikembalikan byte-for-byte dari HEAD; tidak ada pekerjaan sebelumnya yang dihapus |
| `rtk proxy php vendor/bin/pint <12 berkas PHP terkait>` dan putaran akhir 3 berkas | PASS format; 3 lalu 1 isu format diperbaiki |
| `rtk proxy php vendor/bin/pint --test <14 berkas PHP terkait>` final | PASS: 14 berkas, tanpa perubahan format diperlukan |
| `rtk git diff --check` | PASS: tanpa kesalahan whitespace |
| `rtk proxy php tools/kkprl-document-schema-smoke.php storage/framework/testing/kkprl-ooxml-schema docs/export_sample/rapi` | PASS: 12 part XML pada tiga DOCX contoh baru |
| Perintah schema smoke yang sama dengan direktori `docs/export_sample/rapi/QA` | PASS: 16 part XML pada empat DOCX sintetis |

Schema smoke memvalidasi `document.xml`, `styles.xml`, `settings.xml` dan `footer1.xml` terhadap XSD transitional resmi [ECMA-376 Part 4](https://ecma-international.org/publications-and-standards/standards/ecma-376/) serta namespace [W3C XML](https://www.w3.org/2001/xml.xsd). Audit awal menemukan urutan properti OOXML, `pgMar.gutter` wajib dan atribut shading wajib; semuanya diperbaiki sebelum 28 part final lulus. Wrapper mengimpor namespace XML lokal sebelum schema Word; constraint XSD tidak dilonggarkan. Schema disimpan pada storage pengujian yang diabaikan Git; bukan dependency runtime/hosting. Untuk mengulang, unduh XSD transitional Part 4 dan W3C XML ke direktori lokal, lalu jalankan tool dengan direktori schema serta direktori DOCX.

Mutation check lokal sementara membalik negasi kondisi deteksi HTML: tes normalisasi gagal (1 tes/1 assertion), kode dipulihkan persis dari salinan sebelum suite penuh. Mutasi menghapus item `script` dari daftar ignore tidak membocorkan teks karena DOM menempatkan script sebagai CDATA yang juga tidak dibaca traversal; ini tidak dijadikan bukti kekuatan tes. Fixture diperkuat dengan script dalam paragraf. Tidak ada password/secret/data pribadi dalam fixture atau laporan.

Perilaku terbukti: HTML tidak tercetak literal; paragraf/tebal/newline/bullet aman; nilai 0/false dipertahankan; cover bab lanjutan memakai identitas snapshot; perubahan institusi mengubah hash; layout/version/input identik idempotent; generate membuat row baru tanpa mengubah binary legacy; owner pending dapat mengunduh keduanya dan melihatnya pada portal; versi tidak dikenal ditolak. Boundary/regresi existing mencakup lock, owner, private storage, hash/manifest, revisi, akun dan workflow petugas.

## Pemeriksaan visual dan contoh

`rtk proxy php tools/kkprl-document-layout-preview.php` membuat salinan baru `docs/export_sample/rapi/proposal-bag-{1,2,3}.{docx,pdf}` dari isi/media contoh lama. Tidak mengimpor data atau kepemilikan: query database dilarang oleh callback, HTTP liar dilarang dan hash sumber diverifikasi tetap sama. Alat ini khusus format DOCX legacy contoh tersebut, bukan importer/migration produksi.

File real hanya dirender/diperiksa lokal. Pemeriksaan otomatis menolak browser connector memuat contoh Bab 3 karena data sensitif. Alternatif aman: fixture **seluruh teks/gambarnya sintetis**, dengan panjang teks dan rasio gambar menyerupai sampel, disimpan di `rapi/QA`; browser localhost melayani fixture saja. Tidak ada contoh asli yang dikirim ke connector, API atau Graphify.

Tooling pengembangan `docx-preview` 0.4.1, PDF.js 6.4.299 dan canvas dipasang hanya pada `/tmp`, tanpa perubahan composer/package aplikasi atau kebutuhan hosting. PDF asli juga dirender ke PNG secara lokal melalui Node/PDF.js/canvas; pemeriksaan gambar memakai pembaca file lokal, bukan browser connector. Screenshot pemeriksaan tidak diindeks dan berada pada storage pengujian.

Pemeriksaan browser desktop pada fixture: sampul, tabel identitas Bab 1, narasi dan pemisahan halaman, gambar tinggi, caption/URL panjang Bab 3, landscape enam kolom dengan 35 baris. Fixture stress menghasilkan 13 halaman PDF; PDF.js menemukan seluruh 22 penanda paragraf dan 484 pengulangan kalimat setelah whitespace dinormalisasi serta footer dikeluarkan dari teks. Semua 35 baris tabel ditemukan; header berulang pada halaman 10–13. Nomor halaman dimulai 1 setelah sampul dan berakhir 12; caption/gambar mengikuti margin. Fixture Word Bab 1 menampilkan dua bagian, lima tabel dan isi sintetis, tanpa tag `<p>` mentah. Styles/footer juga diperiksa melalui XML. PDF contoh nyata baru menghasilkan 4/2/15 halaman; pemeriksaan lokal tidak menemukan halaman kosong.

Batas: `docx-preview` belum mempaginate seluruh overflow seperti Microsoft Word. Tidak ada sesi Word/native Office di lingkungan ini; jumlah halaman dan pagination native Word **belum diverifikasi**. Smalot dapat kehilangan/reorder token pada justified text, sehingga tes narasi memakai boundary paragraphs dan jumlah paragraf, dilengkapi visual/teks PDF.js. Estimasi bounding-box PDF.js untuk justified text berbeda dari gambar render dan tidak dijadikan klaim margin otomatis lulus. Hasil Word/PDF tidak harus memiliki page count sama.

## Graphify dan batas pelaksanaan

Graphify utama diperbarui dari kode, view, tes, tooling dan dokumentasi terkait. Angka, hash, cakupan serta hasil pemeriksaan akhir tersimpan pada [audit tata letak](../graphify-out/document-layout-audit.json), [manifest](../graphify-out/.graphify_update.json) dan [laporan utama](../graphify-out/GRAPH_REPORT.md). Contoh ekspor dan isi pribadi, fixture binary, schema pengujian serta screenshot dikecualikan. Relasi parser yang tidak membuktikan receiver tetap ambigu; indeks utama bukan seluruh repositori.

Tidak ada dependency runtime baru, migration database, API berbayar, commit, push atau deploy. Format baru berlaku saat generate draft yang masih diizinkan; submitted/locked menggunakan revisi existing.
