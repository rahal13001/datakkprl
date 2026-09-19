<?php

namespace App\Domain\Kkprl;

final class ProposalFieldCatalog
{
    /** @var array<string, list<array{key: string, label: string}>> */
    private const DEFINITIONS = [
        'bag-1' => [
            ['key' => 'applicant_name', 'label' => 'Nama pemohon'],
            ['key' => 'applicant_position', 'label' => 'Jabatan'],
            ['key' => 'institution_name', 'label' => 'Nama instansi/perusahaan'],
            ['key' => 'address', 'label' => 'Alamat'],
            ['key' => 'identity_number', 'label' => 'Nomor KTP'],
            ['key' => 'tax_number', 'label' => 'NPWP'],
            ['key' => 'phone_alternative', 'label' => 'Nomor telepon/fax'],
            ['key' => 'email', 'label' => 'Email'],
            ['key' => 'province', 'label' => 'Provinsi'],
            ['key' => 'regency', 'label' => 'Kabupaten/kota'],
            ['key' => 'subdistrict', 'label' => 'Kecamatan'],
            ['key' => 'village', 'label' => 'Desa/kelurahan'],
            ['key' => 'water_name', 'label' => 'Nama perairan/laut'],
            ['key' => 'latitude', 'label' => 'Latitude'],
            ['key' => 'longitude', 'label' => 'Longitude'],
            ['key' => 'water_area', 'label' => 'Luas kebutuhan perairan'],
            ['key' => 'water_area_unit', 'label' => 'Satuan luas'],
            ['key' => 'depth', 'label' => 'Kedalaman kolom perairan'],
            ['key' => 'depth_unit', 'label' => 'Satuan kedalaman/datum'],
            ['key' => 'main_activity', 'label' => 'Kegiatan utama'],
            ['key' => 'supporting_activity', 'label' => 'Kegiatan penunjang'],
            ['key' => 'activity_status', 'label' => 'Kegiatan eksisting yang dimohonkan'],
            ['key' => 'schedule', 'label' => 'Jadwal pelaksanaan'],
            ['key' => 'other_matters', 'label' => 'Hal lain terkait permohonan'],
            ['key' => 'workforce_male', 'label' => 'Tenaga kerja laki-laki'],
            ['key' => 'workforce_female', 'label' => 'Tenaga kerja perempuan'],
            ['key' => 'investment_value', 'label' => 'Nilai investasi'],
            ['key' => 'investment_unit', 'label' => 'Satuan investasi'],
            ['key' => 'business_status', 'label' => 'Berusaha/nonberusaha'],
            ['key' => 'business_field', 'label' => 'Bidang kegiatan usaha'],
            ['key' => 'strategic_status', 'label' => 'Strategis nasional/nonstrategis nasional'],
            ['key' => 'document_type', 'label' => 'Jenis dokumen PKKPRL/KKRL'],
            ['key' => 'site_plan_description', 'label' => 'Narasi rencana tapak/site plan'],
            ['key' => 'includes_reclamation', 'label' => 'Pilihan reklamasi'],
            ['key' => 'land_relation', 'label' => 'Hubungan dengan daratan'],
            ['key' => 'has_existing_permits', 'label' => 'Perizinan pendukung'],
        ],
        'bag-2' => [
            ['key' => 'marine_space_use_narrative', 'label' => 'Narasi penggunaan ruang laut'],
        ],
        'bag-3' => [
            ['key' => 'mangrove', 'label' => 'Ekosistem mangrove'],
            ['key' => 'seagrass', 'label' => 'Ekosistem lamun'],
            ['key' => 'coral_reef', 'label' => 'Ekosistem terumbu karang'],
            ['key' => 'current', 'label' => 'Arus'],
            ['key' => 'waves', 'label' => 'Gelombang'],
            ['key' => 'tide', 'label' => 'Pasang surut'],
            ['key' => 'bathymetry', 'label' => 'Batimetri'],
            ['key' => 'seabed_profile', 'label' => 'Profil dasar laut'],
            ['key' => 'socio_economic', 'label' => 'Sosial ekonomi masyarakat'],
            ['key' => 'accessibility', 'label' => 'Aksesibilitas lokasi'],
        ],
        'bag-4' => [
            ['key' => 'material_source_location', 'label' => 'Lokasi sumber material'],
            ['key' => 'material_image', 'label' => 'Gambar lokasi material'],
            ['key' => 'material_distance', 'label' => 'Jarak sumber material'],
            ['key' => 'material_distance_unit', 'label' => 'Satuan jarak material'],
            ['key' => 'material_volume', 'label' => 'Jumlah/volume material'],
            ['key' => 'material_volume_unit', 'label' => 'Satuan volume material'],
            ['key' => 'material_method', 'label' => 'Metode pengambilan material'],
            ['key' => 'reclaimed_land_plan', 'label' => 'Rencana pemanfaatan lahan reklamasi'],
            ['key' => 'reclaimed_land_map', 'label' => 'Peta lahan reklamasi'],
            ['key' => 'reclaimed_land_area', 'label' => 'Luas lahan reklamasi'],
            ['key' => 'reclaimed_land_area_unit', 'label' => 'Satuan luas lahan reklamasi'],
            ['key' => 'reclamation_method', 'label' => 'Metode pelaksanaan reklamasi'],
            ['key' => 'reclamation_schedule', 'label' => 'Jadwal pelaksanaan reklamasi'],
            ['key' => 'reclamation_schedule_rows', 'label' => 'Tabel jadwal pelaksanaan'],
        ],
        'bag-5-land' => [
            ['key' => 'land_status', 'label' => 'Status berhimpitan dengan daratan'],
            ['key' => 'land_evidence_type', 'label' => 'Jenis bukti kepemilikan/penguasaan'],
            ['key' => 'land_acquisition_year', 'label' => 'Tahun perolehan'],
            ['key' => 'land_document', 'label' => 'Dokumen bukti lahan'],
            ['key' => 'land_notes', 'label' => 'Catatan lahan'],
        ],
        'bag-5-permits' => [
            ['key' => 'permit_type', 'label' => 'Jenis izin'],
            ['key' => 'permit_number', 'label' => 'Nomor izin'],
            ['key' => 'permit_issuer', 'label' => 'Instansi penerbit'],
            ['key' => 'permit_year', 'label' => 'Tahun perolehan'],
            ['key' => 'permit_validity', 'label' => 'Masa berlaku'],
            ['key' => 'permit_status', 'label' => 'Status izin'],
            ['key' => 'permit_document', 'label' => 'Dokumen izin'],
            ['key' => 'permit_notes', 'label' => 'Catatan izin'],
        ],
    ];

