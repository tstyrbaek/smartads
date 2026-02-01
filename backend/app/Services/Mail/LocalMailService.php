<?php

namespace App\Services\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;

class LocalMailService extends AbstractMailService
{
    /**
     * Send email via local SMTP (Mailpit)
     *
     * @param array $to Array of email addresses
     * @param string $subject Email subject
     * @param string $html HTML content
     * @param string|null $text Plain text content (optional)
     * @return array Result from the mail service
     */
    protected function sendRawViaService(array $to, string $subject, string $html, ?string $text): array
    {
        try {
            $mailable = new class($subject, $html, $text) extends Mailable {
                public function __construct(
                    private string $mailSubject,
                    private string $mailHtml,
                    private ?string $mailText
                ) {}

                public function build()
                {
                    $this->subject($this->mailSubject)
                        ->html($this->mailHtml);

                    if ($this->mailText) {
                        $this->text($this->mailText);
                    }
                }
            };

            Mail::to($to)->send($mailable);

            return [
                'status' => 'sent',
                'service' => 'local',
                'to' => $to,
                'subject' => $subject
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'service' => 'local',
                'error' => $e->getMessage(),
                'to' => $to,
                'subject' => $subject
            ];
        }
    }
}
