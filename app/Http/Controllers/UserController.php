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
    // Cobre todas as possibilidades de escrita de cargos para nunca falhar a criação na Agenda
    private $rolesProfissionais = [
        'professional', 'medico', 'médico', 'medica', 'médica', 
        'dentista', 'fisioterapeuta', 'enfermeiro', 'enfermeira'
    ];

    public function index()
    {
        $usersRaw = User::with('roles')->orderBy('name')->get();
        $professionals = Professional::whereIn('user_id', $usersRaw->pluck('id'))->get()->keyBy('user_id');

        $users = $usersRaw->map(function($user) use ($professionals) {
            $prof = $professionals->get($user->id);
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->roles->first() ? $user->roles->first()->name : 'Sem Cargo',
                'created_at' => $user->created_at->format('d/m/Y'),
                
                'phone' => $prof ? $prof->phone : '',
                'specialty' => $prof ? $prof->specialty : '',
                'council_type' => $prof ? $prof->council_type : '',
                'council_number' => $prof ? $prof->council_number : '',
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
            'role' => 'required|exists:roles,name',
            
            'phone' => 'nullable|string|max:20',
            'specialty' => 'nullable|string|max:255',
            'council_type' => 'nullable|string|max:50',
            'council_number' => 'nullable|string|max:50',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        $user->assignRole($validated['role']);

        // Se o utilizador preencheu os campos clínicos OU se o cargo for de saúde, cria o Profissional
        $isHealthRole = in_array(strtolower($validated['role']), $this->rolesProfissionais);
        $hasClinicalData = !empty($validated['council_number']) || !empty($validated['specialty']);

        if ($isHealthRole || $hasClinicalData) {
            Professional::create([
                'user_id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $validated['phone'] ?? null,
                'specialty' => $validated['specialty'] ?? null,
                'council_type' => $validated['council_type'] ?? null,
                'council_number' => $validated['council_number'] ?? null,
                'is_active' => true
            ]);
        }

        return redirect()->back()->with('success', 'Utilizador registado com sucesso!');
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8',
            'role' => 'required|exists:roles,name',
            
            'phone' => 'nullable|string|max:20',
            'specialty' => 'nullable|string|max:255',
            'council_type' => 'nullable|string|max:50',
            'council_number' => 'nullable|string|max:50',
        ]);

        $user->name = $validated['name'];
        $user->email = $validated['email'];

        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();
        $user->syncRoles([$validated['role']]);

        $isHealthRole = in_array(strtolower($validated['role']), $this->rolesProfissionais);
        $hasClinicalData = !empty($validated['council_number']) || !empty($validated['specialty']);

        if ($isHealthRole || $hasClinicalData) {
            Professional::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'name' => $user->name, 
                    'email' => $user->email,
                    'phone' => $validated['phone'] ?? null,
                    'specialty' => $validated['specialty'] ?? null,
                    'council_type' => $validated['council_type'] ?? null,
                    'council_number' => $validated['council_number'] ?? null,
                    'is_active' => true
                ]
            );
        } else {
            Professional::where('user_id', $user->id)->update(['is_active' => false]);
        }

        return redirect()->back()->with('success', 'Dados atualizados com sucesso!');
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);
        
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Ação bloqueada: Não pode remover a sua própria conta.');
        }
        
        Professional::where('user_id', $user->id)->delete();
        $user->delete();
        
        return redirect()->back()->with('success', 'Utilizador removido do sistema.');
    }
}