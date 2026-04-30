<?php

namespace App\Livewire;

use App\Services\BookingAvailabilityService;
use App\Services\NotificationService;
use App\Models\Service;
use App\Models\Client;
use App\Models\ConsultationLocation;
use App\Models\Schedule;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithFileUploads;

class BookingWizard extends Component
{
    use WithFileUploads;

    public $step = 1;

    // Step 1: Identity & Metadata
    public $name;
    public $booking_type = 'personal'; // personal or company
    public $instance; // Was agency
    public $whatsapp;
    public $email;
    public $address;
    public $technical_data = []; // Array of ['nature' => '', 'activity' => '', 'dimension' => '']
    
    // Step 2: Service + Documents
    public $service_id;
    public $selectedService;
    public $temp_supporting_documents = []; // Temp for new file uploads
    public $supporting_documents = [];      // Accumulated files (1-6)
    public $supporting_document_links = []; // External document links (1-5)
    public $coordinate_file;                // Single file upload

    // Step 3: Location
    public $consultation_location_id;
    public $selectedLocation;

    // Step 4: Schedule (Multi-select)
    public $date;
    public $time_slot;
    public $availableSlots = 0;
    public $schedules_list = [];
    
    // Step 5: Signature & T&C
    public $tanda_tangan;
    public $agreed_to_terms = false;
    
    // UI State
    public $success = false;
    public $errorMessage;

    public function mount()
    {
        $this->technical_data = [
            ['nature' => 'non_business', 'activity' => '', 'location' => '', 'dimension' => '']
        ];
    }

    public function addTechnicalRow()
    {
        $this->technical_data[] = ['nature' => 'non_business', 'activity' => '', 'location' => '', 'dimension' => ''];
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
        // Reset documents when service changes
        $this->supporting_documents = [];
        $this->temp_supporting_documents = [];
        $this->supporting_document_links = [];
        $this->coordinate_file = null;
    }

    public function updatedTempSupportingDocuments()
    {
        // Merge newly uploaded files into main array
        foreach ($this->temp_supporting_documents as $file) {
            if (count($this->supporting_documents) < 6) {
                $this->supporting_documents[] = $file;
            }
        }
        // Clear temp array
        $this->temp_supporting_documents = [];
    }

    public function selectLocation($id)
    {
        $this->consultation_location_id = $id;
        $this->selectedLocation = ConsultationLocation::find($id);
    }

    public function addSchedule()
    {
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

        $this->time_slot = null;
    }

    public function removeSchedule($index)
    {
        unset($this->schedules_list[$index]);
        $this->schedules_list = array_values($this->schedules_list);
    }

    public function removeDocument($index)
    {
        unset($this->supporting_documents[$index]);
        $this->supporting_documents = array_values($this->supporting_documents);
    }

    public function addSupportingDocumentLink()
    {
        if (count($this->supporting_document_links) < 5) {
            $this->supporting_document_links[] = '';
        }
    }

    public function removeSupportingDocumentLink($index)
    {
        unset($this->supporting_document_links[$index]);
        $this->supporting_document_links = array_values($this->supporting_document_links);
    }

    protected function normalizeSupportingDocumentLinks(): void
    {
        $this->supporting_document_links = collect($this->supporting_document_links ?? [])
            ->map(fn ($link) => is_string($link) ? trim($link) : null)
            ->filter(fn ($link) => filled($link))
            ->values()
            ->all();
    }

