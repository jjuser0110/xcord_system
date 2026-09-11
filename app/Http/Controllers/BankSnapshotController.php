<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BankSetting;
use App\Models\BankSnapshot;
use App\Traits\CountryScopeTrait;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;

class BankSnapshotController extends Controller
{
    use CountryScopeTrait;

    public function index(Request $request)
    {
        $selectedDate = $request->input('date', Carbon::today()->toDateString());
        $today = Carbon::today()->toDateString();

        // 1. Fetch all active bank settings filtered by country scope
        $query = BankSetting::with(['bank', 'country'])->where('is_active', 1);
        $this->scopeByCountry($query);
        $bankSettings = $query->get()->unique('id');

        $reportData = [];
        $totalCapital = 0;

        foreach ($bankSettings as $setting) {
            // Rule 1: Today or future -> use current bank setting amount
            if ($selectedDate >= $today) {
                $balance = $setting->amount ?? 0;
            }
            // Rule 2: Past date -> check for snapshot existence
            else {
                $snapshot = BankSnapshot::where('bank_setting_id', $setting->id)
                    ->where('snapshot_date', $selectedDate)
                    ->first();

                // If snapshot exists for this past date, use its capital; otherwise 0
                $balance = $snapshot ? $snapshot->capital : 0.00;
            }

            // Attach computed balance for view rendering
            $setting->computed_balance = $balance;

            $reportData[] = $setting;
            $totalCapital += $balance;
        }

        // Convert array to collection for custom pagination while keeping grand totals accurate
        $collection = collect($reportData);
        $perPage = 50;
        $page = $request->input('page', 1);

        $bankSettings = new LengthAwarePaginator(
            $collection->forPage($page, $perPage),
            $collection->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('bank_snapshot.index', compact('bankSettings', 'selectedDate', 'totalCapital'));
    }
}
