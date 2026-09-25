<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Models\BankSetting;
use App\Models\BankLog;
use App\Models\BankSnapshot;
use App\Models\MerchantSettlement;
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

    public function __construct()
    {
        // Apply the check to actions that modify data
        $this->middleware(\App\Http\Middleware\CheckSnapshotRunning::class)
            ->only(['store', 'update', 'destroy']);

        $this->middleware(function ($request, $next) {
            if (auth()->check()) {
                $user = auth()->user();
                $roleName = optional($user->role)->name;

                $isSuperAdmin = ($user->role_id === 1 || $roleName === 'superadmin');
                $isCompanyStaff = ($roleName === 'company_staff');

                // If not Super Admin or Company Staff
                if (!$isSuperAdmin && !$isCompanyStaff) {
                    // Allow edit route ONLY if it's explicitly in view-only mode
                    if ($request->routeIs('transaction.edit') && $request->query('mode') === 'view') {
                        return $next($request);
                    }

                    return redirect()->route('transaction.index')
                        ->with('error', 'Unauthorized action. Staff viewers have read-only access.');
                }
            }
            return $next($request);
        })->only(['create', 'store', 'edit', 'update', 'destroy']);
    }

    public function index(Request $request)
    {
        $currentMonth = $request->input('month', Carbon::now()->format('Y-m'));
        $sortBy = $request->input('sort');
        $direction = $request->input('direction', 'asc');

        $startDate = Carbon::parse($currentMonth)->startOfMonth()->format('d.m.Y');
        $endDate = Carbon::parse($currentMonth)->endOfMonth()->format('d.m.Y');
        $endOfMonthDate = Carbon::parse($currentMonth)->endOfMonth();

        $query = BankSetting::with(['bank', 'country'])
            ->leftJoin('bank_monthly_summaries', function($join) use ($currentMonth) {
                $join->on('bank_settings.id', '=', 'bank_monthly_summaries.bank_setting_id')
                     ->where('bank_monthly_summaries.closing_month', '=', $currentMonth);
            })
            ->select('bank_settings.*', 'bank_monthly_summaries.end_balance', 'bank_monthly_summaries.transaction_count')
            ->where('bank_settings.created_at', '<=', $endOfMonthDate);

        $this->scopeByCountry($query);

        if ($sortBy === 'bank_setting') {
            $query->orderBy('bank_settings.owner_name', $direction);
        } elseif ($sortBy === 'balance') {
            $query->orderBy(DB::raw('COALESCE(bank_monthly_summaries.end_balance, 0)'), $direction);
        } elseif ($sortBy === 'count') {
            $query->orderBy(DB::raw('COALESCE(bank_monthly_summaries.transaction_count, 0)'), $direction);
        } else {
            $query->orderBy('bank_settings.id', 'desc');
        }

        $bankSettings = $query->paginate(50);

        foreach ($bankSettings as $setting) {
            $setting->monthly_balance = $setting->end_balance ?? 0.00;
            $setting->month_transaction_count = $setting->transaction_count ?? 0;
        }

        // 2. Attach monthly stats efficiently (bulk fetch or from summary table)
        $bankSettingIds = $bankSettings->pluck('id');

        // Fetch summaries for these specific visible accounts for the chosen month
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

        if($activeCountryId !== 'no') {
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
        }

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
            'bank_setting_id'        => 'nullable|exists:bank_settings,id',

            // Conditional validation rule arrays
            'transfers'              => 'required_if:type,own|array',
            'transfers.*.source_bank_id' => 'required_if:type,own|exists:bank_settings,id',
            'transfers.*.target_bank_id' => 'required_if:type,own|exists:bank_settings,id',
            'transfers.*.items'      => 'required_if:type,own|array|min:1',
            'transfers.*.items.*.amount' => 'required_if:type,own|numeric|min:0.01|max:999999999999.99',
            'transfers.*.items.*.purpose_id' => 'required_if:type,own|exists:purposes,id',

            // Multi-row items validation for customer/external transactions
            'items'                  => 'required_if:type,customer|array',
            'items.*.amount'         => 'required_if:type,customer|numeric|min:0.01|max:999999999999.99',
            'items.*.purpose_id'     => 'required_if:type,customer|exists:purposes,id',
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
            $selectedBankId = $request->input('bank_setting_id') ?? request()->route('bank_setting_id');

            if ($type === 'own') {
                $transfers = $request->input('transfers', []);

                foreach ($transfers as $transfer) {
                    if ($direction === '+') {
                        // Bank In: 'from' must be target account (excluding selected bank), 'to' is selected bank
                        $sourceBankId = $transfer['source_bank_id'];
                        $targetBankId = $selectedBankId;
                    } else {
                        // Bank Out: 'from' is selected bank, 'to' must be target account (excluding selected bank)
                        $sourceBankId = $selectedBankId;
                        $targetBankId = $transfer['target_bank_id'];
                    }

                    if (!$sourceBankId || !$targetBankId) {
                        throw new \Exception('Both source and target bank accounts must be selected.');
                    }
                    if ($sourceBankId == $targetBankId) {
                        throw new \Exception('The source and target bank accounts cannot be the same.');
                    }

                    // Ensure the selected account isn't improperly chosen where excluded
                    if ($direction === '+' && $sourceBankId == $selectedBankId) {
                        throw new \Exception('The source bank account cannot be the same as the selected bank account during a Bank In transfer.');
                    }
                    if ($direction === '-' && $targetBankId == $selectedBankId) {
                        throw new \Exception('The target bank account cannot be the same as the selected bank account during a Bank Out transfer.');
                    }

                    $sourceBankMeta = BankSetting::find($sourceBankId);
                    $targetBankMeta = BankSetting::find($targetBankId);

                    foreach ($transfer['items'] as $item) {
                        $amount = $item['amount'];
                        $purpose = Purpose::find($item['purpose_id']);

                        if ($direction === '+' && !in_array($purpose->money_flow_type, ['bank_in', 'both'])) {
                            throw new \Exception('The selected purpose "' . $purpose->title . '" is not permitted for Bank In transactions.');
                        }
                        if ($direction === '-' && !in_array($purpose->money_flow_type, ['bank_out', 'both'])) {
                            throw new \Exception('The selected purpose "' . $purpose->title . '" is not permitted for Bank Out transactions.');
                        }

                        // Lock both accounts
                        $sourceBank = BankSetting::where('id', $sourceBankId)->lockForUpdate()->firstOrFail();
                        $targetBank = BankSetting::where('id', $targetBankId)->lockForUpdate()->firstOrFail();

                        $sourceStart = $sourceBank->amount;
                        $sourceEnd = $sourceStart - $amount;
                        $sourceBank->update(['amount' => $sourceEnd]);

                        $targetStart = $targetBank->amount;
                        $targetEnd = $targetStart + $amount;
                        $targetBank->update(['amount' => $targetEnd]);

                        $batchUuid = Str::uuid();

                        // Auto generated Remarks based on direction/flow
                        if ($direction === '+') {
                            $sourceRemark = 'To ' . ($targetBankMeta ? $targetBankMeta->owner_name . ' - ' . optional($targetBankMeta->bank)->short_name : '');
                            $targetRemark = 'From ' . ($sourceBankMeta ? $sourceBankMeta->owner_name . ' - ' . optional($sourceBankMeta->bank)->short_name : '');
                        } else {
                            $sourceRemark = 'To ' . ($targetBankMeta ? $targetBankMeta->owner_name . ' - ' . optional($targetBankMeta->bank)->short_name : '');
                            $targetRemark = 'From '  . ($sourceBankMeta ? $sourceBankMeta->owner_name . ' - ' . optional($sourceBankMeta->bank)->short_name : '');
                        }

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
                            'remark_1'               => $sourceRemark,
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
                            'remark_1'               => $targetRemark,
                            'remark_2'               => $item['remark_2'] ?? null,
                            'closing_month'          => $closingMonth,
                            'created_by_id'          => Auth::id(),
                        ]);

                        if ($sourceBank->id == $selectedBankId) {
                            $this->handleSettlements($purpose, $sourceTransaction, $sourceBank, -$amount);
                        } elseif ($targetBank->id == $selectedBankId) {
                            $this->handleSettlements($purpose, $targetTransaction, $targetBank, $amount);
                        }

                        $affectedBanks[$sourceBank->id] = $closingMonth;
                        $affectedBanks[$targetBank->id] = $closingMonth;
                    }
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
                    //$this->updateTodaySnapshot($primaryBank);

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

                    $multiplier = ($direction === '+') ? 1 : -1;
                    $this->handleSettlements($purpose, $transaction, $primaryBank, $amount * $multiplier);

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

    protected function handleSettlements($purpose, $transaction, $bank, $amountInFlow)
    {
        if (!$purpose) return;

        // 1. Received from Provider or Topup to Provider -> Provider Settlement
        if (($purpose->show_on_received_from_provider || $purpose->show_on_topup_to_provider) && $purpose->has_provider_settlement) {

            // Determine type based on purpose configuration
            $type = $purpose->show_on_received_from_provider ? 'in' : 'out';

            ProviderSettlement::create([
                'transaction_id'    => $transaction->id,
                'purpose_id'        => $purpose->id,
                'country_id'        => $bank->country_id,
                'bank_setting_id'   => $bank->id,
                'bank_name'         => $bank->bank->bank_name ?? 'Bank',
                'type'              => $type,
                'settlement_amount' => $amountInFlow,
                'provider_name'     => $purpose->provider_name,
                'created_by_id'     => Auth::id(),
            ]);
        }

        // 2. Transfer for Merchant -> Merchant Settlement
        if ($purpose->show_on_transfer_for_merchant) {
            MerchantSettlement::create([
                'transaction_id'    => $transaction->id,
                'purpose_id'        => $purpose->id,
                'country_id'        => $bank->country_id,
                'bank_setting_id'   => $bank->id, // <--- Track specific bank setting ID here
                'bank_name'         => $bank->bank->bank_name ?? 'Bank',
                'settlement_amount' => $amountInFlow,
                'created_by_id'     => Auth::id(),
            ]);
        }
    }

    public function edit(Request $request, Transaction $transaction)
    {
        $isView = $request->query('mode') === 'view';
        // Enforce "Today Only" edit rule
        if (!Carbon::parse($transaction->created_at)->isToday() && !$isView) {
            return redirect()->back()->with('error', 'Only transactions created today can be edited.');
        }

        return view('transaction.edit', compact('transaction', 'isView'));
    }

    // public function edit(Transaction $transaction)
    // {
    //     // Enforce "Today Only" edit rule
    //     if (!Carbon::parse($transaction->created_at)->isToday()) {
    //         return redirect()->back()->with('error', 'Only transactions created today can be edited.');
    //     }

    //     $query = BankSetting::with('bank');
    //     $this->scopeByCountry($query);
    //     $bankSettings = $query->get();

    //     $selectedBank = BankSetting::with('bank')->find($transaction->bank_setting_id);

    //     // Purpose filtering logic
    //     $activeCountryId = session('active_country_id');
    //     $userCountryId = Auth::user()->country_id ?? null;

    //     $purposesQuery = Purpose::query();
    //     $purposesQuery->where(function ($q) use ($activeCountryId, $userCountryId) {
    //         $q->where('is_global', 1);

    //         if ($activeCountryId && $activeCountryId !== 'no') {
    //             $q->orWhereHas('countries', function ($sub) use ($activeCountryId) {
    //                 $sub->where('countries.id', $activeCountryId);
    //             });
    //         } elseif ($userCountryId) {
    //             $q->orWhereHas('countries', function ($sub) use ($userCountryId) {
    //                 $sub->where('countries.id', $userCountryId);
    //             });
    //         }
    //     });
    //     $purposes = $purposesQuery->where('is_active', 1)->get();

    //     return view('transaction.create', compact('bankSettings', 'purposes', 'selectedBank', 'transaction'));
    // }

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
                //$this->updateTodaySnapshot($bank);

                // Update amount and remarks only (protecting locked fields)
                $tx->update([
                    'amount'   => $newAmount,
                    'remark_1' => ($tx->type === 'own') ? $tx->remark_1 : $request->remark_1,
                    'remark_2' => $request->remark_2,
                ]);

                $multiplier = ($tx->transfer_direction === '+') ? 1 : -1;
                $signedAmount = $newAmount * $multiplier;

                // Update Provider Settlement
                $providerSettlement = ProviderSettlement::where('transaction_id', $tx->id)->first();
                if ($providerSettlement) {
                    $providerSettlement->update(['settlement_amount' => $signedAmount]);
                }

                // Update Merchant Settlement
                $merchantSettlement = MerchantSettlement::where('transaction_id', $tx->id)->first();
                if ($merchantSettlement) {
                    $merchantSettlement->update(['settlement_amount' => $signedAmount]);
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
                //$this->updateTodaySnapshot($bank);

                // Delete associated settlements
                ProviderSettlement::where('transaction_id', $tx->id)->delete();
                MerchantSettlement::where('transaction_id', $tx->id)->delete();

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
            ->orderBy('created_at', 'asc')
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

    public function filter(Request $request)
    {
        $currentMonth = $request->input('month', Carbon::now()->format('Y-m'));
        $remark1 = $request->input('remark_1');
        $remark2 = $request->input('remark_2');
        $amount = $request->input('amount');

        $query = Transaction::with(['purpose', 'creator', 'bankSetting.bank'])
            ->where('closing_month', $currentMonth)
            ->orderBy('id', 'desc');

        // Apply country scope based on top navigation / session
        $this->scopeByCountry($query);

        if ($remark1) {
            $query->where('remark_1', 'LIKE', '%' . $remark1 . '%');
        }

        if ($remark2) {
            $query->where('remark_2', 'LIKE', '%' . $remark2 . '%');
        }

        if ($amount !== null && $amount !== '') {
            $query->where('amount', $amount);
        }

        $transactions = $query->paginate(50);

        return view('transaction.filter', compact(
            'transactions',
            'currentMonth',
            'remark1',
            'remark2',
            'amount'
        ));
    }
}
