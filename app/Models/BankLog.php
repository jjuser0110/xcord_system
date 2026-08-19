<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'country_id',
        'bank_setting_id',
        'transaction_id',
        'type',
        'remark',
        'start_balance',
        'amount',
        'end_balance',
        'created_by_id',
    ];

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function bankSetting()
    {
        return $this->belongsTo(BankSetting::class);
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }
}
