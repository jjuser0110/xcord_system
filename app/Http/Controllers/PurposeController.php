<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Country;
use App\Models\ProviderSettlement;
use App\Models\Purpose;
use App\Models\Transaction;
use App\Traits\CountryScopeTrait;
use Illuminate\Support\Facades\Auth;

class PurposeController extends Controller
{
    use CountryScopeTrait;

    public function index(Request $request)
    {
        $activeCountryId = session('active_country_id');

        $query = Purpose::with('countries');

        // Filter based on active session country context
        if ($activeCountryId && $activeCountryId !== 'no') {
            $query->where(function ($q) use ($activeCountryId) {
                $q->where('is_global', true)
                  ->orWhereHas('countries', function ($subQuery) use ($activeCountryId) {
                      $subQuery->where('countries.id', $activeCountryId);
                  });
            });
        }

        $purpose = $query->latest()->get();

        return view('purpose.index')->with('purpose', $purpose);
    }

    public function create()
    {
        $disableCountry = false;
        $countries = $this->getScopedCountriesForForm($disableCountry);

        // Optionally pre-select the active session country if one is chosen
        $activeCountryId = session('active_country_id');
        $selectedCountries = ($activeCountryId && $activeCountryId !== 'no') ? [$activeCountryId] : [];

        return view('purpose.create', compact('countries', 'disableCountry', 'selectedCountries'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'         => 'required|string|max:255',
            'description'   => 'nullable|string',
            'is_global'     => 'nullable|boolean',
            'has_provider_settlement' => 'nullable|boolean',
            'provider_name' => 'nullable|required_if:has_provider_settlement,1|string|max:255',
            'country_ids'   => 'array',
            'country_ids.*' => 'exists:countries,id',
        ]);

        $isGlobal = $request->has('is_global') ? true : false;
        $hasProvider = $request->has('has_provider_settlement') ? true : false;

        $purpose = Purpose::create([
            'title'         => $validated['title'],
            'description'   => $validated['description'] ?? null,
            'is_global'     => $isGlobal,
            'has_provider_settlement' => $hasProvider,
            'provider_name'           => $hasProvider ? $validated['provider_name'] : null,
            'created_by_id' => Auth::id(),
            'is_active'     => 1,
        ]);

        if (!$isGlobal && !empty($validated['country_ids'])) {
            $purpose->countries()->sync($validated['country_ids']);
        }

        return redirect()->route('purpose.index')->withSuccess('Data saved successfully.');
    }

    public function edit(Purpose $purpose)
    {
        $disableCountry = false;
        $countries = $this->getScopedCountriesForForm($disableCountry);

        $selectedCountries = $purpose->countries->pluck('id')->toArray();

        $hasTransactions = Transaction::where('purpose_id', $purpose->id)->exists()
            || ProviderSettlement::where('purpose_id', $purpose->id)->exists();

        return view('purpose.create', compact('purpose', 'countries', 'disableCountry', 'selectedCountries', 'hasTransactions'));
    }

    public function update(Request $request, Purpose $purpose)
    {
        $hasTransactions = Transaction::where('purpose_id', $purpose->id)->exists()
            || ProviderSettlement::where('purpose_id', $purpose->id)->exists();

        $rules = [
            'title'         => 'required|string|max:255',
            'description'   => 'nullable|string',
            'is_global'     => 'nullable|boolean',
            'country_ids'   => 'array',
            'country_ids.*' => 'exists:countries,id',
        ];

        // Only validate/allow changing provider settlement rules if no transactions exist yet
        if (!$hasTransactions) {
            $rules['has_provider_settlement'] = 'nullable|boolean';
            $rules['provider_name'] = 'nullable|required_if:has_provider_settlement,1|string|max:255';
        }

        $validated = $request->validate($rules);

        $isGlobal = $request->has('is_global') ? true : false;

        $updateData = [
            'title'       => $validated['title'],
            'description' => $validated['description'] ?? null,
            'is_global'   => $isGlobal,
        ];

        if (!$hasTransactions) {
            $hasProvider = $request->has('has_provider_settlement') ? true : false;
            $updateData['has_provider_settlement'] = $hasProvider;
            $updateData['provider_name'] = $hasProvider ? $validated['provider_name'] : null;
        }

        $purpose->update($updateData);

        if ($isGlobal) {
            $purpose->countries()->detach();
        } else {
            $purpose->countries()->sync($validated['country_ids'] ?? []);
        }

        return redirect()->route('purpose.index')->withSuccess('Data updated successfully.');
    }

    public function destroy(Purpose $purpose)
    {
        if (Transaction::where('purpose_id', $purpose->id)->exists()) {
            return redirect()->route('purpose.index')
                ->with('error', 'Cannot delete this purpose because it has linked transactions.');
        }

        $purpose->delete();

        return redirect()->route('purpose.index')->withSuccess('Data deleted successfully.');
    }
}
