# Mail Service Module

A reusable, service-agnostic mail module for Laravel applications. Supports multiple mail providers through a unified interface, making it easy to switch between services without changing application code.

## Features

- **Service Agnostic**: Switch between mail providers via configuration
- **Template Support**: Uses Laravel Blade templates for email content
- **Local Development**: Built-in support for Mailpit/local SMTP
- **Production Ready**: MailJet API integration (easily extensible for other providers)
- **Type Safety**: Full PHP type hints and interfaces
- **Error Handling**: Comprehensive error logging and response handling

## Architecture

The module uses the **Abstract Service Pattern**:

```
MailServiceInterface (Contract)
    ↑
AbstractMailService (Base Implementation)
    ↑
LocalMailService (SMTP/Mailpit)
MailJetService (MailJet API)
```

## Installation in Other Laravel Projects

### 1. Copy Files
Copy the entire `app/Services/Mail/` directory to your project:

```bash
cp -r app/Services/Mail /your-project/app/Services/
```

### 2. Update Configuration
Add to your `config/mail.php`:

```php
// Add at the end of config/mail.php
'default_service' => env('MAIL_SERVICE', 'local'),

'services' => [
    'local' => [
        'driver' => 'smtp',
    ],
    'mailjet' => [
        'api_key' => env('MAILJET_API_KEY'),
        'secret_key' => env('MAILJET_SECRET_KEY'),
        'version' => 'v3.1',
    ],
],
```

### 3. Register Service Provider
Add to `app/Providers/AppServiceProvider.php` in the `register()` method:

```php
use App\Services\Mail\MailServiceInterface;
use App\Services\Mail\LocalMailService;
use App\Services\Mail\MailJetService;

// In register() method:
$this->app->bind(MailServiceInterface::class, function ($app) {
    $service = config('mail.default_service', 'local');
    
    return match($service) {
        'local' => new LocalMailService(),
        'mailjet' => new MailJetService(
            config('mail.services.mailjet.api_key'),
            config('mail.services.mailjet.secret_key')
        ),
        default => throw new \Exception("Unknown mail service: {$service}")
    };
});
```

### 4. Add API Routes (Optional)
If you want API endpoints, add to `routes/api.php`:

```php
Route::middleware('auth:sanctum')->prefix('mail')->group(function () {
    Route::post('/send', [App\Http\Controllers\Api\MailController::class, 'send']);
    Route::post('/send-raw', [App\Http\Controllers\Api\MailController::class, 'sendRaw']);
});
```

### 5. Environment Configuration
Add to your `.env` file:

```env
# For local development (Mailpit)
MAIL_SERVICE=local
MAIL_MAILER=smtp
MAIL_HOST=127.0.0.1
MAIL_PORT=1025
MAIL_FROM_ADDRESS="your-email@example.com"
MAIL_FROM_NAME="Your App Name"

# For production (MailJet)
MAIL_SERVICE=mailjet
MAILJET_API_KEY=your-api-key
MAILJET_SECRET_KEY=your-secret-key
```

### 6. Copy Email Templates (Optional)
Copy the email templates to `resources/views/emails/`:

```bash
cp -r resources/views/emails/* /your-project/resources/views/emails/
```

## Usage

### Backend (Laravel)

```php
use App\Services\Mail\MailServiceInterface;

// Dependency injection
class UserController extends Controller
{
    public function __construct(
        private MailServiceInterface $mailService
    ) {}

    public function sendWelcome($user)
    {
        $result = $this->mailService->sendTemplate(
            [$user->email],
            'welcome',
            [
                'name' => $user->name,
                'profile_link' => route('profile.show', $user)
            ]
        );

        if ($result['status'] === 'sent') {
            // Success handling
        }
    }
}

// Or resolve from container
$mail = app(MailServiceInterface::class);
$mail->sendTemplate(['user@example.com'], 'welcome', ['name' => 'John']);
```

### Frontend (API)

```javascript
// Send email via API
await api.post('/mail/send', {
  to: ['user@example.com'],
  template: 'welcome',
  variables: {
    name: 'John',
    profile_link: 'https://...'
  }
});

// Send raw HTML
await api.post('/mail/send-raw', {
  to: ['user@example.com'],
  subject: 'Custom Subject',
  html: '<h1>Hello World</h1>',
  text: 'Hello World'
});
```

