<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Models\BankSetting;
use App\Models\BankSnapshot;
use App\Models\ProviderSettlement;
use App\Models\Purpose;
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

    public function index(Request $request)
    {
        $today = Carbon::today();

        // Support dynamic date range or monthly filters from request inputs
        $filterType = $request->input('filter_type', 'month'); // 'month' or 'date_range'
        $currentMonth = $request->input('month', Carbon::now()->format('Y-m'));
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        // Apply date boundaries helper closure
        $applyDateFilter = function ($query, $dateCol = 'created_at') use ($filterType, $currentMonth, $startDate, $endDate) {
            if ($filterType === 'date_range' && $startDate && $endDate) {
                $query->whereBetween($dateCol, [
                    Carbon::parse($startDate)->startOfDay(),
                    Carbon::parse($endDate)->endOfDay()
                ]);
            } else {
                $query->whereYear($dateCol, Carbon::parse($currentMonth)->year)
                      ->whereMonth($dateCol, Carbon::parse($currentMonth)->month);
            }
        };

        // Standard Daily Metrics
        $todayTransferToOwnQuery = Transaction::whereDate('created_at', $today)->where('type', 'own')->where('transfer_direction', '-');
        $this->scopeByCountry($todayTransferToOwnQuery);
        $todayTransferToOwn = $todayTransferToOwnQuery->sum('amount');

        $todayReceiveFromOwnQuery = Transaction::whereDate('created_at', $today)->where('type', 'own')->where('transfer_direction', '+');
        $this->scopeByCountry($todayReceiveFromOwnQuery);
        $todayReceiveFromOwn = $todayReceiveFromOwnQuery->sum('amount');

        $todayTransferToCustomerQuery = Transaction::whereDate('created_at', $today)->where('type', 'customer')->where('transfer_direction', '-');
        $this->scopeByCountry($todayTransferToCustomerQuery);
        $todayTransferToCustomer = $todayTransferToCustomerQuery->sum('amount');

        $todayReceiveFromCustomerQuery = Transaction::whereDate('created_at', $today)->where('type', 'customer')->where('transfer_direction', '+');
        $this->scopeByCountry($todayReceiveFromCustomerQuery);
        $todayReceiveFromCustomer = $todayReceiveFromCustomerQuery->sum('amount');

        $bankSettingsQuery = BankSetting::with('bank');
        $this->scopeByCountry($bankSettingsQuery);
        $bankSettings = $bankSettingsQuery->get();

        $monthlyMerchantTransferQuery = ProviderSettlement::query();
        $applyDateFilter($monthlyMerchantTransferQuery);
        $monthlyMerchantTransferQuery->whereHas('purpose', function($q) {
            $q->where('show_on_transfer_for_merchant', true);
        });
        $this->scopeByCountry($monthlyMerchantTransferQuery);
        $monthlyTransferForMerchant = $monthlyMerchantTransferQuery->sum('settlement_amount');

        // 2. Received from Provider Report Metric (Based on ProviderSettlement & Flag)
        $monthlyReceiveFromProviderQuery = ProviderSettlement::query();
        $applyDateFilter($monthlyReceiveFromProviderQuery);
        $monthlyReceiveFromProviderQuery->whereHas('purpose', function($q) {
            $q->where('show_on_received_from_provider', true);
        });
        $this->scopeByCountry($monthlyReceiveFromProviderQuery);
        $monthlyReceiveFromProvider = $monthlyReceiveFromProviderQuery->sum('settlement_amount');

        // 3. TopUp to Provider Report Metric (Based on ProviderSettlement & Flag)
        $monthlyTopUpToProviderQuery = ProviderSettlement::query();
        $applyDateFilter($monthlyTopUpToProviderQuery);
        $monthlyTopUpToProviderQuery->whereHas('purpose', function($q) {
            $q->where('show_on_topup_to_provider', true);
        });
        $this->scopeByCountry($monthlyTopUpToProviderQuery);
        $monthlyTopUpToProvider = $monthlyTopUpToProviderQuery->sum('settlement_amount');

        $currentDate = Carbon::today()->toDateString();
        $dailySnapshotsQuery = BankSnapshot::with('bankSetting.bank')->where('snapshot_date', $currentDate);
        $this->scopeByCountry($dailySnapshotsQuery);
        $dailySnapshots = $dailySnapshotsQuery->get();

        return view('home', compact(
            'todayTransferToOwn',
            'todayReceiveFromOwn',
            'todayTransferToCustomer',
            'todayReceiveFromCustomer',
            'bankSettings',
            'monthlyTransferForMerchant',
            'monthlyReceiveFromProvider',
            'monthlyTopUpToProvider',
            'dailySnapshots',
            'filterType',
            'currentMonth',
            'startDate',
            'endDate'
        ));
    }
}
