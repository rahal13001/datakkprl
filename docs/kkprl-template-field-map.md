# Mapping template Proposal KKPRL ke field aplikasi

Mapping ini adalah baseline MVP hasil pembacaan XML/text dari template DOCX pada `docs/template/`. Field yang tidak aktif tidak dirender ke form maupun output.

## Kelengkapan Bab 1 — 8 Oktober 2026

Sumber executable: `ProposalFieldCatalog::requiredFields('bag-1')` dan `ProposalProgress`. Jumlah tetap 36; perubahan ini menyediakan input `depth_unit` yang sebelumnya hilang, bukan menambah persyaratan. Label `document_type` diselaraskan menjadi jenis dokumen permohonan PKKPRL/KKRL, sesuai katalog/template; nilai lama tidak dikonversi.

| Kelompok | Jumlah | Isian wajib |
|---|---:|---|
| Identitas | 8 | Nama, jabatan, instansi, alamat, nomor KTP, NPWP, telepon/fax, email |
| Lokasi, dimensi, koordinat | 11 | Provinsi, kabupaten/kota, kecamatan, desa/kelurahan, nama perairan, bentuk area, daftar koordinat, luas/panjang, satuan luas/panjang, kedalaman, satuan kedalaman/datum |
| Rencana kegiatan | 5 | Kegiatan utama, penunjang, kegiatan/status eksisting, jadwal, keterangan tambahan |
| Profil usaha/investasi | 8 | Pekerja pria, pekerja wanita, nilai investasi, mata uang, sifat usaha, bidang usaha, status strategis, jenis dokumen permohonan |
| Rencana tapak | 1 | Narasi rencana tapak/site plan dengan teks yang tidak kosong |
| Kondisi | 3 | Reklamasi, hubungan daratan, perizinan pendukung |

Koordinat dihitung sebagai satu field wajib; jumlah titik dan rentangnya diperiksa terpisah sesuai bentuk area. `coordinates_raw` adalah state bantu dan bukan isian wajib tambahan. File lampiran tidak masuk hitungan 36. Nilai 0 dan false bukan field kosong. Kelengkapan Bab 1 berarti semua field wajib terisi tanpa error koordinat; persentase keseluruhan menggabungkan seluruh bab aktif. Uraian yang tidak relevan dapat menyatakan **Tidak berlaku**; ini tidak menggantikan kewajiban koordinat valid.

Tabel baseline di bawah mempertahankan sejarah mapping awal `latitude`/`longitude`; input lokasi aktual memakai `shape_type`, `coordinates`, dan state bantu `coordinates_raw`.

Pengukuran peta (ADR-028) membaca koordinat/bentuk serta membandingkan hasil dengan `water_area`/`water_area_unit`: luas Polygon ha atau panjang Line km, dua desimal. Hasil computed tidak menambah field wajib/payload/template, tidak mengganti isian pengguna dan tidak masuk snapshot dokumen. Warning menunjukkan selisih setelah pembulatan atau satuan yang belum cocok; aturan kelengkapan katalog tetap sama.

## Mapping baseline template

| Template | Section | Field aplikasi |
|---|---|---|
| Bag 1 — Rencana Bangunan dan Instalasi Laut | Identitas pemohon/instansi | `applicant_name`, `applicant_position`, `institution_name`, `address`, `identity_number`, `tax_number`, `phone_alternative`, `email` |
| Bag 1 | Lokasi dan rencana kegiatan | `province`, `regency`, `subdistrict`, `village`, `water_name`, `latitude`, `longitude`, `water_area`, `water_area_unit`, `depth`, `depth_unit`, `main_activity`, `supporting_activity`, `activity_status`, `schedule`, `other_matters`, `site_plan_description` |
| Bag 1 | Profil usaha/investasi | `workforce_male`, `workforce_female`, `investment_value`, `investment_unit`, `business_status`, `business_field`, `strategic_status`, `document_type` |
| Bag 2 — Informasi Pemanfaatan Ruang Laut | Narasi pemanfaatan | `marine_space_use_narrative` |
| Bag 3 — Kondisi Terkini | Ekosistem | `mangrove`, `seagrass`, `coral_reef` |
| Bag 3 | Hidro-oseanografi dan lingkungan | `current`, `waves`, `tide`, `bathymetry`, `seabed_profile`, `socio_economic`, `accessibility` |
| Bag 4 — Persyaratan Reklamasi | Material dan metode | `material_source_location`, `material_image`, `material_distance`, `material_distance_unit`, `material_volume`, `material_volume_unit`, `material_method` |
| Bag 4 | Lahan reklamasi dan jadwal | `reclaimed_land_plan`, `reclaimed_land_map`, `reclaimed_land_area`, `reclaimed_land_area_unit`, `reclamation_method`, `reclamation_schedule`, `reclamation_schedule_rows` |
| Bag 5 — Perizinan Lainnya | Lahan darat, bila berhimpitan | `land_status`, `land_evidence_type`, `land_acquisition_year`, `land_document`, `land_notes` |
| Bag 5 | Perizinan existing, bila dipilih | `permit_type`, `permit_number`, `permit_issuer`, `permit_year`, `permit_validity`, `permit_status`, `permit_document`, `permit_notes` |

## Template surat permohonan

`SURAT PERMOHONAN PERIZINAN NON BERUSAHA_KHUSUS SUBMIT MELALUI E-SEA KKP.docx` dibaca sebagai template surat pengantar, bukan bab proposal. Isinya mengulang identitas dan lokasi dari Bab 1 serta menambahkan nomor surat, jumlah lampiran, tanggal/tempat surat, tujuan surat, nama/jabatan penandatangan, dan meterai. Export surat pengantar tidak termasuk acceptance criterion export per bab MVP; karena itu template ini tidak dirender sebagai dokumen terpisah dan tidak menambah field baru yang belum disetujui. Saat fitur surat diaktifkan, field berikut perlu dikonfirmasi dan dipetakan secara eksplisit:

| Elemen template surat | Status MVP | Alasan/field lanjutan |
|---|---|---|
| Kop surat dan logo | Tidak dirender | Aset/identitas organisasi belum menjadi input pemohon; perlu konfigurasi organisasi. |
| Nomor surat, lampiran, tempat/tanggal | Tidak dirender | Metadata surat administratif, bukan data proposal; perlu workflow surat. |
| Hal dan tujuan surat | Tidak dirender | Mengikuti kanal resmi e-Sea/KKPRL; perlu keputusan bisnis. |
| Nama, jabatan, dan tanda tangan pemohon | Tidak dirender | Tanda tangan/meterai tidak termasuk MVP dan perlu kontrol legal. |
| Identitas, lokasi, koordinat, luas, kedalaman, kegiatan | Diwakili Bab 1 | Field aplikasi: `applicant_name`, `applicant_position`, `institution_name`, `address`, `identity_number`, `tax_number`, `phone_alternative`, `email`, `province`, `regency`, `subdistrict`, `village`, `water_name`, `latitude`, `longitude`, `water_area`, `depth`, `main_activity`. |

Kondisi keputusan disimpan sebagai metadata payload: `includes_reclamation`, `land_relation`, dan `has_existing_permits`. Pilihan `Tidak berlaku` adalah nilai eksplisit yang dapat menyelesaikan field ketika memang tidak relevan; sistem tidak mengambil keputusan perizinan dari nilai tersebut.

Format koordinat MVP ditetapkan pada [ADR-010](decisions/ADR-010-coordinate-validation.md): desimal WGS84 dengan rentang latitude -90..90 dan longitude -180..180.
