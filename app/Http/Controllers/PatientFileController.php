<?php

namespace App\Http\Controllers;

use App\Models\PatientFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PatientFileController extends Controller
{
    /**
     * Processa o upload do ficheiro e grava as informações na base de dados.
     */
    public function store(Request $request)
    {
        $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'name' => 'required|string|max:255',
            'file' => 'required|file|max:10240', // Limite máximo de 10MB por ficheiro
            'notes' => 'nullable|string'
        ]);

        // Grava o documento na pasta 'patient_files' dentro do disco público (storage/app/public)
        $path = $request->file('file')->store('patient_files', 'public');

        PatientFile::create([
            'patient_id' => $request->patient_id,
            'name' => $request->name,
            'file_path' => $path,
            'file_type' => $request->file('file')->getClientOriginalExtension(),
            'notes' => $request->notes,
        ]);

        return back()->with('success', 'Arquivo anexado com sucesso ao prontuário!');
    }

    /**
     * Remove o registo da base de dados e elimina o ficheiro físico do servidor.
     */
    public function destroy(PatientFile $patientFile)
    {
        // Verifica se o ficheiro físico existe no disco antes de apagar
        if (Storage::disk('public')->exists($patientFile->file_path)) {
            Storage::disk('public')->delete($patientFile->file_path);
        }
        
        $patientFile->delete();

        return back()->with('success', 'Arquivo excluído permanentemente.');
    }
}