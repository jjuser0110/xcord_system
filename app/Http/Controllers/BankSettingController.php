<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BankSetting;
use App\Models\Bank;
use App\Models\Country;
use App\Models\Purpose;
use App\Models\Transaction;
use App\Models\BankPhoneNumber;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Traits\CountryScopeTrait;
use Carbon\Carbon;

class BankSettingController extends Controller
{
    use CountryScopeTrait;

    public function index(Request $request)
    {
        $query = BankSetting::with(['bank', 'country', 'phoneNumbers']);
        $this->scopeByCountry($query);
        $bank_settings = $query->latest()->get();

        // Get purposes matching country session context for the amount-adjustment modal
        $activeCountryId = session('active_country_id');
        $purposesQuery = Purpose::query();
        if ($activeCountryId && $activeCountryId !== 'no') {
            $purposesQuery->where(function ($q) use ($activeCountryId) {
                $q->where('is_global', true)
                  ->orWhereHas('countries', function ($sub) use ($activeCountryId) {
                      $sub->where('countries.id', $activeCountryId);
                  });
            });
        }
        $purposes = $purposesQuery->where('is_active', 1)->get();

        return view('bank_setting.index', compact('bank_settings', 'purposes'));
    }

    public function create()
    {
        $query = Bank::with('country');
        $this->scopeByCountry($query);
        $banks = $query->get();

        $query = Country::where('is_active', 1);
        $this->scopeByCountry($query, 'id');
        $countries = $query->get();

        return view('bank_setting.create', compact('banks', 'countries'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'bank_id'                => 'required|exists:banks,id',
            'country_id'             => 'required|exists:countries,id',
            'owner_name'             => 'required|string|max:255',
            'capital'                => 'required|numeric|min:0',
            'color'                  => 'required|string|max:50',
            'phones'                 => 'nullable|array',
            'phones.*.phone_number'  => 'nullable|string|max:50',
            'phones.*.expired_date'  => 'required_with:phones.*.phone_number|nullable|date',
        ]);

        DB::transaction(function () use ($validated, $request) {
            // 1. Create Bank Setting
            $bankSetting = BankSetting::create([
                'bank_id'       => $validated['bank_id'],
                'country_id'    => $validated['country_id'],
                'owner_name'    => $validated['owner_name'],
                'capital'       => $validated['capital'],
                'amount'        => $validated['capital'], // Capital same as bank amount initially
                'color'         => $validated['color'],
                'created_by_id' => Auth::id(),
                'is_active'     => 1,
            ]);

            // 2. Save Phone Numbers if provided
            if (!empty($validated['phones'])) {
                foreach ($validated['phones'] as $phoneData) {
                    if (!empty($phoneData['phone_number'])) {
                        BankPhoneNumber::create([
                            'bank_setting_id' => $bankSetting->id,
                            'phone_number'    => $phoneData['phone_number'],
                            'expired_date'    => $phoneData['expired_date'],
                        ]);
                    }
                }
            }

            // 3. Create Opening Balance Transaction Log
            $openingPurpose = Purpose::where('title', 'Opening Balance')->first();

            Transaction::create([
                'transaction_no'     => 'TRX-OP-' . strtoupper(uniqid()),
                'transaction_date'   => Carbon::now(),
                'country_id'         => $bankSetting->country_id,
                'bank_setting_id'    => $bankSetting->id,
                'type'               => 'adjustment',
                'transfer_direction' => '+',
                'amount'             => $bankSetting->capital,
                'start_balance'      => 0,
                'end_balance'        => $bankSetting->capital,
                'purpose_id'         => $openingPurpose ? $openingPurpose->id : 1,
                'remark_1'           => 'Opening Balance Capital Initialization',
                'created_by_id'      => Auth::id(),
            ]);
        });

        return redirect()->route('bank_setting.index')->withSuccess('Bank account setting created successfully.');
    }

