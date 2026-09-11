<?php

namespace App\Console\Commands;

use App\Models\BankSetting;
use App\Models\BankSnapshot;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

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
        $lock = Cache::lock('calculate_daily_banks_lock', 120);

        if (!$lock->get()) {
            $this->warn('The daily bank calculation is already running.');
            return 1;
        }

        Cache::put('is_calculating_snapshot', true, now()->addMinutes(5));
        //sleep(60);

        try {
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
                    Log::warning("Could not snapshot bank ID {$setting->id}: " . $e->getMessage());
                }
            }

            $this->info("Successfully saved capital snapshots for {$count} bank(s) on {$today->toDateString()}.");
            return 0;

        } finally {
            // Always clear the block flag and release the lock even if errors occur
            Cache::forget('is_calculating_snapshot');
            $lock->release();
        }
    }
}
