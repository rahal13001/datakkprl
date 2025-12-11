<?php

namespace App\Livewire;

use App\Services\BookingAvailabilityService;
use App\Services\NotificationService;
use App\Models\Service;
use App\Models\Client;
use App\Models\Assignment;
use App\Models\Schedule;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class BookingWizard extends Component
{
    public $step = 1;

    // Step 1: Identity
    public $name;
    public $agency;
    public $whatsapp;
    public $email;

    // Step 2: Service
    public $service_id;
    public $selectedService;

    // Step 3: Schedule
    public $date;
    public $time_slot;
    public $availableSlots = 0;
    
    // UI State
    public $success = false;
    public $errorMessage;

    protected $rules = [
        1 => [
            'name' => 'required|min:3',
            'agency' => 'required',
            'whatsapp' => 'required|numeric',
            'email' => 'required|email',
        ],
        2 => [
            'service_id' => 'required|exists:services,id',
        ],
        3 => [
            'date' => 'required|date|after_or_equal:today',
            'time_slot' => 'required',
        ],
    ];

    public function updatedDate($value)
    {
        // When date changes, reset slot and check availability
        $this->time_slot = null;
        $this->availableSlots = app(BookingAvailabilityService::class)->getAvailableSlots($value);
    }

    public function selectService($id)
    {
        $this->service_id = $id;
        $this->selectedService = Service::find($id);
    }

    public function nextStep()
    {
        $this->validate($this->rules[$this->step]);
        
        if ($this->step == 2 && !$this->service_id) {
            $this->addError('service_id', 'Silakan pilih layanan terlebih dahulu.');
            return;
        }

        $this->step++;
    }

    public function previousStep()
    {
        $this->step--;
    }

    public function submit()
    {
        $this->validate($this->rules[$this->step]);

        try {
            DB::transaction(function () {
                // 1. Create Client
                $client = Client::create([
                    'name' => $this->name,
                    'instance' => $this->agency, // Map agency to instance
                    'whatsapp' => $this->whatsapp,
                    'email' => $this->email,
                    // Ticket number logic is usually handled in Model Observer or Service, 
                    // assuming Client model handles generation or we generate here:
                    'ticket_number' => 'TIKET-' . strtoupper(uniqid()), 
                ]);

                // 2. Find Available Officer (Simplified assignment)
                // In a real world, we need to pick a specific user who has the slot.
                // For now, let's pick the first available user or admin.
                // Or create an assignment without a user_id if that's allowed (queue).
                // Let's assume we pick a random user for now just to make it work,
                // or leave user_id null if schema allows.
                // Checking schema... User didn't give full schema, but Assignment usually needs user_id.
                $user = User::first(); // Fallback

                // 3. Create Schedule & Assignment
                // Actually Schedule model usually holds the time, and Assignment links Client-User-Schedule.
                // We need to create a Schedule record for this slot? 
                // Or does Schedule represent the *officer's* schedule? 
                // Based on BookingAvailabilityService:
                // $existingBookings = Schedule::where('date', $date)->count();
                // It seems Schedule stores the actual booking time.
                
                $schedule = Schedule::create([
                    'date' => $this->date,
                    'start_time' => $this->time_slot,
                    'end_time' => Carbon::parse($this->time_slot)->addHour()->format('H:i'), // Assume 1 hr
                    // 'user_id' => $user->id, // If schedule belongs to user
                ]);

                Assignment::create([
                    'client_id' => $client->id,
                    'user_id' => $user->id,
                    'schedule_id' => $schedule->id,
                    'service_id' => $this->service_id,
                    'status' => 'pending',
                ]);

                // 4. Notify
                app(NotificationService::class)->sendBookingCreated($client);
            });

            $this->success = true;

        } catch (\Exception $e) {
            $this->errorMessage = 'Terjadi kesalahan sistem: ' . $e->getMessage();
        }
    }

    public function getServicesProperty()
    {
        return Service::all();
    }

    public function render()
    {
        return view('livewire.booking-wizard');
    }
}
