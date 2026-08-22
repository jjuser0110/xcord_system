<?php

namespace App\Traits;

use Illuminate\Support\Facades\Auth;

trait CountryScopeTrait
{
    protected function scopeByCountry($query, $column = 'country_id')
    {
        $user = Auth::user();

        if (!$user) {
            return $query;
        }

        $isSuperAdmin = optional($user->role)->title == 'Super Admin' || optional($user->role)->name == 'superadmin';
        if ($isSuperAdmin) {
            $activeCountryId = session('active_country_id');

            if ($activeCountryId === 'no') {
                return $query;
            }

            $countryId = session('active_country_id', $user->country_id);

            if ($countryId) {
                $query->where($column, $countryId);
            }
        } else {
            $query->where($column, $user->country_id);
        }

        return $query;
    }
}
