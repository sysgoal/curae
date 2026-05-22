<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            
            // Dados Pessoais
            $table->string('name');
            $table->string('cpf', 14)->unique(); // Tamanho 14 permite a máscara (xxx.xxx.xxx-xx)
            $table->string('rg')->nullable();
            $table->date('date_of_birth');
            $table->enum('gender', ['M', 'F', 'Outro', 'Prefere não informar'])->nullable();
            
            // Contato (Essencial para os envios de links depois)
            $table->string('phone')->nullable(); // WhatsApp principal
            $table->string('email')->unique()->nullable();
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone')->nullable();

            // Endereço
            $table->string('zip_code', 9)->nullable();
            $table->string('street')->nullable();
            $table->string('number')->nullable();
            $table->string('complement')->nullable();
            $table->string('neighborhood')->nullable();
            $table->string('city')->nullable();
            $table->string('state', 2)->nullable(); // UF

            // Dados Biológicos Rápidos
            $table->string('blood_type', 3)->nullable(); // Ex: O+, AB-

            // Controle de Registros
            $table->timestamps();
            $table->softDeletes(); // Evita deletar permanentemente (muito importante em sistemas médicos)
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};
