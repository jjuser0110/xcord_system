<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Spatie\Browsershot\Browsershot;
use Illuminate\Http\Request;
use App\Models\Country;
use App\Models\Purpose;
use Bouncer;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class PurposeController extends Controller
{
    public function index(Request $request)
    {
        // TODO: Client decides where conditions
        $purpose = Purpose::with('country')->latest()->get();

        return view('purpose.index')->with('purpose',$purpose);
    }

    public function create()
    {
        $countries = Country::where('is_active', 1)->get();
        return view('purpose.create', compact('countries'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'required|string',
            'country_id'  => 'required|exists:countries,id',
        ]);

        Purpose::create([
            'title'         => $validated['title'],
            'description'   => $validated['description'],
            'country_id'    => $validated['country_id'],
            'created_by_id' => Auth::id(),
            'is_active'     => 1,
        ]);

        return redirect()->route('purpose.index')->withSuccess('Data saved successfully.');
    }

    public function edit(Purpose $purpose)
    {
        $countries = Country::where('is_active', 1)->get();
        return view('purpose.create', compact('purpose', 'countries'));
    }

    public function update(Request $request, Purpose $purpose)
    {
        $validated = $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'required|string',
            'country_id'  => 'required|exists:countries,id',
        ]);

        $purpose->update($validated);

        return redirect()->route('purpose.index')->withSuccess('Data updated successfully.');
    }

    public function destroy(Purpose $purpose)
    {
        $purpose->delete();

        return redirect()->route('purpose.index')->withSuccess('Data deleted successfully.');
    }

}
