<?php

namespace App\Services\Mail;

use Illuminate\Support\Facades\View;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;

abstract class AbstractMailService implements MailServiceInterface
{
    /**
     * Send email using a Blade template
     *
     * @param array $to Array of email addresses
     * @param string $template Blade template name (without .blade.php)
     * @param array $variables Variables to pass to the template
     * @param array $attachments Array of file paths to attach
     * @return array Result from the mail service
     */
    public function sendTemplate(array $to, string $template, array $variables = [], array $attachments = []): array
    {
        $html = View::make("emails.{$template}", $variables)->render();
        $subject = $variables['subject'] ?? 'No subject';
        $text = $variables['text'] ?? null;

        return $this->sendRawViaService($to, $subject, $html, $text, $attachments);
    }

    /**
     * Send raw HTML email
     *
     * @param array $to Array of email addresses
     * @param string $subject Email subject
     * @param string $html HTML content
     * @param string|null $text Plain text content (optional)
     * @param array $attachments Array of file paths to attach
     * @return array Result from the mail service
     */
    public function sendRaw(array $to, string $subject, string $html, string $text = null, array $attachments = []): array
    {
        return $this->sendRawViaService($to, $subject, $html, $text, $attachments);
    }

    /**
     * Abstract method to be implemented by concrete services
     *
     * @param array $to Array of email addresses
     * @param string $subject Email subject
     * @param string $html HTML content
     * @param string|null $text Plain text content (optional)
     * @param array $attachments Array of file paths to attach
     * @return array Result from the mail service
     */
    abstract protected function sendRawViaService(array $to, string $subject, string $html, ?string $text, array $attachments = []): array;
}
