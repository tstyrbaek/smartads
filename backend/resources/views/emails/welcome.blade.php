<!DOCTYPE html>
<html lang="da">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Velkommen til SmartAds</title>
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
    </style>
</head>
<body>
    <div class="header">
        <h1>Velkommen til SmartAds!</h1>
    </div>
    
    <div class="content">
        <p>Hej {{ $name }},</p>
        
        <p>Velkommen til SmartAds! Vi er glade for at have dig ombord. Med vores platform kan du nemt administrere dine annoncer og optimere din markedsføring.</p>
        
        <p>Kom i gang ved at besøge din profil:</p>
        
        <p style="text-align: center;">
            <a href="{{ $profile_link }}" class="button">Gå til min profil</a>
        </p>
        
        <p>Hvis du har spørgsmål, er du altid velkommen til at kontakte os.</p>
        
        <p>Med venlig hilsen,<br>Teamet bag SmartAds</p>
    </div>
    
    <div class="footer">
        <p>Denne e-mail blev sendt automatisk. Du kan ikke svare på denne besked.</p>
    </div>
</body>
</html>
