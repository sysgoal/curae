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
        $users = User::with('roles')->orderBy('name')->get()->map(function($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->roles->first() ? $user->roles->first()->name : 'Sem Cargo',
                'created_at' => $user->created_at->format('d/m/Y')
            ];
        });

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
        ], [
            'email.unique' => 'Este endereço de e-mail já está a ser utilizado por outro utilizador.',
            'password.min' => 'A senha deve ter pelo menos 8 caracteres.'
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        $user->assignRole($validated['role']);

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

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8',
            'role' => 'required|exists:roles,name'
        ], [
            'email.unique' => 'Este endereço de e-mail já está a ser utilizado por outro utilizador.',
            'password.min' => 'A senha deve ter pelo menos 8 caracteres.'
        ]);

        $user->name = $validated['name'];
        $user->email = $validated['email'];

        // Só atualiza a senha se o utilizador digitou uma nova
        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        // Atualiza os privilégios
        $user->syncRoles([$validated['role']]);

        // Se passou a ser um profissional (ou mudou de nome/email), atualiza na tabela da agenda
        if (in_array($validated['role'], ['professional', 'medico', 'dentista', 'fisioterapeuta'])) {
            Professional::updateOrCreate(
                ['user_id' => $user->id],
                ['name' => $user->name, 'email' => $user->email, 'is_active' => true]
            );
        }

        return redirect()->back()->with('success', 'Dados do utilizador atualizados com sucesso!');
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);
        
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Ação bloqueada: Não pode remover a sua própria conta.');
        }
        
        $user->delete();
        return redirect()->back()->with('success', 'Utilizador removido do sistema.');
    }
}