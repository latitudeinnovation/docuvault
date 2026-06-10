<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        config()->set('livewire.temporary_file_upload.rules', [
            'required',
            'file',
            'max:'.config('docuvault.documents.max_upload_size'),
        ]);
    }
}
