<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feedbacks', function (Blueprint $table) {
            $table->id();

            // Relacionamentos
            // Cada feedback pertence a uma consulta específica
            $table->foreignId('appointment_id')->constrained('appointments')->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();

            // Dados da Avaliação
            // Inteiro de 1 a 5 (Estrelas). Nullable porque o registro é criado antes do paciente responder.
            $table->tinyInteger('rating')->nullable(); 
            $table->text('comments')->nullable(); // Sugestões ou elogios
            
            // Privacidade e Controle
            $table->boolean('is_anonymous')->default(false); // Permite ao paciente ocultar o nome na visão do médico
            $table->timestamp('answered_at')->nullable(); // Controla se o paciente já respondeu

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feedbacks');
    }
};
