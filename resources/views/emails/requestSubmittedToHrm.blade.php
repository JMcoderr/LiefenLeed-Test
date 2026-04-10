<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>Nieuwe aanvraag voor HRM</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f7f7f7; padding: 20px;">
<div style="max-width: 640px; margin: 0 auto; background: #ffffff; border: 1px solid #e5e7eb; border-radius: 10px; padding: 20px;">
    <h2 style="margin-top: 0; color: #1f2937;">Nieuwe Lief en Leed aanvraag</h2>

    <p style="color: #374151;">Er is een nieuwe aanvraag ingediend.</p>

    <ul style="color: #374151; line-height: 1.6;">
        <li><strong>Aanvraag-ID:</strong> {{ $requestModel->id }}</li>
        <li><strong>Aanvrager:</strong> {{ $requestModel->employee_requester }}</li>
        <li><strong>Ontvanger:</strong> {{ $recipient->full_name }} ({{ $recipient->email }})</li>
        <li><strong>Gelegenheid:</strong> {{ $eventCost->event->title ?? 'Onbekend' }}</li>
        <li><strong>Bedrag:</strong> EUR {{ number_format((float) $eventCost->amount, 2, ',', '.') }}</li>
        <li><strong>Status:</strong> {{ $requestModel->status->value ?? $requestModel->status }}</li>
        <li><strong>Ingediend op:</strong> {{ $requestModel->created_at?->format('d-m-Y H:i') }}</li>
    </ul>

    <p style="color: #6b7280; font-size: 13px; margin-top: 16px;">Dit is een automatisch bericht van het Lief en Leed portaal.</p>
</div>
</body>
</html>
