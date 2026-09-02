<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProviderSettlement extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'transaction_id',
        'purpose_id',
        'country_id',
        'bank_name',
        'settlement_amount',
        'provider_name',
        'created_by_id',
    ];

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    public function purpose()
    {
        return $this->belongsTo(Purpose::class);
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function created_by()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function bankSetting()
{
    return $this->hasOneThrough(
        BankSetting::class,
        Transaction::class,
        'id',             // Foreign key on transactions table (transaction_id)
        'id',             // Foreign key on bank_settings table (bank_setting_id)
        'transaction_id', // Local key on provider_settlements table
        'bank_setting_id' // Local key on transactions table
    );
}
}
