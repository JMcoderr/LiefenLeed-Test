<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8" />
    <title>Bedankt voor je aanvraag</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f7f9fc; color: #1f2937; padding: 20px; }
        .container { max-width: 600px; background: white; margin: auto; padding: 20px; border-radius: 8px; box-shadow: 0 2px 6px rgb(31, 41, 55); }
        h1 { color: #1f2937; }
        /* Geen aparte achtergrond of monospace meer */
        .details p { font-family: Arial, sans-serif; background: transparent; margin: 0 0 10px 0; }
    </style>
</head>
<body>
<div class="container">
    <h1>Bedankt voor je aanvraag!</h1>
    <p>Beste {{ $account_name }},</p>
    <p>We hebben je aanvraag succesvol ontvangen. Hieronder vind je de details:</p>
    <div class="details">
        <p><strong>Gelegenheid:</strong> {{ $event_cost->event->title }} (&euro;{{ $event_cost->amount }}) van {{ $recipient->name }}</p>
        <p>
            Het bedrag wordt overgemaakt naar: <br>
            <strong>IBAN:</strong> {{ $iban }}
        </p>
    </div>

    <p>Met vriendelijke groet,<br>Het Team</p>
</div>
</body>
</html>
