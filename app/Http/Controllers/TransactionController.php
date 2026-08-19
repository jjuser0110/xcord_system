<?php
namespace App\Http\Controllers;

use App\Models\BankLog;
use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Models\BankSetting;
use App\Models\Purpose;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $countryId = Auth::user()->country_id;
        $query = Transaction::with(['bankSetting.bank', 'targetBankSetting.bank', 'purpose', 'creator'])
                ->where('country_id', $countryId)
                ->latest();

        if ($request->filled('month')) {
            $query->where('closing_month', $request->month);
        }

        $transactions = $query->paginate(15);
        return view('transaction.index', compact('transactions'));
    }

    public function create()
    {
        $countryId = Auth::user()->country_id;

        $bankSettings = BankSetting::with('bank')
            ->where('country_id', $countryId)
            ->get();

        $purposes = Purpose::where('country_id', $countryId)->get();

        return view('transaction.create', compact('bankSettings', 'purposes'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'transaction_date'       => 'required|date',
            'type'                   => 'required|in:own,customer',
            'purpose_id'             => 'required|exists:purposes,id',
            'amount'                 => 'required|numeric|min:0.01|max:999999999999.99',
            'remark_1'               => 'nullable|string',
            'remark_2'               => 'nullable|string',
            'column_color'           => 'nullable|string|max:50',

            // Customer specific validation
            'customer_name'          => 'required_if:type,customer|nullable|string|max:255',
            'customer_phone'         => 'nullable|string|max:50',
            'customer_address'       => 'nullable|string',

            // Bank routing rules
            'bank_setting_id'        => 'required_if:type,customer|nullable|exists:bank_settings,id',
            'transfer_direction'     => 'required_if:type,customer|nullable|in:+,-',
            'source_bank_id'         => 'required_if:type,own|nullable|exists:bank_settings,id|different:target_bank_id',
            'target_bank_id'         => 'required_if:type,own|nullable|exists:bank_settings,id',
        ]);

        DB::beginTransaction();
        try {
            $txDate = Carbon::parse($request->transaction_date);
            $amount = $request->amount;
            $txNo = 'TXN-' . strtoupper(Str::random(8));

            if ($request->type === 'own') {
                $sourceBank = BankSetting::with('bank')->findOrFail($request->source_bank_id);
                $targetBank = BankSetting::with('bank')->findOrFail($request->target_bank_id);

                $sourceStart = $sourceBank->capital;
                $sourceEnd = $sourceStart - $amount;
                $sourceBank->decrement('capital', $amount);

                $targetStart = $targetBank->capital;
                $targetEnd = $targetStart + $amount;
                $targetBank->increment('capital', $amount);

                // Save both source and target balances in 1 transaction row
                $transaction = Transaction::create([
                    'transaction_no'         => $txNo,
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
                    'purpose_id'             => $request->purpose_id,
                    'remark_1'               => $request->remark_1,
                    'remark_2'               => $request->remark_2,
                    'column_color'           => $request->column_color ?? '#ffffff',
                    'closing_month'          => $txDate->format('Y-m'),
                    'created_by_id'          => Auth::id(),
                ]);

                BankLog::create([
                    'country_id'      => $sourceBank->country_id,
                    'bank_setting_id' => $sourceBank->id,
                    'transaction_id'  => $transaction->id,
                    'type'            => 'Transfer Out',
                    'remark'          => $targetBank->bank->bank_name . ' (' . $targetBank->account_no . ')',
                    'start_balance'   => $sourceStart,
                    'amount'          => $amount,
                    'end_balance'     => $sourceEnd,
                    'created_by_id'   => Auth::id(),
                ]);

                BankLog::create([
                    'country_id'      => $targetBank->country_id,
                    'bank_setting_id' => $targetBank->id,
                    'transaction_id'  => $transaction->id,
                    'type'            => 'Transfer In',
                    'remark'          => $sourceBank->bank->bank_name . ' (' . $sourceBank->account_no . ')',
                    'start_balance'   => $targetStart,
                    'amount'          => $amount,
                    'end_balance'     => $targetEnd,
                    'created_by_id'   => Auth::id(),
                ]);

            } else {
                $primaryBank = BankSetting::findOrFail($request->bank_setting_id);
                $direction = $request->transfer_direction;
                $startBalance = $primaryBank->capital;

                if ($direction === '+') {
                    $endBalance = $startBalance + $amount;
                    $primaryBank->increment('capital', $amount);
                } else {
                    $endBalance = $startBalance - $amount;
                    $primaryBank->decrement('capital', $amount);
                }

                $remark1 = $request->filled('remark_1')
                    ? $request->remark_1
                    : collect([
                        $request->filled('customer_name') ? 'Name: ' . $request->customer_name : null,
                        $request->filled('customer_phone') ? 'Phone: ' . $request->customer_phone : null,
                        $request->filled('customer_address') ? 'Address: ' . $request->customer_address : null,
                      ])->filter()->implode(' | ');

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
                    'purpose_id'             => $request->purpose_id,
                    'customer_name'          => $request->customer_name,
                    'customer_phone'         => $request->customer_phone,
                    'customer_address'       => $request->customer_address,
                    'remark_1'               => $remark1,
                    'remark_2'               => $request->remark_2,
                    'column_color'           => $request->column_color ?? '#ffffff',
                    'closing_month'          => $txDate->format('Y-m'),
                    'created_by_id'          => Auth::id(),
                ]);

                BankLog::create([
                    'country_id'      => $primaryBank->country_id,
                    'bank_setting_id' => $primaryBank->id,
                    'transaction_id'  => $transaction->id,
                    'type'            => ucfirst($request->transfer_direction == '+' ? 'Deposit' : 'Withdraw'),
                    'remark'          => $remark1,
                    'start_balance'   => $startBalance,
                    'amount'          => $amount,
                    'end_balance'     => $endBalance,
                    'created_by_id'   => Auth::id(),
                ]);
            }

            DB::commit();

            return redirect()->route('transaction.index')->with('success', 'Transaction saved successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withInput()->with('error', 'Error: ' . $e->getMessage());
        }
    }
}