    public function edit(BankSetting $bank_setting)
    {
        $query = Bank::with('country');
        $this->scopeByCountry($query);
        $banks = $query->get();

        $query = Country::where('is_active', 1);
        $this->scopeByCountry($query, 'id');
        $countries = $query->get();

        $bank_setting->load('phoneNumbers');

        return view('bank_setting.create', compact('bank_setting', 'banks', 'countries'));
    }

    public function update(Request $request, BankSetting $bank_setting)
    {
        // Restriction enforcement: Cannot update bank, country, owner name, and capital
        $validated = $request->validate([
            'color'                  => 'required|string|max:50',
            'phones'                 => 'nullable|array',
            'phones.*.phone_number'  => 'nullable|string|max:50',
            'phones.*.expired_date'  => 'required_with:phones.*.phone_number|nullable|date',
        ]);

        DB::transaction(function () use ($validated, $bank_setting, $request) {
            // Update only allowed fields (color status)
            $bank_setting->update([
                'color' => $validated['color']
            ]);

            // Sync/Replace phone numbers
            $bank_setting->phoneNumbers()->delete();
            if (!empty($validated['phones'])) {
                foreach ($validated['phones'] as $phoneData) {
                    if (!empty($phoneData['phone_number'])) {
                        BankPhoneNumber::create([
                            'bank_setting_id' => $bank_setting->id,
                            'phone_number'    => $phoneData['phone_number'],
                            'expired_date'    => $phoneData['expired_date'],
                        ]);
                    }
                }
            }
        });

        return redirect()->route('bank_setting.index')->withSuccess('Bank account setting updated successfully.');
    }

    public function updateAmount(Request $request, BankSetting $bank_setting)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0',
            'remark' => 'nullable|string',
        ]);

        DB::transaction(function () use ($validated, $bank_setting) {
            $oldBalance = $bank_setting->amount;
            $newBalance = $validated['amount'];
            $difference = $newBalance - $oldBalance;
            $direction = $difference >= 0 ? '+' : '-';

            // Update bank amount tracker
            $bank_setting->update(['amount' => $newBalance]);

            // Default remark if empty
            $remarkText = !empty(trim($validated['remark'])) ? $validated['remark'] : 'Manual Balance Adjustment';

            $txDate = Carbon::now();
            $closingMonth = $txDate->format('Y-m');

            Transaction::create([
                'transaction_no'     => 'TRX-ADJ-' . strtoupper(uniqid()),
                'transaction_date'   => \Carbon\Carbon::now(),
                'country_id'         => $bank_setting->country_id,
                'bank_setting_id'    => $bank_setting->id,
                'type'               => 'adjustment',
                'transfer_direction' => $direction,
                'amount'             => abs($difference),
                'start_balance'      => $oldBalance,
                'end_balance'        => $newBalance,
                'purpose_id'         => 2,
                'remark_1'           => $remarkText,
                'closing_month'      => $closingMonth,
                'created_by_id'      => \Illuminate\Support\Facades\Auth::id(),
            ]);
        });

        return back()->withSuccess('Bank amount updated successfully.');
    }

    public function destroy(BankSetting $bank_setting)
    {
        // Check if there are any transactions that do NOT have purpose_id equal to 1
        $hasOtherTransactions = $bank_setting->transactions()
            ->where('purpose_id', '!=', 1)
            ->exists();

        if ($hasOtherTransactions) {
            return redirect()->route('bank_setting.index')
                ->with('error', 'Cannot delete this bank setting because it contains other transaction records.');
        }

        DB::transaction(function () use ($bank_setting) {
            // Delete the opening balance transaction(s) (purpose_id = 1)
            $bank_setting->transactions()->delete();

            // Delete associated phone numbers
            $bank_setting->phoneNumbers()->delete();

            // Delete the bank setting itself
            $bank_setting->delete();
        });

        return redirect()->route('bank_setting.index')->withSuccess('Bank account setting deleted successfully.');
    }
}
