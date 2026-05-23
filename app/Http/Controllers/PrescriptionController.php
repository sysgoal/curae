<?php

namespace App\Http\Controllers;

use App\Models\Prescription;
use App\Models\Patient;
use App\Models\Professional;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class PrescriptionController extends Controller
{
    public function create(Request $request)
    {
        $patient = Patient::findOrFail($request->patient_id);
        
        return Inertia::render('Prescriptions/Create', [
            'patient' => $patient
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'notes' => 'nullable|string',
            'medications' => 'nullable|array',
        ]);

        $professional = Professional::where('user_id', auth()->id())->first();

        if (!$professional) {
            return back()->withErrors(['error' => 'Acesso negado: O seu utilizador não possui um perfil de profissional de saúde vinculado.']);
        }

        $validated['professional_id'] = $professional->id;
        
        // Gera um código verificador único para a receita
        $validated['verification_code'] = strtoupper(Str::random(8));

        Prescription::create($validated);

        return redirect()->route('patients.show', $request->patient_id)
            ->with('success', 'Receita médica gerada e validada com sucesso!');
    }
}