## Available Templates

### welcome.blade.php
Welcome email for new users.

**Variables:**
- `name` (string) - User's name
- `profile_link` (string) - Link to user profile

### activity_reminder.blade.php
Reminder for upcoming activities.

**Variables:**
- `activity_title` (string) - Activity name
- `activity_time` (string) - Start time
- `activity_ends_at` (string) - End time (optional)
- `activity_location` (string) - Location (optional)
- `activity_description` (string) - Description (optional)
- `link` (string) - Activity link (optional)

### diary_entry.blade.php
Notification for new diary entries.

**Variables:**
- `author_name` (string) - Entry author
- `entry_date` (string) - Entry date
- `entry_mood` (string) - Mood emoji
- `entry_title` (string) - Entry title (optional)
- `entry_content` (string) - Entry content
- `diary_link` (string) - Diary link (optional)

## Adding New Mail Services

### 1. Create Service Class
Extend `AbstractMailService`:

```php
<?php

namespace App\Services\Mail;

class SESMailService extends AbstractMailService
{
    public function __construct(
        private string $accessKey,
        private string $secretKey,
        private string $region
    ) {}

    protected function sendRawViaService(array $to, string $subject, string $html, ?string $text): array
    {
        // Implement AWS SES API call here
        try {
            // Your SES implementation
            return [
                'status' => 'sent',
                'service' => 'ses',
                'to' => $to,
                'subject' => $subject,
                'message_id' => $messageId
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'service' => 'ses',
                'error' => $e->getMessage(),
                'to' => $to,
                'subject' => $subject
            ];
        }
    }
}
```

### 2. Update Configuration
Add to `config/mail.php`:

```php
'services' => [
    // ... existing services
    'ses' => [
        'access_key' => env('AWS_ACCESS_KEY_ID'),
        'secret_key' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],
],
```

### 3. Update Service Provider
Add to `AppServiceProvider.php`:

```php
'ses' => new SESMailService(
    config('mail.services.ses.access_key'),
    config('mail.services.ses.secret_key'),
    config('mail.services.ses.region')
),
```

## Environment Switching

### Development (Local)
```env
MAIL_SERVICE=local
MAIL_MAILER=smtp
MAIL_HOST=127.0.0.1
MAIL_PORT=1025
```

### Production (MailJet)
```env
MAIL_SERVICE=mailjet
MAILJET_API_KEY=your-key
MAILJET_SECRET_KEY=your-secret
```

### Production (SES)
```env
MAIL_SERVICE=ses
AWS_ACCESS_KEY_ID=your-key
AWS_SECRET_ACCESS_KEY=your-secret
AWS_DEFAULT_REGION=us-east-1
```

## Testing

### Test Endpoint
Add a test route to verify your setup:

```php
Route::get('/test-mail', function () {
    $mailService = app(\App\Services\Mail\MailServiceInterface::class);
    
    $result = $mailService->sendTemplate(
        ['test@example.com'],
        'welcome',
        ['name' => 'Test User', 'profile_link' => url('/test')]
    );

    return response()->json([
        'message' => 'Test mail sent',
        'result' => $result
    ]);
});
```

### Mailpit Integration
For local development with Mailpit:

```bash
# Start Mailpit
ddev mailpit

# Visit Mailpit UI
open https://your-project.ddev.site:8025
```

## Error Handling

All services return a consistent response format:

```php
// Success
[
    'status' => 'sent',
    'service' => 'local',
    'to' => ['user@example.com'],
    'subject' => 'Welcome',
    'message_id' => '12345' // For API services
]

// Error
[
    'status' => 'error',
    'service' => 'mailjet',
    'error' => 'API key invalid',
    'to' => ['user@example.com'],
    'subject' => 'Welcome'
]
```

## Security Considerations

- Store API keys in environment variables
- Validate email addresses before sending
- Use HTTPS for API endpoints
- Implement rate limiting for mail endpoints
- Sanitize template variables to prevent XSS

## Dependencies

- Laravel 10+
- PHP 8.1+
- Guzzle HTTP (for API services)
- Mailpit (for local development)

## License

This module is open source and can be freely used in any Laravel project.
