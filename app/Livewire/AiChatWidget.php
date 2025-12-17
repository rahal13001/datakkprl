<?php

namespace App\Livewire;

use Livewire\Component;
use App\Services\AiService;
use App\Models\AiChatLog;
use Illuminate\Support\Facades\Session;

class AiChatWidget extends Component
{
    public $isOpen = false;
    public $messages = [];
    public $question = '';
    public $isLoading = false;

    public function mount()
    {
        try {
            // Load existing chat history based on Session ID
            $previousChats = AiChatLog::where('session_id', Session::getId())
                ->orderBy('created_at', 'asc')
                ->get();

            if ($previousChats->count() > 0) {
                foreach ($previousChats as $chat) {
                    $this->messages[] = ['role' => 'user', 'content' => $chat->question];
                    $this->messages[] = ['role' => 'assistant', 'content' => $chat->response];
                }
            } else {
                $this->setDefaultMessage();
            }
        } catch (\Exception $e) {
            // Fallback if table doesn't exist or DB error
            $this->setDefaultMessage();
        }
    }

    private function setDefaultMessage()
    {
        $this->messages[] = [
            'role' => 'assistant',
            'content' => 'Halo! Saya Kawan Ruang Laut AI. Saya siap membantu menjawab pertanyaan Anda seputar regulasi dan layanan KKPRL.'
        ];
    }

    public function toggleChat()
    {
        $this->isOpen = !$this->isOpen;
    }

    public function sendMessage(AiService $aiService)
    {
        if (empty(trim($this->question))) {
            return;
        }

        $userQuestion = $this->question;
        $this->question = ''; // Reset input
        
        // Optimistic UI Update
        $this->messages[] = ['role' => 'user', 'content' => $userQuestion];
        $this->isLoading = true;

        try {
            // Call Gemini API via AiService
            $response = $aiService->askGemini(Session::getId(), $userQuestion, request()->ip());
            $this->messages[] = ['role' => 'assistant', 'content' => $response];
        } catch (\Exception $e) {
            $this->messages[] = ['role' => 'assistant', 'content' => 'Maaf, terjadi kesalahan saat menghubungkan ke AI. Silakan coba lagi nanti.'];
        }

        $this->isLoading = false;
    }

    public function render()
    {
        return view('livewire.ai-chat-widget');
    }
}
