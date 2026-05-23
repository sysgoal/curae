<style>
    body { font-family: sans-serif; color: #333; line-height: 1.6; }
    .header { text-align: center; border-bottom: 2px solid #4f46e5; padding-bottom: 10px; }
    .content { margin-top: 20px; white-space: pre-wrap; }
    .footer { margin-top: 50px; font-size: 10px; text-align: center; color: #777; }
</style>
<div class="header">
    <h1>Protocolo de Saúde Integrativa</h1>
    <p>Paciente: {{ $patient_name }} | Data: {{ $date }}</p>
</div>
<div class="content">
    {!! nl2br(e($content)) !!}
</div>
<div class="footer">Gerado via Assistente IA Curae - NotebookLM Integration</div>