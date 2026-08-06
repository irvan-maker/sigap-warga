<?php

namespace App\Http\Controllers;

use App\Enums\LetterStatus;
use App\Enums\LetterType;
use App\Enums\UserRole;
use App\Models\Citizen;
use App\Models\Rt;
use App\Models\VillageLetter;
use App\Services\VillageLetterWorkflow;
use DomainException;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class VillageLetterController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', VillageLetter::class);
        $query = VillageLetter::query()->with(['citizen:id,name,nik', 'rt:id,rw_id,code,name', 'rt.rw:id,code,name']);
        $this->scope($query, $request);
        $search = trim((string) $request->query('search'));
        $query->when($search, fn (Builder $q) => $q->where(fn (Builder $q) => $q->where('letter_number', 'like', "%{$search}%")->orWhereHas('citizen', fn (Builder $c) => $c->where('name', 'like', "%{$search}%")->orWhere('nik', 'like', "%{$search}%"))))->when(LetterType::tryFrom((string) $request->query('type')), fn (Builder $q, LetterType $v) => $q->where('letter_type', $v))->when(LetterStatus::tryFrom((string) $request->query('status')), fn (Builder $q, LetterStatus $v) => $q->where('status', $v))->when($request->filled('rt_id'), fn (Builder $q) => $q->where('rt_id', (int) $request->query('rt_id')))->when($request->filled('rw_id'), fn (Builder $q) => $q->whereHas('rt', fn (Builder $rt) => $rt->where('rw_id', (int) $request->query('rw_id'))))->when($request->filled('date_from'), fn (Builder $q) => $q->whereDate('created_at', '>=', $request->query('date_from')))->when($request->filled('date_to'), fn (Builder $q) => $q->whereDate('created_at', '<=', $request->query('date_to')));

        return view('letters.index', ['letters' => $query->latest()->paginate(15)->withQueryString(), 'rts' => $this->rts($request), 'routePrefix' => $this->prefix($request)]);
    }

    public function create(Request $request): View
    {
        Gate::authorize('create', VillageLetter::class);

        return view('letters.form', ['letter' => new VillageLetter, 'citizens' => Citizen::query()->where('rt_id', $request->user()->rt_id)->where('is_active', true)->orderBy('name')->get(), 'routePrefix' => 'rt']);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', VillageLetter::class);
        $data = $this->validateData($request);
        $citizen = Citizen::query()->whereKey($data['citizen_id'])->where('rt_id', $request->user()->rt_id)->firstOrFail();
        $letter = DB::transaction(function () use ($data, $request, $citizen) {
            $letter = VillageLetter::query()->create([...$data, 'rt_id' => $citizen->rt_id, 'submitted_by' => $request->user()->id, 'status' => LetterStatus::DRAFT]);
            $letter->histories()->create(['user_id' => $request->user()->id, 'old_status' => null, 'new_status' => LetterStatus::DRAFT]);

            return $letter;
        }, 3);

        return redirect()->route('rt.letters.show', $letter)->with('status', 'Draft pengajuan surat berhasil dibuat.');
    }

    public function show(Request $request, VillageLetter $letter): View
    {
        Gate::authorize('view', $letter);
        $letter->load(['citizen.rt.rw', 'citizen.familyCard.headCitizen', 'rt.rw', 'submitter', 'reviewer', 'approver', 'histories' => fn ($q) => $q->with('user:id,name')->oldest()]);

        return view('letters.show', ['letter' => $letter, 'routePrefix' => $this->prefix($request)]);
    }

    public function edit(Request $request, VillageLetter $letter): View
    {
        Gate::authorize('update', $letter);

        return view('letters.form', ['letter' => $letter, 'citizens' => Citizen::query()->where('rt_id', $request->user()->rt_id)->where('is_active', true)->orderBy('name')->get(), 'routePrefix' => 'rt']);
    }

    public function update(Request $request, VillageLetter $letter): RedirectResponse
    {
        Gate::authorize('update', $letter);
        $data = $this->validateData($request);
        abort_unless(Citizen::query()->whereKey($data['citizen_id'])->where('rt_id', $request->user()->rt_id)->exists(), 403);
        $letter->update($data);

        return redirect()->route('rt.letters.show', $letter)->with('status', 'Draft surat berhasil diperbarui.');
    }

    public function submit(Request $request, VillageLetter $letter, VillageLetterWorkflow $flow): RedirectResponse
    {
        Gate::authorize('submit', $letter);

        return $this->move($flow, $letter, LetterStatus::SUBMITTED, $request);
    }

    public function review(Request $request, VillageLetter $letter, VillageLetterWorkflow $flow): RedirectResponse
    {
        Gate::authorize('review', $letter);

        return $this->move($flow, $letter, LetterStatus::RW_REVIEWED, $request);
    }

    public function approve(Request $request, VillageLetter $letter, VillageLetterWorkflow $flow): RedirectResponse
    {
        Gate::authorize('approve', $letter);

        return $this->move($flow, $letter, LetterStatus::APPROVED, $request);
    }

    public function reject(Request $request, VillageLetter $letter, VillageLetterWorkflow $flow): RedirectResponse
    {
        Gate::authorize('reject', $letter);
        $request->validate(['note' => ['required', 'string', 'max:2000']]);

        return $this->move($flow, $letter, LetterStatus::REJECTED, $request);
    }

    public function issue(Request $request, VillageLetter $letter, VillageLetterWorkflow $flow): RedirectResponse
    {
        Gate::authorize('issue', $letter);

        return $this->move($flow, $letter, LetterStatus::ISSUED, $request);
    }

    public function pdf(Request $request, VillageLetter $letter): Response
    {
        Gate::authorize('downloadPdf', $letter);
        $letter->load(['citizen.rt.rw', 'citizen.familyCard.headCitizen', 'approver']);
        $options = new Options;
        $options->set('isRemoteEnabled', false);
        $pdf = new Dompdf($options);
        $pdf->loadHtml(view('letters.pdf', ['letter' => $letter])->render());
        $pdf->setPaper('A4');
        $pdf->render();

        return response($pdf->output(), 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'attachment; filename="surat-'.$letter->id.'.pdf"']);
    }

    private function move(VillageLetterWorkflow $flow, VillageLetter $letter, LetterStatus $to, Request $request): RedirectResponse
    {
        try {
            $flow->transition($letter, $to, $request->user(), $request->input('note'));
        } catch (DomainException $e) {
            throw ValidationException::withMessages(['status' => $e->getMessage()]);
        }

        return back()->with('status', 'Status surat berhasil diperbarui.');
    }

    private function validateData(Request $request): array
    {
        return $request->validate(['citizen_id' => ['required', 'integer', Rule::exists('citizens', 'id')], 'letter_type' => ['required', Rule::enum(LetterType::class)], 'purpose' => ['required', 'string', 'max:2000'], 'notes' => ['nullable', 'string', 'max:2000']]);
    }

    private function scope(Builder $query, Request $request): void
    {
        match ($request->user()->role) {
            UserRole::RT => $query->where('rt_id', $request->user()->rt_id),UserRole::RW => $query->whereHas('rt', fn (Builder $q) => $q->where('rw_id', $request->user()->rw_id)),default => null
        };
    }

    private function rts(Request $request)
    {
        return Rt::query()->with('rw:id,code')->when($request->user()->role === UserRole::RW, fn (Builder $q) => $q->where('rw_id', $request->user()->rw_id))->when($request->user()->role === UserRole::RT, fn (Builder $q) => $q->whereKey($request->user()->rt_id))->orderBy('rw_id')->orderBy('code')->get();
    }

    private function prefix(Request $request): string
    {
        return $request->routeIs('rt.*') ? 'rt' : ($request->routeIs('rw.*') ? 'rw' : 'kelurahan');
    }
}
