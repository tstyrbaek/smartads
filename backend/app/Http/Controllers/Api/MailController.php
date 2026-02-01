<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Mail\MailServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class MailController extends Controller
{
    public function __construct(
        private MailServiceInterface $mailService
    ) {}

    /**
     * Send email using a template
     */
    public function send(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'to' => ['required', 'array', 'min:1'],
            'to.*' => ['required', 'email'],
            'template' => ['required', 'string'],
            'variables' => ['array'],
            'variables.*' => ['string'],
        ]);

        try {
            $result = $this->mailService->sendTemplate(
                $validated['to'],
                $validated['template'],
                $validated['variables'] ?? []
            );

            return response()->json([
                'success' => $result['status'] === 'sent',
                'result' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send raw HTML email
     */
    public function sendRaw(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'to' => ['required', 'array', 'min:1'],
            'to.*' => ['required', 'email'],
            'subject' => ['required', 'string', 'max:255'],
            'html' => ['required', 'string'],
            'text' => ['nullable', 'string'],
        ]);

        try {
            $result = $this->mailService->sendRaw(
                $validated['to'],
                $validated['subject'],
                $validated['html'],
                $validated['text'] ?? null
            );

            return response()->json([
                'success' => $result['status'] === 'sent',
                'result' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
