<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
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
        // Komponen Blade dikelompokkan per folder di resources/views/components (ui, nav, booking, dst).
        // Semua subfolder didaftarkan di sini, jadi tag tetap <x-nama_komponen> tanpa nama folder.
        // Syarat: nama file komponen harus unik di semua subfolder.
        foreach (glob(resource_path('views/components/*'), GLOB_ONLYDIR) as $folder) {
            Blade::anonymousComponentPath($folder);
        }
    }
}
