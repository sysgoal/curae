<?php

namespace App\Http\Controllers;

use App\Models\Evolution;
use App\Models\Patient;
use App\Models\Professional;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EvolutionController extends Controller
{
    public function create(Request $request)
    {
        $patient = Patient::findOrFail($request->patient_id);
        
        return Inertia::render('Evolutions/Create', [
            'patient' => $patient
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'clinical_notes' => 'required|string',
            'weight' => 'nullable|numeric|max:500',
            'height' => 'nullable|numeric|max:3',
            'bmi' => 'nullable|numeric',
            'systolic_bp' => 'nullable|integer|max:300',
            'diastolic_bp' => 'nullable|integer|max:200',
            'heart_rate' => 'nullable|integer|max:300',
            'respiratory_rate' => 'nullable|integer|max:100',
            'temperature' => 'nullable|numeric|max:50',
            'oxygen_saturation' => 'nullable|integer|max:100',
            'blood_glucose' => 'nullable|numeric|max:1000',
        ], [
            'height.max' => 'A altura deve ser informada em metros (ex: 1.75).',
        ]);

        // Procura o perfil de profissional de saúde do utilizador que está logado
        $professional = Professional::where('user_id', auth()->id())->first();

        // TRAVA DE SEGURANÇA CLÍNICA: Impede que utilizadores sem perfil de saúde gravem evoluções
        if (!$professional) {
            return redirect()->back()->withErrors([
                'clinical_notes' => 'Ação bloqueada: O seu utilizador de sistema não possui um registo de Profissional Clínico. Apenas Médicos, Enfermeiros e equipa clínica podem assinar evoluções.'
            ])->withInput();
        }

        // Associa a evolução ao profissional encontrado
        $validated['professional_id'] = $professional->id;

        Evolution::create($validated);

        return redirect()->route('patients.show', $request->patient_id)
            ->with('success', 'Evolução clínica e métricas salvas com sucesso no prontuário!');
    }
}