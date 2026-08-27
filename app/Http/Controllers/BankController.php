<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Spatie\Browsershot\Browsershot;
use Illuminate\Http\Request;
use App\Models\Bank;
use App\Models\Country;
use App\Traits\CountryScopeTrait;
use Bouncer;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

// Master setting (open for superadmin only) TODO
class BankController extends Controller
{
    use CountryScopeTrait;
    public function index(Request $request)
    {

        $query = Bank::with('country');
        $this->scopeByCountry($query);
        $bank = $query->latest()->get();
        return view('bank.index')->with('bank',$bank);
    }

    public function create()
    {
        $disableCountry = false;
        $countries = $this->getScopedCountriesForForm($disableCountry);

        return view('bank.create', compact('countries', 'disableCountry'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'bank_name' => 'required|string|max:255|unique:banks,bank_name',
            'short_name' => 'required|string|max:255|unique:banks,short_name',
            'country_id' => 'required|exists:countries,id',

        ]);

        Bank::create([
            'bank_name' => $validated['bank_name'],
            'short_name' => $validated['short_name'],
            'created_by_id' => Auth::id(),
            'country_id' => $validated['country_id']
        ]);

        return redirect()->route('bank.index')->withSuccess('Data saved');
    }

    public function edit(Bank $bank)
    {
        $query = Country::where('is_active', 1);
        $this->scopeByCountry($query, 'id');
        $countries = $query->get();

        $disableCountry = true;
        return view('bank.create', compact('bank', 'countries', 'disableCountry'));
    }

    public function update(Request $request, Bank $bank)
    {
        $validated = $request->validate([
            'bank_name' => 'required|string|max:255|unique:banks,bank_name,' . $bank->id,
            'short_name' => 'required|string|max:255|unique:banks,short_name,' . $bank->id,
            'country_id' => 'required|exists:countries,id',

        ]);
        $bank->update($validated);
        return redirect()->route('bank.index')->withSuccess('Data updated');
    }

    public function destroy(Bank $bank)
    {
        if ($bank->bankSettings()->exists()) {
            return redirect()->route('bank.index')
                ->with('error', 'Cannot delete this bank because it has linked bank accounts/settings.');
        }

        $bank->delete();

        return redirect()->route('bank.index')->withSuccess('Data deleted successfully.');
    }

}
