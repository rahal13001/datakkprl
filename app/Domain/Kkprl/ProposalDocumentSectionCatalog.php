<?php

namespace App\Domain\Kkprl;

final class ProposalDocumentSectionCatalog
{
    /** @var array<string, list<string>> */
    private const SECTION_ORDER = [
        'bag-1' => [
            'INFORMASI PEMOHON',
            'RENCANA KEGIATAN',
            'JENIS KEGIATAN',
            'RENCANA TAPAK/ SITE PLAN',
            'PETA LOKASI',
        ],
        'bag-2' => ['INFORMASI PEMANFAATAN RUANG LAUT EKSISTING'],
        'bag-3' => [
            'Ekosistem Sekitar',
            'Hidro-Oseanografi',
            'Karakteristik Sosial Ekonomi Masyarakat',
            'Aksesibilitas Lokasi',
        ],
        'bag-4' => [
            'RENCANA PENGAMBILAN SUMBER MATERIAL REKLAMASI',
            'RENCANA PEMANFAATAN LAHAN REKLAMASI',
            'GAMBARAN UMUM PELAKSANAAN REKLAMASI',
            'JADWAL RENCANA PELAKSANAAN REKLAMASI',
        ],
        'bag-5' => [
            'BUKTI KEPEMILIKAN/PENGUASAAN LAHAN DARAT',
            'PERIZINAN YANG TELAH DIMILIKI',
        ],
    ];

    /** @var list<string> */
    private const NUMBERED_CHAPTERS = ['bag-1', 'bag-3', 'bag-4', 'bag-5'];

    /** @var array<string, array<string, string>> */
    private const SECTIONS = [
        'bag-1' => [
            'applicant_name' => 'INFORMASI PEMOHON',
            'applicant_position' => 'INFORMASI PEMOHON',
            'institution_name' => 'INFORMASI PEMOHON',
            'address' => 'INFORMASI PEMOHON',
            'identity_number' => 'INFORMASI PEMOHON',
            'tax_number' => 'INFORMASI PEMOHON',
            'phone_alternative' => 'INFORMASI PEMOHON',
            'email' => 'INFORMASI PEMOHON',
            'main_activity' => 'RENCANA KEGIATAN',
            'supporting_activity' => 'RENCANA KEGIATAN',
            'activity_status' => 'RENCANA KEGIATAN',
            'schedule' => 'RENCANA KEGIATAN',
            'other_matters' => 'RENCANA KEGIATAN',
            'workforce_male' => 'RENCANA KEGIATAN',
            'workforce_female' => 'RENCANA KEGIATAN',
            'investment_value' => 'RENCANA KEGIATAN',
            'investment_unit' => 'RENCANA KEGIATAN',
            'business_status' => 'JENIS KEGIATAN',
            'business_field' => 'JENIS KEGIATAN',
            'strategic_status' => 'JENIS KEGIATAN',
            'document_type' => 'JENIS KEGIATAN',
            'includes_reclamation' => 'JENIS KEGIATAN',
            'land_relation' => 'JENIS KEGIATAN',
            'has_existing_permits' => 'JENIS KEGIATAN',
            'site_plan_description' => 'RENCANA TAPAK/ SITE PLAN',
            'province' => 'PETA LOKASI',
            'regency' => 'PETA LOKASI',
            'subdistrict' => 'PETA LOKASI',
            'village' => 'PETA LOKASI',
            'water_name' => 'PETA LOKASI',
            'latitude' => 'PETA LOKASI',
            'longitude' => 'PETA LOKASI',
            'water_area' => 'PETA LOKASI',
            'water_area_unit' => 'PETA LOKASI',
            'depth' => 'PETA LOKASI',
            'depth_unit' => 'PETA LOKASI',
        ],
        'bag-2' => [
            'marine_space_use_narrative' => 'INFORMASI PEMANFAATAN RUANG LAUT EKSISTING',
        ],
        'bag-3' => [
            'mangrove' => 'Ekosistem Sekitar',
            'seagrass' => 'Ekosistem Sekitar',
            'coral_reef' => 'Ekosistem Sekitar',
            'current' => 'Hidro-Oseanografi',
            'waves' => 'Hidro-Oseanografi',
            'tide' => 'Hidro-Oseanografi',
            'bathymetry' => 'Hidro-Oseanografi',
            'seabed_profile' => 'Hidro-Oseanografi',
            'socio_economic' => 'Karakteristik Sosial Ekonomi Masyarakat',
            'accessibility' => 'Aksesibilitas Lokasi',
        ],
        'bag-4' => [
            'material_source_location' => 'RENCANA PENGAMBILAN SUMBER MATERIAL REKLAMASI',
            'material_image' => 'RENCANA PENGAMBILAN SUMBER MATERIAL REKLAMASI',
            'material_distance' => 'RENCANA PENGAMBILAN SUMBER MATERIAL REKLAMASI',
            'material_distance_unit' => 'RENCANA PENGAMBILAN SUMBER MATERIAL REKLAMASI',
            'material_volume' => 'RENCANA PENGAMBILAN SUMBER MATERIAL REKLAMASI',
            'material_volume_unit' => 'RENCANA PENGAMBILAN SUMBER MATERIAL REKLAMASI',
            'material_method' => 'RENCANA PENGAMBILAN SUMBER MATERIAL REKLAMASI',
            'reclaimed_land_plan' => 'RENCANA PEMANFAATAN LAHAN REKLAMASI',
            'reclaimed_land_map' => 'RENCANA PEMANFAATAN LAHAN REKLAMASI',
            'reclaimed_land_area' => 'RENCANA PEMANFAATAN LAHAN REKLAMASI',
            'reclaimed_land_area_unit' => 'RENCANA PEMANFAATAN LAHAN REKLAMASI',
            'reclamation_method' => 'GAMBARAN UMUM PELAKSANAAN REKLAMASI',
            'reclamation_schedule' => 'JADWAL RENCANA PELAKSANAAN REKLAMASI',
            'reclamation_schedule_rows' => 'JADWAL RENCANA PELAKSANAAN REKLAMASI',
        ],
        'bag-5' => [
            'land_status' => 'BUKTI KEPEMILIKAN/PENGUASAAN LAHAN DARAT',
            'land_evidence_type' => 'BUKTI KEPEMILIKAN/PENGUASAAN LAHAN DARAT',
            'land_acquisition_year' => 'BUKTI KEPEMILIKAN/PENGUASAAN LAHAN DARAT',
            'land_document' => 'BUKTI KEPEMILIKAN/PENGUASAAN LAHAN DARAT',
            'land_notes' => 'BUKTI KEPEMILIKAN/PENGUASAAN LAHAN DARAT',
            'permit_type' => 'PERIZINAN YANG TELAH DIMILIKI',
            'permit_number' => 'PERIZINAN YANG TELAH DIMILIKI',
            'permit_issuer' => 'PERIZINAN YANG TELAH DIMILIKI',
            'permit_year' => 'PERIZINAN YANG TELAH DIMILIKI',
            'permit_validity' => 'PERIZINAN YANG TELAH DIMILIKI',
            'permit_status' => 'PERIZINAN YANG TELAH DIMILIKI',
            'permit_document' => 'PERIZINAN YANG TELAH DIMILIKI',
            'permit_notes' => 'PERIZINAN YANG TELAH DIMILIKI',
        ],
    ];

