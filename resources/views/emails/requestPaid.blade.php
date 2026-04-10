<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>Aanvraag uitbetaald</title>
</head>
<body style="margin: 0; padding: 32px; background-color: #f9fafb; font-family: 'Segoe UI', Roboto, sans-serif;">

<div style="max-width: 600px; margin: 0 auto; background-color: #ffffff; padding: 32px; border-radius: 12px; border: 1px solid #e5e7eb;">

    <h2 style="color: #1f2937; font-size: 20px; font-weight: 600; margin-bottom: 20px;">
        ✔️ Aanvraag uitbetaald
    </h2>

    <p style="font-size: 14px; color: #1f2937; line-height: 1.6; margin-bottom: 16px;">
        Beste {{ $account_name }},
    </p>

    <p style="font-size: 14px; color: #1f2937; line-height: 1.6; margin-bottom: 24px;">
        De volgende aanvraag is succesvol uitbetaald:
    </p>

    <ul style="font-size: 14px; color: #1f2937; line-height: 1.6; margin-bottom: 24px; padding-left: 20px;">
        <li><strong>Gebeurtenis:</strong> {{ $requestModel->eventCost->event->title ?? 'Onbekend' }} van <strong>{{ $requestModel->member->name ?? 'Niet bekend' }}</strong></li>
        <li><strong>Bedrag:</strong> €{{ number_format($requestModel->eventCost->amount, 2, ',', '.') }}</li>
        <li><strong>Datum:</strong> {{ \Carbon\Carbon::parse($requestModel->paid_at)->format('d-m-Y') }}</li>
    </ul>

    <p style="font-size: 14px; color: #1f2937; line-height: 1.6;">
        Met vriendelijke groet,<br>
        Team Lief & Leed
    </p>

</div>

</body>
</html>

