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
        $currentMonth = $request->input('month', Carbon::now()->format('Y-m'));
        $query = ProviderSettlement::with([
            'transaction.bankSetting.bank',
            'purpose',
            'country',
            'created_by'
        ])->orderBy('id', 'desc');

        // Filter by month on created_at
        if ($currentMonth) {
            $startDate = Carbon::parse($currentMonth)->startOfMonth();
            $endDate = Carbon::parse($currentMonth)->endOfMonth();
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }

        $this->scopeByCountry($query);

        $totalSum = (clone $query)->sum('settlement_amount');

        $settlements = $query->paginate(50);

        return view('provider_settlement.index', compact('settlements', 'currentMonth', 'totalSum'));
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
