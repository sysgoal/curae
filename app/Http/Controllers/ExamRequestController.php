<?php

namespace App\Http\Controllers;

use App\Models\ExamRequest;
use App\Models\Patient;
use App\Models\Professional;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class ExamRequestController extends Controller
{
    // Renderiza a interface Vue para criar o pedido
    public function create(Request $request)
    {
        $patient = Patient::findOrFail($request->patient_id);
        return inertia('ExamRequests/Create', compact('patient'));
    }

    // Salva no banco e devolve a instrução para o Vue abrir o PDF
    public function store(Request $request)
    {
        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'exams' => 'required|array|min:1',
            'exams.*' => 'required|string|max:255',
            'clinical_indication' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $professional = auth()->user()->professional;

        // Se o seu utilizador atual não for médico, o sistema pega o primeiro profissional 
        // cadastrado na base de dados apenas para não travar os seus testes.
        if (!$professional) {
            $professional = Professional::first();
            
            // Se o banco estiver totalmente vazio de profissionais, avisa.
            if (!$professional) {
                return redirect()->route('patients.show', $validated['patient_id'])
                    ->with('error', 'Erro: Precisa cadastrar pelo menos um profissional na Equipa Clínica primeiro.');
            }
        }

        $validated['professional_id'] = $professional->id;

        // Agora ele salva o exame com sucesso!
        $examRequest = ExamRequest::create($validated);

        // Retorna para o prontuário com o gatilho (flash data) para abrir o PDF automaticamente
        return redirect()->route('patients.show', $validated['patient_id'])
            ->with('success', 'Pedido de exames gerado com sucesso!')
            ->with('open_exam_pdf', route('exam-requests.pdf', $examRequest->id));
    }

    // Gera o PDF (DomPDF)
    public function pdf(ExamRequest $examRequest)
    {
        $examRequest->load(['patient', 'professional']);
        
        $pdf = Pdf::loadView('pdf.exam-request', compact('examRequest'));
        
        return $pdf->stream('pedido_exames_' . $examRequest->patient->name . '.pdf');
    }
}