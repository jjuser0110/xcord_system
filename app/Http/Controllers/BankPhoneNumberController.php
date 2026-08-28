<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BankPhoneNumber;
use App\Traits\CountryScopeTrait;
use Illuminate\Support\Facades\Auth;

class BankPhoneNumberController extends Controller
{
    use CountryScopeTrait;

    public function index(Request $request)
    {
        $query = BankPhoneNumber::with(['bankSetting.bank', 'bankSetting.country']);

        $activeCountryId = session('active_country_id');

        // Only apply the country filter if 'active_country_id' is set and is NOT 'no' (which represents 'ALL')
        if ($activeCountryId && $activeCountryId !== 'no') {
            $query->whereHas('bankSetting', function ($q) use ($activeCountryId) {
                $q->where('country_id', $activeCountryId);
            });
        } elseif (!$activeCountryId && Auth::check() && Auth::user()->country_id) {
            // Fallback to user's assigned country if no session is active yet
            $query->whereHas('bankSetting', function ($q) {
                $q->where('country_id', Auth::user()->country_id);
            });
        }
        // If activeCountryId is 'no', it skips the condition entirely and shows ALL records across countries.

        $phoneNumbers = $query->latest()->paginate(50);

        return view('bank_phone_number.index', compact('phoneNumbers'));
    }
}
