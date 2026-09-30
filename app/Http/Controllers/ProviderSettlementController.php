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
        $sortBy = $request->input('sort');
        $direction = $request->input('direction', 'asc');

        $query = ProviderSettlement::with([
            'transaction.bankSetting.bank',
            'purpose',
            'country',
            'created_by'
        ]);

        // Filter by exact date on created_at
        if ($currentDate) {
            $query->whereDate('provider_settlements.created_at', $currentDate);
        }

        // Filter by In / Out type column
        if ($currentType && in_array($currentType, ['in', 'out'])) {
            $query->where('provider_settlements.type', $currentType);
        }

        $providerQuery = Purpose::query()
            ->whereNotNull('provider_name')
            ->where('provider_name', '!=', '');

        if ($currentType === 'in') {
            $providerQuery->where('show_on_received_from_provider', 1);
        } elseif ($currentType === 'out') {
            $providerQuery->where('show_on_topup_to_provider', 1);
        }

        $providers = $providerQuery->pluck('provider_name')->unique()->filter();

        if ($currentProvider && !$providers->contains($currentProvider)) {
            $currentProvider = null;
        }

        if ($currentProvider) {
            $query->where('provider_settlements.provider_name', $currentProvider);
        }

        $this->scopeByCountry($query);

        if ($sortBy === 'bank_setting') {
            $query->orderBy(
                \App\Models\BankSetting::select('owner_name')
                    ->whereColumn('bank_settings.id', 'provider_settlements.bank_setting_id'),
                $direction
            );
        } elseif ($sortBy === 'settlement') {
            $query->orderBy('provider_settlements.settlement_amount', $direction);
        } elseif ($sortBy === 'provider') {
            $query->orderBy('provider_settlements.provider_name', $direction);
        } else {
            $query->orderBy('provider_settlements.id', 'desc');
        }

        $totalSum = (clone $query)->sum('provider_settlements.settlement_amount');

        $settlements = $query->paginate(50);

        return view('provider_settlement.index', compact('settlements', 'currentDate', 'currentType', 'currentProvider', 'providers', 'totalSum', 'sortBy', 'direction'));
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
