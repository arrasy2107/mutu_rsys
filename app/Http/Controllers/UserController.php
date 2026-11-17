<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $users = User::query()
            ->with('unit')
            ->when($request->search, fn ($q, $s) => $q->where(fn ($sub) => $sub->where('name', 'like', "%{$s}%")
                ->orWhere('username', 'like', "%{$s}%")
                ->orWhere('nama_lengkap', 'like', "%{$s}%")
                ->orWhere('nip', 'like', "%{$s}%")
                ->orWhere('email', 'like', "%{$s}%")
            )
            )
            ->when($request->role, fn ($q, $r) => $q->where('role', $r))
            ->when($request->unit_id, fn ($q, $u) => $q->where('unit_id', $u))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Users/Index', [
            'users' => $users,
            'units' => Unit::aktif()->get(),
            'filters' => $request->only(['search', 'role', 'unit_id']),
            'roles' => $this->getRoles(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Users/Create', [
            'units' => Unit::aktif()->get(),
            'roles' => $this->getRoles(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'username' => 'required|string|max:50|alpha_dash|unique:users,username',
            'name' => 'required|string|max:255',
            'nama_lengkap' => 'nullable|string|max:255',
            'nip' => 'nullable|string|max:50',
            'email' => 'nullable|email|unique:users,email',
            'role' => 'required|in:superadmin,komite_mutu,kepala_unit,pic_unit,direksi',
            'unit_id' => 'nullable|exists:units,id',
            'aktif' => 'boolean',
            'password' => ['required', 'confirmed', Password::min(12)->mixedCase()->numbers()],
        ]);

        $validated['password'] = Hash::make($validated['password']);
        $validated['must_change_password'] = true;
        $validated['aktif'] = $request->boolean('aktif', true);

        User::create($validated);

        return redirect()->route('users.index')
            ->with('success', "Pengguna '{$validated['username']}' berhasil ditambahkan. Sampaikan password sementara secara aman; pengguna wajib menggantinya saat login pertama.");
    }

    public function edit(User $user): Response
    {
        return Inertia::render('Users/Edit', [
            'user' => $user->load('unit'),
            'units' => Unit::aktif()->get(),
            'roles' => $this->getRoles(),
        ]);
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'username' => 'required|string|max:50|alpha_dash|unique:users,username,'.$user->id,
            'name' => 'required|string|max:255',
            'nama_lengkap' => 'nullable|string|max:255',
            'nip' => 'nullable|string|max:50',
            'email' => 'nullable|email|unique:users,email,'.$user->id,
            'password' => ['nullable', 'confirmed', Password::min(12)->mixedCase()->numbers()],
            'role' => 'required|in:superadmin,komite_mutu,kepala_unit,pic_unit,direksi',
            'unit_id' => 'nullable|exists:units,id',
            'aktif' => 'boolean',
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
            $validated['must_change_password'] = true;
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        return redirect()->route('users.index')
            ->with('success', "Data profil pengguna '{$user->username}' berhasil diperbarui.");
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Tidak dapat menghapus akun sendiri.');
        }

        $user->delete();

        return redirect()->route('users.index')
            ->with('success', 'Pengguna berhasil dihapus.');
    }

    private function getRoles(): array
    {
        return [
            ['value' => 'superadmin',   'label' => 'Super Admin'],
            ['value' => 'komite_mutu',  'label' => 'Komite Mutu'],
            ['value' => 'kepala_unit',  'label' => 'Kepala Unit'],
            ['value' => 'pic_unit',     'label' => 'PIC Unit'],
            ['value' => 'direksi',      'label' => 'Direksi'],
        ];
    }
}
