<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AdUpdated implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly int $companyId,
        public readonly string $adId,
        public readonly string $status,
        public readonly ?string $localFilePath,
        public readonly ?string $updatedAt,
    ) {
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('company.' . $this->companyId);
    }

    public function broadcastAs(): string
    {
        return 'ad.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->adId,
            'status' => $this->status,
            'localFilePath' => $this->localFilePath,
            'updatedAt' => $this->updatedAt,
        ];
    }
}
