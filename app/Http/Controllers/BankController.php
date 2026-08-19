<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Spatie\Browsershot\Browsershot;
use Illuminate\Http\Request;
use App\Models\Bank;
use App\Models\Country;
use Bouncer;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class BankController extends Controller
{
    public function index(Request $request)
    {
        // TODO: Client decides where conditions
        $bank = Bank::with('country')->latest()->get();

        return view('bank.index')->with('bank',$bank);
    }

    public function create()
    {
        $countries = Country::where('is_active', 1)->get();
        return view('bank.create', compact('countries'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'bank_name' => 'required|string|max:255',
            'short_name' => 'required|string|max:255',
            'country_id' => 'required|exists:countries,id'
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
        $countries = Country::where('is_active', 1)->get();
        return view('bank.create', compact('bank', 'countries'));
    }

    public function update(Request $request, Bank $bank)
    {
        $validated = $request->validate([
            'bank_name' => 'required|string|max:255',
            'short_name' => 'required|string|max:255',
            'country_id' => 'required|exists:countries,id',
        ]);
        $bank->update($validated);
        return redirect()->route('bank.index')->withSuccess('Data updated');
    }

    public function destroy(Bank $bank)
    {
        $bank->delete();

        return redirect()->route('bank.index')->withSuccess('Data deleted');
    }

}
