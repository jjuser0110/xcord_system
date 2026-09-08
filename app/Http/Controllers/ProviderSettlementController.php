<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ProviderSettlement;
use App\Traits\CountryScopeTrait;
use Carbon\Carbon;

class ProviderSettlementController extends Controller
{
    use CountryScopeTrait;

    public function index(Request $request)
    {
        $currentDate = $request->input('date', Carbon::now()->format('Y-m-d'));
        $currentType = $request->input('type', 'in');

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

        $this->scopeByCountry($query);

        $totalSum = (clone $query)->sum('settlement_amount');

        $settlements = $query->paginate(50);

        return view('provider_settlement.index', compact('settlements', 'currentDate', 'currentType', 'totalSum'));
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
