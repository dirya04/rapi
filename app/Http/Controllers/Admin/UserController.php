<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    /**
     * Create a new user account.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'username'     => ['required', 'string', 'max:100', 'unique:users,username'],
            'fullname'     => ['required', 'string', 'max:150'],
            'phone_number' => ['required', 'string', 'max:25'],
            'role_id'      => ['required', 'integer', Rule::exists('roles', 'id')],
            'password'     => ['required', 'string', 'min:8'],
        ], [
            'username.unique' => 'Username ini sudah terdaftar. Gunakan username lain.',
            'password.min'    => 'Kata sandi minimal harus 8 karakter.',
        ]);

        $role = Role::findOrFail($data['role_id']);
        $data['password'] = Hash::make($data['password']);

        $user = User::create($data);

        return redirect()->back()
            ->with('success', "Pengguna {$user->fullname} ({$role->role_name}) berhasil ditambahkan.");
    }

    /**
     * Update user details and role.
     */
    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'username'     => ['required', 'string', 'max:100', Rule::unique('users', 'username')->ignore($user->id)],
            'fullname'     => ['required', 'string', 'max:150'],
            'phone_number' => ['required', 'string', 'max:25'],
            'role_id'      => ['required', 'integer', Rule::exists('roles', 'id')],
        ]);

        $role = Role::findOrFail($data['role_id']);

        // Proteksi jika akun PIC yang bertanggung jawab atas ruangan aktif diubah rolenya
        if ($role->role_name !== 'PIC' && $user->managedRooms()->where('is_active', true)->exists()) {
            throw ValidationException::withMessages([
                'role_id' => ['Alihkan tanggung jawab ruang aktif terlebih dahulu sebelum mengubah peran PIC ini.'],
            ]);
        }

        $user->update($data);

        return redirect()->back()
            ->with('success', "Data pengguna {$user->fullname} berhasil diperbarui.");
    }

    /**
     * Reset a user's password directly by Admin.
     */
    public function resetPassword(Request $request, User $user)
    {
        $data = $request->validate([
            'password' => ['required', 'string', 'min:8'],
        ], [
            'password.min' => 'Kata sandi baru minimal harus 8 karakter.',
        ]);

        $user->update([
            'password' => Hash::make($data['password']),
        ]);

        return redirect()->back()
            ->with('success', "Kata sandi untuk pengguna @{$user->username} berhasil direset.");
    }

    /**
     * Delete user account.
     */
    public function destroy(Request $request, User $user)
    {
        if ($user->is($request->user())) {
            return redirect()->back()
                ->with('error', 'Anda tidak dapat menghapus akun yang sedang Anda gunakan.');
        }

        $hasReferences = $user->managedRooms()->exists()
            || $user->events()->exists()
            || DB::table('events')->where('pic_id', $user->id)->exists()
            || $user->complaintsReported()->exists()
            || DB::table('events')->where('approved_by', $user->id)->exists()
            || DB::table('complaints')->where('handler_id', $user->id)->exists();

        if ($hasReferences) {
            return redirect()->back()
                ->with('error', "Pengguna @{$user->username} tidak dapat dihapus karena memiliki keterkaitan data agenda/ruangan/pengaduan.");
        }

        $username = $user->username;
        $user->delete();

        return redirect()->back()
            ->with('success', "Akun @{$username} berhasil dihapus dari sistem.");
    }
}
