<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class AutoClockOutCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attendance:auto-clock-out {date?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically detect missing clock out and apply dynamic fines daily';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $date = $this->argument('date') ?: now()->toDateString();
        
        $absensis = \App\Models\Absensi::where('tanggal', $date)
            ->whereNotNull('jam_masuk')
            ->whereNull('jam_keluar')
            ->get();

        $count = 0;
        foreach ($absensis as $abs) {
            $denda = 0;
            if ($abs->shift) {
                $denda = $abs->shift->denda_missing_clockout;
            }

            $abs->update([
                'status' => 'tidak clock out',
                'keterangan' => 'tidak clock out',
                'denda_missing_clockout' => $denda,
            ]);
            $count++;
        }

        $this->info("Successfully updated {$count} attendance records to 'tidak clock out' with appropriate fines for date {$date}.");
        return self::SUCCESS;
    }
}