    protected function validateSupportingDocuments(): void
    {
        $this->normalizeSupportingDocumentLinks();

        $this->validate([
            'supporting_documents' => 'nullable|array|max:6',
            'supporting_documents.*' => 'file|mimes:pdf,doc,docx,jpg,jpeg,png|max:51200',
            'supporting_document_links' => 'nullable|array|max:5',
            'supporting_document_links.*' => 'required|url|max:5000',
            'coordinate_file' => 'nullable|file|mimes:xlsx,xls,pdf,doc,docx,ppt,pptx|max:51200',
        ], [
            'supporting_documents.max' => 'Maksimal 6 dokumen pendukung.',
            'supporting_documents.*.mimes' => 'Format dokumen: PDF, DOC, DOCX, JPG, PNG.',
            'supporting_documents.*.max' => 'Ukuran maksimal per file 50MB.',
            'supporting_document_links.max' => 'Maksimal 5 link dokumen.',
            'supporting_document_links.*.required' => 'Link dokumen tidak boleh kosong.',
            'supporting_document_links.*.url' => 'Link dokumen harus berupa URL yang valid.',
            'supporting_document_links.*.max' => 'Panjang link dokumen terlalu panjang.',
            'coordinate_file.mimes' => 'Format file koordinat: XLSX, XLS, PDF, DOC, DOCX, PPT, PPTX.',
            'coordinate_file.max' => 'Ukuran maksimal file koordinat 50MB.',
        ]);

        if (empty($this->supporting_documents) && empty($this->supporting_document_links)) {
            $message = 'Mohon upload minimal 1 dokumen pendukung atau isi minimal 1 link dokumen.';

            throw ValidationException::withMessages([
                'supporting_documents' => $message,
                'supporting_document_links' => $message,
            ]);
        }
    }

    public function nextStep()
    {
        if ($this->step == 1) {
            $this->validate([
                'name' => 'required|min:3',
                'booking_type' => 'required',
                'instance' => 'required_if:booking_type,company',
                'whatsapp' => 'required|numeric',
                'email' => 'required|email',
                'address' => 'nullable|string',
                'technical_data' => 'required|array|min:1',
                'technical_data.*.activity' => 'required|string|min:2',
                'technical_data.*.location' => 'required|string|min:3',
                'technical_data.*.dimension' => 'required|string',
            ], [
                'name.required' => 'Nama wajib diisi.',
                'email.required' => 'Email wajib diisi.',
                'whatsapp.required' => 'Nomor WhatsApp wajib diisi.',
                'technical_data.required' => 'Mohon isi data teknis minimal satu baris.',
                'technical_data.*.activity.required' => 'Jenis kegiatan wajib diisi.',
                'technical_data.*.location.required' => 'Lokasi kegiatan wajib diisi.',
                'technical_data.*.dimension.required' => 'Luasan/Panjang wajib diisi.',
            ]);
        }
        elseif ($this->step == 2) {
            if (!$this->service_id) {
                $this->addError('service_id', 'Silakan pilih layanan terlebih dahulu.');
                return;
            }

            // Validate documents if service requires them
            if ($this->selectedService?->requires_documents) {
                $this->validateSupportingDocuments();
            }
        }
        elseif ($this->step == 3) {
            if (!$this->consultation_location_id) {
                $this->addError('consultation_location_id', 'Silakan pilih lokasi konsultasi.');
                return;
            }
        }
        elseif ($this->step == 4) {
            $this->validate([
                'schedules_list' => 'required|array|min:1'
            ], ['schedules_list.required' => 'Mohon pilih minimal satu jadwal.']);
        }
        elseif ($this->step == 5) {
            $this->validate([
                'tanda_tangan' => 'required|string',
                'agreed_to_terms' => 'accepted',
            ], [
                'tanda_tangan.required' => 'Tanda tangan wajib diisi sebelum melanjutkan.',
                'agreed_to_terms.accepted' => 'Anda harus menyetujui pernyataan persetujuan.',
            ]);
        }

        if ($this->step < 5) {
            $this->step++;
        }
    }

    public function previousStep()
    {
        if ($this->step > 1) {
            $this->step--;
        }
    }

