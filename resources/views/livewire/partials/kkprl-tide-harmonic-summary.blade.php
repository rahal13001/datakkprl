<div class="min-w-0 space-y-3 rounded-lg border border-blue-200 bg-white p-3">
    <h5 class="text-sm font-semibold text-slate-900">Estimasi Formzahl dan tipe pasut</h5>
    @if($harmonic['status'] === 'estimated')
        <p class="text-sm text-slate-800">F = {{ number_format($harmonic['formzahl'], 3, ',', '.') }} — {{ \App\Services\KkprlTideHarmonicAnalyzer::TYPE_LABELS[$harmonic['tidal_type']] }} (estimasi).</p>
        <p class="text-xs leading-relaxed text-slate-600">Nilai ini menggambarkan periode data yang diunggah. Amplitudo efektif belum memisahkan semua komponen pasut dan bukan konstanta harmonik resmi stasiun.</p>
    @else
        <p class="text-sm font-medium text-slate-800">Formzahl belum dapat ditetapkan.</p>
        @foreach($harmonic['reasons'] as $reason)
            <p class="text-sm leading-relaxed text-slate-700">{{ $reason }}</p>
        @endforeach
        <p class="text-xs text-slate-600">Statistik tinggi dan narasi awal tetap tersedia.</p>
    @endif
    @if($harmonic['rmse_m'] !== null)
        <p class="text-xs text-slate-600">RMSE model: {{ number_format($harmonic['rmse_m'], 4, ',', '.') }} m. R² pasut setelah pemisahan tren: {{ number_format($harmonic['tidal_r_squared'], 4, ',', '.') }}. Kecocokan model tidak menjamin ketepatan tipe pasut.</p>
    @endif
    @if($harmonic['constituents'] !== [])
        <details>
            <summary class="cursor-pointer text-xs font-semibold text-blue-700">Lihat amplitudo komponen dan metode</summary>
            <div class="mt-2 overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-700">
                    <caption class="mb-2 text-left text-slate-600">Amplitudo efektif dalam meter; dihitung dari seluruh sampel dengan kuadrat terkecil dan QR.</caption>
                    <thead><tr><th scope="col" class="p-2">Komponen</th><th scope="col" class="p-2">Amplitudo (m)</th></tr></thead>
                    <tbody>
                        @foreach($harmonic['constituents'] as $name => $component)
                            <tr><th scope="row" class="p-2 font-medium">{{ $name }}</th><td class="p-2">{{ number_format($component['amplitude_m'], 6, ',', '.') }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @foreach($harmonic['warnings'] as $warning)
                <p class="mt-2 text-xs leading-relaxed text-slate-600">{{ $warning }}</p>
            @endforeach
        </details>
    @endif
</div>
