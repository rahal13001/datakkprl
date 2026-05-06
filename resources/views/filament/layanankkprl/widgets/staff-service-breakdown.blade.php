<div class="space-y-4">
    <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
        <div class="text-sm text-slate-500">Total aktivitas layanan</div>
        <div class="mt-1 text-2xl font-semibold text-slate-900">{{ $totalServiceActivities }}</div>
    </div>

    <div class="overflow-hidden rounded-xl border border-slate-200">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold text-slate-700">Jenis layanan</th>
                    <th class="px-4 py-3 text-right font-semibold text-slate-700">Jumlah</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 bg-white">
                @forelse ($breakdown as $item)
                    <tr>
                        <td class="px-4 py-3 text-slate-700">{{ $item['name'] }}</td>
                        <td class="px-4 py-3 text-right font-medium text-slate-900">{{ $item['count'] }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="2" class="px-4 py-6 text-center text-slate-500">
                            Belum ada data layanan untuk petugas ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
