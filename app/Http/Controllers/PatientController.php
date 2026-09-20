<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\Appointment;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PatientController extends Controller
{
    public function index()
    {
        $patients = Patient::orderBy('name')->get();
        return Inertia::render('Patients/Index', [
            'patients' => $patients
        ]);
    }

    public function create()
    {
        return Inertia::render('Patients/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'cpf' => 'required|string|max:14|unique:patients,cpf',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|string|email|max:255',
            'date_of_birth' => 'required|date',
        ]);

        Patient::create($validated);

        return redirect()->route('patients.index')
            ->with('success', 'Paciente cadastrado com sucesso!');
    }

    public function show(Patient $patient)
    {
        $lastAnamnesis = $patient->anamneses()->latest()->first();

        $lastAppointment = Appointment::where('patient_id', $patient->id)
            ->orderBy('appointment_date', 'desc')
            ->first();

        return Inertia::render('Patients/Show', [
            'patient' => $patient,
            'anamneses' => $patient->anamneses()->with('professional')->get(),
            'evolutions' => $patient->evolutions()->with('professional')->get(),
            'prescriptions' => $patient->prescriptions()->with('professional')->get(),
            'examRequests' => $patient->examRequests()->with('professional')->latest()->get(),
            'files' => $patient->files()->get(),
            'last_anamnesis_date' => $lastAnamnesis ? $lastAnamnesis->created_at->toISOString() : null,
            'last_appointment_date' => $lastAppointment ? $lastAppointment->appointment_date : null,
        ]);
    }

    public function edit(Patient $patient)
    {
        return Inertia::render('Patients/Edit', [
            'patient' => $patient
        ]);
    }

    public function update(Request $request, Patient $patient)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'cpf' => 'required|string|max:14|unique:patients,cpf,' . $patient->id,
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|string|email|max:255',
            'date_of_birth' => 'required|date',
        ]);

        $patient->update($validated);

        return redirect()->route('patients.show', $patient->id)
            ->with('success', 'Dados cadastrais atualizados!');
    }

    public function destroy(Patient $patient)
    {
        $patient->delete();
        return redirect()->route('patients.index')
            ->with('success', 'Paciente removido do sistema.');
    }

    public function history(Patient $patient)
    {
        return Inertia::render('Patients/History', [
            'patient' => $patient,
            'anamneses' => $patient->anamneses()->with('professional')->get(),
            'evolutions' => $patient->evolutions()->with('professional')->get(),
            'prescriptions' => $patient->prescriptions()->with('professional')->get(),
            'examRequests' => $patient->examRequests()->with('professional')->get(),
            'files' => $patient->files()->get(),
        ]);
    }
}