<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Models\BankSetting;
use App\Models\BankSnapshot;
use App\Models\MerchantSettlement;
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

        // 1. Transfer to Own Bank Metric
        $transferToOwnQuery = Transaction::query();
        $applyDateFilter($transferToOwnQuery);
        $transferToOwnQuery->where('type', 'own')->where('transfer_direction', '-');
        $this->scopeByCountry($transferToOwnQuery);
        $transferToOwn = $transferToOwnQuery->sum('amount');

        // 2. Received from Own Bank Metric
        $receiveFromOwnQuery = Transaction::query();
        $applyDateFilter($receiveFromOwnQuery);
        $receiveFromOwnQuery->where('type', 'own')->where('transfer_direction', '+');
        $this->scopeByCountry($receiveFromOwnQuery);
        $receiveFromOwn = $receiveFromOwnQuery->sum('amount');

        // 3. Transfer for Merchant Metric
        $transferForMerchantQuery = MerchantSettlement::query();
        $applyDateFilter($transferForMerchantQuery);
        $transferForMerchantQuery->whereHas('purpose', function($q) {
            $q->where('show_on_transfer_for_merchant', true);
        });
        $this->scopeByCountry($transferForMerchantQuery);
        $transferForMerchant = $transferForMerchantQuery->sum('settlement_amount');

        // 4. Expenses Metric (Checking Purpose name/title explicitly)
        $expensesQuery = Transaction::query();
        $applyDateFilter($expensesQuery);
        $expensesQuery->whereHas('purpose', function($q) {
            $q->where('title', 'Expenses');
        });
        $this->scopeByCountry($expensesQuery);
        $expenses = $expensesQuery->sum('amount');

        // 5. Received from Provider Metric
        $receiveFromProviderQuery = ProviderSettlement::query();
        $applyDateFilter($receiveFromProviderQuery);
        $receiveFromProviderQuery->whereHas('purpose', function($q) {
            $q->where('show_on_received_from_provider', true);
        });
        $this->scopeByCountry($receiveFromProviderQuery);
        $receiveFromProvider = $receiveFromProviderQuery->sum('settlement_amount');

        // 6. TopUp to Provider Metric
        $topUpToProviderQuery = ProviderSettlement::query();
        $applyDateFilter($topUpToProviderQuery);
        $topUpToProviderQuery->whereHas('purpose', function($q) {
            $q->where('show_on_topup_to_provider', true);
        });
        $this->scopeByCountry($topUpToProviderQuery);
        $topUpToProvider = $topUpToProviderQuery->sum('settlement_amount');

        $bankSettingsQuery = BankSetting::with('bank');
        $this->scopeByCountry($bankSettingsQuery);
        $bankSettings = $bankSettingsQuery->get();

        $currentDate = Carbon::today()->toDateString();
        $dailySnapshotsQuery = BankSnapshot::with('bankSetting.bank')->where('snapshot_date', $currentDate);
        $this->scopeByCountry($dailySnapshotsQuery);
        $dailySnapshots = $dailySnapshotsQuery->get();

        return view('home', compact(
            'transferToOwn',
            'receiveFromOwn',
            'transferForMerchant',
            'expenses',
            'receiveFromProvider',
            'topUpToProvider',
            'bankSettings',
            'dailySnapshots',
            'filterType',
            'currentMonth',
            'startDate',
            'endDate'
        ));
    }
}
