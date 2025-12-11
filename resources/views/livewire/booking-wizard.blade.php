<div class="bg-white rounded-3xl shadow-xl border border-slate-100 overflow-hidden relative">
    
    <!-- Success State -->
    @if($success)
    <div class="p-12 text-center">
        <div class="w-20 h-20 bg-green-100 text-green-600 rounded-full flex items-center justify-center mx-auto mb-6 text-3xl">
            <i class="fa-solid fa-check"></i>
        </div>
        <h3 class="text-2xl font-bold text-slate-900 mb-2">Permohonan Diterima!</h3>
        <p class="text-slate-500 max-w-md mx-auto mb-8">
            Tiket antrian dan detail jadwal telah dikirim ke email <strong>{{ $email }}</strong>. Silakan cek folder Spam jika tidak masuk Inbox.
        </p>
        <button type="button" onclick="window.location.reload()" class="px-6 py-3 bg-brand-black text-white rounded-xl font-medium">
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
            <div class="grid md:grid-cols-2 gap-6">
                <!-- Name -->
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Nama Lengkap</label>
                    <input wire:model="name" type="text" class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-brand-blue focus:ring-2 focus:ring-blue-100 outline-none transition-all placeholder:text-slate-300" placeholder="Contoh: Budi Santoso">
                </div>
                <!-- Agency -->
                <div>
                     <label class="block text-sm font-semibold text-slate-700 mb-2">Instansi / Perusahaan</label>
                    <input wire:model="agency" type="text" class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-brand-blue focus:ring-2 focus:ring-blue-100 outline-none transition-all placeholder:text-slate-300" placeholder="PT. Laut Sejahtera">
                </div>
            </div>
            
            <div class="grid md:grid-cols-2 gap-6">
                <!-- WA -->
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Nomor WhatsApp</label>
                    <input wire:model="whatsapp" type="text" class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-brand-blue focus:ring-2 focus:ring-blue-100 outline-none transition-all placeholder:text-slate-300" placeholder="0812...">
                    <p class="text-xs text-slate-400 mt-1">Untuk kontak darurat jika ada perubahan jadwal.</p>
                </div>
                <!-- Email -->
                <div>
                     <label class="block text-sm font-semibold text-slate-700 mb-2">Email Aktif <span class="text-red-500">*</span></label>
                    <input wire:model="email" type="email" class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-brand-blue focus:ring-2 focus:ring-blue-100 outline-none transition-all placeholder:text-slate-300" placeholder="email@perusahaan.com">
                    <p class="text-xs text-slate-400 mt-1">Tiket antrian akan dikirim ke sini.</p>
                </div>
            </div>
        </div>
        @endif

        <!-- STEP 2: SERVICE -->
        @if($step === 2)
        <div class="grid md:grid-cols-2 gap-4">
            @foreach($this->services as $service)
            <div wire:click="selectService({{ $service->id }})" 
                 class="cursor-pointer p-6 rounded-2xl border transition-all hover:shadow-md 
                 {{ $service_id == $service->id ? 'border-brand-blue bg-blue-50/50 ring-2 ring-brand-blue/20' : 'border-slate-200 hover:border-brand-blue/50' }}">
                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 rounded-full bg-white border border-slate-100 flex items-center justify-center text-brand-blue shadow-sm">
                        <!-- Icon Placeholder -->
                        <i class="fa-solid fa-briefcase"></i>
                    </div>
                    <div>
                        <h4 class="font-bold text-slate-900">{{ $service->name }}</h4>
                        <p class="text-xs text-slate-500 mt-1">Klik untuk memilih</p>
                    </div>
                    @if($service_id == $service->id)
                        <div class="ml-auto text-brand-blue"><i class="fa-solid fa-circle-check"></i></div>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
        @endif

        <!-- STEP 3: SCHEDULE -->
        @if($step === 3)
        <div class="space-y-8">
            
            <!-- Date Picker (Native for simplicity, styled) -->
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-3">Pilih Tanggal</label>
                <input wire:model.live="date" type="date" class="w-full md:w-1/2 px-4 py-3 rounded-xl border border-slate-200 focus:border-brand-blue outline-none text-slate-700 font-medium">
            </div>

            <!-- Slots -->
            <div class="border-t border-slate-100 pt-6">
                <h4 class="text-sm font-semibold text-slate-700 mb-4">Slot Waktu Tersedia</h4>
                
                @if(!$date)
                    <div class="text-slate-400 text-sm italic">Silakan pilih tanggal terlebih dahulu.</div>
                @elseif($availableSlots <= 0)
                     <div class="p-4 bg-orange-50 text-orange-600 rounded-xl text-sm border border-orange-100">
                        Maaf, tidak ada slot tersedia pada tanggal ini. Silakan pilih tanggal lain.
                    </div>
                @else
                    <div class="grid grid-cols-3 md:grid-cols-4 gap-3">
                        <!-- Hardcoded slots for demo logic (08:00 - 15:00) -->
                        @php
                            $slots = ['08:00', '09:00', '10:00', '11:00', '13:00', '14:00'];
                        @endphp
                        
                        @foreach($slots as $slot)
                        <label class="cursor-pointer">
                            <input type="radio" wire:model="time_slot" value="{{ $slot }}" class="peer sr-only">
                            <div class="px-3 py-2 text-center rounded-lg border border-slate-200 text-slate-600 text-sm font-medium transition-all
                                peer-checked:bg-brand-black peer-checked:text-white peer-checked:border-transparent
                                hover:border-slate-400">
                                {{ $slot }}
                            </div>
                        </label>
                        @endforeach
                    </div>
                @endif
            </div>

        </div>
        @endif

    </div>

    <!-- Footer Actions -->
    <div class="bg-slate-50 border-t border-slate-100 p-6 flex justify-between items-center">
        @if($step > 1)
            <button wire:click="previousStep" class="px-6 py-2 rounded-xl text-slate-500 font-medium hover:bg-slate-200 transition-colors">
                Kembali
            </button>
        @else
            <div></div> <!-- Spacer -->
        @endif

        @if($step < 3)
            <button wire:click="nextStep" class="px-8 py-3 bg-brand-black text-white rounded-xl font-bold shadow-lg hover:bg-slate-800 transition-all flex items-center gap-2">
                Selanjutnya <i class="fa-solid fa-arrow-right"></i>
            </button>
        @else
            <button wire:click="submit" wire:loading.attr="disabled" class="px-8 py-3 bg-brand-blue text-white rounded-xl font-bold shadow-lg shadow-blue-500/30 hover:bg-blue-700 transition-all flex items-center gap-2">
                <span wire:loading.remove>Konfirmasi Janji</span>
                <span wire:loading><i class="fa-solid fa-circle-notch fa-spin"></i> Memproses...</span>
            </button>
        @endif
    </div>

    @endif
</div>
