# SmartAdd (v1)

## URLs (din nuværende opsætning)
- Backend: https://smartadd.ddev.site:33005
- Frontend: http://localhost:5173

## Opsætning

### 1) Start DDEV
Kør i projektroden:
- `ddev start`

Test backend:
- `https://smartadd.ddev.site:33005/api/health`

### 2) Backend config
Opret/ret:
- `backend/config.php`

Sæt:
- `nanobanana.api_key`

Bemærk:
- `callback_url` er sat til `https://smartadd.ddev.site:33005/api/nanobanana/callback`

### 3) Frontend
Kør i `frontend/`:
- `npm install`
- `cp .env.example .env`
- `npm run dev`

Åbn:
- `http://localhost:5173`

## Kendt begrænsning (lokal callback)
NanoBanana kræver `callBackUrl`. Mange eksterne services kan **ikke** nå en lokal DDEV URL.

Hvis generationen aldrig bliver `success`, er det typisk fordi callback ikke når frem.

Næste løsning (v1.1) er at lave polling i backend mod NanoBanana `/record-info` i stedet for callback.
