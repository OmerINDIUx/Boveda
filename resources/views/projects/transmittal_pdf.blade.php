<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Transmittal {{ $transmittal->code }}</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; font-size: 10pt; color: #333; margin: 0; padding: 0; }
        .header { border-bottom: 2px solid #1e293b; padding-bottom: 10px; margin-bottom: 20px; }
        .logo { font-size: 18pt; font-weight: bold; color: #1e40af; }
        .title { text-align: right; font-size: 14pt; font-weight: bold; color: #64748b; }
        
        .info-grid { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        .info-grid td { padding: 5px; vertical-align: top; }
        .label { font-weight: bold; color: #1e293b; width: 120px; }
        
        .doc-table { width: 100%; border-collapse: collapse; margin-bottom: 40px; }
        .doc-table th { background: #f1f5f9; border: 1px solid #cbd5e1; padding: 8px; text-align: left; font-size: 9pt; }
        .doc-table td { border: 1px solid #cbd5e1; padding: 8px; font-size: 9pt; }
        
        .footer { position: fixed; bottom: 0; width: 100%; font-size: 8pt; color: #94a3b8; text-align: center; border-top: 1px solid #e2e8f0; padding-top: 5px; }
        
        .signature-area { margin-top: 50px; width: 100%; }
        .sig-box { width: 45%; border-top: 1px solid #333; text-align: center; padding-top: 10px; float: left; }
        .sig-box-right { float: right; }
    </style>
</head>
<body>
    <div class="header">
        <table width="100%">
            <tr>
                <td class="logo">BÓVEDA • GAMI</td>
                <td class="title">DOCUMENT TRANSMITTAL</td>
            </tr>
        </table>
    </div>

    <table class="info-grid">
        <tr>
            <td class="label">Proyecto:</td>
            <td>{{ $transmittal->project->name }} ({{ $transmittal->project->code }})</td>
            <td class="label">Código:</td>
            <td>{{ $transmittal->code }}</td>
        </tr>
        <tr>
            <td class="label">Fecha:</td>
            <td>{{ $transmittal->created_at->format('d/m/Y H:i') }}</td>
            <td class="label">Asunto:</td>
            <td>{{ $transmittal->subject }}</td>
        </tr>
        <tr>
            <td class="label">Remitente:</td>
            <td>{{ $transmittal->sender_name }}</td>
            <td class="label">Receptor:</td>
            <td>{{ $transmittal->recipient_name }} ({{ $transmittal->recipient_email }})</td>
        </tr>
    </table>

    <div style="margin-bottom: 20px;">
        <strong>Mensaje / Instrucciones:</strong><br>
        {{ $transmittal->message ?: 'Sin comentarios adicionales.' }}
    </div>

    <h3>Documentos Adjuntos</h3>
    <table class="doc-table">
        <thead>
            <tr>
                <th>ID Técnico</th>
                <th>Descripción / Título</th>
                <th>Rev</th>
                <th>Disciplina</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            @foreach($transmittal->items as $item)
            @php $doc = $item->revision->document; @endphp
            <tr>
                <td>{{ $doc->document_number }}</td>
                <td>{{ $doc->title }}</td>
                <td align="center">{{ $item->revision->revision_code }}</td>
                <td>{{ $doc->discipline->name }}</td>
                <td>{{ $item->revision->status }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="signature-area">
        <div class="sig-box">
            Enviado por: {{ $transmittal->sender_name }}<br>
            <span style="font-size: 8pt; color: #94a3b8;">Firma Digital Bóveda</span>
        </div>
        <div class="sig-box sig-box-right">
            Recibido por: {{ $transmittal->recipient_name }}<br>
            <span style="font-size: 8pt; color: #94a3b8;">Fecha y Firma de Recibido</span>
        </div>
    </div>

    <div class="footer">
        Documento generado automáticamente por Sistema Bóveda - Grupo GAMI. <br>
        Página 1 de 1
    </div>
</body>
</html>
