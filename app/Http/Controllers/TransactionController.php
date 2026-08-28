<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Models\BankSetting;
use App\Models\BankLog;
use App\Models\Purpose;
use App\Traits\CountryScopeTrait;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class TransactionController extends Controller
{
    use CountryScopeTrait;

    public function index(Request $request)
    {
        $currentMonth = $request->input('month', Carbon::now()->format('Y-m'));

        // Parse start and end dates for the header display
        $startDate = Carbon::parse($currentMonth)->startOfMonth()->format('d.m.Y');
        $endDate = Carbon::parse($currentMonth)->endOfMonth()->format('d.m.Y');

        $query = BankSetting::with(['bank', 'country']);
        $this->scopeByCountry($query);
        $bankSettings = $query->paginate(50);

        foreach ($bankSettings as $setting) {
            $setting->month_transaction_count = Transaction::where('bank_setting_id', $setting->id)
                ->where('closing_month', $currentMonth)
                ->count();
        }

        return view('transaction.index', compact('bankSettings', 'currentMonth', 'startDate', 'endDate'));
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
            $closingMonth = $txDate->format('Y-m');
            $type = $request->type;
            $direction = $request->transfer_direction;

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

                    // Lock both accounts to prevent race conditions and compute accurate balances
                    $sourceBank = BankSetting::where('id', $sourceBankId)->lockForUpdate()->firstOrFail();
                    $targetBank = BankSetting::where('id', $targetBankId)->lockForUpdate()->firstOrFail();

                    // 1. Calculate Source Bank (Outflow -)
                    $sourceStart = $sourceBank->amount;
                    $sourceEnd = $sourceStart - $amount;
                    $sourceBank->update(['amount' => $sourceEnd]);

                    // 2. Calculate Target Bank (Inflow +)
                    $targetStart = $targetBank->amount;
                    $targetEnd = $targetStart + $amount;
                    $targetBank->update(['amount' => $targetEnd]);

                    // Shared batch identifier reference
                    $batchUuid = strtoupper(Str::random(8));

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

                    // Bank Logs for auditing
                    BankLog::create([
                        'country_id'      => $sourceBank->country_id,
                        'bank_setting_id' => $sourceBank->id,
                        'transaction_id'  => $sourceTransaction->id,
                        'type'            => 'Transfer Out',
                        'remark'          => 'To ' . ($targetBank->bank->bank_name ?? '') . ' - ' . ($item['remark_1'] ?? ''),
                        'start_balance'   => $sourceStart,
                        'amount'          => $amount,
                        'end_balance'     => $sourceEnd,
                        'created_by_id'   => Auth::id(),
                    ]);

                    BankLog::create([
                        'country_id'      => $targetBank->country_id,
                        'bank_setting_id' => $targetBank->id,
                        'transaction_id'  => $targetTransaction->id,
                        'type'            => 'Transfer In',
                        'remark'          => 'From ' . ($sourceBank->bank->bank_name ?? '') . ' - ' . ($item['remark_1'] ?? ''),
                        'start_balance'   => $targetStart,
                        'amount'          => $amount,
                        'end_balance'     => $targetEnd,
                        'created_by_id'   => Auth::id(),
                    ]);
                }

            } else {
                $primaryBankId = $request->bank_setting_id;

                foreach ($request->items as $item) {
                    $amount = $item['amount'];
                    $txNo = 'TXN-CUST-' . strtoupper(Str::random(8));

                    $primaryBank = BankSetting::where('id', $primaryBankId)->lockForUpdate()->firstOrFail();
                    $startBalance = $primaryBank->amount;

                    if ($direction === '+') {
                        $endBalance = $startBalance + $amount;
                    } else {
                        $endBalance = $startBalance - $amount;
                    }

                    $primaryBank->update(['amount' => $endBalance]);

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

                    BankLog::create([
                        'country_id'      => $primaryBank->country_id,
                        'bank_setting_id' => $primaryBank->id,
                        'transaction_id'  => $transaction->id,
                        'type'            => $direction === '+' ? 'Deposit' : 'Withdraw',
                        'remark'          => $item['remark_1'] ?? '',
                        'start_balance'   => $startBalance,
                        'amount'          => $amount,
                        'end_balance'     => $endBalance,
                        'created_by_id'   => Auth::id(),
                    ]);
                }
            }

            DB::commit();
            return redirect()->route('transaction.index', ['month' => $closingMonth])->with('success', 'Transactions recorded successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withInput()->with('error', 'Error: ' . $e->getMessage());
        }
    }
}
