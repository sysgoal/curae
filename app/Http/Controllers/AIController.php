<?php

namespace App\Http\Controllers;

use App\Services\GeminiService;
use Illuminate\Http\Request;

class AIController extends Controller
{
    public function ask(Request $request, GeminiService $gemini)
    {
        $response = $gemini->ask($request->prompt);

        return response()->json($response);
    }
}