<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Models\BankSetting;
use App\Models\BankSnapshot;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Traits\CountryScopeTrait;

class HomeController extends Controller
{
    use CountryScopeTrait;

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $today = Carbon::today();
        $currentMonth = Carbon::now()->format('Y-m');

        $todayTransferToOwnQuery = Transaction::whereDate('transaction_date', $today)
            ->where('type', 'own')
            ->where('transfer_direction', '-');
        $this->scopeByCountry($todayTransferToOwnQuery);
        $todayTransferToOwn = $todayTransferToOwnQuery->sum('amount');

        $todayReceiveFromOwnQuery = Transaction::whereDate('transaction_date', $today)
            ->where('type', 'own')
            ->where('transfer_direction', '+');
        $this->scopeByCountry($todayReceiveFromOwnQuery);
        $todayReceiveFromOwn = $todayReceiveFromOwnQuery->sum('amount');

        $todayTransferToCustomerQuery = Transaction::whereDate('transaction_date', $today)
            ->where('type', 'customer')
            ->where('transfer_direction', '-');
        $this->scopeByCountry($todayTransferToCustomerQuery);
        $todayTransferToCustomer = $todayTransferToCustomerQuery->sum('amount');

        $todayReceiveFromCustomerQuery = Transaction::whereDate('transaction_date', $today)
            ->where('type', 'customer')
            ->where('transfer_direction', '+');
        $this->scopeByCountry($todayReceiveFromCustomerQuery);
        $todayReceiveFromCustomer = $todayReceiveFromCustomerQuery->sum('amount');

        $bankSettingsQuery = BankSetting::with('bank');
        $this->scopeByCountry($bankSettingsQuery);
        $bankSettings = $bankSettingsQuery->get();

        $totalSystemCapital = $bankSettings->sum('capital');

        $monthlyVolumeQuery = Transaction::where('closing_month', $currentMonth);
        $this->scopeByCountry($monthlyVolumeQuery);
        $monthlyVolume = $monthlyVolumeQuery->sum('amount');

        // $currentDate = Carbon::today()->toDateString();
        // $dailySnapshotsQuery = BankSnapshot::with('bankSetting.bank')
        //     ->where('snapshot_date', $currentDate);
        // $this->scopeByCountry($dailySnapshotsQuery);
        //$dailySnapshots = $dailySnapshotsQuery->get();

        return view('home', compact(
            'todayTransferToOwn',
            'todayReceiveFromOwn',
            'todayTransferToCustomer',
            'todayReceiveFromCustomer',
            'bankSettings',
            'totalSystemCapital',
            'monthlyVolume',
            //'dailySnapshots'
        ));
    }
}
