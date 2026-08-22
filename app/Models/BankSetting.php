<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BankSetting extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'bank_id',
        'account_no',
        'owner_name',
        'capital',
        'phone_number',
        'expired_date',
        'color',
        'type',
        'path',
        'is_active',
        'created_by_id',
        'country_id'
    ];

    protected $appends = [
        'current_balance',
    ];


    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function bank()
    {
        return $this->belongsTo('App\Models\Bank')->withTrashed();
    }

    public function created_by()
    {
        return $this->belongsTo('App\Models\User','created_by_id');
    }

    public function getCurrentBalanceAttribute()
    {
        $latestLog = BankLog::where('bank_setting_id', $this->id)
                        ->latest('id')
                        ->first();

        return $latestLog ? $latestLog->end_balance : ($this->capital ?? 0);
    }
}
