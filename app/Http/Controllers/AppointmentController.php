<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Professional;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AppointmentController extends Controller
{
    public function index()
    {
        // Agora carregamos também os dados do profissional vinculado à consulta
        $appointments = Appointment::with(['patient', 'professional'])
            ->orderBy('appointment_date', 'asc')
            ->orderBy('start_time', 'asc')
            ->get();

        $patients = Patient::orderBy('name')->get(['id', 'name', 'cpf']);
        
        // Carrega apenas os profissionais ativos para o dropdown da agenda
        $professionals = Professional::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'profession', 'specialty']);

        return Inertia::render('Appointments/Index', [
            'appointments' => $appointments,
            'patients' => $patients,
            'professionals' => $professionals
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'professional_id' => 'required|exists:professionals,id', // Nova validação
            'appointment_date' => 'required|date',
            'start_time' => 'required',
            'end_time' => 'required',
            'type' => 'required|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $validated['status'] = 'agendado';

        Appointment::create($validated);

        return redirect()->route('appointments.index')->with('success', 'Consulta agendada com sucesso!');
    }

    public function updateStatus(Request $request, $id)
    {
        $appointment = Appointment::findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|in:agendado,confirmado,espera,atendimento,finalizado,falta,cancelado',
            'cancellation_reason' => 'nullable|string'
        ]);

        $appointment->status = $validated['status'];
        
        if ($validated['status'] === 'cancelado') {
            $appointment->cancellation_reason = $validated['cancellation_reason'];
        } else {
            $appointment->cancellation_reason = null; 
        }

        $appointment->save();

        return redirect()->back()->with('success', 'Status atualizado com sucesso!');
    }

    public function destroy(Appointment $appointment)
    {
        $appointment->delete();
        return redirect()->route('appointments.index')->with('success', 'Agendamento removido.');
    }
}