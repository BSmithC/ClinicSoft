<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSpecialtyRequest;
use App\Http\Requests\UpdateSpecialtyRequest;
use App\Models\Specialty;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SpecialtyController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Specialty::class, 'specialty');
    }

    public function index(Request $request)
    {
        $search = $request->input('search');

        return Inertia::render('Specialty/index', [
            'specialties' => Specialty::query()
                ->withCount('doctors', 'sessions')
                ->when($search, fn ($query) => $query
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%"))
                ->orderBy('name')
                ->paginate(10)
                ->withQueryString(),
            'filters' => ['search' => $search],
        ]);
    }

    public function create()
    {
        return Inertia::render('Specialty/create');
    }

    public function store(StoreSpecialtyRequest $request)
    {
        Specialty::create($request->validated());

        return redirect()->route('specialties.index')->with('success', 'Especialidad creada.');
    }

    public function show(Specialty $specialty)
    {
        return Inertia::render('Specialty/show', [
            'specialty' => $specialty,
            'doctors' => $specialty->doctors()->with('user:id,name,last_name')->get(),
        ]);
    }

    public function edit(Specialty $specialty)
    {
        return Inertia::render('Specialty/edit', ['specialty' => $specialty]);
    }

    public function update(UpdateSpecialtyRequest $request, Specialty $specialty)
    {
        $specialty->update($request->validated());

        return redirect()->route('specialties.index')->with('success', 'Especialidad actualizada.');
    }

    public function destroy(Specialty $specialty)
    {
        $specialty->update(['active' => false]);

        return redirect()->route('specialties.index')->with('success', 'Especialidad desactivada.');
    }
}
