<?php

namespace App\Livewire\Setting;

use App\Models\Setting;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;

class Index extends Component
{
    use WithFileUploads;

    public $appName;
    public $newLogo;
    public $newIcon;
    public $currentLogo;
    public $currentIcon;

    public function mount()
    {
        $setting = Setting::first();
        if ($setting) {
            $this->appName     = $setting->app_name;
            $this->currentLogo = $setting->logo;
            $this->currentIcon = $setting->icon;
        } else {
            $this->appName     = 'WorkSync CashCoffee';
            $this->currentLogo = 'logo/logow.png';
            $this->currentIcon = 'logo/logow.png';
        }
    }

    public function save()
    {
        $this->validate([
            'appName' => 'required|string|max:255',
            'newLogo' => 'nullable|image|max:2048',
            'newIcon' => 'nullable|image|max:1024',
        ]);

        $setting = Setting::first();
        if (!$setting) {
            $setting = new Setting();
        }

        $setting->app_name = $this->appName;

        if ($this->newLogo) {
            $path = $this->newLogo->store('settings', 'public');
            $setting->logo = 'storage/' . $path;
            $this->currentLogo = $setting->logo;
            $this->newLogo = null;
        }

        if ($this->newIcon) {
            $path = $this->newIcon->store('settings', 'public');
            $setting->icon = 'storage/' . $path;
            $this->currentIcon = $setting->icon;
            $this->newIcon = null;
        }

        $setting->save();

        session()->flash('success', 'Konfigurasi Web, Logo, dan Icon berhasil diperbarui secara dinamis!');
        
        return $this->redirect('/setting', navigate: true);
    }

    public function render()
    {
        return view('livewire.setting.index')->layout('layouts.app');
    }
}
