<!DOCTYPE html>
<html lang="da">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nulstil adgangskode - SmartAds</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
            border-radius: 10px 10px 0 0;
        }
        .content {
            background: #f8f9fa;
            padding: 30px;
            border-radius: 0 0 10px 10px;
        }
        .button {
            display: inline-block;
            background: #667eea;
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            color: #666;
            font-size: 14px;
        }
        .warning {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 5px;
            padding: 15px;
            margin: 20px 0;
            color: #856404;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Nulstil din adgangskode</h1>
    </div>
    
    <div class="content">
        <p>Hej {{ $name }},</p>
        
        <p>Vi har modtaget en anmodning om at nulstille din adgangskode til SmartAds. Hvis du ikke har anmodet om dette, kan du ignorere denne email.</p>
        
        <p style="text-align: center;">
            <a href="{{ $reset_link }}" class="button">Nulstil adgangskode</a>
        </p>
        
        <div class="warning">
            <strong>Vigtigt:</strong> Dette link udløber om 1 time af sikkerhedsmæssige årsager.
        </div>
        
        <p>Hvis knappen ikke virker, kan du kopiere og indsætte dette link i din browser:</p>
        <p style="word-break: break-all; background: #e9ecef; padding: 10px; border-radius: 5px;">
            {{ $reset_link }}
        </p>
        
        <p>Hvis du har spørgsmål eller har brug for hjælp, er du velkommen til at kontakte os.</p>
        
        <p>Med venlig hilsen,<br>Teamet bag SmartAds</p>
    </div>
    
    <div class="footer">
        <p>Denne e-mail blev sendt automatisk. Du kan ikke svare på denne besked.</p>
    </div>
</body>
</html>
