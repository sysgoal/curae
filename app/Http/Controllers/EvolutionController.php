<?php

namespace App\Http\Controllers;

use App\Models\Evolution;
use App\Models\Patient;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EvolutionController extends Controller
{
    public function create(Request $request)
    {
        $patient = Patient::findOrFail($request->query('patient_id'));

        return Inertia::render('Evolutions/Create', [
            'patient' => $patient
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'weight' => 'nullable|numeric',
            'height' => 'nullable|numeric',
            'bmi' => 'nullable|numeric',
            'systolic_bp' => 'nullable|integer',
            'diastolic_bp' => 'nullable|integer',
            'heart_rate' => 'nullable|integer',
            'respiratory_rate' => 'nullable|integer',
            'temperature' => 'nullable|numeric',
            'oxygen_saturation' => 'nullable|integer',
            'blood_glucose' => 'nullable|integer',
            'clinical_notes' => 'required|string',
        ]);

        // Associa ao médico logado (ou usa o ID 2 provisoriamente)
        $validated['professional_id'] = auth()->id() ?? 2;

        Evolution::create($validated);

        return redirect()->route('patients.show', $request->patient_id)
            ->with('success', 'Evolução e Sinais Vitais registados com sucesso!');
    }
}