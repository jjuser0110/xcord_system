<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Silber\Bouncer\Database\HasRolesAndAbilities;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable,HasRolesAndAbilities,SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'username',
        'password',
        'role_id',
        'date_of_birth',
        'contact_no',
        'main_wallet',
        'last_login',
        'remarks',
        'is_active',
        'is_banned',
        'country_id'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function role()
    {
        return $this->belongsTo('App\Models\Role');
    }

    /**
     * Relationship: A user belongs to a country.
     */
    public function country()
    {
        return $this->belongsTo(Country::class, 'country_id');
    }

    /**
     * Relationship: Countries created by this user.
     */
    public function createdCountries()
    {
        return $this->hasMany(Country::class, 'created_by_id');
    }

    public function createdBanks()
    {
        return $this->hasMany(Bank::class, 'created_by_id');
    }

    public function createdBankSettings()
    {
        return $this->hasMany(BankSetting::class, 'created_by_id');
    }

    public function createdPurposes()
    {
        return $this->hasMany(Purpose::class, 'created_by_id');
    }

    public function createdTransactions()
    {
        return $this->hasMany(Transaction::class, 'created_by_id');
    }

}
