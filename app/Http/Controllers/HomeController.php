<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Models\BankSetting;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class HomeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $countryId = Auth::user()->country_id;
        $today = Carbon::today();
        $currentMonth = Carbon::now()->format('Y-m');

        $todayTransferToOwn = Transaction::where('country_id', $countryId)
            ->whereDate('transaction_date', $today)
            ->where('type', 'own')
            ->where('transfer_direction', '-')
            ->sum('amount');

        $todayReceiveFromOwn = Transaction::where('country_id', $countryId)
            ->whereDate('transaction_date', $today)
            ->where('type', 'own')
            ->where('transfer_direction', '+')
            ->sum('amount');

        $todayTransferToCustomer = Transaction::where('country_id', $countryId)
            ->whereDate('transaction_date', $today)
            ->where('type', 'customer')
            ->where('transfer_direction', '-')
            ->sum('amount');

        $todayReceiveFromCustomer = Transaction::where('country_id', $countryId)
            ->whereDate('transaction_date', $today)
            ->where('type', 'customer')
            ->where('transfer_direction', '+')
            ->sum('amount');

        $bankSettings = BankSetting::with('bank')
            ->where('country_id', $countryId)
            ->get();

        $totalSystemCapital = $bankSettings->sum('capital');

        $monthlyVolume = Transaction::where('country_id', $countryId)
            ->where('closing_month', $currentMonth)
            ->sum('amount');

        return view('home', compact(
            'todayTransferToOwn',
            'todayReceiveFromOwn',
            'todayTransferToCustomer',
            'todayReceiveFromCustomer',
            'bankSettings',
            'totalSystemCapital',
            'monthlyVolume'
        ));
    }
}
