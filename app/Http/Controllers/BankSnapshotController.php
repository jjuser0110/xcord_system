<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BankSnapshot;
use App\Traits\CountryScopeTrait;
use Carbon\Carbon;

class BankSnapshotController extends Controller
{
    use CountryScopeTrait;

    public function index(Request $request)
    {
        $selectedDate = $request->input('date', Carbon::today()->toDateString());

        $query = BankSnapshot::with(['bankSetting.bank', 'country'])
            ->where('snapshot_date', $selectedDate);

        $this->scopeByCountry($query);

        $snapshots = $query->paginate(50);

        // Changed from 'balance' to 'capital' to match your schema
        $totalCapital = (clone $query)->sum('capital');

        return view('bank_snapshot.index', compact('snapshots', 'selectedDate', 'totalCapital'));
    }
}
