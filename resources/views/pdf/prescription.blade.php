<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <title>Receituário - {{ $prescription->patient->name }}</title>
    <style>
        body { font-family: Helvetica, Arial, sans-serif; color: #1f2937; line-height: 1.5; margin: 20px 40px; }
        .header { text-align: center; border-bottom: 2px solid #10b981; padding-bottom: 15px; margin-bottom: 30px; }
        .clinic-name { font-size: 28px; font-weight: bold; color: #065f46; letter-spacing: 1px; }
        .clinic-sub { font-size: 14px; color: #6b7280; }
        .patient-info { margin-bottom: 30px; background: #f9fafb; padding: 15px; border-radius: 5px; font-size: 14px;}
        .section-title { font-size: 12px; font-weight: bold; color: #9ca3af; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 10px; border-bottom: 1px solid #e5e7eb; padding-bottom: 5px;}
        .medication { margin-bottom: 20px; padding-left: 15px; border-left: 3px solid #10b981; }
        .med-name { font-size: 16px; font-weight: bold; color: #111827; }
        .med-dosage { font-size: 14px; color: #4b5563; font-weight: normal; }
        .med-instructions { font-size: 14px; margin-top: 5px; color: #374151; }
        .notes-box { margin-top: 30px; padding: 15px; background-color: #fffbeb; border-left: 4px solid #f59e0b; font-size: 13px; color: #92400e; }
        .footer { position: fixed; bottom: 30px; left: 0; width: 100%; text-align: center; }
        .signature-line { border-top: 1px solid #111827; width: 300px; margin: 0 auto; padding-top: 5px; font-weight: bold; }
        .validation-code { font-family: monospace; background: #ecfdf5; border: 1px solid #a7f3d0; padding: 3px 8px; font-size: 14px; font-weight: bold; color: #065f46;}
        .footer-text { font-size: 11px; color: #9ca3af; margin-top: 15px; }
    </style>
</head>
<body>

    <div class="header">
        <div class="clinic-name">Clínica Curae</div>
        <div class="clinic-sub">Receituário Médico e Prescrição</div>
    </div>

    <div class="patient-info">
        <strong>Paciente:</strong> {{ $prescription->patient->name }}<br>
        <strong>Data da Consulta:</strong> {{ $prescription->created_at->format('d/m/Y') }}
    </div>

    <div class="section-title">Uso Prescrito</div>

    @foreach($prescription->medications as $med)
        <div class="medication">
            <div class="med-name">{{ $med['name'] }} <span class="med-dosage">({{ $med['dosage'] }})</span></div>
            <div class="med-instructions"><strong>Uso:</strong> {{ $med['instructions'] }}</div>
        </div>
    @endforeach

    @if($prescription->notes)
        <div class="notes-box">
            <strong>Orientações Adicionais:</strong><br>
            {{ $prescription->notes }}
        </div>
    @endif

    <div class="footer">
        <div class="signature-line">
            Assinatura e Carimbo do Profissional
        </div>
        <div class="footer-text">
            Código de Validação Digital: <span class="validation-code">{{ $prescription->verification_code }}</span><br>
            Documento emitido digitalmente via sistema Curae.
        </div>
    </div>

</body>
</html>