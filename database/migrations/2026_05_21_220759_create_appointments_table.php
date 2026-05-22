<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            
            // Relacionamentos principais
            $table->foreignId('patient_id')->constrained('patients')->restrictOnDelete();
            $table->foreignId('professional_id')->constrained('professionals')->restrictOnDelete();
            
            // Controle de Data e Hora
            $table->date('appointment_date');
            $table->time('start_time');
            $table->time('end_time'); // Importante para calcular a duração e evitar sobreposição na agenda
            
            // Tipos e Status
            $table->string('type')->default('Consulta'); // Ex: Consulta, Retorno, Procedimento, Exame
            $table->enum('status', [
                'agendado',    // Marcado inicialmente
                'confirmado',  // Paciente confirmou presença (via WhatsApp/Link)
                'espera',      // Paciente chegou na clínica
                'atendimento', // Médico iniciou a consulta
                'finalizado',  // Consulta concluída
                'falta',       // Paciente não compareceu
                'cancelado'    // Consulta cancelada
            ])->default('agendado');

            // Observações
            $table->text('notes')->nullable(); // Anotações da secretária (ex: "Paciente pediu prioridade")
            $table->text('cancellation_reason')->nullable(); // Motivo se o status for 'cancelado'

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};