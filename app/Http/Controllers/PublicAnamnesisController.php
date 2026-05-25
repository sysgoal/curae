<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\Anamnesis;
use App\Models\Professional;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;

class PublicAnamnesisController extends Controller
{
    public function generateLink(Patient $patient)
    {
        $professional = Professional::where('user_id', auth()->id())->first();
        
        if (!$professional) {
            return response()->json(['error' => 'Apenas profissionais podem gerar este link.'], 403);
        }

        $url = URL::temporarySignedRoute(
            'public.anamnesis.create', 
            now()->addHours(48), 
            ['patient' => $patient->id, 'professional' => $professional->id]
        );

        return response()->json(['url' => $url]);
    }

    public function create(Request $request, Patient $patient, Professional $professional)
    {
        if (!$request->hasValidSignature()) {
            abort(403, 'Este link é inválido ou já expirou. Por favor, solicite um novo link à clínica.');
        }

        return Inertia::render('Anamneses/PublicCreate', [
            'patient' => $patient,
            'submitUrl' => $request->fullUrl()
        ]);
    }

    public function store(Request $request, Patient $patient, Professional $professional)
    {
        if (!$request->hasValidSignature()) {
            abort(403, 'Este link é inválido ou expirou durante o preenchimento.');
        }

        $validated = $request->validate([
            'type' => 'required|in:adult,child',
            'chief_complaint' => 'nullable|string',
            'family_history' => 'nullable|string',
            'patient_routine' => 'nullable|string',
            'symptoms_checklist' => 'nullable|array',
            'child_data' => 'nullable|array',
            'adult_data' => 'nullable|array', // Adicionado
        ]);

        $validated['patient_id'] = $patient->id;
        $validated['professional_id'] = $professional->id;

        Anamnesis::create($validated);

        // Marcar no prontuário do paciente a data de preenchimento da anamnese
        $patient->last_anamnesis_at = now();
        $patient->save();

        return Inertia::render('Anamneses/PublicSuccess');
    }
}