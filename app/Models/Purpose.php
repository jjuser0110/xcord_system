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
        'is_active',
        'is_global'
    ];

    /**
     * Relationship: The user who created this purpose.
     */
    public function created_by()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    /**
     * Relationship: Countries linked to this purpose (Many-to-Many).
     */
    public function countries()
    {
        return $this->belongsToMany(Country::class, 'country_purpose', 'purpose_id', 'country_id');
    }
}
