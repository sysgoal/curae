<?php

namespace App\Http\Controllers;

use App\Models\Professional;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ProfessionalController extends Controller
{
    public function index()
    {
        $professionals = Professional::with('user')->orderBy('name')->get();
        return Inertia::render('Professionals/Index', [
            'professionals' => $professionals
        ]);
    }

    public function create()
    {
        return Inertia::render('Professionals/Create');
    }

    public function store(Request $request)
    {
        // Validamos os dados do profissional E os dados de login (email e senha)
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email', // E-mail único na tabela de usuários
            'password' => 'required|string|min:8', // Senha para o login
            'cpf' => 'required|string|max:14|unique:professionals,cpf',
            'phone' => 'nullable|string|max:20',
            'profession' => 'required|string|max:255',
            'specialty' => 'nullable|string|max:255',
            'council_type' => 'nullable|string|max:10',
            'council_number' => 'nullable|string|max:50',
            'council_state' => 'nullable|string|size:2',
            'is_active' => 'boolean',
        ]);

        // Usamos uma transação para garantir que ambas as tabelas sejam salvas em segurança
        DB::transaction(function () use ($validated) {
            
            // 1. Criamos o Usuário para permitir o Login
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
            ]);

            // 2. Criamos o Perfil Profissional vinculando ao ID do usuário criado acima
            Professional::create([
                'user_id' => $user->id,
                'name' => $validated['name'],
                'cpf' => $validated['cpf'],
                'phone' => $validated['phone'] ?? null,
                'profession' => $validated['profession'],
                'specialty' => $validated['specialty'] ?? null,
                'council_type' => $validated['council_type'] ?? null,
                'council_number' => $validated['council_number'] ?? null,
                'council_state' => $validated['council_state'] ?? null,
                'is_active' => $validated['is_active'] ?? true,
            ]);
        });

        return redirect()->route('professionals.index')
            ->with('success', 'Profissional e Acesso ao Sistema criados com sucesso!');
    }

    public function destroy(Professional $professional)
    {
        // Se quiser deletar o usuário junto, descomente a linha abaixo:
        // if ($professional->user_id) User::find($professional->user_id)?->delete();
        
        $professional->delete();
        return redirect()->route('professionals.index')
            ->with('success', 'Profissional removido do sistema.');
    }
}