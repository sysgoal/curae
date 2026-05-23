<?php

namespace App\Http\Controllers;

use App\Models\Evolution;
use App\Models\Patient;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Professional;

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
$professional = Professional::where('user_id', auth()->id())->first();

        if (!$professional) {
            return back()->withErrors(['error' => 'Acesso negado: O seu utilizador não possui um perfil de profissional de saúde vinculado.']);
        }

        $validated['professional_id'] = $professional->id;

        Evolution::create($validated);

        return redirect()->route('patients.show', $request->patient_id)
            ->with('success', 'Evolução clínica gravada com sucesso!');
    }
}