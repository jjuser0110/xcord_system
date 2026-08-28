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
        'owner_name',
        'capital',
        'amount',
        'color',
        'path',
        'is_active',
        'created_by_id',
        'country_id'
    ];

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function bank()
    {
        return $this->belongsTo(Bank::class)->withTrashed();
    }

    public function phoneNumbers()
    {
        return $this->hasMany(BankPhoneNumber::class, 'bank_setting_id');
    }

    public function created_by()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'bank_setting_id');
    }

    public function getCurrentBalanceAttribute()
    {
        $latestLog = BankLog::where('bank_setting_id', $this->id)
                        ->latest('id')
                        ->first();

        return $latestLog ? $latestLog->end_balance : ($this->capital ?? 0);
    }
}
