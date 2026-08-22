<?php

namespace App\Http\Controllers;

use App\Models\Country;
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
    public function index()
    {
        $query = User::with('country')->whereHas('role', function ($q) {
                    $q->where('name', 'company_staff');
                });
        $this->scopeByCountry($query);
        $users = $query->latest()->get();

        return view('user.index')->with('users', $users);
    }

    public function create()
    {
        $query = Country::where('is_active', 1);
        $this->scopeByCountry($query, 'id');
        $countries = $query->get();
        $companyStaffRole = Bouncer::role()->where('name', 'company_staff')->first();

        return view('user.create', compact('countries', 'companyStaffRole'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'       => 'required|string|max:255',
            'username'   => 'required|string|unique:users,username',
            'email'      => 'nullable|email|unique:users,email',
            'password'   => 'required|string|min:6',
            'role_id'    => 'required|integer',
            'country_id' => 'required|exists:countries,id',
        ]);

        $user = User::create([
            'name'       => $validated['name'],
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
        $query = Country::where('is_active', 1);
        $this->scopeByCountry($query, 'id');
        $countries = $query->get();
        $companyStaffRole = Bouncer::role()->where('name', 'company_staff')->first();
        $disableCountry = true;

        return view('user.create', compact('user', 'countries', 'companyStaffRole', 'disableCountry'));
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name'       => 'required|string|max:255',
            'username'   => 'required|string|unique:users,username,' . $user->id,
            'email'      => 'nullable|email|unique:users,email,' . $user->id,
            'password'   => 'nullable|string|min:6',
            'role_id'    => 'required|integer',
            'country_id' => 'required|exists:countries,id',
        ]);

        $user->update([
            'name'       => $validated['name'],
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

        $user->delete();

        return redirect()->route('user.index')->withSuccess('User deleted successfully.');
    }
}
