import re

with open('docs/kkprl-proposal-builder-spec.md', 'r', encoding='utf-8') as f:
    content = f.read()

# Update Status Dokumen
content = content.replace(
    "- Status: Draft untuk review sebelum implementasi",
    "- Status: Disetujui (Updated with AI Integration Plan)"
)

# Insert in Capability Map
cap_map_target = "| `proposal-review` | Daftar, detail, filter, dan peninjauan petugas di Filament | `proposal-draft`, `proposal-document` |"
cap_map_replacement = cap_map_target + "\n| `proposal-ai-chat` | Asisten AI (Gemini Flash) per bab untuk tanya jawab, ekstraksi payload JSON, baca lampiran non-sensitif | `proposal-draft` |"
content = content.replace(cap_map_target, cap_map_replacement)

# Update Urutan build
content = content.replace(
    "Urutan build: `proposal-access` → `proposal-draft` → `proposal-form` → `proposal-document` → `proposal-review`.",
    "Urutan build: `proposal-access` → `proposal-draft` → `proposal-form` (Bab 1) & `proposal-ai-chat` (Bab 2-5) → `proposal-document` → `proposal-review`."
)

# Update Keputusan Produk
keputusan_target = "13. Dokumen dapat diekspor dan diunduh per bab, bukan hanya sebagai satu file gabungan."
keputusan_replacement = keputusan_target + """
14. **Integrasi AI:** Pengisian Bab 2, 3, 4, dan 5 dibantu oleh AI Agent (Gemini Flash) dengan antarmuka Chatbot (Blended UI).
15. **Pemisahan Data Sensitif:** Bab 1 (Identitas, NIK, NPWP) murni form statis tanpa campur tangan AI.
16. **Otorisasi AI:** AI hanya menyarankan *draft payload* JSON. Pengguna wajib menekan konfirmasi sebelum AI menyimpan data ke database.
17. **AI Vision:** Lampiran non-sensitif (Peta, Foto) dapat dianalisis AI untuk membantu melengkapi narasi, lampiran sensitif (KTP) *bypass* AI.
18. **Persistensi Chat:** Riwayat percakapan AI disimpan agar pengguna dapat melanjutkan sesi sebelumnya tanpa mengulang."""
content = content.replace(keputusan_target, keputusan_replacement)

# Update Model Data - kkprl_proposals
model_target = "- `created_at`, `updated_at`."
model_replacement = model_target + "\n\n### `kkprl_proposal_chats`\nTabel baru untuk menyimpan persistensi obrolan AI per proposal/bab:\n- `id`.\n- `proposal_id`.\n- `chapter` (bag-2, bag-3, dst).\n- `messages` (JSON/Array berisi riwayat pesan pengguna & AI).\n- `last_interaction_at`.\n- `created_at`, `updated_at`."
content = content.replace(model_target, model_replacement)

# Add AI Integration Section at the end before Referensi
ai_section = """
## 18. Integrasi AI Agent (Gemini Flash)

Fitur pengisian form statis untuk Bab 2, 3, 4, dan 5 digantikan dengan **Blended UI (Chatbot + Preview Card)**.
- **AI Engine:** Gemini Flash 1.5/2.0 via API. Dipilih karena biaya murah, *context window* besar, dan *native vision*.
- **Blended UI:** Layar dibagi menjadi porsi Obrolan (Chat) dan Kartu Pratinjau (Preview). AI mewawancarai pengguna, mengekstrak jawaban menjadi JSON, lalu meng-update Kartu Pratinjau.
- **Konfirmasi Manual (Anti-Halusinasi):** AI tidak menyimpan data secara diam-diam. Pengguna harus mengklik "Konfirmasi & Simpan" pada Kartu Pratinjau agar data masuk ke `payload` di tabel `kkprl_proposals`.
- **Klasifikasi Lampiran:** Saat pengguna upload file, sistem memilah:
  - *Sensitive* (KTP, NIB): Disimpan via `KkprlProposalAttachmentService` murni, AI tidak memiliki akses.
  - *Non-Sensitive* (Foto Ekosistem, Peta): Dikirim ke API Gemini Vision bersama prompt untuk mendeskripsikan kondisi lokasi, hasilnya ditambahkan ke Kartu Pratinjau.
"""
content = content.replace("## 18. Pertanyaan Terbuka", ai_section + "\n## 19. Pertanyaan Terbuka")
content = content.replace("## 19. Referensi", "## 20. Referensi")

with open('docs/kkprl-proposal-builder-spec.md', 'w', encoding='utf-8') as f:
    f.write(content)

print("Spec updated successfully!")
