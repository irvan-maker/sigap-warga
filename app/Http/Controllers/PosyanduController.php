<?php

namespace App\Http\Controllers;

use App\Enums\PosyanduLifeCycleGroup;
use App\Models\Citizen;
use App\Models\PosyanduSchedule;
use App\Models\PosyanduVisit;
use App\Services\PosyanduAccessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PosyanduController extends Controller
{
    public function index(Request $request, PosyanduAccessService $access): View
    {
        $assignments = $access->assignmentsFor($request->user());
        abort_if($assignments->isEmpty(), 403);

        $siteIds = $assignments->pluck('posyandu_site_id');
        $rtIds = $assignments->pluck('site.rt_id')->unique();

        return view('posyandu.index', [
            'assignments' => $assignments,
            'lifeCycleGroups' => PosyanduLifeCycleGroup::cases(),
            'citizens' => Citizen::query()
                ->whereIn('rt_id', $rtIds)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'rt_id', 'name', 'birth_date']),
            'schedules' => PosyanduSchedule::query()
                ->with('site:id,name')
                ->whereIn('posyandu_site_id', $siteIds)
                ->whereDate('service_date', '>=', today())
                ->orderBy('service_date')
                ->limit(10)
                ->get(),
            'visits' => PosyanduVisit::query()
                ->with(['site:id,name', 'citizen:id,name'])
                ->whereIn('posyandu_site_id', $siteIds)
                ->latest('visited_at')
                ->limit(10)
                ->get(),
            'canManageSchedules' => $assignments->contains(
                fn ($assignment): bool => $access->canManageSchedule($assignment),
            ),
        ]);
    }

    public function storeSchedule(Request $request, PosyanduAccessService $access): RedirectResponse
    {
        $validated = $request->validate([
            'posyandu_site_id' => ['required', 'integer', Rule::exists('posyandu_sites', 'id')->where('is_active', true)],
            'service_date' => ['required', 'date', 'after_or_equal:today'],
            'starts_at' => ['nullable', 'date_format:H:i'],
            'ends_at' => ['nullable', 'date_format:H:i', 'after:starts_at'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
        $assignment = $access->assignmentFor($request->user(), (int) $validated['posyandu_site_id']);
        abort_unless($assignment !== null && $access->canManageSchedule($assignment), 403);

        $schedule = PosyanduSchedule::query()->create([
            ...$validated,
            'created_by_user_id' => $request->user()->getKey(),
        ]);
        $this->audit($request, 'SCHEDULE_CREATED', $schedule);

        return redirect()->route('posyandu.index')->with('status', 'Jadwal Posyandu berhasil ditambahkan.');
    }

    public function storeVisit(Request $request, PosyanduAccessService $access): RedirectResponse
    {
        $validated = $request->validate([
            'posyandu_site_id' => ['required', 'integer', Rule::exists('posyandu_sites', 'id')->where('is_active', true)],
            'citizen_id' => ['required', 'integer', Rule::exists('citizens', 'id')->where('is_active', true)],
            'visited_at' => ['required', 'date', 'before_or_equal:now'],
            'life_cycle_group' => ['required', Rule::enum(PosyanduLifeCycleGroup::class)],
            'weight_kg' => ['nullable', 'numeric', 'min:0.1', 'max:500'],
            'height_cm' => ['nullable', 'numeric', 'min:20', 'max:250'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'follow_up' => ['nullable', 'string', 'max:2000'],
            'referral_required' => ['nullable', 'boolean'],
        ]);
        $assignment = $access->assignmentFor($request->user(), (int) $validated['posyandu_site_id']);
        abort_unless($assignment !== null, 403);

        $citizen = Citizen::query()->findOrFail($validated['citizen_id']);
        abort_unless($citizen->rt_id === $assignment->site->rt_id, 422);

        $visit = PosyanduVisit::query()->create([
            ...$validated,
            'recorded_by_user_id' => $request->user()->getKey(),
            'referral_required' => (bool) ($validated['referral_required'] ?? false),
        ]);
        $this->audit($request, 'VISIT_RECORDED', $visit);

        return redirect()->route('posyandu.index')->with('status', 'Kunjungan Posyandu berhasil dicatat.');
    }

    private function audit(Request $request, string $action, object $subject): void
    {
        DB::table('posyandu_audit_events')->insert([
            'user_id' => $request->user()->getKey(),
            'action' => $action,
            'subject_type' => class_basename($subject),
            'subject_id' => $subject->getKey(),
            'created_at' => now(),
        ]);
    }
}
