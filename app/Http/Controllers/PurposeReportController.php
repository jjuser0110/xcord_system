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
        // Default to today's date to preserve the existing report behaviour.
        $today = Carbon::today()->toDateString();

        $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'purpose_id' => ['nullable', 'integer', 'exists:purposes,id'],
        ]);
        
        $startDate = $request->input('start_date', $today) ?: $today;
        $endDate = $request->input('end_date', $today) ?: $today;
        
        $startDate = Carbon::parse($startDate)->toDateString();
        $endDate = Carbon::parse($endDate)->toDateString();

        // Correct the range if the dates are entered in reverse order.
        if ($startDate > $endDate) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        $currentPurposeId = $request->input('purpose_id');

        // Transaction report query.
        $query = Transaction::with([
            'bankSetting.bank',
            'purpose',
            'country',
            'creator',
        ]);

        // Filter using the transaction date shown in the report.
        $query->whereDate('transaction_date', '>=', $startDate)
              ->whereDate('transaction_date', '<=', $endDate);

        // Filter by purpose.
        if ($currentPurposeId) {
            $query->where('purpose_id', $currentPurposeId);
        }

        // Preserve the existing country scope.
        $this->scopeByCountry($query);

        // Latest transactions first.
        $query->orderBy('id', 'desc');

        // Calculate totals using the same filters as the transaction table.
        $totalIn = (clone $query)
            ->where('transfer_direction', '+')
            ->sum('amount');

        $totalOut = (clone $query)
            ->where('transfer_direction', '-')
            ->sum('amount');

        $netTotal = $totalIn - $totalOut;

        // Paginate without losing the selected filters.
        $transactions = $query->paginate(50);

        // Purpose dropdown, preserving existing country-specific logic.
        $activeCountryId = session('active_country_id');
        $userCountryId = Auth::user()->country_id ?? null;

        $purposesQuery = Purpose::query();

        if ($activeCountryId !== 'no') {
            $purposesQuery->where(function ($q) use (
                $activeCountryId,
                $userCountryId
            ) {
                $q->where('is_global', 1);

                if ($activeCountryId && $activeCountryId !== 'no') {
                    $q->orWhereHas('countries', function ($sub) use (
                        $activeCountryId
                    ) {
                        $sub->where('countries.id', $activeCountryId);
                    });
                } elseif ($userCountryId) {
                    $q->orWhereHas('countries', function ($sub) use (
                        $userCountryId
                    ) {
                        $sub->where('countries.id', $userCountryId);
                    });
                }
            });
        }

        $purposes = $purposesQuery
            ->where('is_active', 1)
            ->orderBy('title', 'asc')
            ->get();

        // Keep currentDate for compatibility with the existing Blade view.
        $currentDate = $startDate === $endDate ? $startDate : null;

        return view('purpose_report.index', compact(
            'transactions',
            'currentDate',
            'startDate',
            'endDate',
            'currentPurposeId',
            'purposes',
            'totalIn',
            'totalOut',
            'netTotal'
        ));
    }
}
