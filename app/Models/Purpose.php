<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Purpose extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'created_by_id',
        'country_id',
        'is_active'
    ];

    /**
     * Relationship: The user who created this purpose.
     */
    public function created_by()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    /**
     * Relationship: The country this purpose belongs to.
     */
    public function country()
    {
        return $this->belongsTo(Country::class, 'country_id');
    }


}
