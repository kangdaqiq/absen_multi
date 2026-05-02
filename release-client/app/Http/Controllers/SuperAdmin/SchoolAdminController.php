<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class SchoolAdminController extends Controller
{
    /**
     * Display a listing of admins for a specific school
     */
    public function index(School $school)
    {
        $admins = $school->users()
            ->where('role', 'admin')
            ->latest()
            ->paginate(15);

        return view('super-admin.admins.index', compact('school', 'admins'));
    }

    /**
     * Show the form for creating a new admin
     */
    public function create(School $school)
    {
        return view('super-admin.admins.create', compact('school'));
    }

    /**
     * Store a newly created admin
     */
    public function store(Request $request, School $school)
    {
        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $admin = User::create([
            'full_name' => $validated['full_name'],
            'username' => $validated['username'],
            'email' => $validated['email'],
            'password_hash' => Hash::make($validated['password']),
            'role' => 'admin',
            'school_id' => $school->id,
        ]);

        return redirect()
            ->route('super-admin.schools.admins.index', $school)
            ->with('success', 'Admin berhasil ditambahkan ke sekolah ' . $school->name);
    }

    /**
     * Display the specified admin
     */
    public function show(School $school, User $admin)
    {
        // Ensure admin belongs to this school
        if ($admin->school_id !== $school->id) {
            abort(404);
        }

        return view('super-admin.admins.show', compact('school', 'admin'));
    }

    /**
     * Show the form for editing the specified admin
     */
    public function edit(School $school, User $admin)
    {
        // Ensure admin belongs to this school
        if ($admin->school_id !== $school->id) {
            abort(404);
        }

        return view('super-admin.admins.edit', compact('school', 'admin'));
    }

    /**
     * Update the specified admin
     */
    public function update(Request $request, School $school, User $admin)
    {
        // Ensure admin belongs to this school
        if ($admin->school_id !== $school->id) {
            abort(404);
        }

        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'username' => ['required', 'string', 'max:255', Rule::unique('users')->ignore($admin->id)],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($admin->id)],
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        $admin->full_name = $validated['full_name'];
        $admin->username = $validated['username'];
        $admin->email = $validated['email'];

        if ($request->filled('password')) {
            $admin->password_hash = Hash::make($validated['password']);
        }

        $admin->save();

        return redirect()
            ->route('super-admin.schools.admins.index', $school)
            ->with('success', 'Admin berhasil diperbarui.');
    }

    /**
     * Remove the specified admin
     */
    public function destroy(School $school, User $admin)
    {
        // Ensure admin belongs to this school
        if ($admin->school_id !== $school->id) {
            abort(404);
        }

        // Prevent deleting yourself
        if ($admin->id === auth()->id()) {
            return back()->with('error', 'Tidak dapat menghapus akun sendiri.');
        }

        $admin->delete();

        return redirect()
            ->route('super-admin.schools.admins.index', $school)
            ->with('success', 'Admin berhasil dihapus.');
    }
}
