<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Solicitação de Exames</title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            color: #333;
            line-height: 1.6;
            margin: 40px;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #4f46e5;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .logo-text {
            font-size: 28px;
            font-weight: bold;
            color: #4f46e5;
            letter-spacing: 2px;
        }
        .title {
            font-size: 18px;
            font-weight: bold;
            text-transform: uppercase;
            margin-top: 10px;
            color: #555;
        }
        .info-box {
            background-color: #f9fafb;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 30px;
            border: 1px solid #e5e7eb;
        }
        .info-box p {
            margin: 5px 0;
            font-size: 14px;
        }
        .exam-list {
            margin-top: 20px;
        }
        .exam-item {
            padding: 10px 0;
            border-bottom: 1px dashed #ccc;
            font-size: 15px;
            font-weight: bold;
        }
        .notes-box {
            margin-top: 30px;
            font-size: 14px;
        }
        .footer {
            margin-top: 100px;
            text-align: center;
        }
        .signature {
            border-top: 1px solid #000;
            width: 300px;
            margin: 0 auto;
            padding-top: 10px;
        }
        .signature p {
            margin: 3px 0;
            font-size: 14px;
        }
        .date {
            text-align: right;
            font-size: 12px;
            color: #888;
            margin-top: 50px;
        }
    </style>
</head>
<body>

    <div class="header">
        <div class="logo-text">CURAE</div>
        <div class="title">Guia de Solicitação de Exames</div>
    </div>

    <div class="info-box">
        <p><strong>Paciente:</strong> {{ $examRequest->patient->name }}</p>
        <p><strong>Data de Nascimento:</strong> {{ \Carbon\Carbon::parse($examRequest->patient->date_of_birth)->format('d/m/Y') }}</p>
        
        @if($examRequest->clinical_indication)
            <p style="margin-top: 15px;"><strong>Indicação Clínica / CID:</strong> {{ $examRequest->clinical_indication }}</p>
        @endif
    </div>

    <div class="exam-list">
        <h3 style="color: #4f46e5; margin-bottom: 15px;">Exames Solicitados:</h3>
        
        @foreach($examRequest->exams as $index => $exam)
            <div class="exam-item">
                {{ $index + 1 }}. {{ $exam }}
            </div>
        @endforeach
    </div>

    @if($examRequest->notes)
        <div class="notes-box">
            <p><strong>Observações ao Laboratório:</strong><br>
            {{ $examRequest->notes }}</p>
        </div>
    @endif

    <div class="footer">
        <div class="signature">
            <p><strong>{{ $examRequest->professional->name }}</strong></p>
            <p>{{ $examRequest->professional->specialty ?? 'Médico Assistente' }}</p>
            <p>Documento Profissional: {{ $examRequest->professional->council_number ?? 'Não informado' }}</p>
        </div>
    </div>

    <div class="date">
        Documento gerado digitalmente em {{ $examRequest->created_at->format('d/m/Y \à\s H:i') }}
    </div>

</body>
</html>