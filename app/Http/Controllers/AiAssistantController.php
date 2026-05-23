<?php

namespace App\Http\Controllers;

use App\Models\Anamnesis;
use Illuminate\Http\Request;
use Gemini\Laravel\Facades\Gemini;
use Barryvdh\DomPDF\Facade\Pdf;

class AiAssistantController extends Controller
{
    /**
     * Recebe um texto do frontend e pede à IA para analisar (Chat Geral).
     */
    public function analyzeText(Request $request)
    {
        $request->validate([
            'prompt' => 'required|string',
        ]);

        try {
            // ATUALIZADO: Usando o modelo mais recente e ativo (2.5-flash)
            $result = Gemini::generativeModel('gemini-2.5-flash')->generateContent($request->prompt);

            return response()->json([
                'success' => true,
                'response' => $result->text()
            ]);
            
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Erro Gemini (Chat): ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro ao contactar a IA: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Analisa uma Anamnese específica e gera um Protocolo Integrativo.
     */
    public function generateProtocol(Request $request)
    {
        $request->validate(['anamnesis_id' => 'required|exists:anamneses,id']);

        $anamnesis = Anamnesis::with('patient')->findOrFail($request->anamnesis_id);
        
        $prompt = "
        Atue como um Especialista em Saúde Integrativa. 
        Analise a seguinte Anamnese e monte um PROTOCOLO DE TRATAMENTO.
        
        [DADOS DO PACIENTE]
        Nome: {$anamnesis->patient->name}
        Queixa Principal: {$anamnesis->chief_complaint}
        Histórico Familiar: {$anamnesis->family_history}
        Sintomas Relatados: " . implode(', ', $anamnesis->symptoms_checklist ?? []) . "
        
        [ESTRUTURA DO PROTOCOLO REQUERIDA]
        1. Análise Fisiológica (O que os sintomas sugerem)
        2. Recomendações Alimentares e Estilo de Vida
        3. Sugestão de Suplementação Integrativa (se aplicável)
        4. Próximos Passos e Exames Complementares
        
        Responda em formato profissional, usando linguagem clínica, mas clara.
        ";

        try {
            // ATUALIZADO: Usando o modelo mais recente e ativo (2.5-flash)
            $result = Gemini::generativeModel('gemini-2.5-flash')->generateContent($prompt);
            $protocolText = $result->text();

            session(['last_protocol' => [
                'patient_name' => $anamnesis->patient->name,
                'content' => $protocolText,
                'date' => now()->format('d/m/Y')
            ]]);

            return response()->json([
                'success' => true,
                'protocol' => $protocolText
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Erro Gemini (Protocolo): ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Descarrega o PDF do último protocolo gerado.
     */
    public function downloadProtocolPdf()
    {
        $data = session('last_protocol');
        
        if (!$data) {
            return back()->withErrors(['error' => 'Nenhum protocolo encontrado na sessão para imprimir.']);
        }

        $pdf = Pdf::loadView('pdf.protocol', $data);
        return $pdf->download("Protocolo_{$data['patient_name']}.pdf");
    }
}