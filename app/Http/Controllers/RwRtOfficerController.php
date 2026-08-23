<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Rt;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RwRtOfficerController extends Controller
{
    public function index(Request $request, Rt $rt): View
    {
        $this->authorizeRt($request, $rt);

        $officers = User::query()
            ->where('role', UserRole::RT)
            ->where('rw_id', $rt->rw_id)
            ->where('rt_id', $rt->id)
            ->orderBy('name')
            ->orderBy('id')
            ->get();

        return view('rw.rts.officers.index', [
            'managedRt' => $rt,
            'officers' => $officers,
        ]);
    }

    public function store(Request $request, Rt $rt): RedirectResponse
    {
        $this->authorizeRt($request, $rt);

        if (! $rt->is_active) {
            throw ValidationException::withMessages([
                'status' => 'Petugas tidak dapat ditambahkan ke RT yang nonaktif.',
            ]);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'confirmed', Password::min(12)],
        ]);

        DB::transaction(function () use ($data, $request, $rt): void {
            User::query()->create([
                'name' => trim($data['name']),
                'email' => strtolower(trim($data['email'])),
                'password' => Hash::make($data['password']),
                'role' => UserRole::RT,
                'position' => null,
                'is_active' => true,
                'rw_id' => $request->user()->rw_id,
                'rt_id' => $rt->id,
            ]);
        }, 3);

        return redirect()
            ->route('rw.rts.officers.index', $rt)
            ->with('status', 'Petugas RT berhasil ditambahkan.');
    }

    public function update(Request $request, Rt $rt, User $officer): RedirectResponse
    {
        $this->authorizeRt($request, $rt);
        $this->authorizeOfficer($rt, $officer);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($officer->id),
            ],
        ]);

        DB::transaction(function () use ($data, $rt, $officer): void {
            $locked = User::query()->lockForUpdate()->findOrFail($officer->id);
            $this->authorizeOfficer($rt, $locked);

            $locked->update([
                'name' => trim($data['name']),
                'email' => strtolower(trim($data['email'])),
            ]);
        }, 3);

        return redirect()
            ->route('rw.rts.officers.index', $rt)
            ->with('status', 'Data petugas RT berhasil diperbarui.');
    }

    public function toggleActive(Request $request, Rt $rt, User $officer): RedirectResponse
    {
        $this->authorizeRt($request, $rt);
        $this->authorizeOfficer($rt, $officer);

        DB::transaction(function () use ($rt, $officer): void {
            $locked = User::query()->lockForUpdate()->findOrFail($officer->id);
            $this->authorizeOfficer($rt, $locked);

            if (! $locked->is_active && ! $rt->is_active) {
                throw ValidationException::withMessages([
                    'status' => 'Petugas tidak dapat diaktifkan selama RT masih nonaktif.',
                ]);
            }

            $locked->update(['is_active' => ! $locked->is_active]);

            if (! $locked->is_active) {
                $this->deleteUserSessions($locked);
            }
        }, 3);

        return redirect()
            ->route('rw.rts.officers.index', $rt)
            ->with('status', 'Status petugas RT berhasil diperbarui.');
    }

    public function resetPassword(Request $request, Rt $rt, User $officer): RedirectResponse
    {
        $this->authorizeRt($request, $rt);
        $this->authorizeOfficer($rt, $officer);

        $data = $request->validate([
            'password' => ['required', 'confirmed', Password::min(12)],
        ]);

        DB::transaction(function () use ($data, $rt, $officer): void {
            $locked = User::query()->lockForUpdate()->findOrFail($officer->id);
            $this->authorizeOfficer($rt, $locked);

            $locked->forceFill([
                'password' => Hash::make($data['password']),
                'remember_token' => Str::random(60),
            ])->save();

            DB::table('password_reset_tokens')
                ->where('email', $locked->email)
                ->delete();

            $this->deleteUserSessions($locked);
        }, 3);

        return redirect()
            ->route('rw.rts.officers.index', $rt)
            ->with('status', 'Password petugas RT berhasil direset.');
    }

    private function authorizeRt(Request $request, Rt $rt): void
    {
        Gate::authorize('update', $rt);

        abort_unless(
            $request->user()?->role === UserRole::RW
                && $request->user()?->is_active
                && $request->user()?->rw_id === $rt->rw_id,
            403,
        );
    }

    private function authorizeOfficer(Rt $rt, User $officer): void
    {
        abort_unless(
            $officer->role === UserRole::RT
                && $officer->rw_id === $rt->rw_id
                && $officer->rt_id === $rt->id,
            404,
        );
    }

    private function deleteUserSessions(User $user): void
    {
        DB::table((string) config('session.table', 'sessions'))
            ->where('user_id', $user->getKey())
            ->delete();
    }
}
