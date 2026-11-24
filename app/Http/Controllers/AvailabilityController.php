<?php

namespace App\Http\Controllers;

use App\Http\Requests\AvailabilityRequest;
use App\Models\BlockedDate;
use App\Models\MedicalSession;
use App\Services\AvailabilityService;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AvailabilityController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:admin|doctor']);
    }

    public function index(AvailabilityRequest $request, AvailabilityService $availability)
    {
        $validated = $request->validated();
        $doctorId = $validated['doctor_id'] ?? null;

        if (! Auth::user()->isAdmin() && $doctorId && (int) $doctorId !== (int) Auth::id()) {
            abort(403);
        }

        $query = MedicalSession::query()
            ->bookable()
            ->with('specialty:id,name', 'doctor:id,name,last_name')
            ->when($doctorId, fn ($q) => $q->where('doctor_id', $doctorId))
            ->when($validated['specialty_id'] ?? null, fn ($q, $id) => $q->where('specialty_id', $id))
            ->when($validated['session_id'] ?? null, fn ($q, $id) => $q->whereKey($id))
            ->when($validated['date'] ?? null, fn ($q, $date) => $q->whereDate('date', $date))
            ->orderBy('date')
            ->orderBy('start_time');

        $sessions = $query->get();
        $slots = [];
        $patientId = $validated['patient_id'] ?? null;

        foreach ($sessions as $session) {
            $slots[$session->id] = $availability->slotsForSession($session, $patientId ? (int) $patientId : null);
        }

        $blockedDates = BlockedDate::query()
            ->when($doctorId, fn ($q) => $q->where('doctor_id', $doctorId))
            ->when(! $doctorId && ! Auth::user()->isAdmin(), fn ($q) => $q->where('doctor_id', Auth::id()))
            ->get(['doctor_id', 'date', 'reason']);

        // Partial reload target for the appointment wizard (same Inertia component).
        return Inertia::render('Appointment/create', [
            'availabilitySessions' => $sessions,
            'availabilitySlots' => $slots,
            'blockedDates' => $blockedDates,
        ]);
    }
}
