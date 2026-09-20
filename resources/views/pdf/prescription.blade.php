<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Receituário Clínico - {{ $prescription->verification_code }}</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; color: #1f2937; margin: 0; padding: 10px; line-height: 1.5; }
        .header { text-align: center; border-bottom: 3px solid #4f46e5; padding-bottom: 12px; margin-bottom: 30px; }
        .clinic-name { font-size: 28px; font-weight: bold; color: #4f46e5; text-transform: uppercase; letter-spacing: 1px; }
        .clinic-sub { font-size: 11px; color: #6b7280; text-transform: uppercase; margin-top: 4px; }
        
        .section-title { font-size: 11px; font-weight: bold; text-transform: uppercase; color: #4f46e5; letter-spacing: 1px; margin-bottom: 10px; border-bottom: 1px solid #e5e7eb; padding-bottom: 4px; }
        
        .patient-info { width: 100%; border-collapse: collapse; margin-bottom: 35px; background-color: #f9fafb; border: 1px solid #e5e7eb; }
        .patient-info td { padding: 10px; font-size: 13px; }
        
        .medication-box { margin-bottom: 25px; padding-left: 15px; border-left: 4px solid #10b981; page-break-inside: avoid; }
        .medication-title { font-size: 16px; font-weight: bold; color: #111827; }
        .medication-instructions { font-size: 13px; color: #4b5563; margin-top: 4px; font-style: italic; }
        
        .notes-section { margin-top: 40px; page-break-inside: avoid; }
        .notes-content { font-size: 13px; color: #374151; white-space: pre-wrap; background-color: #f3f4f6; padding: 15px; border-radius: 6px; }
        
        .footer { position: absolute; bottom: 40px; left: 0; right: 0; text-align: center; }
        .signature-line { width: 280px; border-bottom: 1px solid #9ca3af; margin: 0 auto 6px auto; }
        .doctor-name { font-size: 14px; font-weight: bold; color: #111827; }
        .doctor-spec { font-size: 11px; color: #6b7280; }
        
        .security-box { margin-top: 30px; border: 1px dashed #10b981; background-color: #f0fdf4; padding: 10px; display: inline-block; border-radius: 6px; }
        .security-title { font-size: 10px; font-weight: bold; color: #047857; text-transform: uppercase; }
        .security-code { font-family: monospace; font-size: 15px; font-weight: bold; color: #065f46; margin-top: 2px; }
    </style>
</head>
<body>

    <div class="header">
        <div class="clinic-name">Curae</div>
        <div class="clinic-sub">Sistema Integrado de Prontuário e Triagem Clínica</div>
    </div>

    <div class="section-title">Paciente</div>
    <table class="patient-info">
        <tr>
            <td><strong>Nome:</strong> {{ $prescription->patient->name }}</td>
            <td><strong>CPF:</strong> {{ $prescription->patient->cpf }}</td>
        </tr>
        <tr>
            <td><strong>Data de Emissão:</strong> {{ \Carbon\Carbon::parse($prescription->created_at)->format('d/m/Y H:i') }}</td>
            <td><strong>Identificação:</strong> Registo Digital Interno</td>
        </tr>
    </table>

    <div class="section-title">Medicamentos / Formulações</div>
    @foreach($prescription->medications as $index => $med)
        <div class="medication-box">
            <div class="medication-title">{{ $index + 1 }}. {{ $med['name'] }} ———————— {{ $med['dosage'] }}</div>
            <div class="medication-instructions">Uso: {{ $med['instructions'] }}</div>
        </div>
    @endforeach

    @if(!empty($prescription->notes))
        <div class="notes-section">
            <div class="section-title">Orientações Gerais ao Paciente</div>
            <div class="notes-content">{{ $prescription->notes }}</div>
        </div>
    @endif

    <div class="footer">
        <div class="signature-line"></div>
        <div class="doctor-name">{{ $prescription->professional->name }}</div>
        <div class="doctor-spec">
            {{ $prescription->professional->profession }} 
            @if($prescription->professional->specialty) | {{ $prescription->professional->specialty }} @endif
            @if($prescription->professional->council_type) <br> {{ $prescription->professional->council_type }}: {{ $prescription->professional->council_number }} @endif
        </div>

        <div class="security-box">
            <div class="security-title">Código de Autenticação Digital</div>
            <div class="security-code">{{ $prescription->verification_code }}</div>
        </div>
    </div>

</body>
</html>