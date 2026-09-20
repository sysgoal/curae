<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\Prescription;
use App\Models\Professional;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Barryvdh\DomPDF\Facade\Pdf; // <-- Importação do pacote de PDF

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
            'medications' => 'required|array|min:1',
            'medications.*.name' => 'required|string',
            'medications.*.dosage' => 'required|string',
            'medications.*.instructions' => 'required|string',
            'notes' => 'nullable|string',
        ]);

        $professional = Professional::where('user_id', auth()->id())->first();

        if (!$professional) {
            return back()->withErrors(['error' => 'Acesso bloqueado: Não possui um registo profissional associado para prescrever.']);
        }

        $verificationCode = strtoupper(Str::random(10));

        $prescription = Prescription::create([
            'patient_id' => $validated['patient_id'],
            'professional_id' => $professional->id,
            'medications' => $validated['medications'],
            'notes' => $validated['notes'],
            'verification_code' => $verificationCode,
        ]);

        // Redireciona de volta para o prontuário e injeta a rota do PDF na sessão (Flash data)
        return redirect()->route('patients.show', $validated['patient_id'])
            ->with('success', 'Receita médica gerada e autenticada com sucesso!')
            ->with('open_prescription_pdf', route('prescriptions.pdf', $prescription->id));
    }

    /**
     * Gera e exibe o arquivo PDF da receita médica.
     */
   /**
     * Gera e exibe o arquivo PDF da receita médica.
     */
    public function generatePdf(Prescription $prescription)
    {
        // Alterado aqui: Removemos o 'professional.user'
        $prescription->load(['patient', 'professional']);
        
        $pdf = Pdf::loadView('pdf.prescription', compact('prescription'));
        $pdf->setPaper('a4', 'portrait');
        
        return $pdf->stream('receita-' . $prescription->verification_code . '.pdf');
    }
}