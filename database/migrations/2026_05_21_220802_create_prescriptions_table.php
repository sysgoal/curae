<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prescriptions', function (Blueprint $table) {
            $table->id();

            // Relacionamentos
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('professional_id')->constrained('professionals')->restrictOnDelete();
            
            // Nullable, pois o médico pode renovar uma receita sem uma consulta formal
            $table->foreignId('appointment_id')->nullable()->constrained('appointments')->nullOnDelete();

            // Conteúdo da Prescrição
            // JSON para salvar um array de objetos: [{"drug": "Dipirona", "dosage": "500mg", "frequency": "De 8/8h"}]
            $table->json('medications'); 
            
            // Orientações gerais em texto livre (ex: "Fazer repouso", "Beber muita água")
            $table->text('orientations')->nullable();

            // Validação e Segurança (Essencial para o envio por link)
            $table->date('valid_until')->nullable(); // Validade da receita (ex: 30 dias para antibióticos)
            $table->string('verification_code')->unique(); // Código único (ex: CURAE-9X8B-A1B2) para a farmácia consultar
            $table->boolean('is_signed_digitally')->default(false); // Flag para futura integração com certificado digital (ICP-Brasil)

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prescriptions');
    }
};