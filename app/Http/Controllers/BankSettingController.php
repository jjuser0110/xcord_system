<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\BankSetting;
use App\Models\Bank;
use App\Models\BankLog;
use App\Models\Country;
use Illuminate\Support\Facades\Auth;
use App\Traits\CountryScopeTrait;

// Setting open for all system users TODO
class BankSettingController extends Controller
{
    use CountryScopeTrait;

    public function index(Request $request)
    {
        $query = BankSetting::with(['bank', 'country', 'created_by']);
        $this->scopeByCountry($query);
        $bank_settings = $query->latest()->get();

        return view('bank_setting.index', compact('bank_settings'));
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
            'bank_id'      => 'required|exists:banks,id',
            'country_id'   => 'required|exists:countries,id',
            'account_no'   => 'nullable|string|max:255',
            'owner_name'   => 'nullable|string|max:255',
            'capital'      => 'required|numeric',
            'phone_number' => 'nullable|string|max:255',
            'expired_date' => 'nullable|date',
            'color'        => 'required|string|max:50',
            'type'         => 'nullable|string|max:50',
        ]);

        $bank_setting = BankSetting::create([
            'bank_id'       => $validated['bank_id'],
            'country_id'    => $validated['country_id'],
            'account_no'    => $validated['account_no'] ?? null,
            'owner_name'    => $validated['owner_name'] ?? null,
            'capital'       => $validated['capital'],
            'phone_number'  => $validated['phone_number'] ?? null,
            'expired_date'  => $validated['expired_date'] ?? null,
            'color'         => $validated['color'] ?? null,
            'type'          => $validated['type'] ?? null,
            'created_by_id' => Auth::id(),
            'is_active'     => 1,
        ]);

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

        return view('bank_setting.create', compact('bank_setting', 'banks', 'countries'));
    }

    public function update(Request $request, BankSetting $bank_setting)
    {
        $validated = $request->validate([
            'bank_id'      => 'required|exists:banks,id',
            'country_id'   => 'required|exists:countries,id',
            'account_no'   => 'nullable|string|max:255',
            'owner_name'   => 'nullable|string|max:255',
            'capital'      => 'required|numeric',
            'phone_number' => 'nullable|string|max:255',
            'expired_date' => 'nullable|date',
            'color'        => 'required|string|max:50',
            'type'         => 'nullable|string|max:50',
        ]);

        $bank_setting->update($validated);

        return redirect()->route('bank_setting.index')->withSuccess('Bank account setting updated successfully.');
    }

    public function destroy(BankSetting $bank_setting)
    {
        $bank_setting->delete();
        return redirect()->route('bank_setting.index')->withSuccess('Bank account setting deleted successfully.');
    }

    public function active(BankSetting $bank_setting)
    {
        $bank_setting->update(['is_active' => 1]);
        return redirect()->route('bank_setting.index')->withSuccess('Bank account activated.');
    }

    public function inactive(BankSetting $bank_setting)
    {
        $bank_setting->update(['is_active' => 0]);
        return redirect()->route('bank_setting.index')->withSuccess('Bank account deactivated.');
    }

    public function log(BankSetting $bankSetting)
    {
        $id = $bankSetting?->id;

        if (Auth::user()->country_id && $bankSetting->country_id != Auth::user()->country_id) {
            abort(403, 'Unauthorized action.');
        }

        $logs = BankLog::where('bank_setting_id', $id)
            ->with(['creator', 'transaction'])
            ->latest()
            ->paginate(10);

        return view('bank_setting.log', compact('bankSetting', 'logs'));
    }
}
