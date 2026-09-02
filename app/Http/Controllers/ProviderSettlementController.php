<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ProviderSettlement;
use App\Traits\CountryScopeTrait;

class ProviderSettlementController extends Controller
{
    use CountryScopeTrait;

    public function index(Request $request)
    {
        $query = ProviderSettlement::with([
            'transaction.bankSetting.bank',
            'purpose',
            'country',
            'created_by'
        ])->orderBy('id', 'desc');

        $this->scopeByCountry($query);

        $settlements = $query->paginate(50);

        return view('provider_settlement.index', compact('settlements'));
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
