<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8" />
    <title>Aanvraag geaccepteerd</title>
</head>
<body style="font-family: sans-serif; background-color: #f9f9f9; padding: 20px; color: rgb(31,41,55);">
<div style="max-width: 600px; margin: auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 2px 5px rgb(31,41,55);">
    <h2 style="color: rgb(31,41,55);">✅ Je aanvraag is geaccepteerd</h2>

    <p>Goed nieuws! Je aanvraag voor <strong>{{$requestModel->eventCost->event->title ?? 'Niet opgegeven' }}</strong> van <strong>{{ $requestModel->member->name ?? 'Niet bekend' }}</strong> is <strong>goedgekeurd</strong>.</p>

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
            <td style="font-weight: bold; padding: 6px;">Rekeninghouder:</td>
            <td style="padding: 6px;">{{ $requestModel->account_name }}</td>
        </tr>
        <tr>
            <td style="font-weight: bold; padding: 6px;">IBAN:</td>
            <td style="padding: 6px;">{{ $requestModel->iban }}</td>
        </tr>
        <tr>
            <td style="font-weight: bold; padding: 6px;">Datum aanvraag:</td>
            <td style="padding: 6px;">{{ $requestModel->created_at->format('d-m-Y H:i') }}</td>
        </tr>
    </table>

    <p style="margin-top: 30px;">💸 Het bedrag wordt binnenkort overgemaakt. Je hoeft verder niets meer te doen.</p>

    <p style="color: #1f2937;">Bedankt voor je aanvraag,<br />
        Het Lief & Leed Team</p>
</div>
</body>
</html>
