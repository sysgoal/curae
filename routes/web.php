<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\ProfessionalController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\AnamnesisController;
use App\Http\Controllers\EvolutionController;
use App\Http\Controllers\PrescriptionController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PatientFileController;
use App\Http\Controllers\AiAssistantController;
use App\Http\Controllers\PublicAnamnesisController;
use App\Http\Controllers\AiController;
use App\Http\Controllers\ExamRequestController;
use App\Http\Controllers\UserController;
// Rota inicial redireciona para o login
Route::get('/', function () {
    return redirect()->route('login');
});

// -------------------------------------------------------------
// ÁREA PÚBLICA (Links Seguros para Pacientes)
// -------------------------------------------------------------
Route::middleware('signed')->group(function () {
    Route::get('/receita/{prescription}', [PrescriptionController::class, 'showPublic'])->name('prescriptions.show.public');
    Route::get('/avaliacao/{feedback}', [FeedbackController::class, 'editPublic'])->name('feedbacks.edit.public');
    Route::put('/avaliacao/{feedback}', [FeedbackController::class, 'updatePublic'])->name('feedbacks.update.public');
    Route::get('/meus-graficos/{patient}', [EvolutionController::class, 'showPublicCharts'])->name('evolutions.charts.public');
    Route::get('/ficha-clinica/{patient}/{professional}', [PublicAnamnesisController::class, 'create'])->name('public.anamnesis.create');
    Route::post('/ficha-clinica/{patient}/{professional}', [PublicAnamnesisController::class, 'store'])->name('public.anamnesis.store');
});

// -------------------------------------------------------------
// PAINEL INTERNO PROTEGIDO (Requer Autenticação)
// -------------------------------------------------------------
Route::middleware(['auth', 'verified'])->group(function () {

    // Comum a todos os utilizadores autenticados
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // ---------------------------------------------------------
    // PERFIL EXCLUSIVO: ADMINISTRADOR GERAL
    // ---------------------------------------------------------
   Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::put('/users/{id}', [UserController::class, 'update'])->name('users.update');
    Route::delete('/users/{id}', [UserController::class, 'destroy'])->name('users.destroy');
});

    // ---------------------------------------------------------
    // ACESSO ADMINISTRATIVO COMPARTILHADO (Admin + Secretaria)
    // ---------------------------------------------------------
    Route::middleware(['role:admin|secretaria'])->group(function () {
        Route::get('/appointments/calendar', [AppointmentController::class, 'calendar'])->name('appointments.calendar');
    });

    // ---------------------------------------------------------
    // ACESSO COLETIVO DE CADASTRO (Secretaria + Equipa Clínica)
    // ---------------------------------------------------------
    Route::middleware(['role:admin|secretaria|medico|enfermeira|nutricionista|fisioterapeuta'])->group(function () {
        Route::resource('patients', PatientController::class);
        Route::resource('appointments', AppointmentController::class);
        Route::patch('/appointments/{id}/status', [AppointmentController::class, 'updateStatus'])->name('appointments.updateStatus');
        Route::get('feedbacks', [FeedbackController::class, 'index'])->name('feedbacks.index');
        Route::post('/patients/{patient}/anamnesis-link', [PublicAnamnesisController::class, 'generateLink'])->name('patients.anamnesis.link');
        Route::post('/schedule-blocks', [AppointmentController::class, 'storeBlock'])->name('schedule-blocks.store');
        Route::delete('/schedule-blocks/{id}', [AppointmentController::class, 'destroyBlock'])->name('schedule-blocks.destroy');
       
        });
// Rotas de Inteligência Artificial Clínica
    Route::middleware(['auth', 'role:admin|medico'])->group(function () {
    // Rota de IA protegida para médicos e equipe clínica
        Route::post('/ai/analyze', [AiController::class, 'analyze'])->name('ai.analyze');
        
    });

    // ---------------------------------------------------------
    // ACESSO CLÍNICO EXPANSIVO (Corpo Técnico de Saúde)
    // ---------------------------------------------------------
    Route::middleware(['role:admin|medico|enfermeira|nutricionista|fisioterapeuta'])->group(function () {
        Route::resource('anamneses', AnamnesisController::class)->except(['index']);
        Route::resource('evolutions', EvolutionController::class)->except(['index']);
        Route::post('/patient-files', [PatientFileController::class, 'store'])->name('patient-files.store');
        Route::delete('/patient-files/{patientFile}', [PatientFileController::class, 'destroy'])->name('patient-files.destroy');
    });

    // ---------------------------------------------------------
    // ACESSO CLÍNICO EXPANSIVO (Corpo Técnico de Saúde)
    // ---------------------------------------------------------
    Route::middleware(['role:admin|medico|enfermeira|nutricionista|fisioterapeuta'])->group(function () {
        
        // ADICIONE ESTA LINHA AQUI:
        Route::get('/patients/{patient}/history', [PatientController::class, 'history'])->name('patients.history');
        
        Route::resource('anamneses', AnamnesisController::class)->except(['index']);
        Route::resource('evolutions', EvolutionController::class)->except(['index']);
        // Pedidos de Exames
        Route::get('/exam-requests/create', [ExamRequestController::class, 'create'])->name('exam-requests.create');
        Route::post('/exam-requests', [ExamRequestController::class, 'store'])->name('exam-requests.store');
        Route::get('/exam-requests/{examRequest}/pdf', [ExamRequestController::class, 'pdf'])->name('exam-requests.pdf');
        // ...
    });

    // ---------------------------------------------------------
    // ACESSO CLÍNICO PRIVILEGIADO (Apenas Médicos)
    // ---------------------------------------------------------
    Route::middleware(['role:admin|medico'])->group(function () {
        Route::resource('prescriptions', PrescriptionController::class)->except(['index']);
        Route::get('/prescriptions/{prescription}/pdf', [PrescriptionController::class, 'generatePdf'])->name('prescriptions.pdf');
        Route::post('/ai/analyze', [AiController::class, 'analyze'])->name('ai.analyze');
        
    });

    Route::get('/atualizar-banco', function () {
    if (!\Illuminate\Support\Facades\Schema::hasColumn('professionals', 'council_number')) {
        \Illuminate\Support\Facades\Schema::table('professionals', function ($table) {
            $table->string('phone')->nullable();
            $table->string('specialty')->nullable();
            $table->string('council_type')->nullable();
            $table->string('council_number')->nullable();
        });
        return 'Sucesso: As colunas do Conselho Médico foram adicionadas à base de dados!';
    }
    return 'Tudo OK: As colunas já existem na base de dados.';
});

});

require __DIR__ . '/auth.php';