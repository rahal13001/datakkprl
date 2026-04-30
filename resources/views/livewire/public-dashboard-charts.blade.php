<div class="grid gap-6 lg:grid-cols-2">
    <div x-data="{ open: false }" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <h3 class="text-base font-semibold text-slate-900">Berdasarkan Jenis Layanan</h3>
        <button @click="open = !open" type="button" class="mt-3 inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-semibold uppercase tracking-wider text-slate-600">
            <span x-text="open ? 'Sembunyikan Filter' : 'Tampilkan Filter'"></span>
        </button>
        <div x-show="open" x-collapse class="mt-4 grid gap-3 sm:grid-cols-2">
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wider text-slate-500">Lokasi</label>
                <select wire:model.live="serviceTypeFilters.location_id" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm">
                    <option value="">Semua Lokasi</option>
                    @foreach($locations as $location)
                        <option value="{{ $location->id }}">{{ $location->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wider text-slate-500">Sifat</label>
                <select wire:model.live="serviceTypeFilters.activity_type" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm">
                    <option value="">Semua Sifat</option>
                    <option value="non_business">Non Berusaha</option>
                    <option value="business">Berusaha</option>
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wider text-slate-500">Dari</label>
                <input wire:model.live="serviceTypeFilters.start_date" type="date" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wider text-slate-500">Sampai</label>
                <input wire:model.live="serviceTypeFilters.end_date" type="date" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm">
            </div>
        </div>
        <div class="mt-4 h-[320px]" wire:key="chart-service-type-{{ md5(json_encode($this->serviceTypeChartConfig)) }}" x-data="landingChart(@js($this->serviceTypeChartConfig))" x-init="init()">
            <canvas x-ref="canvas"></canvas>
        </div>
    </div>

    <div x-data="{ open: false }" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <h3 class="text-base font-semibold text-slate-900">Berdasarkan Lokasi Konsultasi</h3>
        <button @click="open = !open" type="button" class="mt-3 inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-semibold uppercase tracking-wider text-slate-600">
            <span x-text="open ? 'Sembunyikan Filter' : 'Tampilkan Filter'"></span>
        </button>
        <div x-show="open" x-collapse class="mt-4 grid gap-3 sm:grid-cols-2">
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wider text-slate-500">Layanan</label>
                <select wire:model.live="serviceLocationFilters.service_id" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm">
                    <option value="">Semua Layanan</option>
                    @foreach($services as $service)
                        <option value="{{ $service->id }}">{{ $service->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wider text-slate-500">Sifat</label>
                <select wire:model.live="serviceLocationFilters.activity_type" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm">
                    <option value="">Semua Sifat</option>
                    <option value="non_business">Non Berusaha</option>
                    <option value="business">Berusaha</option>
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wider text-slate-500">Dari</label>
                <input wire:model.live="serviceLocationFilters.start_date" type="date" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wider text-slate-500">Sampai</label>
                <input wire:model.live="serviceLocationFilters.end_date" type="date" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm">
            </div>
        </div>
        <div class="mt-4 h-[320px]" wire:key="chart-service-location-{{ md5(json_encode($this->serviceLocationChartConfig)) }}" x-data="landingChart(@js($this->serviceLocationChartConfig))" x-init="init()">
            <canvas x-ref="canvas"></canvas>
        </div>
    </div>

    <div x-data="{ open: false }" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <h3 class="text-base font-semibold text-slate-900">Berdasarkan Sifat Kegiatan</h3>
        <button @click="open = !open" type="button" class="mt-3 inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-semibold uppercase tracking-wider text-slate-600">
            <span x-text="open ? 'Sembunyikan Filter' : 'Tampilkan Filter'"></span>
        </button>
        <div x-show="open" x-collapse class="mt-4 grid gap-3 sm:grid-cols-2">
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wider text-slate-500">Layanan</label>
                <select wire:model.live="activityTypeFilters.service_id" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm">
                    <option value="">Semua Layanan</option>
                    @foreach($services as $service)
                        <option value="{{ $service->id }}">{{ $service->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wider text-slate-500">Lokasi</label>
                <select wire:model.live="activityTypeFilters.location_id" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm">
                    <option value="">Semua Lokasi</option>
                    @foreach($locations as $location)
                        <option value="{{ $location->id }}">{{ $location->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wider text-slate-500">Dari</label>
                <input wire:model.live="activityTypeFilters.start_date" type="date" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wider text-slate-500">Sampai</label>
                <input wire:model.live="activityTypeFilters.end_date" type="date" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm">
            </div>
        </div>
        <div class="mt-4 h-[320px]" wire:key="chart-activity-type-{{ md5(json_encode($this->activityTypeChartConfig)) }}" x-data="landingChart(@js($this->activityTypeChartConfig))" x-init="init()">
            <canvas x-ref="canvas"></canvas>
        </div>
    </div>

    <div x-data="{ open: false }" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <h3 class="text-base font-semibold text-slate-900">Tren Layanan Bulanan</h3>
        <button @click="open = !open" type="button" class="mt-3 inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-semibold uppercase tracking-wider text-slate-600">
            <span x-text="open ? 'Sembunyikan Filter' : 'Tampilkan Filter'"></span>
        </button>
        <div x-show="open" x-collapse class="mt-4 grid gap-3 sm:grid-cols-2">
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wider text-slate-500">Layanan</label>
                <select wire:model.live="monthlyFilters.service_id" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm">
                    <option value="">Semua Layanan</option>
                    @foreach($services as $service)
                        <option value="{{ $service->id }}">{{ $service->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wider text-slate-500">Lokasi</label>
                <select wire:model.live="monthlyFilters.location_id" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm">
                    <option value="">Semua Lokasi</option>
                    @foreach($locations as $location)
                        <option value="{{ $location->id }}">{{ $location->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wider text-slate-500">Sifat</label>
                <select wire:model.live="monthlyFilters.activity_type" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm">
                    <option value="">Semua Sifat</option>
                    <option value="non_business">Non Berusaha</option>
                    <option value="business">Berusaha</option>
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wider text-slate-500">Dari</label>
                <input wire:model.live="monthlyFilters.start_date" type="date" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm">
            </div>
            <div class="sm:col-span-2">
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wider text-slate-500">Sampai</label>
                <input wire:model.live="monthlyFilters.end_date" type="date" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm">
            </div>
        </div>
        <div class="mt-4 h-[320px]" wire:key="chart-monthly-service-{{ md5(json_encode($this->monthlyServiceChartConfig)) }}" x-data="landingChart(@js($this->monthlyServiceChartConfig))" x-init="init()">
            <canvas x-ref="canvas"></canvas>
        </div>
    </div>
</div>

@once
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        function landingChart(config) {
            return {
                chart: null,
                init() {
                    if (!window.Chart || !this.$refs.canvas) {
                        return;
                    }

                    this.chart = new window.Chart(this.$refs.canvas.getContext('2d'), config);
                },
            };
        }
    </script>
@endonce
