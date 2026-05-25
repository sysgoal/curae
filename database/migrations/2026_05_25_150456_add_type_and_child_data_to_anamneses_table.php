<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('anamneses', function (Blueprint $table) {
            $table->string('type')->default('adult')->after('professional_id');
            $table->json('child_data')->nullable()->after('symptoms_checklist');
        });
    }

    public function down()
    {
        Schema::table('anamneses', function (Blueprint $table) {
            $table->dropColumn(['type', 'child_data']);
        });
    }
};