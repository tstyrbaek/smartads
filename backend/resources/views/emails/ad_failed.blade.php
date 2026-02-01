<!DOCTYPE html>
<html lang="da">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fejl i annonce generering - SmartAds</title>
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
            background: linear-gradient(135deg, #dc3545 0%, #fd7e14 100%);
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
        .ad-details {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #dc3545;
        }
        .error-box {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 5px;
            padding: 15px;
            margin: 15px 0;
            color: #721c24;
        }
        .button {
            display: inline-block;
            background: #dc3545;
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
        .error-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="error-icon">⚠️</div>
        <h1>Fejl i annonce generering</h1>
    </div>
    
    <div class="content">
        <p>Hej {{ $name }},</p>
        
        <p>Der opstod desværre en fejl under generering af din annonce for <strong>{{ $company_name }}</strong>.</p>
        
        <div class="ad-details">
            <h3>Annonce detaljer:</h3>
            <p><strong>Tekst:</strong> "{{ $ad_text }}"</p>
            <p><strong>Status:</strong> {{ $status }}</p>
            
            @if($error)
                <div class="error-box">
                    <strong>Fejlbesked:</strong><br>
                    {{ $error }}
                </div>
            @endif
        </div>
        
        <p style="text-align: center;">
            <a href="{{ $ad_link }}" class="button">Se alle annoncer</a>
        </p>
        
        <p>Du kan prøve at generere annoncen igen, eller kontakte support hvis problemet fortsætter.</p>
        
        <p>Med venlig hilsen,<br>Teamet bag SmartAds</p>
    </div>
    
    <div class="footer">
        <p>Denne e-mail blev sendt automatisk. Du kan ikke svare på denne besked.</p>
    </div>
</body>
</html>
