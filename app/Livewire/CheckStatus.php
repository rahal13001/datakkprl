<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Client;
use App\Models\SatisfactionSurvey;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;

class CheckStatus extends Component
{
    public $ticket_number;
    public $access_token;
    
    // Result
    public ?Client $client = null;
    
    // Feedback Form
    public $ratings = []; // [assignment_id => rating]
    public $criticism;
    public $suggestion;

    protected $queryString = [
        'ticket_number' => ['except' => '', 'as' => 'ticket'],
        'access_token' => ['except' => '', 'as' => 'token'],
    ];

    public function mount()
    {
        // Auto-check if parameters exist in URL
        if ($this->ticket_number && $this->access_token) {
            $this->check();
        }
    }

    public function check()
    {
        $this->validate([
            'ticket_number' => 'required',
            'access_token' => 'required',
        ]);

        $this->client = Client::where('ticket_number', $this->ticket_number)
            ->where('access_token', $this->access_token)
            ->with(['service', 'schedules', 'assignments.user', 'latestConsultationReport', 'beritaAcara'])
            ->first();

        if (! $this->client) {
            $this->addError('ticket_number', 'Tiket atau Token tidak ditemukan.');
            return;
        }

        // Initialize ratings with null for each assignment to ensure validation catches unrated items
        foreach ($this->client->assignments as $assignment) {
            $this->ratings[$assignment->id] = null;
        }
    }

    public function submitFeedback()
    {
        $rules = [
            'criticism' => 'required|string',
            'suggestion' => 'required|string',
        ];

        if ($this->client->assignments->isNotEmpty()) {
            $rules['ratings'] = 'required|array';
            $rules['ratings.*'] = 'required|integer|min:1|max:5';
        }

        $this->validate($rules, [
            'criticism.required' => 'Kritik / umpan balik wajib diisi.',
            'suggestion.required' => 'Saran wajib diisi.',
            'ratings.required' => 'Mohon beri penilaian untuk petugas layanan.',
            'ratings.*.required' => 'Setiap petugas wajib diberi penilaian.',
            'ratings.*.integer' => 'Nilai bintang tidak valid.',
            'ratings.*.min' => 'Minimal penilaian adalah 1 bintang.',
            'ratings.*.max' => 'Maksimal penilaian adalah 5 bintang.',
        ]);

        if (! $this->client) return;

        DB::transaction(function () {
            // 1. Update Staff Rating (Assignments)
            // Score: 1 star = 2, 5 stars = 10.
            foreach ($this->client->assignments as $assignment) {
                if (isset($this->ratings[$assignment->id])) {
                    $score = $this->ratings[$assignment->id] * 2;
                    $assignment->update(['score' => $score]);
                }
            }

            // 2. Create Satisfaction Survey (Criticism/Suggestion)
            // Check if already exists to prevent duplicates
            SatisfactionSurvey::firstOrCreate(
                ['client_id' => $this->client->id],
                [
                    'criticism' => $this->criticism,
                    'suggestion' => $this->suggestion,
                ]
            );
        });

        // Refresh client relationship
        $this->client->refresh();

        Notification::make()
            ->title('Terima kasih atas masukan Anda!')
            ->success()
            ->send();
    }

    public function getHasFeedbackProperty()
    {
        if (! $this->client) return false;
        
        return $this->client->hasSatisfactionFeedback();
    }

    public function render()
    {
        return view('livewire.check-status');
    }
}
