<?php

namespace App\Http\Controllers;

use App\Models\Country;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

// Master Setting
class CountryController extends Controller
{
    public function index()
    {
        // TODO: Client decides where conditions
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
            'currency_code' => 'required|string|max:255',
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
            'currency_code' => 'required|string|max:255',
        ]);

        $country->update($validated);

        return redirect()->route('country.index')->withSuccess('Country updated successfully.');
    }

    public function destroy(Country $country)
    {
        $country->delete();

        return redirect()->route('country.index')->withSuccess('Data deleted');
    }
}
