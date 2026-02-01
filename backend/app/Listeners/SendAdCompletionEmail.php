<?php

namespace App\Listeners;

use App\Events\AdUpdated;
use App\Services\Mail\MailServiceInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class SendAdCompletionEmail implements ShouldQueue
{
    public function __construct(
        private MailServiceInterface $mailService
    ) {}

    public function handle(AdUpdated $event): void
    {
        // Only send email for completed ads (success or failed)
        if (!in_array($event->status, ['success', 'failed'])) {
            return;
        }

        try {
            $ad = \App\Models\Ad::with('user')->find($event->adId);
            
            if (!$ad || !$ad->user) {
                Log::warning('Ad completion email: Ad or user not found', [
                    'adId' => $event->adId,
                    'companyId' => $event->companyId
                ]);
                return;
            }

            $user = $ad->user;
            $company = $ad->company;
            
            $template = $event->status === 'success' ? 'ad_completed' : 'ad_failed';
            $subject = $event->status === 'success' 
                ? 'Din annonce er klar - SmartAds' 
                : 'Fejl i annonce generering - SmartAds';

            $variables = [
                'name' => $user->name,
                'ad_text' => $ad->text,
                'company_name' => $company->name,
                'status' => $event->status,
                'ad_link' => url("/ads"),
                'subject' => $subject,
            ];

            if ($event->status === 'success' && $event->localFilePath) {
                $variables['image_url'] = url($event->localFilePath);
            }

            if ($event->status === 'failed') {
                $variables['error'] = $ad->error;
            }

            // Prepare attachments for successful ads
            $attachments = [];
            if ($event->status === 'success' && $event->localFilePath) {
                $fullPath = Storage::disk('public')->path($event->localFilePath);
                if (file_exists($fullPath)) {
                    $attachments[] = $fullPath;
                }
            }

            $this->mailService->sendTemplate(
                [$user->email],
                $template,
                $variables,
                $attachments
            );

            Log::info('Ad completion email sent', [
                'adId' => $event->adId,
                'userId' => $user->id,
                'status' => $event->status
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send ad completion email', [
                'adId' => $event->adId,
                'companyId' => $event->companyId,
                'status' => $event->status,
                'error' => $e->getMessage()
            ]);
        }
    }
}
