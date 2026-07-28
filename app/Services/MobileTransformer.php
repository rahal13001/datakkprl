<?php

namespace App\Services;

use App\Models\Assignment;
use App\Models\BeritaAcara;
use App\Models\Client;
use App\Models\ConsultationReport;
use App\Models\PublicFeedback;
use App\Models\SatisfactionSurvey;
use App\Models\Schedule;
use App\Models\User;

class MobileTransformer
{
    public function user(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'nip' => $user->nip,
            'jabatan' => $user->jabatan,
            'instansi' => $user->instansi,
            'avatar_url' => $user->avatar_url,
            'roles' => $user->getRoleNames()->values(),
            'permissions' => $user->getAllPermissions()->pluck('name')->sort()->values(),
            'active' => (bool) $user->status,
        ];
    }

    public function clientSummary(Client $client): array
    {
        return [
            'ticket_number' => $client->ticket_number,
            'name' => $client->name,
            'instance' => $client->instance,
            'status' => $client->status,
            'activity_type' => $client->activity_type,
            'service' => $client->service ? [
                'id' => $client->service->id,
                'name' => $client->service->name,
            ] : null,
            'location' => $client->consultationLocation ? [
                'id' => $client->consultationLocation->id,
                'name' => $client->consultationLocation->name,
                'is_online' => (bool) $client->consultationLocation->is_online,
            ] : null,
            'next_schedule' => $client->schedules->sortBy('date')->first()
                ? $this->schedule($client->schedules->sortBy('date')->first())
                : null,
            'version' => $this->version($client),
            'updated_at' => $client->updated_at?->toISOString(),
        ];
    }

    public function client(Client $client): array
    {
        return array_merge($this->clientSummary($client), [
            'booking_type' => $client->booking_type,
            'email' => $client->email,
            'whatsapp' => $client->whatsapp,
            'address' => $client->address,
            'metadata' => $client->metadata,
            'supporting_documents' => collect($client->supporting_documents ?? [])
                ->map(fn (string $path) => ['path' => $path, 'name' => basename($path)])
                ->values(),
            'supporting_document_links' => $client->supporting_document_links ?? [],
            'coordinate_file' => $client->coordinate_file
                ? ['path' => $client->coordinate_file, 'name' => basename($client->coordinate_file)]
                : null,
            'schedules' => $client->schedules->map(fn (Schedule $schedule) => $this->schedule($schedule))->values(),
            'assignments' => $client->assignments->map(fn (Assignment $assignment) => $this->assignment($assignment))->values(),
            'consultation_reports' => $client->consultationReports
                ->map(fn (ConsultationReport $report) => $this->report($report))->values(),
            'berita_acara' => $client->beritaAcara ? $this->beritaAcara($client->beritaAcara) : null,
            'satisfaction_survey' => $client->satisfactionSurvey
                ? $this->satisfactionSurvey($client->satisfactionSurvey)
                : null,
        ]);
    }

    public function schedule(Schedule $schedule): array
    {
        return [
            'id' => $schedule->id,
            'date' => $schedule->date?->toDateString(),
            'start_time' => $schedule->start_time,
            'end_time' => $schedule->end_time,
            'is_online' => (bool) $schedule->is_online,
            'meeting_link' => $schedule->meeting_link,
            'version' => $this->version($schedule),
        ];
    }

    public function assignment(Assignment $assignment): array
    {
        return [
            'id' => $assignment->id,
            'status' => $assignment->status,
            'score' => $assignment->score,
            'schedule_id' => $assignment->schedule_id,
            'officer' => $assignment->user ? [
                'id' => $assignment->user->id,
                'name' => $assignment->user->name,
                'nip' => $assignment->user->nip,
                'jabatan' => $assignment->user->jabatan,
            ] : null,
            'version' => $this->version($assignment),
        ];
    }

    public function report(ConsultationReport $report): array
    {
        return [
            'id' => $report->id,
            'content' => $report->content,
            'status' => $report->status,
            'documentation' => collect($report->documentation ?? [])
                ->map(fn (string $path) => ['path' => $path, 'name' => basename($path)])
                ->values(),
            'reviewed_by' => $report->reviewed_by,
            'reviewed_at' => $report->reviewed_at?->toISOString(),
            'has_signature' => filled($report->officer_signature),
            'signed_by' => $report->signer ? [
                'id' => $report->signer->id,
                'name' => $report->signer->name,
                'jabatan' => $report->signer->jabatan,
            ] : null,
            'signed_at' => $report->signed_at?->toISOString(),
            'version' => $this->version($report),
            'created_at' => $report->created_at?->toISOString(),
        ];
    }

    public function beritaAcara(BeritaAcara $record): array
    {
        return [
            'id' => $record->id,
            'nomor_berita_acara' => $record->nomor_berita_acara,
            'kbli' => $record->kbli,
            'tanggal_pelaksanaan' => $record->tanggal_pelaksanaan?->toDateString(),
            'lokasi_permohonan' => $record->lokasi_permohonan,
            'hasil_pendampingan' => $record->hasil_pendampingan,
            'status' => $record->status,
            'attendance_is_open' => (bool) $record->attendance_is_open,
            'signing_deadline' => $record->signing_deadline?->toISOString(),
            'attachments' => [
                'map' => $record->lampiran_peta,
                'documentation' => $record->lampiran_dokumentasi ?? [],
                'other' => $record->lampiran_lainnya ?? [],
            ],
            'attendees' => $record->attendees->map(fn ($attendee) => [
                'id' => $attendee->id,
                'name' => $attendee->nama,
                'position' => $attendee->jabatan,
                'institution' => $attendee->instansi,
                'email' => $attendee->email,
                'phone' => $attendee->no_hp,
                'is_officer' => (bool) $attendee->is_officer,
                'is_signatory' => (bool) $attendee->is_signatory,
                'confirmed_at' => $attendee->confirmed_at?->toISOString(),
                'signing_url' => $attendee->getSigningUrl(),
            ])->values(),
            'version' => $this->version($record),
        ];
    }

    public function satisfactionSurvey(SatisfactionSurvey $survey): array
    {
        return [
            'id' => $survey->id,
            'ticket_number' => $survey->client?->ticket_number,
            'criticism' => $survey->criticism,
            'suggestion' => $survey->suggestion,
            'estimated_cost_savings' => $survey->estimated_cost_savings,
            'created_at' => $survey->created_at?->toISOString(),
        ];
    }

    public function publicFeedback(PublicFeedback $feedback): array
    {
        return [
            'id' => $feedback->id,
            'is_anonymous' => (bool) $feedback->is_anonymous,
            'submitter' => $feedback->submitter_display,
            'feedback' => $feedback->feedback,
            'suggestion' => $feedback->suggestion,
            'officers' => $feedback->users->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'jabatan' => $user->jabatan,
            ])->values(),
            'created_at' => $feedback->created_at?->toISOString(),
        ];
    }

    public function version($model): ?string
    {
        return $model->updated_at?->format('Y-m-d\TH:i:s.uP');
    }
}
