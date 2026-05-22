<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('anamneses', function (Blueprint $table) {
            $table->id();

            // Relacionamentos
            // Ligamos direto ao paciente para facilitar buscas de histórico completo
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            // Ligamos à consulta específica
          //  $table->foreignId('appointment_id')->constrained('appointments')->cascadeOnDelete();
            // Opcional: Quem preencheu (caso seja diferente do médico agendado, ex: triagem da enfermagem)
            $table->foreignId('professional_id')->constrained('professionals')->restrictOnDelete();

            // Dados da Anamnese Padrão
            $table->text('chief_complaint')->nullable(); // Queixa Principal (QP)
            $table->text('history_present_illness')->nullable(); // História da Moléstia Atual (HMA)
            
            // Histórico e Fatores de Risco
            $table->text('past_medical_history')->nullable(); // Histórico Médico Pregresso
            $table->text('family_history')->nullable(); // Histórico Familiar
            $table->text('social_history')->nullable(); // Hábitos de vida (tabagismo, etilismo, etc.)
            
            // Alertas Críticos
            $table->text('allergies')->nullable(); // Alergias
            $table->text('current_medications')->nullable(); // Medicamentos em uso
            
            // Avaliação e Plano
            $table->text('physical_examination')->nullable(); // Exame Físico Geral
            $table->text('diagnostic_hypothesis')->nullable(); // Hipótese Diagnóstica (HD) ou CID
            $table->text('conduct_plan')->nullable(); // Conduta / Plano de Tratamento
            // Como deve ficar a linha dentro do método up():
$table->foreignId('appointment_id')->nullable()->constrained()->onDelete('cascade');


            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('anamneses');
    }
};