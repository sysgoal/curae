<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Professional;
use App\Models\ScheduleBlock;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class AppointmentController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        
        $query = Appointment::with(['patient', 'professional']);
        $blockQuery = ScheduleBlock::with('professional');

        if (!$user->hasRole(['admin', 'secretaria'])) {
            $professional = Professional::where('user_id', $user->id)->first();
            if (!$professional) {
                return redirect()->route('dashboard')->with('error', 'Acesso negado: Perfil não localizado.');
            }
            $query->where('professional_id', $professional->id);
            $blockQuery->where('professional_id', $professional->id);
        } else {
            if ($request->filled('professional_id')) {
                $query->where('professional_id', $request->professional_id);
                $blockQuery->where('professional_id', $request->professional_id);
            }
        }

        $appointments = $query->orderBy('appointment_date', 'asc')->orderBy('start_time', 'asc')->get()->map(function($appt) {
            $dataLimpa = Carbon::parse($appt->appointment_date)->format('Y-m-d');
            $horaInicioLimpa = Carbon::parse($appt->start_time)->format('H:i:s');
            $horaFimLimpa = Carbon::parse($appt->end_time)->format('H:i:s');

            $fullStartTime = $dataLimpa . 'T' . $horaInicioLimpa;
            $fullEndTime = $dataLimpa . 'T' . $horaFimLimpa;

            return [
                'id' => $appt->id,
                'patient_id' => $appt->patient_id,
                'professional_id' => $appt->professional_id,
                'start_time' => $fullStartTime,
                'end_time' => $fullEndTime,
                'status' => $appt->status,
                'notes' => $appt->notes,
                'patient' => $appt->patient,
                'professional' => $appt->professional,
                'formatted_start' => Carbon::parse($dataLimpa . ' ' . $horaInicioLimpa)->format('d/m/Y, H:i'),
                'formatted_end_time_only' => Carbon::parse($horaFimLimpa)->format('H:i'),
            ];
        });

        $scheduleBlocks = $blockQuery->where('end_time', '>=', now())->orderBy('start_time', 'asc')->get()->map(function($block) {
            return [
                'id' => $block->id,
                'professional_id' => $block->professional_id,
                'start_time' => Carbon::parse($block->start_time)->format('Y-m-d\TH:i:s'),
                'end_time' => Carbon::parse($block->end_time)->format('Y-m-d\TH:i:s'),
                'reason' => $block->reason,
                'professional' => $block->professional,
                'formatted_start' => Carbon::parse($block->start_time)->format('d/m/Y, H:i'),
                'formatted_end' => Carbon::parse($block->end_time)->format('d/m/Y, H:i'),
            ];
        });

        if ($user->hasRole(['admin', 'secretaria'])) {
            $professionals = Professional::with('user')
                ->where('is_active', true)
                ->whereHas('user', function ($query) {
                    $query->whereHas('roles', function ($roleQuery) {
                        $roleQuery->whereNotIn('name', ['admin', 'secretaria']);
                    });
                })
                ->orderBy('name')
                ->get();
        } else {
            $professionals = Professional::where('user_id', $user->id)->get();
        }

        $patients = Patient::orderBy('name')->get();

        return Inertia::render('Appointments/Index', [
            'appointments' => $appointments,
            'scheduleBlocks' => $scheduleBlocks,
            'professionals' => $professionals,
            'patients' => $patients,
            'filters' => $request->only('professional_id')
        ]);
    }

    public function store(Request $request)
    {
        $user = auth()->user();

        $rules = [
            'patient_id' => 'required|exists:patients,id',
            'start_time' => 'required|string', 
            'end_time' => 'required|string',
            'status' => 'required|string|in:agendado,concluido,cancelado',
            'notes' => 'nullable|string'
        ];

        if ($user->hasRole(['admin', 'secretaria'])) {
            $rules['professional_id'] = 'required|exists:professionals,id';
        }

        $validated = $request->validate($rules, [
            'professional_id.required' => 'É obrigatório selecionar um profissional de saúde.',
            'professional_id.exists' => 'O usuário selecionado não é um profissional de saúde válido.'
        ]);

        if ($user->hasRole(['admin', 'secretaria'])) {
            $professionalId = $validated['professional_id'];
        } else {
            $professional = Professional::where('user_id', $user->id)->firstOrFail();
            $professionalId = $professional->id;
        }

        $startTime = Carbon::parse($validated['start_time'])->format('Y-m-d H:i:s');
        $endTime = Carbon::parse($validated['end_time'])->format('Y-m-d H:i:s');
        $appointmentDate = Carbon::parse($validated['start_time'])->format('Y-m-d');

        $isBlocked = ScheduleBlock::where('professional_id', $professionalId)
            ->where(function($query) use ($startTime, $endTime) {
                $query->where('start_time', '<', $endTime)
                      ->where('end_time', '>', $startTime);
            })->exists();

        if ($isBlocked) {
            return back()->withErrors([
                'start_time' => 'Erro: O profissional possui um bloqueio de agenda neste horário.'
            ]);
        }

        $hasAppointmentConflict = Appointment::where('professional_id', $professionalId)
            ->where('status', '!=', 'cancelado')
            ->where(function($query) use ($startTime, $endTime) {
                $query->where('start_time', '<', $endTime)
                      ->where('end_time', '>', $startTime);
            })->exists();

        if ($hasAppointmentConflict) {
            return back()->withErrors([
                'start_time' => 'Erro: Já existe um agendamento ativo para este profissional neste intervalo de horário.'
            ]);
        }

        Appointment::create([
            'patient_id' => $validated['patient_id'],
            'professional_id' => $professionalId,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'appointment_date' => $appointmentDate,
            'status' => $validated['status'],
            'notes' => $validated['notes']
        ]);

        return redirect()->route('appointments.index')->with('success', 'Agendamento registado com sucesso!');
    }

    public function storeBlock(Request $request)
    {
        $user = auth()->user();

        $rules = [
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
            'reason' => 'required|string|max:255'
        ];

        if ($user->hasRole(['admin', 'secretaria'])) {
            $rules['professional_id'] = 'required|exists:professionals,id';
        }

        $validated = $request->validate($rules, [
            'professional_id.required' => 'É obrigatório selecionar um profissional de saúde.',
            'professional_id.exists' => 'O usuário selecionado não é um profissional de saúde válido.'
        ]);

        if ($user->hasRole(['admin', 'secretaria'])) {
            $professionalId = $validated['professional_id'];
        } else {
            $professional = Professional::where('user_id', $user->id)->firstOrFail();
            $professionalId = $professional->id;
        }

        ScheduleBlock::create([
            'professional_id' => $professionalId,
            'start_time' => Carbon::parse($validated['start_time'])->format('Y-m-d H:i:s'),
            'end_time' => Carbon::parse($validated['end_time'])->format('Y-m-d H:i:s'),
            'reason' => $validated['reason'],
        ]);

        return redirect()->back()->with('success', 'Horário bloqueado com sucesso na agenda.');
    }

    public function destroyBlock($id)
    {
        $block = ScheduleBlock::findOrFail($id);
        $user = auth()->user();

        if (!$user->hasRole(['admin', 'secretaria'])) {
            $professional = Professional::where('user_id', $user->id)->first();
            if ($block->professional_id !== $professional->id) {
                return abort(403);
            }
        }

        $block->delete();
        return redirect()->back()->with('success', 'Bloqueio de agenda removido.');
    }

    public function updateStatus(Request $request, $id) 
    { 
        $appointment = Appointment::findOrFail($id);
        $validated = $request->validate([
            'status' => 'required|in:agendado,concluido,cancelado'
        ]);
        
        $appointment->update(['status' => $validated['status']]);
        return redirect()->back()->with('success', 'Status da consulta atualizado!');
    }
    
    public function destroy($id) 
    {  
        Appointment::findOrFail($id)->delete();
        return redirect()->route('appointments.index')->with('success', 'Agendamento removido permanentemente.');
    }
}