<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\BankSetting;
use App\Models\BankSnapshot;
use Carbon\Carbon;

class CalculateDailyBankAmounts extends Command
{
    protected $signature = 'banks:calculate-daily';
    protected $description = 'Calculate and save daily capital snapshot for all active bank settings at midnight';

    public function handle()
    {
        $today = Carbon::yesterday()->toDateString(); // Or Carbon::today() depending on preference

        $bankSettings = BankSetting::all();

        foreach ($bankSettings as $bank) {
            BankSnapshot::updateOrCreate(
                [
                    'bank_setting_id' => $bank->id,
                    'snapshot_date'   => $today,
                ],
                [
                    'country_id'      => $bank->country_id,
                    'capital'         => $bank->capital,
                ]
            );
        }

        $this->info('Daily bank amounts successfully saved for ' . $today);
    }
}
