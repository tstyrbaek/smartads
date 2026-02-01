<!DOCTYPE html>
<html lang="da">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Din annonce er klar - SmartAds</title>
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
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
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
        .ad-preview {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #28a745;
        }
        .ad-image {
            width: 100%;
            max-width: 300px;
            height: auto;
            border-radius: 8px;
            margin: 15px 0;
        }
        .button {
            display: inline-block;
            background: #28a745;
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
        .success-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="success-icon">✅</div>
        <h1>Din annonce er klar!</h1>
    </div>
    
    <div class="content">
        <p>Hej {{ $name }},</p>
        
        <p>Din annonce for <strong>{{ $company_name }}</strong> er blevet genereret med succes!</p>
        
        <div class="ad-preview">
            @if($image_url)
                <h3>Resultat:</h3>
                <img src="{{ $image_url }}" alt="Annonce billede" class="ad-image">
            @endif
        </div>
        
        <p style="text-align: center;">
            <a href="{{ $ad_link }}" class="button">Se alle annoncer</a>
        </p>
        
        <p>Du kan nu downloade og bruge din annonce i dine markedsføringsmaterialer. Annoncebilledet er vedhæftet til denne email.</p>
        
        <p>Med venlig hilsen,<br>Teamet bag SmartAds</p>
    </div>
    
    <div class="footer">
        <p>Denne e-mail blev sendt automatisk. Du kan ikke svare på denne besked.</p>
    </div>
</body>
</html>
