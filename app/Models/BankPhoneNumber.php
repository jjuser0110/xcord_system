<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankPhoneNumber extends Model
{
    use HasFactory;

    protected $fillable = ['bank_setting_id', 'phone_number', 'expired_date'];

    public function bankSetting()
    {
        return $this->belongsTo(BankSetting::class, 'bank_setting_id');
    }
}
