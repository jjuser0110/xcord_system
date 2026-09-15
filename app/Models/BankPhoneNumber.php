<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankPhoneNumber extends Model
{
    use HasFactory;

    protected $fillable = ['bank_setting_id', 'telco', 'phone_number', 'expired_date'];

    public const TELCOS = [
        'HOTLINK'  => 'Hotlink',
        'UMOBILE'  => 'U Mobile',
        'DIGI'     => 'Digi',
        'TUNETALK' => 'Tune Talk',
        'XOX'      => 'XOX',
        'REDONE'   => 'redONE',
    ];

    public function bankSetting()
    {
        return $this->belongsTo(BankSetting::class, 'bank_setting_id');
    }
}