    public static function label(string $chapter, string $field): ?string
    {
        return self::SECTIONS[$chapter][$field] ?? null;
    }

    public static function heading(string $chapter, string $field): ?string
    {
        $label = self::label($chapter, $field);
        if ($label === null || ! in_array($chapter, self::NUMBERED_CHAPTERS, true)) {
            return $label;
        }

        $position = array_search($label, self::SECTION_ORDER[$chapter] ?? [], true);

        return $position === false
            ? $label
            : self::roman((int) $position + 1).'. '.$label;
    }

    /** @param list<string> $fields @return list<string> */
    public static function orderedFields(string $chapter, array $fields): array
    {
        $sectionOrder = self::SECTION_ORDER[$chapter] ?? [];

        $positions = array_flip($sectionOrder);
        $originalPositions = array_flip($fields);

        usort($fields, function (string $left, string $right) use ($chapter, $positions, $originalPositions): int {
            $leftPosition = $positions[self::label($chapter, $left) ?? ''] ?? PHP_INT_MAX;
            $rightPosition = $positions[self::label($chapter, $right) ?? ''] ?? PHP_INT_MAX;

            return [$leftPosition, $originalPositions[$left]] <=> [$rightPosition, $originalPositions[$right]];
        });

        return $fields;
    }

    private static function roman(int $number): string
    {
        $map = [
            10 => 'X',
            9 => 'IX',
            5 => 'V',
            4 => 'IV',
            1 => 'I',
        ];
        $roman = '';

        foreach ($map as $value => $symbol) {
            while ($number >= $value) {
                $roman .= $symbol;
                $number -= $value;
            }
        }

        return $roman;
    }
}
