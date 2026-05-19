<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::whereIn('role', ['admin', 'super_admin', 'wali_kelas', 'waka_kurikulum'])->orderBy('full_name');

        // Filter by school_id for non-super admin users
        if (auth()->user() && !auth()->user()->isSuperAdmin()) {
            $query->where('school_id', auth()->user()->school_id)
                  ->whereIn('role', ['admin', 'wali_kelas', 'waka_kurikulum']);
        }

        $users = $query->get();
        return view('users.index', compact('users'));
    }

    public function create()
    {
        $gurusQuery = \App\Models\Guru::orderBy('nama');
        if (auth()->user() && !auth()->user()->isSuperAdmin()) {
            $gurusQuery->where('school_id', auth()->user()->school_id);
        }
        $gurus = $gurusQuery->get();
        return view('users.create', compact('gurus'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users',
            'email' => 'required|string|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|in:admin,super_admin,wali_kelas,waka_kurikulum',
            'guru_id' => 'nullable|exists:guru,id'
        ]);

        $data = [
            'full_name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
            'password_hash' => Hash::make($request->password),
            'role' => $request->role,
        ];

        if (auth()->user() && !auth()->user()->isSuperAdmin()) {
            $data['school_id'] = auth()->user()->school_id;
        }

        $user = User::create($data);

        if (in_array($request->role, ['wali_kelas', 'waka_kurikulum']) && $request->filled('guru_id')) {
            \App\Models\Guru::where('id', $request->guru_id)->update(['user_id' => $user->id]);
        }

        return redirect()->route('users.index')->with('success', 'User berhasil ditambahkan.');
    }

    public function edit(User $user)
    {
        $gurusQuery = \App\Models\Guru::orderBy('nama');
        if (auth()->user() && !auth()->user()->isSuperAdmin()) {
            $gurusQuery->where('school_id', auth()->user()->school_id);
        }
        $gurus = $gurusQuery->get();
        return view('users.edit', compact('user', 'gurus'));
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'username' => ['required', 'string', 'max:255', Rule::unique('users')->ignore($user->id)],
            'email' => ['required', 'string', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => 'nullable|string|min:4|confirmed',
            'role' => 'required|in:admin,super_admin,wali_kelas,waka_kurikulum',
            'guru_id' => 'nullable|exists:guru,id'
        ]);

        $user->full_name = $request->name;
        $user->username = $request->username;
        $user->email = $request->email;
        $user->role = $request->role;

        if ($request->filled('password')) {
            $user->password_hash = Hash::make($request->password);
        }

        $user->save();

        if (in_array($request->role, ['wali_kelas', 'waka_kurikulum'])) {
            // Unlink previous guru if any
            \App\Models\Guru::where('user_id', $user->id)->update(['user_id' => null]);
            if ($request->filled('guru_id')) {
                \App\Models\Guru::where('id', $request->guru_id)->update(['user_id' => $user->id]);
            }
        }

        return redirect()->route('users.index')->with('success', 'User berhasil diperbarui.');
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Tidak dapat menghapus akun sendiri.');
        }

        \App\Models\Guru::where('user_id', $user->id)->update(['user_id' => null]);

        $user->delete();
        return redirect()->route('users.index')->with('success', 'User berhasil dihapus.');
    }

    public function bulkDestroy(Request $request)
    {
        $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
        ]);

        $userIds = $request->user_ids;
        $currentUserId = auth()->id();

        // Remove current user from the list if present
        $userIds = array_filter($userIds, function ($id) use ($currentUserId) {
            return $id != $currentUserId;
        });

        if (empty($userIds)) {
            return back()->with('error', 'Tidak ada user yang dapat dihapus.');
        }

        \App\Models\Guru::whereIn('user_id', $userIds)->update(['user_id' => null]);

        $deletedCount = User::whereIn('id', $userIds)->delete();

        return redirect()->route('users.index')->with('success', "Berhasil menghapus {$deletedCount} user.");
    }
}
