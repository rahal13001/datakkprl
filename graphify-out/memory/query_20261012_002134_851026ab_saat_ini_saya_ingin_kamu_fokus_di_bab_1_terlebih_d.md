---
type: "query"
date: "2026-10-12T00:21:34.521707+00:00"
question: "saat ini saya ingin kamu fokus di bab 1 terlebih dahulu, di bab 1 idealnya ada peta namun dari hasil ekspor document yang saya cek belum ada peta, saya ingin bertanya dulu kepadamu apakah ekspor peta bisa dibuat ? contoh peta yang sudah pernah saya buat dengan arcgis ada di file C:\\laragon\\www\\listdatakkprl\\docs\\sampel_peta tidak harus sama percis komponen yang tidak ada seperti logo itu bisa dinamis atau bisa dihilangkan karena bisa jadi user tidak memiliki logo, kemudian untuk garis pantai tidak perlu ada karena master datanya tidak saya upload di hosting atau database, kemudian untuk sumber bisa dinamis jika user beri sumber bisa disampaikan jika tidak hilangkan saja sumbernya apakah kamu bisa melakukan itu, silahkan tanya pada saya jika kamu bingung"
contributor: "graphify"
outcome: "useful"
source_nodes: ["KkprlProposalSnapshotService", "KkprlProposalDocxRenderer", "KkprlBathymetryMapRenderer"]
---

# Q: saat ini saya ingin kamu fokus di bab 1 terlebih dahulu, di bab 1 idealnya ada peta namun dari hasil ekspor document yang saya cek belum ada peta, saya ingin bertanya dulu kepadamu apakah ekspor peta bisa dibuat ? contoh peta yang sudah pernah saya buat dengan arcgis ada di file C:\laragon\www\listdatakkprl\docs\sampel_peta tidak harus sama percis komponen yang tidak ada seperti logo itu bisa dinamis atau bisa dihilangkan karena bisa jadi user tidak memiliki logo, kemudian untuk garis pantai tidak perlu ada karena master datanya tidak saya upload di hosting atau database, kemudian untuk sumber bisa dinamis jika user beri sumber bisa disampaikan jika tidak hilangkan saja sumbernya apakah kamu bisa melakukan itu, silahkan tanya pada saya jika kamu bingung

## Answer

Expanded graph vocabulary: coordinates snapshot attachment document renderer map bathymetry. Query located the snapshot, document renderers and existing GD bathymetry renderer; actual code verifies the current Bab 1 Leaflet/OpenStreetMap view stores coordinates but has no automatic static location-map export. Proposal only: deterministic map image from validated coordinates, optional logo and user source, no custom shoreline overlay, embedded in Word/PDF through immutable private snapshots. Basemap provider/rights and landscape versus portrait await user preferences. No feature implementation or test claims; main graph artifacts not rebuilt. Private sample content not indexed.

## Outcome

- Signal: useful

## Source Nodes

- KkprlProposalSnapshotService
- KkprlProposalDocxRenderer
- KkprlBathymetryMapRenderer