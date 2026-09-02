<?php

namespace App\Console\Commands;

use App\Models\BankSetting;
use App\Models\BankSnapshot;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CalculateDailyBanks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:calculate-daily-banks';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Save a daily snapshot of every bank capital amount';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $bankSettings = BankSetting::all();
        $today = Carbon::today();
        if ($bankSettings->isEmpty()) {
            $this->warn('No bank settings found to snapshot.');
            return 0;
        }

        $count = 0;
        foreach ($bankSettings as $setting) {
            try {
                BankSnapshot::updateOrCreate(
                    [
                        'bank_setting_id' => $setting->id,
                        'snapshot_date'   => $today,
                    ],
                    [
                        'country_id'      => $setting->country_id,
                        'capital'         => $setting->amount,
                    ]
                );
                $count++;
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning("Could not snapshot bank ID {$setting->id}: " . $e->getMessage());
            }
        }
        $this->info("Successfully saved capital snapshots for {$count} bank(s) on {$today->toDateString()}.");

        return 0;
    }
}
