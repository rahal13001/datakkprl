<div class="flex flex-col space-y-6 w-full mt-2">
    @if($chapter === 'bag-3')
        @include('livewire.partials.kkprl-chapter-three-evidence')
    @endif
    
    <!-- Bagian Chat (Atas) -->
    <div class="flex flex-col h-[550px] bg-white rounded-2xl shadow-lg border border-slate-200 overflow-hidden relative">
        
        <!-- Chat Header -->
        <div class="bg-gradient-to-r from-blue-600 to-blue-800 p-4 flex items-center shadow-md z-10 shrink-0">
            <div class="w-10 h-10 rounded-full bg-white/20 flex items-center justify-center mr-4 backdrop-blur-sm border border-white/30">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
            </div>
            <div>
                <h3 class="font-bold text-lg text-white tracking-wide leading-tight">Asisten AI KKPRL</h3>
                <p class="text-blue-100 text-xs font-medium mt-0.5">Membantu pengisian Bab {{ str_replace('bag-', '', $chapter) }} secara interaktif</p>
            </div>
        </div>
        
        <!-- Chat Area -->
        <div class="flex-1 p-4 sm:p-6 overflow-y-auto bg-slate-50 flex flex-col space-y-5 scroll-smooth" id="chat-container">
            @foreach($messages as $message)
                @if($message['role'] === 'ai')
                    <div class="flex items-start max-w-[90%] sm:max-w-[85%] group">
                        <div class="flex-shrink-0 w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center mr-3 mt-1 ring-4 ring-white shadow-sm text-lg">🤖</div>
                        <div class="bg-white px-5 py-3.5 rounded-2xl rounded-tl-none shadow-sm border border-slate-200 text-[14.5px] text-slate-700 leading-relaxed whitespace-pre-wrap">{{ trim($message['content']) }}</div>
                    </div>
                @elseif(isset($message['role']) && $message['role'] === 'user')
                    <div class="flex items-start justify-end w-full">
                        <div class="bg-blue-600 px-5 py-3.5 rounded-2xl rounded-tr-none shadow-md text-[14.5px] text-white max-w-[90%] sm:max-w-[85%] leading-relaxed whitespace-pre-wrap">{{ trim($message['content'] ?? '') }}</div>
                    </div>
                @endif
            @endforeach
            
            <div wire:loading wire:target="sendMessage,analyzeEvidence" class="flex items-start max-w-[85%]">
                 <div class="flex-shrink-0 w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center mr-3 mt-1 ring-4 ring-white shadow-sm text-lg">🤖</div>
                 <div class="bg-white px-5 py-3.5 rounded-2xl rounded-tl-none shadow-sm border border-slate-200 text-sm text-slate-400 italic flex items-center space-x-2">
                     <span>AI sedang memproses</span>
                     <span class="flex space-x-1 ml-2">
                        <div class="w-1.5 h-1.5 bg-slate-400 rounded-full animate-bounce"></div>
                        <div class="w-1.5 h-1.5 bg-slate-400 rounded-full animate-bounce" style="animation-delay: 0.1s"></div>
                        <div class="w-1.5 h-1.5 bg-slate-400 rounded-full animate-bounce" style="animation-delay: 0.2s"></div>
                     </span>
                 </div>
            </div>
        </div>

        @if($errorMessage)
            <div class="px-4 py-2 mx-4 mb-2 bg-red-100 border border-red-200 text-red-600 text-xs rounded-xl shadow-sm flex items-center justify-between">
                <div class="flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span>{{ $errorMessage }}</span>
                </div>
                <button type="button" wire:click="$set('errorMessage', null)" class="text-red-400 hover:text-red-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
        @endif

        <!-- Input Area -->
        <div class="p-3 sm:p-4 bg-white border-t border-slate-200 z-10 shrink-0">
            <div class="relative flex items-center">
                <textarea 
                    wire:model="userInput"
                    x-on:keydown.enter="if (!$event.shiftKey) { $event.preventDefault(); $wire.sendMessage() }"
                    id="kkprl-chat-input-{{ $chapter }}"
                    aria-label="Pesan untuk asisten AI Bab {{ str_replace('bag-', '', $chapter) }}"
                    wire:loading.attr="disabled"
                    wire:target="sendMessage,confirmAndSave,analyzeEvidence"
                    @disabled($locked)
                    placeholder="Ketik jawaban atau deskripsi Anda di sini..." 
                    rows="2"
                    class="w-full pl-4 pr-16 py-3 bg-slate-50 border border-slate-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 focus:bg-white outline-none text-sm transition-all shadow-inner resize-none custom-scrollbar"
                ></textarea>
                <button 
                    type="button" 
                    wire:click="sendMessage"
                    wire:loading.attr="disabled"
                    @disabled($locked)
                    aria-label="Kirim pesan ke asisten AI"
                    class="absolute right-3 top-3 bottom-3 aspect-square bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors shadow-md disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center"
                >
                    <svg class="w-5 h-5 translate-x-[-1px] translate-y-[1px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                </button>
            </div>
            @error('userInput') <p role="alert" class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
            <p class="text-[11px] text-slate-400 mt-2 ml-1">Tekan <kbd class="bg-slate-100 px-1 py-0.5 rounded border border-slate-200">Enter</kbd> untuk mengirim, <kbd class="bg-slate-100 px-1 py-0.5 rounded border border-slate-200">Shift+Enter</kbd> untuk baris baru.</p>
        </div>
    </div>

    <!-- Bagian Pratinjau / Preview Card (Bawah) -->
    <div class="flex flex-col bg-slate-800 rounded-2xl shadow-xl border border-slate-700 overflow-hidden relative text-slate-200 mt-6">
        
        <!-- Preview Header -->
        <div class="bg-slate-900 p-4 sm:p-5 border-b border-slate-700 flex items-center justify-between">
            <div class="flex items-center">
                <div class="w-10 h-10 rounded-xl bg-slate-800 flex items-center justify-center border border-slate-600 mr-4">
                    <svg class="w-5 h-5 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                </div>
                <div>
                    <h3 class="font-bold text-lg text-white">Pratinjau Draft Bab {{ str_replace('bag-', '', $chapter) }}</h3>
                    <p class="text-slate-300 text-sm mt-1">Minta AI memperbaiki narasi melalui chat atau edit langsung isian di bawah.</p>
                </div>
            </div>
        </div>
        
        <div class="p-4 sm:p-6 bg-slate-800">
            @if($locked)
                <p role="status" class="mb-5 text-sm text-amber-200">Proposal sudah terkunci. Pratinjau hanya dapat dibaca.</p>
            @endif
            <p class="mb-5 text-sm text-slate-300">Koreksi tersimpan sementara dalam sesi chat. Tekan Konfirmasi &amp; Simpan Draft untuk menyimpan data proposal. Bab boleh belum lengkap.</p>
            @error('previewData') <p role="alert" class="mb-4 text-sm text-red-300">{{ $message }}</p> @enderror
            <div class="space-y-5">
                @foreach($previewFields as $field)
                    @php($key = $field['key'])
                    <div wire:key="preview-{{ $chapter }}-{{ $key }}">
                        @if($key === 'reclamation_schedule_rows')
                            @include('livewire.partials.kkprl-preview-schedule')
                        @else
                            <label for="preview-{{ $chapter }}-{{ $key }}" class="block mb-2 text-sm font-semibold text-slate-100">{{ $field['label'] }}</label>
                            <textarea
                                id="preview-{{ $chapter }}-{{ $key }}"
                                name="preview_{{ $key }}"
                                wire:model.live.blur="previewData.{{ $key }}"
                                wire:loading.attr="disabled"
                                wire:target="sendMessage,confirmAndSave,analyzeEvidence"
                                rows="4"
                                maxlength="30000"
                                placeholder="Lengkapi lewat chat atau tulis koreksi Anda di sini."
                                @readonly($locked)
                                @if($errors->has('previewData.'.$key)) aria-invalid="true" aria-describedby="preview-error-{{ $chapter }}-{{ $key }}" @endif
                                class="block w-full min-w-0 rounded-xl border border-slate-500 bg-slate-900 p-4 text-sm leading-relaxed text-white placeholder:text-slate-400 focus:border-blue-400 focus:ring-2 focus:ring-blue-400 outline-none disabled:opacity-60"
                            ></textarea>
                        @endif
                        @error('previewData.'.$key)
                            <p id="preview-error-{{ $chapter }}-{{ $key }}" role="alert" class="mt-2 text-sm text-red-300">{{ $message }}</p>
                        @enderror
                    </div>
                @endforeach
            </div>
        </div>
        
        <!-- Action Button -->
        <div class="p-4 sm:p-5 bg-slate-900 border-t border-slate-700">
            @if(session()->has('success'))
                <div class="mb-4 p-4 bg-emerald-500/10 border border-emerald-500/20 rounded-xl flex items-center text-sm text-emerald-400 font-medium">
                    <svg class="w-5 h-5 mr-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    {{ session('success') }}
                </div>
            @endif
            
            <button 
                type="button"
                wire:click="confirmAndSave" 
                wire:loading.attr="disabled"
                wire:target="sendMessage,confirmAndSave,updatedPreviewData,analyzeEvidence"
                class="w-full py-4 bg-gradient-to-r from-emerald-600 to-green-500 text-white font-bold text-base rounded-xl hover:from-emerald-500 hover:to-green-400 transition-all shadow-lg shadow-emerald-900/30 disabled:opacity-30 disabled:cursor-not-allowed flex justify-center items-center group"
                @disabled($locked)
            >
                <svg class="w-6 h-6 mr-2 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <span wire:loading.remove wire:target="confirmAndSave">Konfirmasi &amp; Simpan Draft</span>
                <span wire:loading wire:target="confirmAndSave">Menyimpan draft...</span>
            </button>
            <p class="text-[12px] text-center text-slate-500 mt-4 font-medium">
                Periksa fakta dan narasi sebelum menyimpan. Export tersedia setelah bab lengkap.
            </p>
        </div>
    </div>
</div>

<style>
    .custom-scrollbar::-webkit-scrollbar { width: 6px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 10px; }
    .custom-scrollbar:hover::-webkit-scrollbar-thumb { background-color: #94a3b8; }
</style>

<script>
    document.addEventListener('livewire:initialized', () => {
        const chatContainer = document.getElementById('chat-container');
        if (chatContainer) chatContainer.scrollTop = chatContainer.scrollHeight;
        
        Livewire.hook('morph.updated', ({ component, el }) => {
            if (chatContainer) {
                setTimeout(() => {
                    chatContainer.scrollTop = chatContainer.scrollHeight;
                }, 100);
            }
        });
    });
</script>
