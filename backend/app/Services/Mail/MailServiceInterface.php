<?php

namespace App\Services\Mail;

interface MailServiceInterface
{
    /**
     * Send email using a Blade template
     *
     * @param array $to Array of email addresses
     * @param string $template Blade template name (without .blade.php)
     * @param array $variables Variables to pass to the template
     * @return array Result from the mail service
     */
    public function sendTemplate(array $to, string $template, array $variables = []): array;

    /**
     * Send raw HTML email
     *
     * @param array $to Array of email addresses
     * @param string $subject Email subject
     * @param string $html HTML content
     * @param string|null $text Plain text content (optional)
     * @return array Result from the mail service
     */
    public function sendRaw(array $to, string $subject, string $html, string $text = null): array;
}
