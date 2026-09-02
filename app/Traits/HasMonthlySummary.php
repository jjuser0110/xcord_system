<?php
namespace App\Traits;

use App\Models\Transaction;
use App\Models\BankSetting;
use Illuminate\Support\Facades\DB;

trait HasMonthlySummary
{
    private function refreshMonthlySummary($bankSettingId, $closingMonth)
    {
        $txQuery = Transaction::where('bank_setting_id', $bankSettingId)
            ->where('closing_month', $closingMonth);

        $count = (clone $txQuery)->count();
        $totalIn = (clone $txQuery)->where('transfer_direction', '+')->sum('amount');
        $totalOut = (clone $txQuery)->where('transfer_direction', '-')->sum('amount');

        $lastTx = (clone $txQuery)->orderBy('created_at', 'desc')->orderBy('id', 'desc')->first();
        $bankSetting = BankSetting::find($bankSettingId);

        if ($lastTx) {
            $endBalance = $lastTx->end_balance;
        } else {
            $prevSummary = DB::table('bank_monthly_summaries')
                ->where('bank_setting_id', $bankSettingId)
                ->where('closing_month', '<', $closingMonth)
                ->orderBy('closing_month', 'desc')
                ->first();

            $endBalance = $prevSummary ? $prevSummary->end_balance : ($bankSetting->capital ?? 0);
        }

        DB::table('bank_monthly_summaries')->updateOrInsert(
            ['bank_setting_id' => $bankSettingId, 'closing_month' => $closingMonth],
            [
                'end_balance' => $endBalance,
                'total_in' => $totalIn,
                'total_out' => $totalOut,
                'transaction_count' => $count,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }
}
