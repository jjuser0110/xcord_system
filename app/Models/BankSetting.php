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

    // public function deposit_transaction()
    // {
    //     return $this->hasMany('App\Models\BankInDepositTransaction');
    // }

    // public function withdraw_transaction()
    // {
    //     return $this->hasMany('App\Models\WithdrawTransaction');
    // }

    // public function bank_logs()
    // {
    //     return $this->morphMany('App\Models\BankLog', 'content');
    // }

    // public function all_bank_logs()
    // {
    //     return $this->hasMany('App\Models\BankLog', 'bank_setting_id');
    // }
}
