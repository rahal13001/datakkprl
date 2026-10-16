# ADR-030: Tata letak Word/PDF berversi

## Status

Accepted. Memperluas ADR-009 dan ADR-014; identitas dokumen generated lama tetap berlaku.

## Date

2026-10-11

## Context

Contoh ekspor menunjukkan paragraf tanpa hierarki, HTML editor tercetak mentah, sampul bab lanjutan tanpa identitas, serta gambar/label yang memerlukan koreksi manual. Hosting bersama tidak menyediakan converter Word atau layanan tambahan. Cache snapshot lama akan mengembalikan layout lama meskipun renderer diperbaiki.

## Decision

- `ProposalDocumentPresentation` membentuk blok heading, identitas singkat, narasi, tabel dan lampiran. Word/PDF membaca blok semantik yang sama; isi narasi tidak ditulis ulang oleh AI.
- `KkprlProposalDocxRenderer` membuat OOXML dengan named styles, outline headings, margin A4, tabel berlebar tetap/header berulang, gambar proporsional, section sampul dan footer PAGE. `KkprlProposalPdfRenderer` memakai Dompdf existing, font DejaVu Sans, pagination dan nomor halaman setelah sampul.
- HTML editor dibaca lokal melalui DOM dengan `LIBXML_NONET`, lalu hanya teks, paragraf, bullet, tebal dan miring yang dibentuk kembali. Resource/script tidak diteruskan; teks output di-escape.
- Baris tabel PDF yang diperkirakan melebihi tinggi halaman menjadi rincian per kolom yang boleh mengalir antarhalaman. Estimasi memperhitungkan newline, jumlah kolom dan orientasi; teks tidak dipangkas. Word mempertahankan tabel dengan baris yang boleh terpisah.
- Caption/sumber tampil dekat gambar; nama file menjadi fallback bila caption kosong. Nama asli tetap dalam manifest. Nomor lampiran mengikuti urutan render setelah pengurutan field resmi.
- Snapshot baru menambahkan `document_layout_version = 2026-10-11` dan `cover_name` dari identitas proposal Bab 1. Metadata masuk hash snapshot terenkripsi, tanpa kolom database baru atau metadata akun.
- `captureForDocument` merekonstruksi hash berdasarkan versi metadata artefak. Dokumen tanpa versi memakai bentuk snapshot legacy persis seperti sebelumnya; versi tak dikenal ditolak. Controller download, wizard dan portal memakai resolver yang sama.
- Generate pada draft lengkap membuat artefak baru jika versi layout berbeda. Retry versi/input yang sama idempotent; row/binary lama tidak ditimpa. Submitted/locked mengikuti revisi existing, tanpa regenerate otomatis.
- Checkpoint akun/approval/owner/lock, manifest, private storage, audit dan pemeriksaan kembali setelah render dipertahankan. Kuota dan validitas ilmiah pasut/GMRT tidak berubah.

## Alternatives Considered

- Converter DOCX ke PDF sistem: membutuhkan kemampuan hosting yang tidak tersedia.
- Menimpa cache/file lama: merusak identitas immutable, audit dan integritas historis.
- Mengubah `template_version` proposal atau migration seluruh data: mencampurkan layout visual dengan versi template/form dan mengubah hash historis tanpa kebutuhan.
- Hanya CSS PDF: tidak memperbaiki DOCX atau struktur paragraf/HTML sumber.

## Consequences

Dokumen baru konsisten dan dapat disunting melalui styles Word. Bab lanjutan menyertakan nama sampul dari snapshot Bab 1; perubahan nama membuat dokumen baru stale sebagaimana perubahan konten. Dokumen lama tetap memakai aturan integritas sebelumnya dan dapat diunduh owner pending.

Word/PDF dapat berbeda jumlah halaman karena engine/font berbeda. Browser `docx-preview` memeriksa styles dan pemisahan bagian, tetapi tidak menggantikan pagination native Microsoft Word. Smalot terbatas membaca teks positioned pada baris justified; pemeriksaan visual PDF.js pada fixture sintetis melengkapi tes posisi/isi PDF.

Struktur empat part Word divalidasi terhadap XSD transitional ECMA-376 melalui tool pengembangan lokal. Urutan properti, gutter dan atribut shading mengikuti schema resmi. Validasi schema tidak menggantikan pemeriksaan pagination native Word dan tidak menambah kebutuhan instalasi hosting.

Referensi: [OpenXML KeepNext](https://learn.microsoft.com/en-us/dotnet/api/documentformat.openxml.wordprocessing.keepnext?view=openxml-3.0.1), [batas pagination tabel Dompdf](https://dompdf.github.io/), [docx-preview](https://github.com/VolodymyrBaydalka/docxjs), [ECMA-376](https://ecma-international.org/publications-and-standards/standards/ecma-376/). [Spesifikasi layout](../kkprl-document-layout-spec.md); [hasil validasi](../kkprl-document-layout-validation.md).
