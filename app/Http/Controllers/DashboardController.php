<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Professional; // <-- Importação do novo Model
use Illuminate\Http\Request;
use Carbon\Carbon;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $today = Carbon::today()->format('Y-m-d');

        $stats = [
            'total_patients' => Patient::count(),
            'total_professionals' => Professional::count(), // <-- Estatística para o administrador
            'appointments_today' => Appointment::where('appointment_date', $today)->count(),
            'completed_today' => Appointment::where('appointment_date', $today)->where('status', 'finalizado')->count(),
        ];

        $nextAppointments = Appointment::with(['patient', 'professional'])
            ->where('appointment_date', $today)
            ->whereNotIn('status', ['cancelado', 'finalizado'])
            ->orderBy('start_time', 'asc')
            ->take(5)
            ->get();

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'nextAppointments' => $nextAppointments,
        ]);
    }
}