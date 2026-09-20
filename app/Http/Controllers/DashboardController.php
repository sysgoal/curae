<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\Appointment;
use App\Models\Professional;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $stats = [];

        if ($user->hasRole('admin')) {
            $stats = [
                'total_patients' => Patient::count(),
                'total_professionals' => Professional::count(),
                'pending_appointments' => Appointment::where('status', 'scheduled')->count(),
            ];
        } elseif ($user->hasRole('secretaria')) {
            $stats = [
                'today_appointments' => Appointment::whereDate('appointment_date', today())->count(),
                'total_patients' => Patient::count(),
            ];
        } else {
            // Médicos, Enfermeiras, Nutricionistas e Fisioterapeutas
            $professional = Professional::where('user_id', $user->id)->first();
            $stats = [
                'my_appointments_today' => Appointment::where('professional_id', $professional?->id ?? 0)
                    ->whereDate('appointment_date', today())
                    ->count(),
                'total_linked_patients' => Patient::whereHas('anamneses', function ($query) use ($professional) {
                    $query->where('professional_id', $professional?->id ?? 0);
                })->count(),
            ];
        }

        return Inertia::render('Dashboard', [
            'stats' => $stats
        ]);
    }
}