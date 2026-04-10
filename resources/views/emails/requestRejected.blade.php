<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>Aanvraag afgewezen</title>
</head>
<body style="font-family: sans-serif; background-color: #f9f9f9; padding: 20px; color: #1f2937;">
<div style="max-width: 600px; margin: auto; background-color: #ffffff; padding: 30px; border-radius: 8px; box-shadow: 0 2px 5px rgb(31,41,55);">
    <h2 style="color: #dc3545;">❌ Je aanvraag is afgewezen</h2>

    <p>Helaas is je aanvraag <strong>niet</strong> goedgekeurd.</p>

    <h4 style="margin-top: 20px; margin-bottom: 10px;">📋 Aanvraaggegevens:</h4>
    <table style="width: 100%; border-collapse: collapse;">
        <tr>
            <td style="font-weight: bold; padding: 6px;">Aanvraagtype:</td>
            <td style="padding: 6px;">{{ $requestModel->eventCost->event->title ?? 'Niet opgegeven' }}</td>
        </tr>
        <tr>
            <td style="font-weight: bold; padding: 6px;">Voor:</td>
            <td style="padding: 6px;">{{ $requestModel->member->name ?? 'Niet bekend' }}</td>
        </tr>
        <tr>
            <td style="font-weight: bold; padding: 6px;">Datum aanvraag:</td>
            <td style="padding: 6px;">{{ $requestModel->created_at->format('d-m-Y H:i') }}</td>
        </tr>
    </table>

    @if(!empty($requestModel->reason))
        <p style="margin-top: 20px; white-space: pre-line;"><strong>Reden van afwijzing:</strong><br>
            {{ $requestModel->reason }}
        </p>
    @endif

    <p style="margin-top: 30px;">📬 Heb je vragen over deze beslissing? Neem gerust contact met ons op.</p>

    <p style="color: #1f2937;">Met vriendelijke groet,<br>
        Het Lief & Leed Team</p>
</div>
</body>
</html>

