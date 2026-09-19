# ADR-010 — Format koordinat manual MVP

## Status

Accepted for MVP.

## Decision

Latitude dan longitude diisi sebagai angka desimal WGS84. Latitude harus berada pada -90 sampai 90; longitude pada -180 sampai 180. Nilai eksplisit `Tidak berlaku` tetap dapat digunakan jika lokasi memang tidak memiliki koordinat yang relevan.

## Rationale

Format desimal paling ringan untuk input HP dan tidak membutuhkan parser peta pada MVP. Sistem tetap menyimpan nilai apa adanya di payload terenkripsi; normalisasi atau konversi format lain dapat ditambahkan kemudian.

## Consequence

Submit menolak koordinat numerik di luar rentang. Validasi ini tidak menerbitkan atau memvalidasi izin resmi.
