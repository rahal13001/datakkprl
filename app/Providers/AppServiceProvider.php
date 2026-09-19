<?php

namespace App\Providers;

use App\Contracts\KkprlProposalAccessVerifier;
use App\Filament\Layanankkprl\Resources\Clients\Pages\ViewClient;
use App\Models\Activity;
use App\Models\Assignment;
use App\Models\BeritaAcara;
use App\Models\Client;
use App\Models\ConsultationReport;
use App\Models\Faq;
use App\Models\PublicFeedback;
use App\Models\Regulation;
use App\Models\SatisfactionSurvey;
use App\Models\Schedule;
use App\Observers\ActivityObserver;
use App\Observers\AssignmentObserver;
use App\Observers\BeritaAcaraObserver;
use App\Observers\ClientObserver;
use App\Observers\ConsultationReportObserver;
use App\Observers\FaqObserver;
use App\Observers\PublicFeedbackObserver;
use App\Observers\RegulationObserver;
use App\Observers\SatisfactionSurveyObserver;
use App\Observers\ScheduleObserver;
use App\Services\KkprlTicketPhoneAccessVerifier;
use App\Support\WindowsSafeFilesystem;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\Compilers\BladeCompiler;
use ReflectionProperty;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(KkprlProposalAccessVerifier::class, KkprlTicketPhoneAccessVerifier::class);

        $this->app->forgetInstance('files');
        $this->app->forgetInstance(Filesystem::class);

        $this->app->singleton('files', fn (): WindowsSafeFilesystem => new WindowsSafeFilesystem);
        $this->app->alias('files', Filesystem::class);

        $this->app->afterResolving('blade.compiler', function (BladeCompiler $compiler): void {
            $files = new ReflectionProperty($compiler, 'files');
            $files->setAccessible(true);
            $files->setValue($compiler, $this->app->make('files'));
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Activity::observe(ActivityObserver::class);
        Faq::observe(FaqObserver::class);
        Regulation::observe(RegulationObserver::class);
        Client::observe(ClientObserver::class);
        Assignment::observe(AssignmentObserver::class);
        ConsultationReport::observe(ConsultationReportObserver::class);
        BeritaAcara::observe(BeritaAcaraObserver::class);
        Schedule::observe(ScheduleObserver::class);
        SatisfactionSurvey::observe(SatisfactionSurveyObserver::class);
        PublicFeedback::observe(PublicFeedbackObserver::class);

        FilamentView::registerRenderHook(
            PanelsRenderHook::PAGE_START,
            fn (): string => '<div wire:poll.5s="refreshRecord" class="hidden"></div>',
            scopes: [ViewClient::class],
        );
    }
}
