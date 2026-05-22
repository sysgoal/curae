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

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Aqui é onde você registra as rotas web da sua aplicação.
|
*/

// Rota inicial (Redireciona direto para o login, pois é um sistema restrito)
Route::get('/', function () {
    return redirect()->route('login');
});

// Substitua a rota '/dashboard' antiga por esta:
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

// -------------------------------------------------------------
// ÁREA PÚBLICA DO PACIENTE (Acesso via Links Seguros)
// -------------------------------------------------------------
// O middleware 'signed' garante que a URL não foi adulterada
Route::middleware('signed')->group(function () {
    
    // Receita Digital Segura
    Route::get('/receita/{prescription}', [PrescriptionController::class, 'showPublic'])
        ->name('prescriptions.show.public');
    
    // Formulário de Avaliação Pós-Consulta
    Route::get('/avaliacao/{feedback}', [FeedbackController::class, 'editPublic'])
        ->name('feedbacks.edit.public');
        
    Route::put('/avaliacao/{feedback}', [FeedbackController::class, 'updatePublic'])
        ->name('feedbacks.update.public');
    
    // Gráficos de Evolução do Paciente
    Route::get('/meus-graficos/{patient}', [EvolutionController::class, 'showPublicCharts'])
        ->name('evolutions.charts.public');
});

// -------------------------------------------------------------
// PAINEL DO SISTEMA (Requer Login)
// -------------------------------------------------------------
// O middleware 'auth' protege todas as rotas deste grupo
Route::middleware(['auth', 'verified'])->prefix('painel')->group(function () {
    
    // Dashboard Inicial (Agora renderizado via Vue.js/Inertia)
    Route::get('/', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    // Cadastros Principais (Gera rotas para index, create, store, show, edit, update, destroy)
    Route::resource('patients', PatientController::class);
    Route::resource('professionals', ProfessionalController::class);
    Route::resource('appointments', AppointmentController::class);

    // Prontuário, Evolução e Prescrições
    // Omitimos o método 'index' porque essas listas geralmente aparecem dentro da tela de detalhes do paciente
    Route::resource('anamneses', AnamnesisController::class)->except(['index']);
    Route::resource('evolutions', EvolutionController::class)->except(['index']);
    Route::resource('prescriptions', PrescriptionController::class)->except(['index']);
    
    // Feedbacks recebidos (Apenas visualização no painel)
    Route::get('feedbacks', [FeedbackController::class, 'index'])->name('feedbacks.index');
    Route::get('feedbacks/{feedback}', [FeedbackController::class, 'show'])->name('feedbacks.show');
});
// Rotas de Perfil do Usuário (Breeze)
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});
Route::get('/prescriptions/{prescription}/pdf', [App\Http\Controllers\PrescriptionController::class, 'generatePdf'])->name('prescriptions.pdf');
// Coloque esta linha PRIMEIRO
Route::patch('/appointments/{id}/status', [App\Http\Controllers\AppointmentController::class, 'updateStatus'])->name('appointments.updateStatus');

// E o resource DEPOIS
Route::resource('appointments', App\Http\Controllers\AppointmentController::class);
// -------------------------------------------------------------
// AUTENTICAÇÃO (Breeze)
// -------------------------------------------------------------
// Carrega as rotas de Login, Registro, Logout e Recuperação de Senha do Vue
require __DIR__.'/auth.php';