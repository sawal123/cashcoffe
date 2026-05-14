<?php

namespace App\Providers;

use App\Models\Setting;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Schema;
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
        try {
            if (Schema::hasTable('settings')) {
                $setting = Setting::first();
                if (!$setting) {
                    $setting = new Setting([
                        'app_name' => 'WorkSync CashCoffee',
                        'logo'     => 'logo/logow.png',
                        'icon'     => 'logo/logow.png',
                    ]);
                }
                View::share('webSetting', $setting);
            }
        } catch (\Exception $e) {
            // Abaikan error jika koneksi database belum stabil saat boot
        }
    }
}
