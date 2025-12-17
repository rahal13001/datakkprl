<div class="bg-white rounded-3xl shadow-xl border border-slate-100 overflow-hidden relative">

    <!-- Success State -->
    @if($success)
        <div class="p-12 text-center">
            <div
                class="w-20 h-20 bg-green-100 text-green-600 rounded-full flex items-center justify-center mx-auto mb-6 text-3xl">
                <i class="fa-solid fa-check"></i>
            </div>
            <h3 class="text-2xl font-bold text-slate-900 mb-2">Permohonan Diterima!</h3>
            <p class="text-slate-500 max-w-md mx-auto mb-8">
                Tiket antrian dan detail jadwal telah dikirim ke email <strong>{{ $email }}</strong>. Silakan cek folder
                Spam jika tidak masuk Inbox.
            </p>
            <button type="button" onclick="window.location.reload()"
                class="px-6 py-3 bg-brand-black text-white rounded-xl font-medium">
                Kembali ke Halaman Utama
            </button>
        </div>
    @else

        <!-- Header / Progress -->
        <div class="bg-slate-50 border-b border-slate-100 p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-slate-900">Formulir Reservasi</h3>
                <span class="text-xs font-mono text-slate-400">STEP {{ $step }} / 3</span>
            </div>
            <!-- Progress Bar -->
            <div class="h-1 w-full bg-slate-200 rounded-full overflow-hidden">
                <div class="h-full bg-brand-blue transition-all duration-500" style="width: {{ ($step / 3) * 100 }}%"></div>
            </div>
        </div>

        <!-- Content -->
        <div class="p-6 lg:p-10 min-h-[400px]">

            <!-- Error Validation Global -->
            @if ($errors->any())
                <div class="mb-6 bg-red-50 border border-red-100 text-red-600 px-4 py-3 rounded-xl text-sm">
                    <ul class="list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if($errorMessage)
                <div class="mb-6 bg-red-50 border border-red-100 text-red-600 px-4 py-3 rounded-xl text-sm">
                    {{ $errorMessage }}
                </div>
            @endif

            <!-- STEP 1: IDENTITY -->
            @if($step === 1)
                <div class="space-y-6">
                    <!-- Booking Type -->
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Jenis Pemohon</label>
                        <div class="flex gap-4">
                            <label
                                class="flex items-center gap-2 cursor-pointer p-3 border rounded-xl hover:bg-slate-50 transition-colors {{ $booking_type == 'personal' ? 'border-brand-blue bg-blue-50/50' : 'border-slate-200' }}">
                                <input type="radio" wire:model.live="booking_type" value="personal"
                                    class="text-brand-blue focus:ring-brand-blue">
                                <span class="text-slate-700 font-medium">Perorangan</span>
                            </label>
                            <label
                                class="flex items-center gap-2 cursor-pointer p-3 border rounded-xl hover:bg-slate-50 transition-colors {{ $booking_type == 'company' ? 'border-brand-blue bg-blue-50/50' : 'border-slate-200' }}">
                                <input type="radio" wire:model.live="booking_type" value="company"
                                    class="text-brand-blue focus:ring-brand-blue">
                                <span class="text-slate-700 font-medium">Instansi / Perusahaan</span>
                            </label>
                        </div>
                    </div>

                    <div class="grid md:grid-cols-2 gap-6">
                        <!-- Name -->
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Nama Lengkap</label>
                            <input wire:model="name" type="text"
                                class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-brand-blue focus:ring-2 focus:ring-blue-100 outline-none transition-all placeholder:text-slate-300"
                                placeholder="Contoh: Salsabila Jannah">
                        </div>

                        <!-- Instance (Conditional) -->
                        @if($booking_type == 'company')
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-2">Nama Instansi / Perusahaan</label>
                                <input wire:model="instance" type="text"
                                    class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-brand-blue focus:ring-2 focus:ring-blue-100 outline-none transition-all placeholder:text-slate-300"
                                    placeholder="PT. Laut Sejahtera">
                            </div>
                        @endif
                    </div>

                    <div class="grid md:grid-cols-2 gap-6">
                        <!-- WA -->
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Nomor WhatsApp</label>
                            <input wire:model="whatsapp" type="text"
                                class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-brand-blue focus:ring-2 focus:ring-blue-100 outline-none transition-all placeholder:text-slate-300"
                                placeholder="0812...">
                            <p class="text-xs text-slate-400 mt-1">Untuk kontak darurat jika ada perubahan jadwal.</p>
                        </div>
                        <!-- Email -->
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Email Aktif <span
                                    class="text-red-500">*</span></label>
                            <input wire:model="email" type="email"
                                class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-brand-blue focus:ring-2 focus:ring-blue-100 outline-none transition-all placeholder:text-slate-300"
                                placeholder="email@perusahaan.com">
                            <p class="text-xs text-slate-400 mt-1">Tiket antrian akan dikirim ke sini.</p>
                        </div>
                    </div>

                    <!-- Address (Optional) -->
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Alamat (Opsional)</label>
                        <textarea wire:model="address" rows="2"
                            class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-brand-blue focus:ring-2 focus:ring-blue-100 outline-none transition-all placeholder:text-slate-300"
                            placeholder="Alamat lengkap..."></textarea>
                    </div>

                    <!-- Informasi Tambahan -->
                    <div class="border-t border-slate-100 pt-8">
                        <div class="flex items-center justify-between mb-4">
                            <h4 class="text-base font-bold text-slate-800">Data Teknis & Kegiatan</h4>
                            <button wire:click="addTechnicalRow"
                                class="text-sm font-semibold text-brand-blue hover:text-blue-700 transition-colors flex items-center gap-2">
                                <i class="fa-solid fa-plus-circle"></i> Tambah Baris
                            </button>
                        </div>

                        <div class="space-y-4">
                            @foreach($technical_data as $index => $row)
                                <div
                                    class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden hover:border-brand-blue/30 transition-all group">

                                    <!-- Card Header (Index & Delete) -->
                                    <div class="bg-slate-50 px-4 py-3 border-b border-slate-100 flex justify-between items-center">
                                        <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Data No.
                                            {{ $index + 1 }}</span>
                                        @if(count($technical_data) > 1)
                                            <button wire:click="removeTechnicalRow({{ $index }})"
                                                class="text-xs font-semibold text-red-500 hover:text-red-700 hover:bg-red-50 px-2 py-1 rounded-md transition-all flex items-center gap-1">
                                                <i class="fa-solid fa-trash-can"></i> Hapus
                                            </button>
                                        @endif
                                    </div>

                                    <div class="p-5 grid md:grid-cols-12 gap-4">
                                        <!-- 1. Sifat Kegiatan -->
                                        <div class="md:col-span-3">
                                            <label
                                                class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Sifat</label>
                                            <div class="relative">
                                                <select wire:model="technical_data.{{ $index }}.nature"
                                                    class="w-full appearance-none px-3 py-2.5 rounded-lg bg-slate-50 border border-slate-200 focus:bg-white focus:border-brand-blue focus:ring-2 focus:ring-blue-100 outline-none text-sm font-medium text-slate-700 transition-all">
                                                    <option value="non_business">Non Berusaha</option>
                                                    <option value="business">Berusaha</option>
                                                </select>
                                                <div
                                                    class="absolute inset-y-0 right-0 flex items-center px-3 pointer-events-none text-slate-400">
                                                    <i class="fa-solid fa-chevron-down text-xs"></i>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- 2. Jenis Kegiatan -->
                                        <div class="md:col-span-5">
                                            <label
                                                class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Jenis
                                                Kegiatan</label>
                                            <input wire:model="technical_data.{{ $index }}.activity" type="text"
                                                class="w-full px-3 py-2.5 rounded-lg bg-slate-50 border border-slate-200 focus:bg-white focus:border-brand-blue focus:ring-2 focus:ring-blue-100 outline-none text-sm placeholder:text-slate-400 transition-all"
                                                placeholder="Contoh: Reklamasi, Dermaga...">
                                            @error("technical_data.{$index}.activity") <span
                                            class="text-[10px] text-red-500 block mt-1">Wajib diisi</span> @enderror
                                        </div>

                                        <!-- 3. Luasan / Panjang -->
                                        <div class="md:col-span-4">
                                            <label
                                                class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Dimensi
                                                (Luas/Panjang)</label>
                                            <input wire:model="technical_data.{{ $index }}.dimension" type="text"
                                                class="w-full px-3 py-2.5 rounded-lg bg-slate-50 border border-slate-200 focus:bg-white focus:border-brand-blue focus:ring-2 focus:ring-blue-100 outline-none text-sm placeholder:text-slate-400 transition-all"
                                                placeholder="Contoh: 2 Ha / 150 m">
                                            @error("technical_data.{$index}.dimension") <span
                                            class="text-[10px] text-red-500 block mt-1">Wajib diisi</span> @enderror
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        @if(count($technical_data) == 0)
                            <div class="text-center py-6 border-2 border-dashed border-slate-200 rounded-xl">
                                <button wire:click="addTechnicalRow" class="text-sm font-medium text-brand-blue hover:underline">
                                    + Tambah Data Teknis
                                </button>
                            </div>
                        @endif

                        @error('technical_data') <p class="text-xs text-red-500 mt-2">{{ $message }}</p> @enderror
                    </div>
                </div>
            @endif


            @if($step === 2)
                <div x-data="{ showModal: false, title: '', desc: '' }" class="relative">
                    
                    <div class="grid md:grid-cols-2 gap-4">
                        @foreach($this->services as $service)
                            <div wire:click="selectService({{ $service->id }})"
                                class="relative cursor-pointer p-6 rounded-2xl border transition-all hover:shadow-md 
                                 {{ $service_id == $service->id ? 'border-brand-blue bg-blue-50/50 ring-2 ring-brand-blue/20' : 'border-slate-200 hover:border-brand-blue/50' }}">
                                <div class="flex items-center gap-4">
                                    <div
                                        class="w-10 h-10 rounded-full bg-white border border-slate-100 flex items-center justify-center text-brand-blue shadow-sm">
                                        <!-- Icon Placeholder -->
                                        <i class="fa-solid fa-briefcase"></i>
                                    </div>
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2">
                                            <h4 class="font-bold text-slate-900">{{ $service->name }}</h4>
                                            <button type="button" 
                                                @click.stop="showModal = true; title = '{{ $service->name }}'; desc = `{{ $service->description ?? 'Belum ada deskripsi untuk layanan ini.' }}`"
                                                class="text-slate-400 hover:text-brand-blue transition-colors"
                                                title="Lihat Deskripsi">
                                                <i class="fa-solid fa-circle-info"></i>
                                            </button>
                                        </div>
                                        <p class="text-xs text-slate-500 mt-1">Klik untuk memilih</p>
                                    </div>
                                    
                                    @if($service_id == $service->id)
                                        <div class="text-brand-blue"><i class="fa-solid fa-circle-check text-xl"></i></div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Description Modal -->
                    <div x-show="showModal" style="display: none;" 
                        class="fixed inset-0 z-50 flex items-center justify-center px-4 bg-black/50 backdrop-blur-sm"
                        x-transition:enter="transition ease-out duration-300"
                        x-transition:enter-start="opacity-0"
                        x-transition:enter-end="opacity-100"
                        x-transition:leave="transition ease-in duration-200"
                        x-transition:leave-start="opacity-100"
                        x-transition:leave-end="opacity-0">
                        
                        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden transform transition-all"
                             @click.away="showModal = false"
                             x-transition:enter="transition ease-out duration-300 transform"
                             x-transition:enter-start="opacity-0 translate-y-4 scale-95"
                             x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                             x-transition:leave="transition ease-in duration-200 transform"
                             x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                             x-transition:leave-end="opacity-0 translate-y-4 scale-95">
                            
                            <div class="bg-slate-50 px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                                <h3 class="font-bold text-slate-900 text-lg" x-text="title"></h3>
                                <button @click="showModal = false" class="text-slate-400 hover:text-red-500 transition-colors">
                                    <i class="fa-solid fa-xmark text-xl"></i>
                                </button>
                            </div>
                            
                            <div class="p-6">
                                <p class="text-slate-600 leading-relaxed whitespace-pre-line" x-text="desc"></p>
                            </div>

                            <div class="bg-slate-50 px-6 py-4 border-t border-slate-100 text-right">
                                <button @click="showModal = false" class="px-4 py-2 bg-brand-blue text-white rounded-lg font-medium hover:bg-blue-700 transition-colors">
                                    Tutup
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- STEP 3: SCHEDULE -->
            @if($step === 3)
                <div class="space-y-8">

                    <div class="bg-blue-50/50 p-6 rounded-2xl border border-blue-100">
                        <h4 class="text-sm font-semibold text-slate-700 mb-4"><i
                                class="fa-solid fa-calendar-plus mr-2 text-brand-blue"></i> Tambah Jadwal</h4>

                        <div class="flex flex-col md:flex-row gap-4 items-end">
                            <!-- Date Picker -->
                            <div class="w-full">
                                <label class="block text-sm font-semibold text-slate-700 mb-2">Pilih Tanggal</label>
                                <input wire:model.live="date" type="date"
                                    class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-brand-blue outline-none text-slate-700 font-medium">
                            </div>

                            <!-- Slot Selection -->
                            @if($date && $availableSlots > 0)
                                <div class="w-full">
                                    <label class="block text-sm font-semibold text-slate-700 mb-2">Pilih Jam</label>
                                    <select wire:model="time_slot"
                                        class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-brand-blue outline-none text-slate-700 font-medium">
                                        <option value="">-- Pilih Jam --</option>
                                        @foreach($this->availableTimeSlots as $slot)
                                            <option value="{{ $slot['value'] }}">{{ $slot['label'] }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="w-full md:w-auto">
                                    <button wire:click="addSchedule"
                                        class="w-full md:w-auto px-6 py-3 bg-brand-blue text-white rounded-xl font-medium hover:bg-blue-700 transition-colors whitespace-nowrap">
                                        <i class="fa-solid fa-plus"></i> Tambah
                                    </button>
                                </div>
                            @endif
                        </div>

                        @if($date && $availableSlots <= 0)
                            <div class="mt-3 text-orange-600 text-sm">
                                <i class="fa-solid fa-circle-exclamation mr-1"></i> Tidak ada slot tersedia pada tanggal ini.
                            </div>
                        @endif

                        @error('schedules_list')
                            <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                        @enderror
                        @error('time_slot')
                            <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Selected Schedules List -->
                    @if(count($schedules_list) > 0)
                        <div>
                            <h4 class="text-sm font-semibold text-slate-700 mb-3">Jadwal Dipilih ({{ count($schedules_list) }})</h4>
                            <div class="space-y-3">
                                @foreach($schedules_list as $index => $item)
                                    <div
                                        class="flex items-center justify-between p-4 bg-white border border-slate-200 rounded-xl shadow-sm">
                                        <div class="flex items-center gap-4">
                                            <div
                                                class="w-10 h-10 rounded-full bg-blue-50 text-brand-blue flex items-center justify-center">
                                                <i class="fa-regular fa-calendar"></i>
                                            </div>
                                            <div>
                                                <p class="font-bold text-slate-900">
                                                    {{ \Carbon\Carbon::parse($item['date'])->isoFormat('dddd, D MMMM Y') }}</p>
                                                <p class="text-sm text-slate-500">Pukul {{ $item['time'] }} -
                                                    {{ \Carbon\Carbon::parse($item['time'])->addHour()->format('H:i') }}</p>
                                            </div>
                                        </div>
                                        <button wire:click="removeSchedule({{ $index }})"
                                            class="text-slate-400 hover:text-red-500 transition-colors p-2">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <div class="text-center py-8 text-slate-400 border-2 border-dashed border-slate-200 rounded-xl">
                            <i class="fa-regular fa-calendar-xmark text-2xl mb-2"></i>
                            <p>Belum ada jadwal yang dipilih.</p>
                        </div>
                    @endif

                </div>
            @endif

        </div>

        <!-- Footer Actions -->
        <div class="bg-slate-50 border-t border-slate-100 p-6 flex justify-between items-center">
            @if($step > 1)
                <button wire:click="previousStep"
                    class="px-6 py-2 rounded-xl text-slate-500 font-medium hover:bg-slate-200 transition-colors">
                    Kembali
                </button>
            @else
                <div></div> <!-- Spacer -->
            @endif

            @if($step < 3)
                <button wire:click="nextStep"
                    class="px-8 py-3 bg-brand-black text-white rounded-xl font-bold shadow-lg hover:bg-slate-800 transition-all flex items-center gap-2">
                    Selanjutnya <i class="fa-solid fa-arrow-right"></i>
                </button>
            @else
                <button wire:click="submit" wire:loading.attr="disabled"
                    class="px-8 py-3 bg-brand-blue text-white rounded-xl font-bold shadow-lg shadow-blue-500/30 hover:bg-blue-700 transition-all flex items-center gap-2">
                    <span wire:loading.remove>Konfirmasi Janji</span>
                    <span wire:loading><i class="fa-solid fa-circle-notch fa-spin"></i> Memproses...</span>
                </button>
            @endif
        </div>

    @endif
</div>