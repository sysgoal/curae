<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('professionals', function (Blueprint $table) {
            $table->id();
            
            // Relacionamento com a tabela de usuários (login)
            // É nullable caso o profissional seja cadastrado apenas para a secretária gerenciar a agenda
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            
            // Dados Cadastrais
            $table->string('name');
            $table->string('cpf', 14)->unique();
            $table->string('phone')->nullable();
            
            // Dados Profissionais
            $table->string('profession'); // Ex: Médico, Nutricionista, Enfermeiro, Fisioterapeuta
            $table->string('specialty')->nullable(); // Ex: Cardiologia, Nutrição Esportiva
            
            // Registro de Classe (Conselho)
            $table->string('council_type', 10)->nullable(); // Ex: CRM, COREN, CRN, CRP, CREFITO
            $table->string('council_number')->nullable(); // Ex: 12345
            $table->string('council_state', 2)->nullable(); // UF do registro (ex: MG, SP)

            $table->boolean('is_active')->default(true); // Controle para inativar profissionais sem deletar o histórico

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('professionals');
    }
};