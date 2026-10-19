<?php

namespace App\Http\Controllers\Api;

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
    private function safe(User $user): array
    {
        return $user->only(['id', 'username', 'fullname', 'phone_number', 'role_id'])
            + ['role' => $user->role?->role_name];
    }

    public function index()
    {
        $users = User::with('role')->orderBy('fullname')->paginate(20)
            ->through(fn (User $user) => $this->safe($user));

        return response()->json(['data' => $users]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'username' => ['required', 'string', 'max:100', 'unique:users,username'],
            'fullname' => ['required', 'string', 'max:150'],
            'phone_number' => ['required', 'string', 'max:25'],
            'password' => ['required', 'string', 'min:8'],
            'role_id' => ['required', 'integer', Rule::exists('roles', 'id')],
        ]);

        $role = Role::findOrFail($data['role_id']);
        abort_unless(in_array($role->role_name, ['Admin', 'PIC', 'Guru', 'Organisasi'], true), 422,
            'Role tidak termasuk role yang didukung.');
        $data['password'] = Hash::make($data['password']);

        return response()->json(['data' => $this->safe(User::create($data))], 201);
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'username' => ['sometimes', 'required', 'string', 'max:100', Rule::unique('users', 'username')->ignore($user->id)],
            'fullname' => ['sometimes', 'required', 'string', 'max:150'],
            'phone_number' => ['sometimes', 'required', 'string', 'max:25'],
            'password' => ['sometimes', 'required', 'string', 'min:8'],
            'role_id' => ['sometimes', 'required', 'integer', Rule::exists('roles', 'id')],
        ]);

        if (isset($data['role_id'])) {
            $role = Role::findOrFail($data['role_id']);
            abort_unless(in_array($role->role_name, ['Admin', 'PIC', 'Guru', 'Organisasi'], true), 422,
                'Role tidak termasuk role yang didukung.');

            if ($role->role_name !== 'PIC' && $user->managedRooms()->where('is_active', true)->exists()) {
                throw ValidationException::withMessages([
                    'role_id' => ['Alihkan tanggung jawab semua ruang aktif sebelum mengubah role PIC ini.'],
                ]);
            }
        }
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }
        $user->update($data);

        return response()->json(['data' => $this->safe($user->fresh('role'))]);
    }

    public function destroy(User $user)
    {
        abort_if($user->is(auth()->user()), 422, 'Akun yang sedang digunakan tidak dapat dihapus.');

        $hasReferences = $user->managedRooms()->exists()
            || $user->events()->exists()
            || DB::table('events')->where('pic_id', $user->id)->exists()
            || $user->complaintsReported()->exists()
            || DB::table('events')->where('approved_by', $user->id)->exists()
            || DB::table('complaints')->where('handler_id', $user->id)->exists();
        abort_if($hasReferences, 422,
            'Akun masih terhubung ke data. Nonaktifkan atau alihkan referensinya, jangan hapus.');

        $user->delete();

        return response()->json(['message' => 'Akun dihapus.']);
    }
}
