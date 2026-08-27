<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Bouncer;

class Role extends Model
{
    use HasFactory;
	protected $fillable = [
        'name',
        'title',
        'scope',
    ];

    public static function getCompanyStaffRoleId()
    {
        $role = Bouncer::role()->where('name', 'company_staff')->first();

        return $role ? $role->id : null;
    }
}
