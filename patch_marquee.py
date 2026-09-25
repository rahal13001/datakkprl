import sys

with open('resources/views/livewire/landing-page.blade.php', 'r', encoding='utf-8') as f:
    lines = f.readlines()

new_lines = []
for line in lines:
    new_lines.append(line)
    if '<section id="home" class="landing-hero relative pt-32 lg:pt-40 overflow-hidden">' in line:
        new_lines.append("""
        @php
            // Mengambil pesan aktif dari database
            $runningTexts = [];
            try {
                if (class_exists(\App\Models\RunningText::class)) {
                    $runningTexts = \App\Models\RunningText::where('is_active', true)->orderBy('order_column')->pluck('message')->toArray();
                }
            } catch (\Exception $e) {
                // Abaikan jika tabel belum di-migrate
            }
            if (empty($runningTexts)) {
                $runningTexts = ['Selamat Datang, kami menghimbau kepada seluruh Pemrakarsa PKKPRL agar WASPADA terhadap potensi PENIPUAN, Layanan kami tidak dipungut biaya.'];
            }
            $runningTextString = implode(' &nbsp;&nbsp;&bull;&nbsp;&nbsp; ', $runningTexts);
        @endphp
        
        <div style="position: absolute; top: 90px; left: 0; width: 100%; background-color: #FFFF00; color: #FF0000; font-weight: bold; padding: 10px 0; font-size: 1.1rem; z-index: 40; display: flex; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <marquee behavior="scroll" direction="left" scrollamount="6">
                {!! $runningTextString !!}
            </marquee>
        </div>
""")

with open('resources/views/livewire/landing-page.blade.php', 'w', encoding='utf-8') as f:
    f.writelines(new_lines)
