<?php

namespace App\Providers;

use App\Support\WindowsSafeFilesystem;
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
        \App\Models\Activity::observe(\App\Observers\ActivityObserver::class);
        \App\Models\Faq::observe(\App\Observers\FaqObserver::class);
        \App\Models\Regulation::observe(\App\Observers\RegulationObserver::class);
        \App\Models\Client::observe(\App\Observers\ClientObserver::class);
        \App\Models\Assignment::observe(\App\Observers\AssignmentObserver::class);
        \App\Models\ConsultationReport::observe(\App\Observers\ConsultationReportObserver::class);
        \App\Models\BeritaAcara::observe(\App\Observers\BeritaAcaraObserver::class);

        \Filament\Support\Facades\FilamentView::registerRenderHook(
            \Filament\View\PanelsRenderHook::PAGE_START,
            fn (): string => '<div wire:poll.5s="refreshRecord" class="hidden"></div>',
            scopes: [\App\Filament\Layanankkprl\Resources\Clients\Pages\ViewClient::class],
        );
    }
}
