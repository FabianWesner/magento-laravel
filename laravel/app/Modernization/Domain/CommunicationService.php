<?php

namespace App\Modernization\Domain;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

class CommunicationService
{
    /**
     * @return array<string, mixed>
     */
    public function plan(string $kind, string $recipient): array
    {
        $normalizedKind = str_replace('_', ' ', $kind);

        return [
            'kind' => $kind,
            'recipient' => $recipient,
            'newsletter' => $kind === 'newsletter',
            'product alert' => $normalizedKind === 'product alert',
            'send to friend' => $normalizedKind === 'send to friend',
            'email' => true,
            'mail_artifact' => Mail::class,
            'notification_artifact' => Notification::class,
            'queue' => 'domain-communications',
        ];
    }
}
