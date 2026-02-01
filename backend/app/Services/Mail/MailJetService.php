<?php

namespace App\Services\Mail;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MailJetService extends AbstractMailService
{
    public function __construct(
        private string $apiKey,
        private string $secretKey
    ) {}

    /**
     * Send email via MailJet API
     *
     * @param array $to Array of email addresses
     * @param string $subject Email subject
     * @param string $html HTML content
     * @param string|null $text Plain text content (optional)
     * @param array $attachments Array of file paths to attach
     * @return array Result from the mail service
     */
    protected function sendRawViaService(array $to, string $subject, string $html, ?string $text, array $attachments = []): array
    {
        try {
            $recipients = array_map(fn($email) => ['Email' => $email], $to);

            $payload = [
                'Messages' => [
                    [
                        'From' => [
                            'Email' => config('mail.from.address'),
                            'Name' => config('mail.from.name', 'SmartAds')
                        ],
                        'To' => $recipients,
                        'Subject' => $subject,
                        'HTMLPart' => $html,
                    ]
                ]
            ];

            if ($text) {
                $payload['Messages'][0]['TextPart'] = $text;
            }

            // Add attachments
            if (!empty($attachments)) {
                $attachmentData = [];
                foreach ($attachments as $attachment) {
                    if (file_exists($attachment)) {
                        $filename = basename($attachment);
                        $content = base64_encode(file_get_contents($attachment));
                        $mimeType = mime_content_type($attachment) ?? 'application/octet-stream';
                        
                        $attachmentData[] = [
                            'ContentType' => $mimeType,
                            'Filename' => $filename,
                            'Base64Content' => $content
                        ];
                    }
                }
                
                if (!empty($attachmentData)) {
                    $payload['Messages'][0]['Attachments'] = $attachmentData;
                }
            }

            $response = Http::withBasicAuth($this->apiKey, $this->secretKey)
                ->post('https://api.mailjet.com/v3.1/send', $payload);

            if ($response->successful()) {
                $data = $response->json();
                
                return [
                    'status' => 'sent',
                    'service' => 'mailjet',
                    'to' => $to,
                    'subject' => $subject,
                    'message_id' => $data['Messages'][0]['MessageID'] ?? null,
                    'attachments' => count($attachments),
                    'response' => $data
                ];
            } else {
                Log::error('MailJet API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'to' => $to,
                    'subject' => $subject
                ]);

                return [
                    'status' => 'error',
                    'service' => 'mailjet',
                    'error' => 'MailJet API error: ' . $response->body(),
                    'to' => $to,
                    'subject' => $subject
                ];
            }
        } catch (\Exception $e) {
            Log::error('MailJet service error', [
                'error' => $e->getMessage(),
                'to' => $to,
                'subject' => $subject
            ]);

            return [
                'status' => 'error',
                'service' => 'mailjet',
                'error' => $e->getMessage(),
                'to' => $to,
                'subject' => $subject
            ];
        }
    }
}
