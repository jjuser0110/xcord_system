<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Bank extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'bank_name',
        'short_name',
        'created_by_id',
        'country_id',
        'is_active'
    ];

    public function setShortNameAttribute($value)
    {
        $this->attributes['short_name'] = strtoupper($value);
    }

    /**
     * Relationship: The user who created this bank.
     */
    public function created_by()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    /**
     * Relationship: The country this bank belongs to.
     */
    public function country()
    {
        return $this->belongsTo(Country::class, 'country_id');
    }

    /**
     * Relationship: Bank settings belonging to this bank.
     */
    public function bankSettings()
    {
        return $this->hasMany(BankSetting::class, 'bank_id');
    }

}
