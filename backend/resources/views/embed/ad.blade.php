<!doctype html>
<html lang="da">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>{{ $ad->title ?? 'Annonce' }}</title>
    <style>
        .smartads-card{font-family:ui-sans-serif,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Arial,"Noto Sans","Liberation Sans",sans-serif;max-width:640px;border:1px solid #e5e7eb;border-radius:12px;overflow:hidden}
        .smartads-img{width:100%;height:auto;display:block}
        .smartads-body{padding:16px}
        .smartads-title{font-weight:700;margin:0 0 8px 0;font-size:18px}
        .smartads-text{white-space:pre-wrap;margin:0;color:#111827;line-height:1.5;font-size:14px}
    </style>
</head>
<body>
    <div class="smartads-card">
        @if (is_string($ad->local_file_path) && $ad->local_file_path !== '')
            <img class="smartads-img" src="{{ asset('storage/' . $ad->local_file_path) }}" alt="Annonce" />
        @endif

        <div class="smartads-body">
            @if($ad->title)
                <div class="smartads-title">{{ $ad->title }}</div>
            @endif
            <div class="smartads-text">{{ $ad->text }}</div>
        </div>
    </div>
</body>
</html>
