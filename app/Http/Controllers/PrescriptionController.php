<?php

namespace App\Http\Controllers;

use App\Models\Prescription;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Str; 
use Inertia\Inertia;
use Barryvdh\DomPDF\Facade\Pdf; 

class PrescriptionController extends Controller
{
    public function create(Request $request)
    {
        $patient = Patient::findOrFail($request->query('patient_id'));

        return Inertia::render('Prescriptions/Create', [
            'patient' => $patient
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'medications' => 'required|array|min:1',
            'notes' => 'nullable|string',
        ]);

        // Associa ao médico logado (ou força o ID 2)
        $validated['professional_id'] = auth()->id() ?? 2; 

        // GERA O CÓDIGO DE VERIFICAÇÃO AUTOMÁTICO (Ex: A7B9F2K1X3)
        $validated['verification_code'] = Str::upper(Str::random(10));

        Prescription::create($validated);

        return redirect()->route('patients.show', $request->patient_id)
            ->with('success', 'Prescrição salva com sucesso!');
    }


// <-- Importação do PDF
    public function generatePdf(Prescription $prescription)
    {
        // Carrega os dados do paciente para podermos usar no PDF
        $prescription->load('patient');

        // Renderiza a vista Blade que vamos criar no passo seguinte
        $pdf = Pdf::loadView('pdf.prescription', compact('prescription'));

        // Configura para abrir no navegador em vez de descarregar logo
        return $pdf->stream('receita_' . Str::slug($prescription->patient->name) . '.pdf');
    }
}
