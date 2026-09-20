<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use App\Models\User;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Criação dos Perfis (Roles)
        Role::create(['name' => 'admin']);
        Role::create(['name' => 'secretaria']);
        Role::create(['name' => 'enfermeira']);
        Role::create(['name' => 'medico']);
        Role::create(['name' => 'nutricionista']);
        Role::create(['name' => 'fisioterapeuta']);

        // Pega o primeiro utilizador do sistema (que é você testando) 
        // e atribui o perfil de enfermeira para testarmos o comportamento atual!
        $firstUser = User::first();
        if ($firstUser) {
            $firstUser->assignRole('enfermeira');
        }
    }
}