<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Models\BankSetting;
use App\Models\BankLog;
use App\Models\BankSnapshot;
use App\Models\ProviderSettlement;
use App\Models\Purpose;
use App\Traits\CountryScopeTrait;
use App\Traits\HasMonthlySummary;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class TransactionController extends Controller
{
    use CountryScopeTrait;
    use HasMonthlySummary;

    public function index(Request $request)
    {
        $currentMonth = $request->input('month', Carbon::now()->format('Y-m'));

        $startDate = Carbon::parse($currentMonth)->startOfMonth()->format('d.m.Y');
        $endDate = Carbon::parse($currentMonth)->endOfMonth()->format('d.m.Y');
        $endOfMonthDate = Carbon::parse($currentMonth)->endOfMonth();

        // 1. Fetch paginated bank settings based on country scope
        $query = BankSetting::with(['bank', 'country'])->where('created_at', '<=', $endOfMonthDate)->orderBy('id', 'desc');
        $this->scopeByCountry($query);
        $bankSettings = $query->paginate(50);

        // 2. Attach monthly stats efficiently (bulk fetch or from summary table)
        $bankSettingIds = $bankSettings->pluck('id');

        // Fetch summaries for these specific visible accounts for the chosen month
        $summaries = DB::table('bank_monthly_summaries')
            ->whereIn('bank_setting_id', $bankSettingIds)
            ->where('closing_month', $currentMonth)
            ->get()
            ->keyBy('bank_setting_id');

        foreach ($bankSettings as $setting) {
            $summary = $summaries->get($setting->id);

            // If summary exists, use it; otherwise fallback dynamically for past un-synced data
            $setting->monthly_balance = $summary ? $summary->end_balance : 0.00;
            $setting->month_transaction_count = $summary ? $summary->transaction_count : 0;
        }

        // 3. Compute Table-Wide Totals for the current page or entire filtered scope
        // (Using database aggregation query instead of PHP loops for speed)
        $totalsQuery = DB::table('bank_monthly_summaries')
            ->whereIn('bank_setting_id', $bankSettingIds)
            ->where('closing_month', $currentMonth);

        $totalTableCount = $totalsQuery->sum('transaction_count');
        $totalTableBalance = $totalsQuery->sum('end_balance');

        return view('transaction.index', compact(
            'bankSettings',
            'currentMonth',
            'startDate',
            'endDate',
            'totalTableCount',
            'totalTableBalance'
        ));
    }

    public function log(Request $request, BankSetting $bank_setting)
    {
        $currentMonth = $request->input('month', Carbon::now()->format('Y-m'));

        $query = Transaction::with(['purpose', 'creator'])
            ->where('bank_setting_id', $bank_setting->id)
            ->where('closing_month', $currentMonth)
            ->orderBy('id', 'desc');

        $transactions = $query->paginate(50);

        // Calculate total In and Total Out for the month
        $summaryQuery = Transaction::where('bank_setting_id', $bank_setting->id)
            ->where('closing_month', $currentMonth);

        $totalIn = (clone $summaryQuery)->where('transfer_direction', '+')->sum('amount');
        $totalOut = (clone $summaryQuery)->where('transfer_direction', '-')->sum('amount');

        return view('transaction.log', compact('bank_setting', 'transactions', 'currentMonth', 'totalIn', 'totalOut'));
    }

    public function create(Request $request)
    {
        $query = BankSetting::with('bank');
        $this->scopeByCountry($query);
        $bankSettings = $query->get();

        // Purpose filtering: is_global = 1 OR linked to session/active country context
        $activeCountryId = session('active_country_id');
        $userCountryId = Auth::user()->country_id ?? null;

        $purposesQuery = Purpose::query();
        $purposesQuery->where(function ($q) use ($activeCountryId, $userCountryId) {
            $q->where('is_global', 1);

            if ($activeCountryId && $activeCountryId !== 'no') {
                $q->orWhereHas('countries', function ($sub) use ($activeCountryId) {
                    $sub->where('countries.id', $activeCountryId);
                });
            } elseif ($userCountryId) {
                $q->orWhereHas('countries', function ($sub) use ($userCountryId) {
                    $sub->where('countries.id', $userCountryId);
                });
            }
        });

        $purposes = $purposesQuery->where('is_active', 1)->get();

        $selectedBank = null;
        if ($request->filled('bank_setting_id')) {
            $selectedBank = BankSetting::with('bank')->find($request->bank_setting_id);
        }

        return view('transaction.create', compact('bankSettings', 'purposes', 'selectedBank'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'transaction_date'       => 'required|date',
            'type'                   => 'required|in:own,customer',
            'transfer_direction'     => 'required|in:+,-',

            // Allow nullable/exists checks flexibly without strict required_if traps
            'source_bank_id'         => 'nullable|exists:bank_settings,id',
            'target_bank_id'         => 'nullable|exists:bank_settings,id',
            'bank_setting_id'        => 'nullable|exists:bank_settings,id',

            // Multi-row items validation
            'items'                  => 'required|array|min:1',
            'items.*.amount'         => 'required|numeric|min:0.01|max:999999999999.99',
            'items.*.purpose_id'     => 'required|exists:purposes,id',
            'items.*.remark_1'       => 'nullable|string',
            'items.*.remark_2'       => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $txDate = Carbon::parse($request->transaction_date);
            $closingMonth = Carbon::now()->format('Y-m');
            $type = $request->type;
            $direction = $request->transfer_direction;
            $affectedBanks = [];

            if ($type === 'own') {
                // Determine the selected page bank (the bank whose log you are currently viewing)
                $selectedBankId = $request->input('bank_setting_id') ?? request()->route('bank_setting_id');

                if ($direction === '+') {
                    // Bank In: Money flows FROM the chosen dropdown source account TO your selected bank account (Target)
                    $sourceBankId = $request->source_bank_id;
                    $targetBankId = $selectedBankId ?? $request->target_bank_id;
                } else {
                    // Bank Out: Money flows FROM your selected bank account (Source) TO the chosen dropdown target account
                    $sourceBankId = $selectedBankId ?? $request->source_bank_id;
                    $targetBankId = $request->target_bank_id;
                }

                // Ensure both source and target are present and different
                if (!$sourceBankId || !$targetBankId) {
                    throw new \Exception('Both source and target bank accounts must be selected.');
                }
                if ($sourceBankId == $targetBankId) {
                    throw new \Exception('The source and target bank accounts cannot be the same.');
                }

                foreach ($request->items as $item) {
                    $amount = $item['amount'];
                    $purpose = Purpose::find($item['purpose_id']);

                    // Lock both accounts to prevent race conditions and compute accurate balances
                    $sourceBank = BankSetting::where('id', $sourceBankId)->lockForUpdate()->firstOrFail();
                    $targetBank = BankSetting::where('id', $targetBankId)->lockForUpdate()->firstOrFail();

                    // 1. Calculate Source Bank (Outflow -)
                    $sourceStart = $sourceBank->amount;
                    $sourceEnd = $sourceStart - $amount;
                    $sourceBank->update(['amount' => $sourceEnd]);
                    $this->updateTodaySnapshot($sourceBank);

                    // 2. Calculate Target Bank (Inflow +)
                    $targetStart = $targetBank->amount;
                    $targetEnd = $targetStart + $amount;
                    $targetBank->update(['amount' => $targetEnd]);
                    $this->updateTodaySnapshot($targetBank);

                    // Shared batch identifier reference
                    $batchUuid = Str::uuid();

                    // --- TRANSACTION RECORD 1: Source Bank (Outflow) ---
                    $sourceTransaction = Transaction::create([
                        'transaction_no'         => 'TXN-OWN-OUT-' . $batchUuid,
                        'transaction_date'       => $txDate,
                        'country_id'             => $sourceBank->country_id,
                        'bank_setting_id'        => $sourceBank->id,
                        'type'                   => 'own',
                        'target_bank_setting_id' => $targetBank->id,
                        'transfer_direction'     => '-',
                        'amount'                 => $amount,
                        'start_balance'          => $sourceStart,
                        'end_balance'            => $sourceEnd,
                        'target_start_balance'   => $targetStart,
                        'target_end_balance'     => $targetEnd,
                        'purpose_id'             => $item['purpose_id'],
                        'remark_1'               => $item['remark_1'] ?? null,
                        'remark_2'               => $item['remark_2'] ?? null,
                        'closing_month'          => $closingMonth,
                        'created_by_id'          => Auth::id(),
                    ]);

                    // --- TRANSACTION RECORD 2: Target Bank (Inflow) ---
                    $targetTransaction = Transaction::create([
                        'transaction_no'         => 'TXN-OWN-IN-' . $batchUuid,
                        'transaction_date'       => $txDate,
                        'country_id'             => $targetBank->country_id,
                        'bank_setting_id'        => $targetBank->id,
                        'type'                   => 'own',
                        'target_bank_setting_id' => $sourceBank->id,
                        'transfer_direction'     => '+',
                        'amount'                 => $amount,
                        'start_balance'          => $targetStart,
                        'end_balance'            => $targetEnd,
                        'target_start_balance'   => $sourceStart,
                        'target_end_balance'     => $sourceEnd,
                        'purpose_id'             => $item['purpose_id'],
                        'remark_1'               => $item['remark_1'] ?? null,
                        'remark_2'               => $item['remark_2'] ?? null,
                        'closing_month'          => $closingMonth,
                        'created_by_id'          => Auth::id(),
                    ]);

                    if ($purpose && $purpose->has_provider_settlement) {
                        ProviderSettlement::create([
                            'transaction_id'    => $sourceTransaction->id,
                            'purpose_id'        => $purpose->id,
                            'country_id'        => $sourceBank->country_id,
                            'bank_name'         => $sourceBank->bank->bank_name ?? 'Bank',
                            'settlement_amount' => -$amount,
                            'provider_name'     => $purpose->provider_name,
                            'created_by_id'     => Auth::id(),
                        ]);

                        // 2. Target Provider Settlement (Positive)
                        ProviderSettlement::create([
                            'transaction_id'    => $targetTransaction->id,
                            'purpose_id'        => $purpose->id,
                            'country_id'        => $targetBank->country_id,
                            'bank_name'         => $targetBank->bank->bank_name ?? 'Bank',
                            'settlement_amount' => $amount,
                            'provider_name'     => $purpose->provider_name,
                            'created_by_id'     => Auth::id(),
                        ]);
                    }

                    $affectedBanks[$sourceBank->id] = $closingMonth;
                    $affectedBanks[$targetBank->id] = $closingMonth;
                }

            } else {
                $primaryBankId = $request->bank_setting_id;

                foreach ($request->items as $item) {
                    $amount = $item['amount'];
                    $txNo = 'TXN-CUST-' . Str::uuid();
                    $purpose = Purpose::find($item['purpose_id']);

                    $primaryBank = BankSetting::where('id', $primaryBankId)->lockForUpdate()->firstOrFail();
                    $startBalance = $primaryBank->amount;

                    if ($direction === '+') {
                        $endBalance = $startBalance + $amount;
                    } else {
                        $endBalance = $startBalance - $amount;
                    }

                    $primaryBank->update(['amount' => $endBalance]);
                    $this->updateTodaySnapshot($primaryBank);

                    $transaction = Transaction::create([
                        'transaction_no'         => $txNo,
                        'transaction_date'       => $txDate,
                        'country_id'             => $primaryBank->country_id,
                        'bank_setting_id'        => $primaryBank->id,
                        'type'                   => 'customer',
                        'target_bank_setting_id' => null,
                        'transfer_direction'     => $direction,
                        'amount'                 => $amount,
                        'start_balance'          => $startBalance,
                        'end_balance'            => $endBalance,
                        'target_start_balance'   => 0,
                        'target_end_balance'     => 0,
                        'purpose_id'             => $item['purpose_id'],
                        'remark_1'               => $item['remark_1'] ?? null,
                        'remark_2'               => $item['remark_2'] ?? null,
                        'closing_month'          => $closingMonth,
                        'created_by_id'          => Auth::id(),
                    ]);

                    if ($purpose && $purpose->has_provider_settlement) {
                        $multiplier = ($direction === '+') ? 1 : -1;
                        ProviderSettlement::create([
                            'transaction_id'    => $transaction->id,
                            'purpose_id'        => $purpose->id,
                            'country_id'        => $primaryBank->country_id,
                            'bank_name'         => $primaryBank->bank->bank_name ?? 'Bank',
                            'settlement_amount' => $amount * $multiplier,
                            'provider_name'     => $purpose->provider_name,
                            'created_by_id'     => Auth::id(),
                        ]);
                    }

                    $affectedBanks[$primaryBank->id] = $closingMonth;
                }
            }

            foreach ($affectedBanks as $bankSettingId => $month) {
                $this->refreshMonthlySummary($bankSettingId, $month);
            }

            DB::commit();

            $redirectBankId = $request->input('bank_setting_id') ?? request()->route('bank_setting_id');
            return redirect()->route('transaction.log', ['month' => $closingMonth, 'bank_setting' => $redirectBankId])->with('success', 'Transactions recorded successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withInput()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    public function edit(Transaction $transaction)
    {
        // Enforce "Today Only" edit rule
        if (!Carbon::parse($transaction->created_at)->isToday()) {
            return redirect()->back()->with('error', 'Only transactions created today can be edited.');
        }

        $query = BankSetting::with('bank');
        $this->scopeByCountry($query);
        $bankSettings = $query->get();

        $selectedBank = BankSetting::with('bank')->find($transaction->bank_setting_id);

        // Purpose filtering logic
        $activeCountryId = session('active_country_id');
        $userCountryId = Auth::user()->country_id ?? null;

        $purposesQuery = Purpose::query();
        $purposesQuery->where(function ($q) use ($activeCountryId, $userCountryId) {
            $q->where('is_global', 1);

            if ($activeCountryId && $activeCountryId !== 'no') {
                $q->orWhereHas('countries', function ($sub) use ($activeCountryId) {
                    $sub->where('countries.id', $activeCountryId);
                });
            } elseif ($userCountryId) {
                $q->orWhereHas('countries', function ($sub) use ($userCountryId) {
                    $sub->where('countries.id', $userCountryId);
                });
            }
        });
        $purposes = $purposesQuery->where('is_active', 1)->get();

        return view('transaction.create', compact('bankSettings', 'purposes', 'selectedBank', 'transaction'));
    }

    public function update(Request $request, Transaction $transaction)
    {
        // Enforce "Today Only" edit rule based on creation date
        if (!Carbon::parse($transaction->created_at)->isToday()) {
            return redirect()->back()->with('error', 'Only transactions created today can be edited.');
        }

        $request->validate([
            'amount'   => 'required|numeric|min:0.01|max:999999999999.99',
            'remark_1' => 'nullable|string',
            'remark_2' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $newAmount = $request->amount;
            $closingMonth = $transaction->closing_month;
            $affectedBanks = [];

            // Identify all related transactions if it's an own-account transfer pair
            $transactionsToUpdate = collect([$transaction]);
            if ($transaction->type === 'own') {
                $batchNo = str_replace(['TXN-OWN-OUT-', 'TXN-OWN-IN-'], '', $transaction->transaction_no);
                $transactionsToUpdate = Transaction::where('transaction_no', 'LIKE', '%' . $batchNo . '%')->get();
            }

            foreach ($transactionsToUpdate as $tx) {
                $oldAmount = $tx->amount;
                $diff = $newAmount - $oldAmount;

                $bank = BankSetting::where('id', $tx->bank_setting_id)->lockForUpdate()->firstOrFail();

                // Adjust bank balance based on direction and the amount difference
                if ($tx->transfer_direction === '+') {
                    $bank->amount += $diff;
                } else {
                    $bank->amount -= $diff;
                }
                $bank->save();
                $this->updateTodaySnapshot($bank);

                // Update amount and remarks only (protecting locked fields)
                $tx->update([
                    'amount'   => $newAmount,
                    'remark_1' => $request->remark_1,
                    'remark_2' => $request->remark_2,
                ]);

                // Update related Provider Settlement record if applicable
                $purpose = Purpose::find($tx->purpose_id);
                if ($purpose && $purpose->has_provider_settlement) {
                    $settlement = ProviderSettlement::where('transaction_id', $tx->id)->first();
                    if ($settlement) {
                        $multiplier = ($tx->transfer_direction === '+') ? 1 : -1;
                        $settlement->update([
                            'settlement_amount' => $newAmount * $multiplier
                        ]);
                    }
                }

                $affectedBanks[$bank->id] = $closingMonth;
            }

            // Re-chain balances sequentially and refresh monthly summaries
            foreach ($affectedBanks as $bankSettingId => $month) {
                $this->rechainBankTransactions($bankSettingId);
                $this->refreshMonthlySummary($bankSettingId, $month);
            }

            DB::commit();
            return redirect()->back()->with('success', 'Transaction updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withInput()->with('error', 'Error updating transaction: ' . $e->getMessage());
        }
    }

    public function destroy(Transaction $transaction)
    {
        // Enforce "Today Only" deletion rule
        if (!Carbon::parse($transaction->created_at)->isToday()) {
            return redirect()->back()->with('error', 'Only transactions created today can be deleted.');
        }

        DB::beginTransaction();
        try {
            $closingMonth = $transaction->closing_month;
            $affectedBanks = [];

            // Handle paired own-account transfers together
            $transactionsToDelete = collect([$transaction]);
            if ($transaction->type === 'own') {
                $batchNo = str_replace(['TXN-OWN-OUT-', 'TXN-OWN-IN-'], '', $transaction->transaction_no);
                $transactionsToDelete = Transaction::where('transaction_no', 'LIKE', '%' . $batchNo . '%')->get();
            }

            foreach ($transactionsToDelete as $tx) {
                $bank = BankSetting::where('id', $tx->bank_setting_id)->lockForUpdate()->firstOrFail();

                // Reverse bank balance impact
                if ($tx->transfer_direction === '+') {
                    $bank->amount -= $tx->amount;
                } else {
                    $bank->amount += $tx->amount;
                }
                $bank->save();
                $this->updateTodaySnapshot($bank);

                // Delete associated provider settlements[cite: 39]
                ProviderSettlement::where('transaction_id', $tx->id)->delete();

                // Delete transaction record
                $tx->delete();

                $affectedBanks[$bank->id] = $closingMonth;
            }

            // Re-chain sequential balances and refresh monthly summaries
            foreach ($affectedBanks as $bankSettingId => $month) {
                $this->rechainBankTransactions($bankSettingId);
                $this->refreshMonthlySummary($bankSettingId, $month);
            }

            DB::commit();
            return redirect()->back()->with('success', 'Transaction deleted successfully and balances re-chained.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Error deleting transaction: ' . $e->getMessage());
        }
    }

    protected function rechainBankTransactions($bankSettingId)
    {
        $transactions = Transaction::where('bank_setting_id', $bankSettingId)
            ->orderBy('transaction_date', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $bank = BankSetting::find($bankSettingId);

        if ($transactions->isNotEmpty()) {
            $currentBalance = $transactions->first()->start_balance ?? ($bank->amount - ($transactions->first()->transfer_direction === '+' ? $transactions->first()->amount : -$transactions->first()->amount));

            foreach ($transactions as $tx) {
                $start = $currentBalance;
                if ($tx->transfer_direction === '+') {
                    $currentBalance += $tx->amount;
                } else {
                    $currentBalance -= $tx->amount;
                }
                $end = $currentBalance;

                $tx->update([
                    'start_balance' => $start,
                    'end_balance'   => $end
                ]);
            }

            $bank->update(['amount' => $currentBalance]);
        }
    }

    protected function updateTodaySnapshot(BankSetting $bankSetting)
    {
        BankSnapshot::updateOrCreate(
            [
                'bank_setting_id' => $bankSetting->id,
                'snapshot_date'   => Carbon::today()->toDateString(),
            ],
            [
                'country_id'      => $bankSetting->country_id,
                'capital'         => $bankSetting->amount,
            ]
        );
    }
}
