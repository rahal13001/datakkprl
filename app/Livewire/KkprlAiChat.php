<?php

namespace App\Livewire;

use App\Models\KkprlProposal;
use App\Models\KkprlProposalChat;
use App\Services\KkprlAiService;
use Livewire\Component;

class KkprlAiChat extends Component
{
    public int $proposalId;
    public string $chapter;
    
    public string $userInput = '';
    public array $messages = [];
    public array $previewData = [];
    public bool $isExtracting = false;
    public ?string $errorMessage = null;

    public function mount(int $proposalId, string $chapter)
    {
        $this->proposalId = $proposalId;
        $this->chapter = $chapter;

        $this->loadChatHistory();
    }

    public function loadChatHistory()
    {
        $chat = KkprlProposalChat::where('proposal_id', $this->proposalId)
            ->where('chapter', $this->chapter)
            ->first();

        if ($chat && !empty($chat->messages)) {
            $this->messages = $chat->messages;
        } else {
            // First time greeting
            $this->messages = [
                ['role' => 'ai', 'content' => $this->getGreetingMessage()]
            ];
            $this->saveChatHistory();
        }

        // Try to load any existing payload from proposal to preview data
        $proposal = KkprlProposal::find($this->proposalId);
        $this->previewData = $proposal->payload[$this->chapter] ?? [];
        
        // Recover unconfirmed preview data from chat history
        foreach ($this->messages as $msg) {
            if (isset($msg['role']) && $msg['role'] === 'system_preview' && isset($msg['data'])) {
                $this->previewData = array_merge($this->previewData, $msg['data']);
            }
        }
    }

    public function sendMessage(KkprlAiService $aiService)
    {
        $this->errorMessage = null;
        $input = trim($this->userInput);
        if (empty($input)) return;

        // Save original input in case we need to revert it on error
        $originalInput = $input;

        // Add user message
        $this->messages[] = ['role' => 'user', 'content' => $input];
        $this->userInput = '';

        // Generate response from AI
        $systemPrompt = $this->getSystemPrompt();
        $response = $aiService->chat($this->messages, $systemPrompt);

        if ($response) {
            // Check if response contains JSON block
            if (preg_match('/```json\s*(.*?)\s*```/s', $response, $matches)) {
                $jsonString = $matches[1];
                $extracted = json_decode($jsonString, true);
                
                if (json_last_error() === JSON_ERROR_NONE) {
                    $this->previewData = array_merge($this->previewData, $extracted);
                    $this->isExtracting = true;
                    // Remove the JSON block from the text shown to the user
                    $response = preg_replace('/```json\s*(.*?)\s*```/s', '', $response);
                    
                    // Add a hidden system message to store the preview data in the DB
                    $this->messages[] = ['role' => 'system_preview', 'data' => $extracted];
                }
            }

            $this->messages[] = ['role' => 'ai', 'content' => trim($response)];
            $this->saveChatHistory();
        } else {
            // Remove the user's message from history so they can retry without messing up the sequence
            array_pop($this->messages);
            $this->userInput = $originalInput;
            $this->errorMessage = 'Maaf, sistem AI (Google Gemini) sedang sibuk atau mengalami gangguan. Silakan coba klik Kirim lagi.';
        }
    }

    public function confirmAndSave()
    {
        $proposal = KkprlProposal::findOrFail($this->proposalId);
        $payload = $proposal->payload ?? [];
        
        // Merge the confirmed AI preview data into the payload
        $payload[$this->chapter] = array_merge($payload[$this->chapter] ?? [], $this->previewData);
        
        $proposal->payload = $payload;
        $proposal->save();

        session()->flash('success', 'Data Bab ini berhasil disimpan. Silakan lanjut ke bagian berikutnya.');
        
        // Notify parent component (KkprlProposalWizard) to update its state
        $this->dispatch('draft-saved');
    }

    private function saveChatHistory()
    {
        KkprlProposalChat::updateOrCreate(
            ['proposal_id' => $this->proposalId, 'chapter' => $this->chapter],
            [
                'messages' => $this->messages,
                'last_interaction_at' => now()
            ]
        );
    }

    private function getGreetingMessage(): string
    {
        return match ($this->chapter) {
            'bag-2' => 'Halo! Saya asisten AI yang akan membantu Anda menyusun Bab 2 (Informasi Pemanfaatan Ruang Laut). Bisa ceritakan kegiatan apa saja yang ada di sekitar perairan lokasi Anda saat ini?',
            'bag-3' => 'Halo! Mari kita bahas Bab 3 (Kondisi Terkini). Bagaimana kondisi ekosistem seperti mangrove atau terumbu karang di lokasi Anda?',
            'bag-4' => 'Halo! Untuk Bab 4 (Reklamasi), dari mana rencana sumber material yang akan digunakan?',
            default => 'Halo! Mari lengkapi data bagian ini bersama-sama. Ada yang bisa saya bantu?'
        };
    }

    private function getSystemPrompt(): string
    {
        $base = "Anda adalah asisten ahli penyusunan dokumen KKPRL untuk pelaku usaha kecil. Bersikaplah ramah dan sabar, namun HARUS KRITIS dan BERDAYA NALAR TINGGI. Jangan langsung puas dengan jawaban singkat yang tidak jelas atau mencurigakan. Jika jawaban pengguna terasa janggal, kurang detail, tidak masuk akal secara teknis, atau saling bertentangan, kejar dengan pertanyaan konfirmasi (mengapa/bagaimana). Tanyakan satu per satu (jangan memberondong pertanyaan).\n";
        
        $rules = match ($this->chapter) {
            'bag-2' => "Tugas Anda melengkapi data Bab 2. Kumpulkan info: 1. Narasi rinci kegiatan sekitar perairan, 2. Sumber info yang logis, 3. Waktu/Periode info didapat.\nBila dirasa info sudah logis, konsisten, dan sangat lengkap, outputkan blok JSON murni dengan key: `surrounding_usage_narrative`, `information_source`, `information_period`.",
            'bag-3' => "Tugas Anda melengkapi data Bab 3. Kumpulkan info: Ekosistem (Mangrove/Terumbu Karang/Lamun), Arus/Gelombang, dan Sosial Ekonomi.\nBila dirasa info sudah logis, konsisten, dan sangat lengkap, outputkan blok JSON murni dengan key: `ecosystem_narrative`, `hydro_oceanography_narrative`, `socio_economic_narrative`.",
            default => "Kumpulkan informasi dari pengguna dengan ramah tapi kritis. Jika lengkap dan logis, berikan rangkuman dalam format JSON."
        };

        return $base . $rules . "\nPENTING: Selalu masukkan format JSON di dalam tag ```json ... ``` HANYA JIKA Anda sudah 100% yakin data tersebut lengkap dan logis untuk divalidasi.";
    }

    public function render()
    {
        return view('livewire.kkprl-ai-chat');
    }
}
