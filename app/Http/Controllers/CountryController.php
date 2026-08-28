<?php

namespace App\Http\Controllers;

use App\Models\BankSetting;
use App\Models\BankSnapshot;
use App\Models\Country;
use App\Models\Purpose;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

// Master Setting just open for superadmin
class CountryController extends Controller
{
    public function index()
    {
        $countries = Country::all();
        return view('country.index', compact('countries'));
    }

    public function create()
    {
        return view('country.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'currency_code' => 'required|string|max:255|unique:countries,currency_code',
        ]);

        Country::create([
            'name' => $validated['name'],
            'currency_code' => $validated['currency_code'],
            'created_by_id' => Auth::id(),
        ]);

        return redirect()->route('country.index')->withSuccess('Country created successfully.');
    }

    public function show(string $id)
    {
        //
    }

    public function edit(Country $country)
    {
        return view('country.create')->with('country',$country);
    }

    public function update(Request $request, Country $country)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'currency_code' => 'required|string|max:255|unique:countries,currency_code,' . $country->id,
        ]);

        $country->update($validated);

        return redirect()->route('country.index')->withSuccess('Country updated successfully.');
    }

    public function destroy(Country $country)
    {
        $hasUsers = $country->users()->exists();
        $hasBanks = $country->banks()->exists();
        $hasBankSettings = BankSetting::where('country_id', $country->id)->exists();

        // Check if any purposes are linked to this country via the pivot table
        $hasPurposes = $country->purposes()->exists();

        $hasTransactions = Transaction::where('country_id', $country->id)->exists();
        $hasBankSnapshots = BankSnapshot::where('country_id', $country->id)->exists();
        //$hasBankLogs = \App\Models\BankLog::where('country_id', $country->id)->exists();

        if ($hasUsers || $hasBanks || $hasBankSettings || $hasPurposes || $hasTransactions || $hasBankSnapshots) {
            return redirect()->route('country.index')
                ->with('error', 'Cannot delete this country because it has linked records (users, banks, purposes, transactions, etc.).');
        }

        // Proceed with soft delete if no relations exist
        $country->delete();

        return redirect()->route('country.index')->withSuccess('Data deleted successfully.');
    }

    public function switch($id)
    {
        $user = Auth::user();

        if (!optional($user->role)->title == 'Super Admin' && !$user->hasRole('Super Admin')) {
            abort(403, 'Unauthorized action.');
        }

        // Handle "See All" selection
        if ($id === 'no') {
            session([
                'active_country_id'   => 'no',
                'active_country_code' => 'ALL',
                'active_country_name' => 'All Countries'
            ]);

            return redirect()->route('home', ['country' => 'ALL'])->with('success', 'Switched context to All Countries');
        }

        // Handle normal country switch
        $country = Country::where('id', $id)->where('is_active', 1)->firstOrFail();

        session([
            'active_country_id'   => $country->id,
            'active_country_code' => $country->currency_code,
            'active_country_name' => $country->name
        ]);

        return redirect()->route('home', ['country' => $country->currency_code])->with('success', 'Switched context to ' . $country->name);
    }
}
