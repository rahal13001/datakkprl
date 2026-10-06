<?php

namespace App\Domain\Kkprl;

final class ProposalEvidenceCatalog
{
    public static function fields(): array
    {
        return [
            'mangrove' => ['label' => 'Mangrove (hutan bakau)', 'source' => 'gmw', 'guide' => 'Aktifkan lapisan sebaran mangrove dan pilih tahun data yang tersedia.'],
            'seagrass' => ['label' => 'Lamun (padang rumput laut)', 'source' => 'aca', 'guide' => 'Aktifkan peta habitat dasar laut dan legenda kelas seagrass/lamun. Cakupan Atlas terbatas pada wilayah terumbu yang dipetakan.'],
            'coral_reef' => ['label' => 'Terumbu karang', 'source' => 'aca', 'guide' => 'Aktifkan peta habitat dasar laut dan legenda coral/algae. Kelas ini tidak menunjukkan kesehatan atau jenis karang.'],
            'current' => ['label' => 'Arus laut', 'source' => 'copernicus', 'guide' => 'Pilih produk arus laut, kedalaman dan tanggal yang sesuai. Sertakan arah, satuan kecepatan dan legenda.'],
            'waves' => ['label' => 'Gelombang', 'source' => 'copernicus', 'guide' => 'Pilih produk gelombang dan tanggal yang sesuai. Sertakan satuan tinggi gelombang serta legenda.'],
            'tide' => ['label' => 'Pasang surut', 'source' => 'big', 'guide' => 'Pilih stasiun terdekat, lalu tampilkan grafik pasang surut dengan nama stasiun, tanggal, satuan dan acuan tinggi.'],
            'bathymetry' => ['label' => 'Batimetri (kedalaman laut)', 'source' => 'gebco', 'guide' => 'Buka peta kedalaman di lokasi usaha. Sertakan legenda, satuan, skala dan versi data.'],
            'seabed_profile' => ['label' => 'Profil dasar laut', 'source' => 'gebco', 'guide' => 'Gunakan gambar relief atau grafik penampang kedalaman yang memang tersedia. Peta kedalaman saja tidak membuktikan jenis pasir, lumpur atau batuan.'],
        ];
    }

    public static function sources(): array
    {
        return [
            'gmw' => ['label' => 'Global Mangrove Watch', 'url' => 'https://www.globalmangrovewatch.org/', 'credit' => 'Global Mangrove Watch', 'notice' => 'Akses peta gratis. Gunakan kredit dan ketentuan dataset sesuai versi yang dipilih.'],
            'aca' => ['label' => 'Allen Coral Atlas', 'url' => 'https://allencoralatlas.org/atlas/', 'credit' => 'Allen Coral Atlas Partnership dan Arizona State University', 'notice' => 'FAQ menyebut lisensi CC BY 4.0 untuk habitat, tetapi ketentuan situs membatasi penggunaan komersial tanpa izin tertulis. Untuk gambar Atlas dalam proposal usaha, pastikan izin tersebut tersedia. Citra Planet juga dibatasi nonkomersial. Alternatif: foto sendiri atau sumber lain yang mengizinkan penggunaan proposal.', 'terms_url' => 'https://allencoralatlas.org/tou/'],
            'copernicus' => ['label' => 'Copernicus Marine', 'url' => 'https://data.marine.copernicus.eu/viewer', 'credit' => 'E.U. Copernicus Marine Service Information', 'notice' => 'Data gratis dengan atribusi; beberapa layanan memerlukan akun. Resolusi produk regional tidak selalu menggambarkan kondisi tepat di keramba atau pantai.'],
            'big' => ['label' => 'BIG — Stasiun Pasang Surut', 'url' => 'https://srgi.big.go.id/tides', 'credit' => 'Badan Informasi Geospasial', 'notice' => 'Portal memerlukan login dan persetujuan ketentuan penggunaan. Akses data lebih dari tiga hari diarahkan untuk menghubungi BIG. Jika tidak tersedia, gunakan sumber lain yang mengizinkan penggunaannya.'],
            'gebco' => ['label' => 'GEBCO', 'url' => 'https://download.gebco.net/', 'credit' => 'GEBCO Compilation Group', 'notice' => 'Grid GEBCO tersedia bebas dengan pengakuan sumber. Data global merupakan gambaran umum dan tidak menggantikan pengukuran kedalaman setempat.'],
            'field_photo' => ['label' => 'Foto atau pengamatan lapangan sendiri', 'url' => '', 'credit' => 'Dokumentasi pelaku usaha', 'notice' => 'Tuliskan tanggal, lokasi dan keterangan foto. Foto permukaan tidak membuktikan kedalaman atau kondisi seluruh dasar laut.'],
            'other' => ['label' => 'Sumber lain yang boleh digunakan', 'url' => '', 'credit' => '', 'notice' => 'Cantumkan nama sumber, tautan dan kredit. Pilih gambar yang izin penggunaannya mencakup proposal Anda.'],
        ];
    }
}
