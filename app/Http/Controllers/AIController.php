<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiController extends Controller
{
    public function analyze(Request $request)
    {
        $request->validate([
            'prompt' => 'required|string'
        ]);

        // Por padrão, usa o provedor da nuvem Groq (Llama 3 ultra-rápido)
        $provider = env('LLAMA_PROVIDER', 'groq');
        
        // Blindagem de Contexto: Obriga o Llama a atuar dentro de limites éticos e clínicos
        $systemPrompt = "Você é o 'Curae', um assistente de Inteligência Artificial especializado em saúde integrativa. " .
                        "A sua função é analisar dados de prontuários eletrônicos e fornecer insights, hipóteses e " .
                        "sugestões de conduta para o médico ler. Regras estritas: " .
                        "1. Responda SEMPRE em Português do Brasil de forma clara e estruturada. " .
                        "2. Nunca afirme um diagnóstico definitivo, trate tudo como hipótese clínica baseada nos relatos. " .
                        "3. Seja conciso, use tópicos e foque em correlações.";

        try {
            if ($provider === 'ollama') {
                return $this->useOllama($systemPrompt, $request->prompt);
            }

            return $this->useGroq($systemPrompt, $request->prompt);

        } catch (\Exception $e) {
            Log::error('Erro Crítico na Integração com Llama: ' . $e->getMessage());
            
            return response()->json([
                'response' => '⚠️ O serviço de Inteligência Artificial encontra-se indisponível ou com falha de comunicação.'
            ], 500);
        }
    }

    /**
     * Integração com Groq Cloud (Llama 3 ultra-rápido na nuvem)
     */
    private function useGroq($systemPrompt, $userPrompt)
    {
        // O withoutVerifying() contorna o erro de SSL (cURL error 60) no ambiente local
        $response = Http::withoutVerifying()
            ->withToken(env('GROQ_API_KEY'))
            ->timeout(30)
            ->post('https://api.groq.com/openai/v1/chat/completions', [
                'model' => 'llama3-70b-8192', 
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $userPrompt]
                ],
                'temperature' => 0.3, 
            ]);

        if ($response->successful()) {
            return response()->json([
                'response' => $response->json('choices.0.message.content')
            ]);
        }

        throw new \Exception('Falha na API da Groq: ' . $response->body());
    }

    /**
     * Integração com Ollama (Llama 3 rodando 100% Local / LGPD Compliant)
     */
    private function useOllama($systemPrompt, $userPrompt)
    {
        $response = Http::withoutVerifying()
            ->timeout(300)
            ->post(env('OLLAMA_URL', 'http://127.0.0.1:11434/api/generate'), [
                'model' => 'llama3.1', 
                'system' => $systemPrompt,
                'prompt' => $userPrompt,
                'stream' => false,
                'options' => [
                    'temperature' => 0.3
                ]
            ]);

        if ($response->successful()) {
            return response()->json([
                'response' => $response->json('response')
            ]);
        }

        throw new \Exception('Falha no Ollama Local: ' . $response->body());
    }
}