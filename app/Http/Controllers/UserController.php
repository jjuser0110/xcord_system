<?php

namespace App\Http\Controllers;

use App\Models\Country;
use App\Models\Role;
use App\Models\User;
use App\Traits\CountryScopeTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Bouncer;
use Illuminate\Support\Facades\Auth;

// TODO
class UserController extends Controller
{
    use CountryScopeTrait;
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (auth()->check()) {
                if (auth()->user()->role_id !== 1) {
                    return redirect()->route('home')
                        ->with('error', 'Unauthorized action. Only superadmin can access this page.');
                }
            }
            return $next($request);
        });
    }

    public function index()
    {
        $query = User::with('country')->whereHas('role', function ($q) {
                    $q->whereIn('name', ['company_staff', 'staff_viewer']);
                });
        $this->scopeByCountry($query);
        $users = $query->latest()->get();

        return view('user.index')->with('users', $users);
    }

    public function create()
    {
        $disableCountry = false;
        $countries = $this->getScopedCountriesForForm($disableCountry);

        $roles = Bouncer::role()->whereIn('name', ['company_staff', 'staff_viewer'])->get();

        //$companyStaffRole = Bouncer::role()->where('name', 'company_staff')->first();

        return view('user.create', compact('countries', 'disableCountry', 'roles'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'       => 'nullable|string|max:255',
            'username'   => 'required|string|unique:users,username',
            'email'      => 'nullable|email|unique:users,email',
            'password'   => 'required|string|min:6',
            'role_id'    => 'required|integer|exists:roles,id',
            'country_id' => 'required|exists:countries,id',
        ]);

        //$companyStaffRole = Role::getCompanyStaffRoleId();
        $user = User::create([
            'name'       => $validated['name'] ?? null,
            'username'   => $validated['username'],
            'email'      => $validated['email'] ?? null,
            'password'   => Hash::make($validated['password']),
            'role_id'    => $validated['role_id'],
            'country_id' => $validated['country_id'],
            'is_active'  => 1,
            'is_banned'  => 0,
        ]);

        $role = Bouncer::role()->find($validated['role_id']);
        if ($role) {
            $user->assign($role);
        }

        return redirect()->route('user.index')->withSuccess('User created successfully.');
    }

    public function show(string $id)
    {
        //
    }

    public function edit(User $user)
    {
        // disabled country for editing
        $selectedCountryId = $user->country->id;
        $myrCountry = Country::find($selectedCountryId);
        $countries = $myrCountry ? collect([$myrCountry]) : collect();
        $disableCountry = true;

        //$companyStaffRole = Bouncer::role()->where('name', 'company_staff')->first();
        $roles = Bouncer::role()->whereIn('name', ['company_staff', 'staff_viewer'])->get();

        return view('user.create', compact('user', 'countries', 'disableCountry', 'roles'));
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name'       => 'nullable|string|max:255',
            'username'   => 'required|string|unique:users,username,' . $user->id,
            'email'      => 'nullable|email|unique:users,email,' . $user->id,
            'password'   => 'nullable|string|min:6',
            'role_id'    => 'required|integer|exists:roles,id',
            'country_id' => 'required|exists:countries,id',
        ]);

        //$companyStaffRole = Role::getCompanyStaffRoleId();
        $user->update([
            'name'       => $validated['name'] ?? null,
            'username'   => $validated['username'],
            'email'      => $validated['email'] ?? null,
            'role_id'    => $validated['role_id'],
            'country_id' => $validated['country_id'],
        ]);

        if ($request->filled('password')) {
            $user->update([
                'password' => Hash::make($request->password)
            ]);
        }

        $user->retract($user->getRoles());
        $role = Bouncer::role()->find($request->role_id);
        if ($role) {
            $user->assign($role);
        }

        return redirect()->route('user.index')->withSuccess('User updated successfully.');
    }

    public function destroy(User $user)
    {
        if ($user->id === Auth::id()) {
            return redirect()->route('user.index')->with('error', 'You cannot delete your own account.');
        }

        $hasCountries    = $user->createdCountries()->exists();
        $hasBanks        = $user->createdBanks()->exists();
        $hasBankSettings = $user->createdBankSettings()->exists();
        $hasPurposes     = $user->createdPurposes()->exists();
        $hasTransactions = $user->createdTransactions()->exists();

        if ($hasCountries || $hasBanks || $hasBankSettings || $hasPurposes || $hasTransactions) {
            return redirect()->route('user.index')
                ->with('error', 'Cannot delete this user account because they have linked system records (countries, banks, transactions, etc.).');
        }

        $user->delete();

        return redirect()->route('user.index')->withSuccess('User deleted successfully.');
    }
}
