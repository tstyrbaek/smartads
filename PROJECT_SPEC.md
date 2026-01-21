# SmartAdd – v1 projektbeskrivelse (demo annoncesystem)

## Formål
En webapp til at administrere brands og generere annoncebilleder via Google Gemini.

Du kan:
- Logge ind (email + password)
- Administrere brands via roller og rettigheder
- Uploade logo
- Vælge 2 primære farver
- Tilføje firma-beskrivelse og målgruppe-beskrivelse
- Oprette en webannonce ved at skrive annonce-teksten
- Tilføje op til 3 referencebilleder til annonce-generering
- Generere annoncen automatisk via Google Gemini
- Downloade annoncen som PNG

## Scope / afgrænsning (v1)
- Email + password login
- API tokens (Sanctum) for adgang fra andre systemer
- Kun admin kan oprette brugere
- Ingen debug-data gemmes i databasen
- 1 forretning = 1 brand
- Referencebilleder gemmes ikke permanent (slettes efter generering)
- Ingen S3
- Annonce-output er **PNG**
- Annonce-format er **1:1** (kvadrat). Krav: “600x600” – i v1 bruger vi NanoBanana `image_size: "1:1"` og accepterer leverandørens konkrete pixelstørrelse, så længe den er kvadratisk.
- Logo skal være public (backend serverer logo-fil via URL)

## Tech stack
- Frontend: Vue 3 + Vite + TailwindCSS
- Backend: Laravel (PHP)
- Database: MySQL
- Auth: Laravel Sanctum (token-baseret)
- Jobs: Laravel queue + retry/backoff
- Lokalt miljø: DDEV

## Repo-struktur (separeret frontend/backend)
Projekt root:
- `frontend/`  (Vue app)
- `backend/`   (Laravel API)
- `.ddev/`     (DDEV config, webroot peger på `backend/public`)

Backend mapper:
- `backend/public/` (entrypoint + evt. front-controller)
- `backend/src/` (PHP kode)
- `backend/storage/` (Laravel storage)
  - `backend/storage/app/public/uploads/` (logo)
  - `backend/storage/app/public/generated/` (downloadbare annoncer)

## Data (MySQL)
### Roller
- `admin`
- `partner`
- `business`

### Entiteter (overblik)
- `partners`
- `businesses` (kan eksistere uden partner)
- `brands` (1:1 med `businesses`)
- `ads` (tilhører altid et brand, id er UUID)
- `users` (rolle + evt. relation til partner/business)

## Backend API (v1)
Base: `/api`

### Auth
- `POST /api/auth/login`
  - Body: `{ "email": "...", "password": "..." }`
  - Returnerer et API token
- `POST /api/auth/logout`
  - Revoker current token
- `GET /api/me`
  - Returnerer brugerinfo + rolle

### Brand
- `GET /api/brands/me`
  - Returnerer brand for den forretning brugeren har adgang til
- `POST /api/brands/me`
  - `multipart/form-data`
  - Felter:
    - `companyName`
    - `companyDescription`
    - `audienceDescription`
    - `primaryColor1` (hex)
    - `primaryColor2` (hex)
    - `logo` (jpg/png/webp)
  - Gemmer logo i public storage

### Ads
- `POST /api/ads`
  - `multipart/form-data`
  - Felter:
    - `text`
    - `images[]` (valgfrit, op til 3)
  - Opretter ad-record (UUID) og starter async generation via queue
  - Returnerer: `{ "adId": "...", "status": "queued" }`

- `GET /api/ads/{id}`
  - Returnerer ad status (inkl. download link når klar)

- `GET /api/ads/{id}/download`
  - Downloader den genererede PNG fra `backend/storage/generated/`

## Google Gemini (integration)
- Generering kører i queue job
- Referencebilleder (op til 3) sendes som inline image data og slettes efter job
- Generated output gemmes som PNG lokalt i public storage

## Frontend (Vue) UI flow
- Login
- Brand setup
  - Felter: firma, beskrivelser, farver, logo upload
  - “Gem brand”
  - Vis simpelt preview
- Opret annonce
  - Textarea til annonce-tekst
  - Upload op til 3 referencebilleder
  - “Generér annonce”
  - Vis status (poll backend)
  - Når klar: vis billedet + “Download PNG”

## Konfiguration / nøgler
API key skal ligge i backend configfil og må ikke committes.

Derudover:
- DB credentials via `.env`
- Gemini API key via `.env`

## Næste milepæle
- Laravel setup + Sanctum
- Migrations + seed (admin user)
- Brand endpoints + upload
- Ad flow + queue job + retry
- Vue opdateres til auth + nye endpoints
