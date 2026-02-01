# SmartAds Deployment Guide

## Automatisk Deployment

### 1. GitHub Actions (Anbefalet)
- Push til `main` branch trigger automatisk deployment
- Workflow: `.github/workflows/deploy.yml`
- Bygger frontend assets, kører migrations, optimerer cache

### 2. Manuel Deployment Script
Kør `./deploy.sh` på live server efter git pull:

```bash
# Pull seneste ændringer
git pull origin main

# Kør deployment script
./deploy.sh
```

## Script Detaljer

### deploy.sh
Scriptet udfører følgende trin:

1. **Backend Setup**
   - `composer install --no-dev --optimize-autoloader`
   - `php artisan migrate --force`

2. **Cache Management**
   - Rydder alle caches (cache, config, routes, views, events)
   - Optimerer caches for bedre performance

3. **Frontend Build**
   - `npm ci --production`
   - `npm run build`

4. **Queue Management**
   - `php artisan queue:restart`

## Før Første Deployment

### 1. Environment Setup
```bash
# Kopier environment fil
cp backend/.env.example backend/.env

# Sæt nødvendige variabler
APP_ENV=production
APP_DEBUG=false
APP_URL=https://din-domain.dk

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=smartads
DB_USERNAME=din_user
DB_PASSWORD=din_password

# Mail service
MAIL_SERVICE=local
MAIL_FROM_EMAIL=noreply@din-domain.dk
MAIL_FROM_NAME=SmartAds
```

### 2. File Permissions
```bash
# Sæt korrekte permissions
chmod -R 755 backend/storage
chmod -R 755 backend/bootstrap/cache
chown -R www-data:www-data backend/storage
chown -R www-data:www-data backend/bootstrap/cache
```

### 3. Webserver Setup
**Apache:**
```apache
<VirtualHost *:80>
    ServerName din-domain.dk
    DocumentRoot /path/to/smartads/backend/public
    
    <Directory /path/to/smartads/backend/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

**Nginx:**
```nginx
server {
    listen 80;
    server_name din-domain.dk;
    root /path/to/smartads/backend/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

## Efter Deployment

### 1. Tjek Status
```bash
cd backend
php artisan about
php artisan queue:failed
```

### 2. Monitor Logs
```bash
# Tjek Laravel logs
tail -f storage/logs/laravel.log

# Tjek queue logs
php artisan queue:failed
```

### 3. Verificer Frontend
- Tjek at frontend assets er bygget korrekt
- Verificer at `backend/public/assets/` indeholder de nye filer

## Troubleshooting

### Cache Problemer
```bash
php artisan optimize:clear
php artisan optimize
```

### Queue Problemer
```bash
php artisan queue:restart
php artisan queue:work --timeout=60
```

### Migration Problemer
```bash
php artisan migrate:status
php artisan migrate --force --step
```

## Production Tips

1. **Brug HTTPS** - Sæt SSL certifikat op
2. **Cron Jobs** - Sæt Laravel scheduler op:
   ```bash
   * * * * * cd /path/to/smartads/backend && php artisan schedule:run >> /dev/null 2>&1
   ```
3. **Backup** - Tag regelmæssige database backups
4. **Monitoring** - Overvåg server performance og fejllogs