    /** @return list<string> */
    public static function fields(string $chapter): array
    {
        return array_column(self::DEFINITIONS[$chapter] ?? [], 'key');
    }

    /** @return list<array{key: string, label: string}> */
    public static function definitions(string $chapter): array
    {
        return self::DEFINITIONS[$chapter] ?? [];
    }

    /** @param array<string, mixed> $payload @return list<array{key: string, label: string}> */
    public static function definitionsForPayload(string $chapter, array $payload): array
    {
        if ($chapter !== 'bag-5') {
            return self::definitions($chapter);
        }

        $definitions = [];
        if (($payload['land_relation'] ?? null) === 'adjacent') {
            $definitions = array_merge($definitions, self::definitions('bag-5-land'));
        }
        if (in_array($payload['has_existing_permits'] ?? false, [true, 1, '1', 'true'], true)) {
            $definitions = array_merge($definitions, self::definitions('bag-5-permits'));
        }

        return $definitions;
    }

    /** @param array<string, mixed> $payload @return list<string> */
    public static function fieldsForPayload(string $chapter, array $payload): array
    {
        return array_column(self::definitionsForPayload($chapter, $payload), 'key');
    }

    public static function label(string $field): string
    {
        foreach (self::DEFINITIONS as $definitions) {
            foreach ($definitions as $definition) {
                if ($definition['key'] === $field) {
                    return $definition['label'];
                }
            }
        }

        return ucwords(str_replace('_', ' ', $field));
    }
}
