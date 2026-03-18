<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class AnalysisCompleted implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly int $analysisId,
        public readonly int $userId,
    ) {}

    public function broadcastOn(): Channel
    {
        return new PrivateChannel("analysis.{$this->userId}");
    }

    /**
     * @return array<string, int>
     */
    public function broadcastWith(): array
    {
        return ['analysis_id' => $this->analysisId];
    }
}
