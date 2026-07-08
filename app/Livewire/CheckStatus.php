<?php

namespace App\Livewire;

use App\Models\Client;
use App\Models\SatisfactionSurvey;
use App\Services\DataHashService;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
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

    public $estimated_cost_savings;

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

        $tokenHash = app(DataHashService::class)->token($this->access_token);

        $this->client = Client::where('ticket_number', $this->ticket_number)
            ->where(function ($query) use ($tokenHash) {
                $query->where('access_token_hash', $tokenHash)
                    ->orWhere(function ($legacyQuery) {
                        $legacyQuery->whereNull('access_token_hash')
                            ->where('access_token', $this->access_token);
                    });
            })
            ->with(['service', 'consultationLocation', 'schedules', 'assignments.user', 'latestConsultationReport', 'beritaAcara'])
            ->first();

        if (! $this->client) {
            $this->addError('ticket_number', 'Tiket atau Token tidak ditemukan.');

            return;
        }

        // Initialize ratings with null for each assignment to ensure validation catches unrated items
        foreach ($this->client->assignments as $assignment) {
            $this->ratings[$assignment->id] = null;
        }

        if (! $this->requiresCostSavingsEstimate) {
            $this->estimated_cost_savings = null;
        }
    }

    public function submitFeedback()
    {
        $requiresCostSavingsEstimate = $this->requiresCostSavingsEstimate;

        $rules = [
            'criticism' => 'required|string',
            'suggestion' => 'required|string',
            'estimated_cost_savings' => [
                $requiresCostSavingsEstimate ? 'required' : 'nullable',
                'integer',
                'min:0',
                'max:999999999999',
            ],
        ];

        if ($this->client->assignments->isNotEmpty()) {
            $rules['ratings'] = 'required|array';
            $rules['ratings.*'] = 'required|integer|min:1|max:5';
        }

        $this->validate($rules, [
            'criticism.required' => 'Kritik / umpan balik wajib diisi.',
            'suggestion.required' => 'Saran wajib diisi.',
            'estimated_cost_savings.required' => 'Perkiraan penghematan biaya wajib diisi. Isi 0 jika tidak ada penghematan.',
            'estimated_cost_savings.integer' => 'Perkiraan penghematan harus berupa angka Rupiah tanpa titik atau koma.',
            'estimated_cost_savings.min' => 'Perkiraan penghematan tidak boleh kurang dari Rp0.',
            'estimated_cost_savings.max' => 'Perkiraan penghematan terlalu besar. Mohon periksa kembali angka yang dimasukkan.',
            'ratings.required' => 'Mohon beri penilaian untuk petugas layanan.',
            'ratings.*.required' => 'Setiap petugas wajib diberi penilaian.',
            'ratings.*.integer' => 'Nilai bintang tidak valid.',
            'ratings.*.min' => 'Minimal penilaian adalah 1 bintang.',
            'ratings.*.max' => 'Maksimal penilaian adalah 5 bintang.',
        ]);

        if (! $this->client) {
            return;
        }

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
                    'estimated_cost_savings' => $this->requiresCostSavingsEstimate
                        ? $this->estimated_cost_savings
                        : null,
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
        if (! $this->client) {
            return false;
        }

        return $this->client->hasSatisfactionFeedback();
    }

    public function getRequiresCostSavingsEstimateProperty(): bool
    {
        if (! $this->client) {
            return false;
        }

        return $this->client->requiresCostSavingsEstimate();
    }

    public function getCostSavingsChannelLabelProperty(): string
    {
        $location = $this->client?->consultationLocation;

        if (! $location) {
            return 'secara luring di';
        }

        if ($location->is_online) {
            return 'secara online';
        }

        return ' Kantor '.$location->name;
    }

    public function render()
    {
        return view('livewire.check-status');
    }
}
