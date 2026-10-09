<?php

namespace App\Http\Controllers;

use App\Models\Purpose;
use App\Models\Transaction;
use App\Traits\CountryScopeTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PurposeReportController extends Controller
{
    use CountryScopeTrait;

    public function index(Request $request)
    {
        $currentDate = $request->input('date', Carbon::now()->format('Y-m-d'));
        $currentPurposeId = $request->input('purpose_id');

        $query = Transaction::with([
            'bankSetting.bank',
            'purpose',
            'country',
            'creator'
        ])->orderBy('id', 'desc');

        if ($currentDate) {
            $query->whereDate('created_at', $currentDate);
        }

        $activeCountryId = session('active_country_id');
        $userCountryId = Auth::user()->country_id ?? null;

        $purposesQuery = Purpose::query();

        if ($activeCountryId !== 'no') {
            $purposesQuery->where(function ($q) use ($activeCountryId, $userCountryId) {
                $q->where('is_global', 1);

                if ($activeCountryId && $activeCountryId !== 'no') {
                    $q->orWhereHas('countries', function ($sub) use ($activeCountryId) {
                        $sub->where('countries.id', $activeCountryId);
                    });
                } elseif ($userCountryId) {
                    $q->orWhereHas('countries', function ($sub) use ($userCountryId) {
                        $sub->where('countries.id', $userCountryId);
                    });
                }
            });
        }

        $purposes = $purposesQuery->where('is_active', 1)->orderBy('title', 'asc')->get();

        if ($currentPurposeId) {
            $query->where('purpose_id', $currentPurposeId);
        }

        $this->scopeByCountry($query);

        // Calculate totals based on transfer direction
        $summaryQuery = clone $query;
        $totalIn = (clone $summaryQuery)->where('transfer_direction', '+')->sum('amount');
        $totalOut = (clone $summaryQuery)->where('transfer_direction', '-')->sum('amount');
        $netTotal = $totalIn - $totalOut;

        $transactions = $query->paginate(50);

        return view('purpose_report.index', compact(
            'transactions',
            'currentDate',
            'currentPurposeId',
            'purposes',
            'totalIn',
            'totalOut',
            'netTotal'
        ));
    }
}
