<?php

namespace App\Http\Controllers;

use App\Models\Anamnesis;
use App\Models\Patient;
use App\Models\Professional;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AnamnesisController extends Controller
{
    public function create(Request $request)
    {
        $patient = Patient::findOrFail($request->patient_id);
        
        return Inertia::render('Anamneses/Create', [
            'patient' => $patient
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'chief_complaint' => 'nullable|string',
            'family_history' => 'nullable|string',
            'patient_routine' => 'nullable|string',
            'symptoms_checklist' => 'nullable|array',
        ]);

        // Procura o perfil profissional do utilizador atualmente logado
        $professional = Professional::where('user_id', auth()->id())->first();

        if (!$professional) {
            return back()->withErrors(['error' => 'Acesso negado: O seu utilizador não possui um perfil de profissional de saúde vinculado.']);
        }

        // Injeta o ID correto do profissional na validação
        $validated['professional_id'] = $professional->id;

        Anamnesis::create($validated);

        return redirect()->route('patients.show', $request->patient_id)
            ->with('success', 'Anamnese guardada com sucesso no prontuário!');
    }
}