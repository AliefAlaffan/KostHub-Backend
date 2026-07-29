<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class UserManagementController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->isAdmin(), 403);

        return response()->json(
            User::with('assignedProperties')
                ->orderBy('role')->orderBy('name')
                ->get(['id', 'name', 'email', 'phone', 'role', 'status', 'created_at'])
        );
    }

    /** Admin membuat akun Staff baru */
    public function storeStaff(Request $request)
    {
        abort_unless($request->user()->isAdmin(), 403);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|max:20',
            'property_ids' => 'array',
            'property_ids.*' => 'exists:properties,id',
        ]);

        $plainPassword = Str::password(8, symbols: false);

        $staff = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => $plainPassword,
            'role' => 'staff',
            'status' => 'active',
            'created_by' => $request->user()->id,
        ]);
        $staff->assignRole('staff');

        foreach ($data['property_ids'] ?? [] as $propertyId) {
            $staff->assignedProperties()->attach($propertyId);
        }

        return response()->json([
            'user' => $staff,
            'plain_password' => $plainPassword,
        ], 201);
    }

    public function resetPassword(Request $request, User $user)
    {
        abort_unless($request->user()->isAdmin(), 403);

        $plainPassword = Str::password(8, symbols: false);
        $user->update(['password' => $plainPassword]);

        return response()->json(['message' => 'Password berhasil direset.', 'plain_password' => $plainPassword]);
    }

    public function toggleStatus(Request $request, User $user)
    {
        abort_unless($request->user()->isAdmin(), 403);

        // Admin tidak boleh menonaktifkan akunnya sendiri
        abort_if($user->id === $request->user()->id, 422, 'Tidak bisa menonaktifkan akun sendiri.');

        $user->update(['status' => $user->status === 'active' ? 'inactive' : 'active']);

        return response()->json($user);
    }
}