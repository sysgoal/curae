<?php

namespace App\Http\Controllers;

use App\Models\Anamnesis;
use App\Models\Patient;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AnamnesisController extends Controller
{
    public function create(Request $request)
    {
        $patientId = $request->query('patient_id');
        $patient = Patient::findOrFail($patientId);

        return Inertia::render('Anamneses/Create', [
            'patient' => $patient
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'chief_complaint' => 'nullable|string',
            'patient_routine' => 'nullable|string',
            'family_history' => 'nullable|string',
            'medications_in_use' => 'nullable|string',
            'symptoms_checklist' => 'nullable|array', // Valida que recebemos o array de sintomas
        ]);

        $validated['professional_id'] = auth()->id();

        Anamnesis::create($validated);

        return redirect()->route('patients.show', $request->patient_id)
            ->with('success', 'Anamnese Integrativa registrada com sucesso!');
    }
}