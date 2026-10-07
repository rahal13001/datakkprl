# ADR-027 — State input, kelengkapan dan editor Bab 1

- Status: Accepted
- Tanggal: 2026-10-08
- Lingkup: perbaikan wizard Bab 1; melengkapi ADR penyuntingan draft tanpa mengganti ADR akun/approval maupun identitas snapshot.

## Konteks

Autosave dapat menimpa teks yang sedang diketik saat respons server mengubah urutan key payload atau mengubah array kosong menjadi object. Ini direproduksi pada Livewire 4.4.1 dengan respons terlambat. Sanitizer tetap diperlukan untuk membatasi data yang boleh disimpan. Proposal lama memakai payload flat; mengubah format storage tanpa edit isi dapat mengubah hash snapshot dokumen.

Katalog mewajibkan 36 isian Bab 1, tetapi satuan kedalaman/datum belum mempunyai input. Progress bar menggabungkan semua bab aktif. CKEditor memakai teks petunjuk elemen sumber sebagai isi dan mengirim perubahan setiap ketikan.

## Keputusan

Wizard menyiapkan struktur UI Bab 1 dengan key bernilai null sejak mount, mengubah payload flat menjadi chapter untuk editing, dan mempertahankan urutan key UI pada echo yang telah disanitasi. Null scaffold dibuang sebelum persist. Semua chapter legacy tetap dibawa; format storage flat yang sudah ada dipertahankan agar save tanpa edit isi tidak mengubah identitas dokumen. Metadata proposal diturunkan dari payload sanitized sesuai formatnya, sehingga nama/instansi/lokasi legacy tidak dikosongkan. Tidak ada migration atau penulisan payload saat mount. Payload input flat juga dinormalisasi sebelum sanitizer agar submit melalui jalur lama tidak kehilangan chapter.

Evaluator `ProposalProgress` mengembalikan daftar `missing` dari katalog wajib yang sama dengan submit/export. UI menampilkan jumlah, label isian kosong dan kesalahan koordinat. `depth_unit` mendapat input. Nilai 0 dan false tetap dihitung. Narasi site plan yang hanya berisi markup/spasi tidak dihitung. Persyaratan lain dan batas validitas ilmiah tidak diturunkan.

Editor draft baru kosong; petunjuk berada di luar konten atau placeholder. Toolbar dan tampilan disederhanakan. Perubahan narasi memperbarui state Livewire lokal melalui `$set(..., false)`, lalu dikirim saat blur atau action wizard. Instance editor dihancurkan ketika bagian dilepas; isi lama tidak dihapus atau ditulis ulang otomatis.

## Pilihan yang dipertimbangkan

- Hanya menjaga urutan key: uji browser draft baru membuktikan perubahan array menjadi object masih menimpa teks. Diperlukan scaffold UI.
- Menghapus autosave seluruh form: mengubah kebiasaan penyimpanan dan menambah risiko kehilangan isian ketika pengguna lupa menyimpan. Autosave blur tetap dipakai.
- Menyimpan scaffold atau memigrasikan semua payload menjadi nested: tidak diperlukan dan dapat mengubah identitas snapshot/dokumen lama. Perubahan dibatasi pada state UI.
- Mengganti editor/library: stack yang ada mencukupi. Tidak menambah dependency atau kebutuhan shared hosting.

## Konsekuensi dan batas

Mount/echo dan persist mempunyai representasi berbeda yang diuji eksplisit. Perbaikan melindungi input lokal pada alur autosave satu wizard; tidak menambahkan protokol konflik editing serentak dari beberapa tab/akun. Progress menunjukkan kelengkapan data aplikasi, bukan kepastian regulasi atau kualitas ilmiah. Authorization backend, approval, ownership, lock/revisi, quota, snapshot, audit dan private storage tetap wajib melalui service yang ada.

Lihat [spesifikasi](../kkprl-chapter-one-input-spec.md), [validasi](../kkprl-chapter-one-input-validation.md), dan [peta field](../kkprl-template-field-map.md).
