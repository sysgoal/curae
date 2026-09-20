<?php

namespace App\Http\Controllers;

use App\Models\Professional;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class ProfessionalController extends Controller
{
    public function index(Request $request)
    {
        // Inicia a query carregando a relação com o utilizador e os seus perfis (Spatie)
        $query = \App\Models\Professional::with(['user.roles']);

        // 1. Filtro de Pesquisa em Texto Livre
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('specialty', 'like', "%{$search}%")
                  ->orWhere('council_number', 'like', "%{$search}%");
            });
        }

        // 2. Filtro por Tipo de Utilizador (Role)
        if ($request->filled('role')) {
            $role = $request->role;
            $query->whereHas('user.roles', function($q) use ($role) {
                $q->where('name', $role);
            });
        }

        // Paginamos e mantemos os parâmetros na URL para a paginação não quebrar o filtro
        $professionals = $query->orderBy('name', 'asc')
                               ->paginate(15)
                               ->withQueryString();

        return inertia('Professionals/Index', [
            'professionals' => $professionals,
            'filters' => $request->only(['search', 'role']) // Devolve os filtros para o Vue
        ]);
    }

    public function create()
    {
        $roles = Role::all();
        
        return Inertia::render('Professionals/Create', [
            'roles' => $roles
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'cpf' => 'required|string|max:14|unique:professionals,cpf',
            'phone' => 'nullable|string|max:20',
            'profession' => 'required|string|max:255',
            'specialty' => 'nullable|string|max:255',
            'council_type' => 'nullable|string|max:10',
            'council_number' => 'nullable|string|max:50',
            'council_state' => 'nullable|string|size:2',
            'is_active' => 'boolean',
            'role' => 'required|exists:roles,name',
        ]);

        DB::transaction(function () use ($validated) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
            ]);

            $user->assignRole($validated['role']);

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

    public function edit(Professional $professional)
    {
        $professional->load(['user', 'user.roles']);
        $roles = Role::all();
        
        return Inertia::render('Professionals/Edit', [
            'professional' => $professional,
            'roles' => $roles
        ]);
    }

    public function update(Request $request, Professional $professional)
    {
        $user = $professional->user;

        // 1. Constrói a regra de e-mail dinamicamente para evitar o erro "null"
        $emailRule = 'required|string|email|max:255|unique:users,email';
        if ($user) {
            $emailRule .= ',' . $user->id;
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => $emailRule,
            'password' => 'nullable|string|min:8|confirmed',
            'cpf' => 'required|string|max:14|unique:professionals,cpf,' . $professional->id,
            'phone' => 'nullable|string|max:20',
            'profession' => 'required|string|max:255',
            'specialty' => 'nullable|string|max:255',
            'council_type' => 'nullable|string|max:10',
            'council_number' => 'nullable|string|max:50',
            'council_state' => 'nullable|string|size:2',
            'is_active' => 'required|boolean',
            'role' => 'required|exists:roles,name',
        ]);

        DB::transaction(function () use ($validated, $user, $professional) {
            
            // Se o utilizador já existir, atualizamos normalmente
            if ($user) {
                $userFields = [
                    'name' => $validated['name'],
                    'email' => $validated['email'],
                ];

                if (!empty($validated['password'])) {
                    $userFields['password'] = Hash::make($validated['password']);
                }

                $user->update($userFields);
                $user->syncRoles($validated['role']);
            } 
            // Se o profissional for um registo "órfão", criamos o acesso de login para ele agora
            else {
                $newUser = User::create([
                    'name' => $validated['name'],
                    'email' => $validated['email'],
                    // Se não enviar palavra-passe, cria uma padrão segura para ele mudar depois
                    'password' => Hash::make(!empty($validated['password']) ? $validated['password'] : 'Curae@123'),
                ]);
                
                $newUser->assignRole($validated['role']);
                
                // Vincula o novo ID de utilizador ao profissional
                $professional->user_id = $newUser->id;
            }

            // Atualiza os dados do Perfil Profissional
            $professional->update([
                'name' => $validated['name'],
                'cpf' => $validated['cpf'],
                'phone' => $validated['phone'] ?? null,
                'profession' => $validated['profession'],
                'specialty' => $validated['specialty'] ?? null,
                'council_type' => $validated['council_type'] ?? null,
                'council_number' => $validated['council_number'] ?? null,
                'council_state' => $validated['council_state'] ?? null,
                'is_active' => $validated['is_active'],
            ]);
        });

        return redirect()->route('professionals.index')
            ->with('success', 'Profissional atualizado com sucesso!');
    }

    public function destroy(Professional $professional)
    {
        if ($professional->user_id) {
            User::find($professional->user_id)?->delete();
        }
        
        $professional->delete();
        
        return redirect()->route('professionals.index')
            ->with('success', 'Profissional removido do sistema.');
    }
}