<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Professional;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index()
    {
        // Lista todos os utilizadores com os respetivos cargos
        $users = User::with('roles')->orderBy('name')->get()->map(function($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->roles->first() ? $user->roles->first()->name : 'Sem Cargo',
                'created_at' => $user->created_at->format('d/m/Y')
            ];
        });

        // Traz os cargos disponíveis na base de dados para o formulário
        $roles = Role::orderBy('name')->get();

        return Inertia::render('Users/Index', [
            'users' => $users,
            'roles' => $roles
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'role' => 'required|exists:roles,name'
        ]);

        // 1. Cria o utilizador com a senha encriptada
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        // 2. Atribui o cargo de acesso ao sistema
        $user->assignRole($validated['role']);

        // 3. Automação: Se for um profissional de saúde, cria o perfil dele na agenda
        if (in_array($validated['role'], ['professional', 'medico', 'dentista', 'fisioterapeuta'])) {
            Professional::create([
                'user_id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_active' => true
            ]);
        }

        return redirect()->back()->with('success', 'Utilizador criado com sucesso!');
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);
        
        // Impede que o administrador apague a própria conta por acidente
        if ($user->id === auth()->id()) {
            return back()->withErrors(['error' => 'Ação bloqueada: Não pode remover a sua própria conta.']);
        }
        
        $user->delete();
        return redirect()->back()->with('success', 'Utilizador removido do sistema.');
    }
}