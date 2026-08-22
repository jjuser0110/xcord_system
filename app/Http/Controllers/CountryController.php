<?php

namespace App\Http\Controllers;

use App\Models\Country;
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

    public function switch($id)
    {
        $user = Auth::user();

        if (!optional($user->role)->title == 'Super Admin' && !$user->hasRole('Super Admin')) {
            abort(403, 'Unauthorized action.');
        }

        $previousUrl = strtok(url()->previous(), '?');

        // Block switching if they are on a create or edit page
        if (str_contains($previousUrl, '/create') || str_contains($previousUrl, '/edit')) {
            return redirect()->to($previousUrl)
                ->with('error', 'You cannot switch countries while filling out a form.');
        }

        // Handle "See All" selection
        if ($id === 'no') {
            session([
                'active_country_id'   => 'no',
                'active_country_code' => 'ALL',
                'active_country_name' => 'All Countries'
            ]);

            return redirect()->to($previousUrl . '?country=ALL')
                ->with('success', 'Switched context to All Countries');
        }

        // Handle normal country switch
        $country = Country::where('id', $id)->where('is_active', 1)->firstOrFail();

        session([
            'active_country_id'   => $country->id,
            'active_country_code' => $country->currency_code,
            'active_country_name' => $country->name
        ]);

        return redirect()->to($previousUrl . '?country=' . $country->currency_code)
            ->with('success', 'Switched context to ' . $country->name);
    }
}
