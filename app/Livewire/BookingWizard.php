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

    // Step 1: Identity & Metadata
    public $name;
    public $booking_type = 'personal'; // personal or company
    public $instance; // Was agency
    public $whatsapp;
    public $email;
    public $address;
    
    // Metadata
    // public $activity_type; // Removed, moved to technical_data
    public $technical_data = []; // Array of ['nature' => '', 'activity' => '', 'dimension' => '']
    
    // Step 2: Service
    public $service_id;
    public $selectedService;

    // Step 3: Schedule (Multi-select)
    public $date; // Temp for selection
    public $time_slot; // Temp for selection
    public $availableSlots = 0;
    public $schedules_list = []; // Array of ['date' => ..., 'time' => ...]
    
    // UI State
    public $success = false;
    public $errorMessage;

    protected $rules = [
        1 => [
            'name' => 'required|min:3',
            'booking_type' => 'required',
            'instance' => 'required_if:booking_type,company', 
            'whatsapp' => 'required|numeric',
            'email' => 'required|email',
            'address' => 'nullable|string',
            
            'technical_data' => 'required|array|min:1',
            'technical_data.*.nature' => 'required|in:business,non_business',
            'technical_data.*.activity' => 'required|string',
            'technical_data.*.dimension' => 'required|string',
        ],
        2 => [
            'service_id' => 'required|exists:services,id',
        ],
        3 => [
            'schedules_list' => 'required|array|min:1',
        ],
    ];

    public function mount()
    {
        // Initialize with one empty row for UX
        $this->technical_data = [
            ['nature' => 'non_business', 'activity' => '', 'dimension' => '']
        ];
    }

    public function addTechnicalRow()
    {
        $this->technical_data[] = ['nature' => 'non_business', 'activity' => '', 'dimension' => ''];
    }

    public function removeTechnicalRow($index)
    {
        unset($this->technical_data[$index]);
        $this->technical_data = array_values($this->technical_data);
    }

    public function updatedDate($value)
    {
        $this->time_slot = null;
        $this->availableSlots = app(BookingAvailabilityService::class)->getAvailableSlots($value);
    }

    public function selectService($id)
    {
        $this->service_id = $id;
        $this->selectedService = Service::find($id);
    }

    public function addSchedule()
    {
        // Simple validation for the add action
        $this->validate([
            'date' => 'required|date|after_or_equal:today',
            'time_slot' => 'required',
        ]);

        // Check duplicates
        foreach ($this->schedules_list as $s) {
            if ($s['date'] == $this->date && $s['time'] == $this->time_slot) {
                $this->addError('time_slot', 'Jadwal ini sudah dipilih.');
                return;
            }
        }

        $this->schedules_list[] = [
            'date' => $this->date,
            'time' => $this->time_slot
        ];

        // Reset
        $this->time_slot = null;
        // Keep date for convenience
    }

    public function removeSchedule($index)
    {
        unset($this->schedules_list[$index]);
        $this->schedules_list = array_values($this->schedules_list);
    }

    public function nextStep()
    {
        if ($this->step == 1) {
             // Custom validation for Repeater
             $this->validate([
                'technical_data' => 'required|array|min:1',
                'technical_data.*.activity' => 'required|string|min:2',
                'technical_data.*.dimension' => 'required|string',
             ], [
                'technical_data.required' => 'Mohon isi data teknis minimal satu baris.',
                'technical_data.*.activity.required' => 'Jenis kegiatan wajib diisi.',
                'technical_data.*.dimension.required' => 'Luasan/Panjang wajib diisi.',
             ]);
             
             // Validate other fields
             $this->validate($this->rules[$this->step]);
        }
        elseif ($this->step == 3) {
             $this->validate([
                'schedules_list' => 'required|array|min:1'
             ], ['schedules_list.required' => 'Mohon pilih minimal satu jadwal.']);
        } else {
             $this->validate($this->rules[$this->step]);
        }

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
        // Final validation
        $this->validate([
             'schedules_list' => 'required|array|min:1',
             'technical_data' => 'required|array|min:1'
        ]);

        try {
            DB::transaction(function () {
                // Prepare metadata
                $metadata = [];
                // Store technical data as the metadata structure
                $metadata['data_teknis'] = $this->technical_data; 

                // 1. Create Client
                $client = Client::create([
                    'name' => $this->name,
                    'booking_type' => $this->booking_type,
                    'instance' => $this->booking_type == 'company' ? $this->instance : null,
                    'whatsapp' => $this->whatsapp,
                    'email' => $this->email,
                    'address' => $this->address,
                    
                    // Derive main activity type from first row or default
                    'activity_type' => $this->technical_data[0]['nature'] ?? 'non_business',
                    'metadata' => $metadata, // Casts to JSON automatically

                    'contact_details' => [], 
                    'service_id' => $this->service_id,
                    'ticket_number' => 'TIKET-' . strtoupper(uniqid()),  
                ]);

                // 2. Find Officer
                $user = User::first(); 

                // 3. Create Schedules (Loop)
                foreach ($this->schedules_list as $slot) {
                    $schedule = Schedule::create([
                        'client_id' => $client->id, // Fixed: Added client_id
                        'date' => $slot['date'],
                        'start_time' => $slot['time'],
                        'end_time' => Carbon::parse($slot['time'])->addHour()->format('H:i'),
                        // 'time_slot' removed as it's not in schema usually, assuming start_time covers it
                        // checking schema from model... it has start_time/end_time.
                    ]);

                    // Assignment will be created manually by Admin
                    // Assignment::withoutEvents(...) removed to prevent auto-assignment

                }

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

    public function getAvailableTimeSlotsProperty()
    {
        if (!$this->date) return [];

        $isFriday = Carbon::parse($this->date)->isFriday();
        $slots = [];

        // Operational Hours
        // 08:00 - 12:00 (Break) 13:00 - 16:00 (Mon-Thu) / 16:30 (Fri) 
        
        $morning = ['08:00', '09:00', '10:00', '11:00'];
        $afternoon = ['13:00', '14:00', '15:00'];

        foreach ($morning as $time) {
            $end = Carbon::parse($time)->addHour()->format('H:i');
            $slots[] = [
                'value' => $time,
                'label' => "$time - $end"
            ];
        }

        foreach ($afternoon as $time) {
            $endTime = Carbon::parse($time)->addHour()->format('H:i');
            
            // Special case for Friday last slot (15:00)
            if ($isFriday && $time === '15:00') {
                $endTime = '16:30'; // Extend to 16:30
            }

            $slots[] = [
                'value' => $time,
                'label' => "$time - $endTime"
            ];
        }

        return $slots;
    }

    public function render()
    {
        return view('livewire.booking-wizard');
    }
}
