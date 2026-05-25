<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('anamneses', function (Blueprint $table) {
            // Adiciona a coluna de rotina se ela não existir
            if (!Schema::hasColumn('anamneses', 'patient_routine')) {
                $table->text('patient_routine')->nullable()->after('family_history');
            }
            
            // Adiciona a coluna de dados de adulto se ela não existir
            if (!Schema::hasColumn('anamneses', 'adult_data')) {
                $table->json('adult_data')->nullable()->after('child_data');
            }
        });
    }

    public function down()
    {
        Schema::table('anamneses', function (Blueprint $table) {
            $table->dropColumn(['patient_routine', 'adult_data']);
        });
    }
};