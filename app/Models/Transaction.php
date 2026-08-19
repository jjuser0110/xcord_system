<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaction extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    public function bankSetting()
    {
        return $this->belongsTo(BankSetting::class, 'bank_setting_id');
    }

    public function targetBankSetting()
    {
        return $this->belongsTo(BankSetting::class, 'target_bank_setting_id');
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function purpose()
    {
        return $this->belongsTo(Purpose::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }
}
