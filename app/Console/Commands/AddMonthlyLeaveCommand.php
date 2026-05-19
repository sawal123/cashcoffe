<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;

class AddMonthlyLeaveCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attendance:add-monthly-leave';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add 2 days to jatah_cuti for all active users';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Add 2 to jatah_cuti for all users. You can add conditions here (e.g., active users only)
        User::query()->increment('jatah_cuti', 2);
        
        $this->info('Successfully added 2 days to jatah_cuti for all users.');
    }
}
