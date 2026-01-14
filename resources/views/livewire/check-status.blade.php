<div class="min-h-screen pt-32 pb-20 px-6 lg:px-8">
    <div class="max-w-4xl mx-auto">
        
        <!-- Header -->
        <div class="text-center mb-16">
            <h1 class="text-4xl lg:text-5xl font-bold text-slate-900 tracking-tight mb-4">
                Cek Status <span class="text-gradient">Permohonan.</span>
            </h1>
            <p class="text-lg text-slate-500 font-light max-w-2xl mx-auto">
                Lacak progres layanan konsultasi Anda secara real-time. Masukkan Nomor Tiket dan Akses Token Anda.
            </p>
        </div>

        <!-- Search Form -->
        <div class="bg-white/80 backdrop-blur-xl rounded-3xl p-8 border border-white/20 shadow-xl shadow-slate-200/50 mb-12">
            <form wire:submit="check" class="grid lg:grid-cols-5 gap-6">
                <div class="lg:col-span-2">
                    <label class="block text-sm font-medium text-slate-700 mb-2">Nomor Tiket</label>
                    <input type="text" wire:model="ticket_number" placeholder="TICKET-202X..." 
                        class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-brand-blue focus:border-brand-blue transition-all outline-none font-mono text-sm uppercase">
                    @error('ticket_number') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                </div>
                
                <div class="lg:col-span-2">
                    <label class="block text-sm font-medium text-slate-700 mb-2">Akses Token</label>
                    <input type="text" wire:model="access_token" placeholder="UUID Format..." 
                        class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-brand-blue focus:border-brand-blue transition-all outline-none font-mono text-xs">
                    @error('access_token') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                </div>

                <div class="flex items-end">
                    <button type="submit" class="w-full px-6 py-3.5 bg-gradient-to-br from-brand-blue to-blue-600 text-white rounded-xl font-semibold shadow-lg shadow-blue-500/30 hover:shadow-blue-500/50 hover:scale-[1.02] active:scale-[0.98] transition-all duration-200 flex items-center justify-center gap-2 group">
                        <i class="fa-solid fa-search group-hover:rotate-12 transition-transform"></i>
                        Cek Status
                    </button>
                </div>
            </form>
        </div>

        @if($client)
            <!-- Results -->
            <div wire:poll.5s class="bg-white rounded-3xl overflow-hidden shadow-xl border border-slate-100 animate-fade-in-up">
                
                <!-- Client Info Header -->
                <div class="p-8 bg-slate-50 border-b border-slate-100 flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                    <div>
                        <div class="text-xs font-mono text-slate-500 uppercase tracking-wide mb-1">Pemohon</div>
                        <h3 class="text-2xl font-bold text-slate-900">{{ $client->name }}</h3>
                        <div class="flex items-center gap-3 mt-2 text-sm text-slate-500">
                            <i class="fa-regular fa-envelope"></i> {{ $client->email }}
                            <span class="w-1 h-1 rounded-full bg-slate-300"></span>
                            <i class="fa-brands fa-whatsapp"></i> {{ $client->whatsapp }}
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-slate-200 rounded-lg shadow-sm">
                            <span class="w-2 h-2 rounded-full 
                                @if($client->status === 'completed') bg-green-500 
                                @elseif($client->status === 'waiting') bg-slate-400
                                @else bg-blue-500 @endif
                            animate-pulse"></span>
                            <span class="font-mono text-sm font-medium uppercase">{{ str_replace('_', ' ', $client->status) }}</span>
                        </div>
                        <div class="mt-2 text-xs text-slate-400 font-mono">{{ $client->created_at->format('d M Y, H:i') }} WIT</div>
                    </div>
                </div>

                <div class="p-8 grid lg:grid-cols-2 gap-12">
                    <!-- Timeline -->
                    <div>
                        <h4 class="font-bold text-slate-900 mb-6">Timeline Layanan</h4>
                        <div class="relative space-y-8 pl-8 before:content-[''] before:absolute before:left-[11px] before:top-2 before:bottom-2 before:w-[2px] before:bg-slate-100">
                            
                            <!-- Step 1: Submission -->
                            <div class="relative">
                                <span class="absolute -left-[41px] w-6 h-6 rounded-full border-2 border-white shadow-sm flex items-center justify-center z-10 bg-green-500 text-white text-xs">
                                    <i class="fa-solid fa-check"></i>
                                </span>
                                <h5 class="font-medium text-slate-900">Permohonan Masuk</h5>
                                <p class="text-sm text-slate-500 mt-1">Data Anda telah diterima sistem.</p>
                            </div>

                            <!-- Step 2: Scheduled -->
                            <div class="relative">
                                @php $isScheduled = in_array($client->status, ['scheduled', 'completed']); @endphp
                                <span class="absolute -left-[41px] w-6 h-6 rounded-full border-2 border-white shadow-sm flex items-center justify-center z-10
                                    {{ $isScheduled ? 'bg-green-500 text-white' : 'bg-slate-200 text-slate-400' }}">
                                    @if($isScheduled) <i class="fa-solid fa-check"></i> @else 2 @endif
                                </span>
                                <h5 class="font-medium {{ $isScheduled ? 'text-slate-900' : 'text-slate-400' }}">Penjadwalan</h5>
                                @if($isScheduled && $client->assignments->isNotEmpty())
                                    <div class="mt-2 p-3 bg-blue-50 border border-blue-100 rounded-xl space-y-2">
                                        <div class="text-xs text-blue-600 font-medium mb-1">Petugas Layanan:</div>
                                        @foreach($client->assignments as $assignment)
                                            <div class="flex items-center gap-2">
                                                <div class="w-6 h-6 rounded-full bg-blue-200 flex items-center justify-center text-xs text-blue-700 font-bold shrink-0">
                                                    {{ substr($assignment->user->name ?? 'A', 0, 1) }}
                                                </div>
                                                <span class="text-sm text-slate-700 font-medium truncate">{{ $assignment->user->name ?? 'Staff' }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="text-sm text-slate-400 mt-1">Admin sedang mengatur jadwal.</p>
                                @endif
                            </div>

                            <!-- Step 3: Finished -->
                            <div class="relative">
                                @php $isFinished = $client->status === 'completed'; @endphp
                                <span class="absolute -left-[41px] w-6 h-6 rounded-full border-2 border-white shadow-sm flex items-center justify-center z-10
                                    {{ $isFinished ? 'bg-green-500 text-white' : 'bg-slate-200 text-slate-400' }}">
                                    @if($isFinished) <i class="fa-solid fa-check"></i> @else 3 @endif
                                </span>
                                <h5 class="font-medium {{ $isFinished ? 'text-slate-900' : 'text-slate-400' }}">Selesai</h5>
                                <p class="text-sm text-slate-500 mt-1">Layanan konsultasi selesai.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Actions / Survey -->
                    <div>
                        @if($client->status === 'completed')
                            @if($this->hasFeedback)
                                <div class="h-full flex flex-col items-center justify-center text-center p-8 bg-green-50/50 rounded-2xl border border-green-100">
                                    <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center text-green-600 mb-4 text-2xl">
                                        <i class="fa-solid fa-heart"></i>
                                    </div>
                                    <h4 class="font-bold text-slate-900 mb-2">Terima Kasih!</h4>
                                    <p class="text-slate-600 text-sm mb-6">Masukan Anda sangat berharga bagi kami untuk meningkatkan kualitas layanan.</p>
                                    
                                    @if($client->latestConsultationReport)
                                        <a href="{{ route('client.report.download', ['client' => $client->ticket_number, 'token' => $client->access_token]) }}" target="_blank" class="inline-flex items-center gap-2 px-6 py-3 bg-white text-green-600 border border-green-200 rounded-xl font-bold hover:bg-green-50 transition-colors shadow-sm cursor-pointer">
                                            <i class="fa-solid fa-file-pdf"></i>
                                            Unduh Laporan Konsultasi
                                        </a>
                                    @endif
                                </div>
                            @else
                                <div class="bg-indigo-50/50 rounded-2xl border border-indigo-100 p-6">
                                    <h4 class="font-bold text-slate-900 mb-4 flex items-center gap-2">
                                        <i class="fa-regular fa-star text-indigo-500"></i>
                                        Berikan Penilaian
                                    </h4>
                                    
                                    <div class="mb-6 p-4 bg-blue-50 border border-blue-100 rounded-xl flex gap-3 text-blue-700 text-sm">
                                        <i class="fa-solid fa-circle-info mt-0.5"></i>
                                        <p>Mohon luangkan waktu sejenak untuk menilai layanan kami. <strong>Laporan Hasil Konsultasi</strong> dapat diunduh setelah Anda mengisi survei ini.</p>
                                    </div>
                                    
                                    <form wire:submit="submitFeedback">
                                        <!-- Ratings Loop -->
                                        <div class="space-y-6 mb-8">
                                            @foreach($client->assignments as $assignment)
                                                <div class="bg-white p-4 rounded-xl border border-slate-100 shadow-sm" x-data="{ rating: @entangle('ratings.' . $assignment->id) }">
                                                    <div class="text-center mb-3">
                                                        <div class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">PETUGAS PELAYANAN</div>
                                                        <div class="font-bold text-slate-800">{{ $assignment->user->name ?? 'Petugas' }}</div>
                                                    </div>
                                                    
                                                    <div class="flex justify-center gap-2">
                                                        @foreach(range(1, 5) as $i)
                                                            <button type="button" @click="rating = {{ $i }}" 
                                                                class="text-2xl transition-all hover:scale-110 focus:outline-none"
                                                                :class="rating >= {{ $i }} ? 'text-yellow-400 drop-shadow-sm' : 'text-slate-200 hover:text-yellow-200'">
                                                                <i class="fa-solid fa-star"></i>
                                                            </button>
                                                        @endforeach
                                                    </div>
                                                    @error('ratings.' . $assignment->id) <span class="text-red-500 text-xs block mt-1 text-center">Wajib dinilai</span> @enderror
                                                </div>
                                            @endforeach
                                        </div>

                                        <!-- Feedback -->
                                        <div class="space-y-4 mb-6">
                                            <div>
                                                <label class="block text-xs font-medium text-slate-700 mb-1">Kritik</label>
                                                <textarea wire:model="criticism" rows="2" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:ring-1 focus:ring-indigo-500 outline-none placeholder:text-slate-300" placeholder="Apa yang kurang?"></textarea>
                                            </div>
                                            <div>
                                                <label class="block text-xs font-medium text-slate-700 mb-1">Saran</label>
                                                <textarea wire:model="suggestion" rows="2" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:ring-1 focus:ring-indigo-500 outline-none placeholder:text-slate-300" placeholder="Apa yang bisa ditingkatkan?"></textarea>
                                            </div>
                                        </div>

                                        <button type="submit" class="w-full py-2.5 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 transition-colors shadow-lg shadow-indigo-500/20">
                                            Kirim Penilaian
                                        </button>
                                    </form>
                                </div>
                            @endif
                        @else
                            <div class="h-full flex flex-col items-center justify-center text-center p-8 border-2 border-dashed border-slate-100 rounded-2xl">
                                <i class="fa-solid fa-clock-rotate-left text-4xl text-slate-200 mb-4"></i>
                                <p class="text-slate-400 text-sm">Penilaian layanan dapat dilakukan setelah status <span class="font-bold text-slate-500">Selesai</span>.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
