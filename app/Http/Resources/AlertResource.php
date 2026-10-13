<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One alert shape for every consumer - the rescuee app, the dashboard, and
 * tests - so a change to the payload only has to happen once.
 */
class AlertResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'facility' => $this->facility,
            'type' => $this->type,
            'location' => $this->location,
            'details' => $this->details,
            'status' => $this->status,
            'open' => $this->isOpen(),
            'acknowledged_at' => $this->acknowledged_at?->toIso8601String(),
            'resolved_at' => $this->resolved_at?->toIso8601String(),
            'response_seconds' => $this->responseSeconds(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
