<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Enums\VillagePosition;
use App\Http\Requests\IndexAdminUsersRequest;
use App\Http\Requests\ResetAdminUserPasswordRequest;
use App\Http\Requests\StoreAdminUserRequest;
use App\Http\Requests\ToggleAdminUserStatusRequest;
use App\Http\Requests\UpdateAdminUserRequest;
use App\Models\Rt;
use App\Models\Rw;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AdminUserController extends Controller
{
    public function index(IndexAdminUsersRequest $request): View
    {
        $filters = $request->validated();

        $users = User::query()
            ->with([
                'rw:id,code,name',
                'rt:id,rw_id,code,name',
            ])
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when(
                isset($filters['role']),
                fn (Builder $query): Builder => $query->where(
                    'role',
                    UserRole::from($filters['role']),
                ),
            )
            ->when(
                isset($filters['status']),
                fn (Builder $query): Builder => $query->where(
                    'is_active',
                    $filters['status'] === 'active',
                ),
            )
            ->orderBy('name')
            ->orderBy('id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.users.index', ['users' => $users]);
    }

    public function create(): View
    {
        Gate::authorize('create', User::class);

        return view('admin.users.create', $this->regionOptions());
    }

    public function store(StoreAdminUserRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $user = DB::transaction(fn (): User => User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => UserRole::from($data['role']),
            'position' => $data['position'] ?? null,
            'is_active' => true,
            'rw_id' => $data['rw_id'] ?? null,
            'rt_id' => $data['rt_id'] ?? null,
        ]), 3);

        return redirect()
            ->route('admin.users.edit', $user)
            ->with('status', 'Pengguna berhasil dibuat.');
    }

    public function edit(User $user): View
    {
        Gate::authorize('update', $user);

        return view('admin.users.edit', [
            'managedUser' => $user->load(['rw:id,code,name', 'rt:id,rw_id,code,name']),
            ...$this->regionOptions(),
        ]);
    }

    public function update(UpdateAdminUserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($data, $user): void {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->getKey());
            if ($lockedUser->isSystemAdmin()
                && ($data['position'] ?? null) !== VillagePosition::SYSTEM_ADMIN->value
                && User::query()->where('position', VillagePosition::SYSTEM_ADMIN)->where('is_active', true)->count() <= 1) {
                throw ValidationException::withMessages(['position' => 'Administrator Sistem aktif terakhir tidak dapat diubah.']);
            }
            $lockedUser->update([
                'name' => $data['name'],
                'email' => $data['email'],
                'role' => UserRole::from($data['role']),
                'position' => $data['position'] ?? null,
                'rw_id' => $data['rw_id'] ?? null,
                'rt_id' => $data['rt_id'] ?? null,
            ]);
        }, 3);

        return redirect()
            ->route('admin.users.edit', $user)
            ->with('status', 'Data pengguna berhasil diperbarui.');
    }

    public function toggleActive(
        ToggleAdminUserStatusRequest $request,
        User $user,
    ): RedirectResponse {
        DB::transaction(function () use ($user): void {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->getKey());
            if ($lockedUser->isSystemAdmin()
                && User::query()->where('position', VillagePosition::SYSTEM_ADMIN)->where('is_active', true)->count() <= 1) {
                throw ValidationException::withMessages(['status' => 'Administrator Sistem aktif terakhir tidak dapat dinonaktifkan.']);
            }
            $lockedUser->update(['is_active' => ! $lockedUser->is_active]);

            if (! $lockedUser->is_active) {
                $this->deleteUserSessions($lockedUser);
            }
        }, 3);

        return redirect()
            ->route('admin.users.edit', $user)
            ->with('status', 'Status pengguna berhasil diperbarui.');
    }

    public function resetPassword(
        ResetAdminUserPasswordRequest $request,
        User $user,
    ): RedirectResponse {
        $data = $request->validated();

        DB::transaction(function () use ($data, $user): void {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->getKey());
            $lockedUser->forceFill([
                'password' => Hash::make($data['password']),
                'remember_token' => Str::random(60),
            ])->save();
            DB::table('password_reset_tokens')->where('email', $lockedUser->email)->delete();
            $this->deleteUserSessions($lockedUser);
        }, 3);

        return redirect()
            ->route('admin.users.edit', $user)
            ->with('status', 'Password pengguna berhasil direset.');
    }

    /** @return array{rws: Collection, rts: Collection} */
    private function regionOptions(): array
    {
        return [
            'rws' => Rw::query()->orderBy('code')->get(['id', 'code', 'name']),
            'rts' => Rt::query()
                ->with('rw:id,code')
                ->orderBy('rw_id')
                ->orderBy('code')
                ->get(['id', 'rw_id', 'code', 'name']),
        ];
    }

    private function deleteUserSessions(User $user): void
    {
        DB::table((string) config('session.table', 'sessions'))
            ->where('user_id', $user->getKey())
            ->delete();
    }
}
