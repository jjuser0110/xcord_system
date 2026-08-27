<?php

namespace App\Models;

use Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Country extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'currency_code',
        'is_active',
        'created_by_id'
    ];

    public function setCurrencyCodeAttribute($value)
    {
        $this->attributes['currency_code'] = strtoupper($value);
    }

    /**
     * Relationship: The user who created this country.
     */
    public function created_by()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    /**
     * Relationship: Users that belong to this country.
     */
    public function users()
    {
        return $this->hasMany(User::class, 'country_id');
    }

    /**
     * Relationship: Banks belonging to this country.
     */
    public function banks()
    {
        return $this->hasMany(Bank::class, 'country_id');
    }
}
