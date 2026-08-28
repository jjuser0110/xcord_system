<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BankPhoneNumber;
use App\Traits\CountryScopeTrait;

class BankPhoneNumberController extends Controller
{
    use CountryScopeTrait;

    public function index(Request $request)
    {
        $query = BankPhoneNumber::with(['bankSetting.bank', 'bankSetting.country']);

        $this->scopeByCountry($query, 'bank_settings');

        $phoneNumbers = $query->latest()->get();

        return view('bank_phone_number.index', compact('phoneNumbers'));
    }
}
