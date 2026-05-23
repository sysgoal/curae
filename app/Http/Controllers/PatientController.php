<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\Anamnesis;
use App\Models\Evolution;
use App\Models\Prescription;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\PatientFile;

class PatientController extends Controller
{
    public function index()
    {
        $patients = Patient::orderBy('name')->paginate(10);

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
            'date_of_birth' => 'required|date',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255|unique:patients,email',
            'gender' => 'nullable|string|in:M,F,Outro,Prefere não informar',
        ], [
            'cpf.unique' => 'Este CPF já está cadastrado no sistema.',
            'email.unique' => 'Este e-mail já está em uso.',
        ]);

        Patient::create($validated);

        return redirect()->route('patients.index')->with('success', 'Paciente cadastrado com sucesso!');
    }

   public function show(Patient $patient)
    {
        // Usamos o caminho completo (\App\Models\...) por precaução
        $anamneses = \App\Models\Anamnesis::where('patient_id', $patient->id)->latest()->get();
        $evolutions = \App\Models\Evolution::where('patient_id', $patient->id)->latest()->get();
        
        // Mantive a versão sem 'with(medications)' baseada na sua última mensagem
        $prescriptions = \App\Models\Prescription::where('patient_id', $patient->id)->latest()->get();
        
        // A linha que estava a causar o erro, agora com o caminho completo da classe
        $files = \App\Models\PatientFile::where('patient_id', $patient->id)->latest()->get();

        return Inertia::render('Patients/Show', [
            'patient' => $patient,
            'anamneses' => $anamneses,
            'evolutions' => $evolutions,
            'prescriptions' => $prescriptions,
            'files' => $files,
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
            'date_of_birth' => 'required|date',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255|unique:patients,email,' . $patient->id,
            'gender' => 'nullable|string|in:M,F,Outro,Prefere não informar',
        ], [
            'cpf.unique' => 'Este CPF já está cadastrado em outro paciente.',
            'email.unique' => 'Este e-mail já está em uso por outro paciente.',
        ]);

        $patient->update($validated);

        return redirect()->route('patients.index')->with('success', 'Cadastro atualizado com sucesso!');
    }

    public function destroy(Patient $patient)
    {
        $patient->delete();

        return redirect()->route('patients.index')->with('success', 'Paciente removido com sucesso!');
    }
}