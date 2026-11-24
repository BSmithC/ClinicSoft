<?php

namespace App\Http\Controllers;

use App\Enums\MedicalSessionStatus;
use App\Http\Requests\CancelSessionRequest;
use App\Http\Requests\StoreMedicalSessionRequest;
use App\Http\Requests\UpdateMedicalSessionRequest;
use App\Models\Doctor;
use App\Models\MedicalSession;
use App\Models\Specialty;
use App\Models\User;
use App\Services\AvailabilityService;
use App\Services\MedicalSessionService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class MedicalSessionController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(MedicalSession::class, 'session');
    }

    public function index(Request $request)
    {
        $query = MedicalSession::query()
            ->with('doctor:id,name,last_name', 'specialty:id,name')
            ->withCount('appointments');

        if (! Auth::user()->isAdmin()) {
            $query->where('doctor_id', Auth::id());
        }

        if ($request->filled('date')) {
            $query->whereDate('date', $request->input('date'));
        }

        if ($request->filled('specialty_id')) {
            $query->where('specialty_id', $request->input('specialty_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        return Inertia::render('Session/index', [
            'sessions' => $query->orderByDesc('date')->orderBy('start_time')
                ->paginate(10)->withQueryString(),
            'filters' => $request->only(['date', 'specialty_id', 'status']),
            'specialties' => Specialty::query()->active()->get(['id', 'name']),
            'statuses' => MedicalSessionStatus::options(),
        ]);
    }

    public function create()
    {
        return Inertia::render('Session/create', [
            'doctors' => $this->doctors(),
            'specialties' => Specialty::query()->active()->get(['id', 'name']),
            'statuses' => MedicalSessionStatus::options(),
            'blockedDates' => \App\Models\BlockedDate::query()
                ->when(! Auth::user()->isAdmin(), fn ($query) => $query->where('doctor_id', Auth::id()))
                ->get(['id', 'doctor_id', 'date', 'reason']),
        ]);
    }

    public function store(StoreMedicalSessionRequest $request, MedicalSessionService $service)
    {
        $data = $request->validated();

        if (! Auth::user()->isAdmin()) {
            $data['doctor_id'] = Auth::id();
        }

        $service->create($data);

        return redirect()->route('sessions.index')->with('success', 'Sesión médica programada.');
    }

    public function show(MedicalSession $session, AvailabilityService $availability)
    {
        $session->load('doctor:id,name,last_name', 'specialty:id,name', 'breaks', 'appointments.patient:id,name,last_name');

        return Inertia::render('Session/show', [
            'session' => $session,
            'slots' => $availability->slotsForSession($session),
            'statuses' => MedicalSessionStatus::options(),
        ]);
    }

    public function edit(MedicalSession $session)
    {
        return Inertia::render('Session/edit', [
            'session' => $session->load('breaks'),
            'doctors' => $this->doctors(),
            'specialties' => Specialty::query()->active()->get(['id', 'name']),
            'statuses' => MedicalSessionStatus::options(),
        ]);
    }

    public function update(
        UpdateMedicalSessionRequest $request,
        MedicalSession $session,
        MedicalSessionService $service
    ) {
        $data = $request->validated();

        if (! Auth::user()->isAdmin()) {
            $data['doctor_id'] = Auth::id();
        }

        if (($data['status'] ?? null) === MedicalSessionStatus::Cancelled->value) {
            $service->cancel($session, $data['reason'] ?? null);

            return redirect()->route('sessions.index')
                ->with('success', 'Sesión cancelada; las citas afectadas quedaron registradas como canceladas.');
        }

        $service->update($session, $data);

        return redirect()->route('sessions.index')->with('success', 'Sesión médica actualizada.');
    }

    public function cancel(
        CancelSessionRequest $request,
        MedicalSession $session,
        MedicalSessionService $service
    ) {
        $this->authorize('update', $session);

        $service->cancel($session, $request->validated('reason'));

        return redirect()->route('sessions.index')
            ->with('success', 'Sesión cancelada; las citas afectadas quedaron registradas como canceladas.');
    }

    public function destroy(MedicalSession $session, MedicalSessionService $service)
    {
        // No physical deletion: sessions with clinical history are cancelled instead.
        $service->cancel($session, 'Sesión archivada desde el sistema.');

        return redirect()->route('sessions.index')->with('success', 'Sesión archivada correctamente.');
    }

    private function doctors()
    {
        $query = Doctor::query()->with('user:id,name,last_name');

        if (! Auth::user()->isAdmin()) {
            $query->where('user_id', Auth::id());
        }

        return $query->get();
    }
}
