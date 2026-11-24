<?php

namespace App\Http\Controllers;

use App\Http\Requests\BlockDateRequest;
use App\Models\BlockedDate;
use App\Models\MedicalSession;
use App\Models\SessionBreak;
use App\Services\MedicalSessionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class ScheduleController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(BlockedDate::class, 'blockedDate');
    }

    public function store(BlockDateRequest $request, MedicalSessionService $service)
    {
        $data = $request->validated();

        if (! Auth::user()->isAdmin() && (int) $data['doctor_id'] !== (int) Auth::id()) {
            abort(403);
        }

        $service->blockDate($data);

        return back()->with('success', 'Fecha bloqueada correctamente.');
    }

    public function destroy(BlockedDate $blockedDate)
    {
        $blockedDate->delete();

        return back()->with('success', 'Bloqueo eliminado.');
    }

    public function storeBreak(Request $request, MedicalSession $session, MedicalSessionService $service)
    {
        $this->authorize('update', $session);

        $validated = $request->validate([
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $breaks = $session->breaks->toArray();
        $breaks[] = $validated;

        try {
            $service->update($session, [
                'doctor_id' => $session->doctor_id,
                'date' => $session->date->toDateString(),
                'start_time' => $session->start_time,
                'end_time' => $session->end_time,
                'breaks' => $breaks,
            ]);
        } catch (ValidationException $exception) {
            throw $exception;
        }

        return back()->with('success', 'Descanso agregado a la sesión.');
    }

    public function destroyBreak(SessionBreak $sessionBreak)
    {
        $this->authorize('delete', $sessionBreak);

        $sessionBreak->delete();

        return back()->with('success', 'Descanso eliminado.');
    }
}
