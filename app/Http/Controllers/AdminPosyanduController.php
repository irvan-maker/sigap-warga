<?php

namespace App\Http\Controllers;

use App\Enums\PosyanduStaffRole;
use App\Models\PosyanduSite;
use App\Models\PosyanduStaffAssignment;
use App\Models\Rt;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminPosyanduController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->isSystemAdmin(), 403);

        return view('admin.posyandu.index', [
            'sites' => PosyanduSite::query()
                ->with(['rt.rw', 'staffAssignments.user:id,name,role'])
                ->orderBy('name')
                ->get(),
            'rts' => Rt::query()
                ->with('rw:id,code,name,is_active')
                ->where('is_active', true)
                ->whereHas('rw', fn ($rw) => $rw->where('is_active', true))
                ->orderBy('rw_id')
                ->orderBy('code')
                ->get(),
            'users' => User::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'role']),
            'staffRoles' => PosyanduStaffRole::cases(),
        ]);
    }

    public function storeSite(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isSystemAdmin(), 403);
        $validated = $request->validate([
            'rt_id' => ['required', 'integer', Rule::exists('rts', 'id')->where('is_active', true)],
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:2000'],
        ]);
        $rt = Rt::query()->with('rw')->findOrFail($validated['rt_id']);
        abort_unless($rt->isAvailableForService(), 422, 'RT dan RW harus aktif.');
        PosyanduSite::query()->create($validated);

        return redirect()->route('admin.posyandu.index')->with('status', 'Lokasi Posyandu berhasil ditambahkan.');
    }

    public function storeStaff(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isSystemAdmin(), 403);
        $validated = $request->validate([
            'posyandu_site_id' => ['required', 'integer', Rule::exists('posyandu_sites', 'id')->where('is_active', true)],
            'user_id' => ['required', 'integer', Rule::exists('users', 'id')->where('is_active', true)],
            'role' => ['required', Rule::enum(PosyanduStaffRole::class)],
        ]);
        PosyanduStaffAssignment::query()->updateOrCreate(
            [
                'posyandu_site_id' => $validated['posyandu_site_id'],
                'user_id' => $validated['user_id'],
            ],
            ['role' => $validated['role'], 'is_active' => true],
        );

        return redirect()->route('admin.posyandu.index')->with('status', 'Penugasan Posyandu berhasil disimpan.');
    }
}
