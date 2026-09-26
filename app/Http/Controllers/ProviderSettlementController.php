<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ProviderSettlement;
use App\Models\Purpose;
use App\Traits\CountryScopeTrait;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class ProviderSettlementController extends Controller
{
    use CountryScopeTrait;

    public function index(Request $request)
    {
        $currentDate = $request->input('date', Carbon::now()->format('Y-m-d'));
        $currentType = $request->input('type', 'in');
        $currentProvider = $request->input('provider_name');

        $query = ProviderSettlement::with([
            'transaction.bankSetting.bank',
            'purpose',
            'country',
            'created_by'
        ])->orderBy('id', 'desc');

        // Filter by exact date on created_at
        if ($currentDate) {
            $query->whereDate('created_at', $currentDate);
        }

        // Filter by In / Out type column
        if ($currentType && in_array($currentType, ['in', 'out'])) {
            $query->where('type', $currentType);
        }

        if ($currentProvider) {
            $query->where('provider_name', $currentProvider);
        }

        $this->scopeByCountry($query);

        $totalSum = (clone $query)->sum('settlement_amount');

        $settlements = $query->paginate(50);

        $providerQuery = Purpose::query()
            ->whereNotNull('provider_name')
            ->where('provider_name', '!=', '');

        // 1. Filter by Type (In / Out)
        if ($currentType === 'in') {
            $providerQuery->where('show_on_received_from_provider', 1);
        } elseif ($currentType === 'out') {
            $providerQuery->where('show_on_topup_to_provider', 1);
        }

        $providers = $providerQuery->pluck('provider_name')->unique()->filter();

        return view('provider_settlement.index', compact('settlements', 'currentDate', 'currentType', 'currentProvider', 'providers', 'totalSum'));
    }

    public function show(ProviderSettlement $providerSettlement)
    {
        $providerSettlement->load([
            'transaction.bankSetting.bank',
            'purpose',
            'country',
            'created_by'
        ]);

        return view('provider_settlement.view', compact('providerSettlement'));
    }
}