    public function submit()
    {
        // Final validation
        $this->validate([
            'schedules_list' => 'required|array|min:1',
            'technical_data' => 'required|array|min:1',
            'service_id' => 'required|exists:services,id',
            'consultation_location_id' => 'required|exists:consultation_locations,id',
            'tanda_tangan' => 'required|string',
            'agreed_to_terms' => 'accepted',
        ]);

        if ($this->selectedService?->requires_documents) {
            $this->validateSupportingDocuments();
        }

        try {
            DB::transaction(function () {
                // 1. Create Client first to get ticket_number
                $client = Client::create([
                    'name' => $this->name,
                    'booking_type' => $this->booking_type,
                    'instance' => $this->booking_type == 'company' ? $this->instance : null,
                    'whatsapp' => $this->whatsapp,
                    'email' => $this->email,
                    'address' => $this->address,
                    'activity_type' => $this->technical_data[0]['nature'] ?? 'non_business',
                    'metadata' => ['data_teknis' => $this->technical_data],
                    'contact_details' => [],
                    'service_id' => $this->service_id,
                    'consultation_location_id' => $this->consultation_location_id,
                    'agreed_to_terms' => $this->agreed_to_terms,
                ]);

                // 2. Upload supporting documents if any
                $documentPaths = [];
                $documentLinks = [];
                if ($this->selectedService?->requires_documents && !empty($this->supporting_documents)) {
                    $folder = 'client-documents/' . $client->ticket_number;
                    foreach ($this->supporting_documents as $doc) {
                        $path = $doc->store($folder, 'public');
                        $documentPaths[] = $path;
                    }
                }

                if ($this->selectedService?->requires_documents) {
                    $this->normalizeSupportingDocumentLinks();
                    $documentLinks = $this->supporting_document_links;
                }
                
                // 3. Upload coordinate file if any
                $coordinatePath = null;
                if ($this->coordinate_file) {
                    $folder = 'client-documents/' . $client->ticket_number;
                    $coordinatePath = $this->coordinate_file->store($folder, 'public');
                }

                // 4. Update client with file paths
                if (!empty($documentPaths) || !empty($documentLinks) || $coordinatePath) {
                    $client->update([
                        'supporting_documents' => $documentPaths,
                        'supporting_document_links' => $documentLinks,
                        'coordinate_file' => $coordinatePath,
                    ]);
                }

                // 5. Determine if location is online
                $isOnline = $this->selectedLocation?->is_online ?? false;

                // 6. Create Schedules
                foreach ($this->schedules_list as $slot) {
                    Schedule::create([
                        'client_id' => $client->id,
                        'date' => $slot['date'],
                        'start_time' => $slot['time'],
                        'end_time' => Carbon::parse($slot['time'])->addHour()->format('H:i'),
                        'is_online' => $isOnline,
                    ]);
                }

                // 7. Automatically generate Berita Acara
                $firstScheduleDate = count($this->schedules_list) > 0 ? $this->schedules_list[0]['date'] : null;
                $locationName = $this->selectedLocation ? $this->selectedLocation->name : null;
                
                \App\Models\BeritaAcara::create([
                    'client_id' => $client->id,
                    'tanggal_pelaksanaan' => $firstScheduleDate, // as requested
                    'lokasi_permohonan' => $locationName,
                    'tanda_tangan_pemohon' => $this->tanda_tangan,
                    'status' => 'draft',
                    'attendance_is_open' => false,
                ]);

                // 8. Send notification
                app(NotificationService::class)->sendBookingCreated($client);
            });

            $this->success = true;

        } catch (\Exception $e) {
            $this->errorMessage = 'Terjadi kesalahan sistem: ' . $e->getMessage();
        }
    }

    public function getServicesProperty()
    {
        return Service::where('is_active', true)->get();
    }

    public function getConsultationLocationsProperty()
    {
        return ConsultationLocation::where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }

    public function getAvailableTimeSlotsProperty()
    {
        if (!$this->date) return [];

        $isFriday = Carbon::parse($this->date)->isFriday();
        $slots = [];

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
            
            if ($isFriday && $time === '15:00') {
                $endTime = '16:30';
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
