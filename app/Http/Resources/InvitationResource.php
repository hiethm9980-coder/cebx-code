<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvitationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'email'       => $this->email,
            'name'        => $this->name,
            'status'      => $this->status,
            'is_expired'  => $this->isExpired(),
            'is_usable'   => $this->isUsable(),
            'role'        => $this->whenLoaded('role', fn () => [
                'id'           => $this->role->id,
                'display_name' => $this->role->display_name,
            ]),
            'invited_by'  => $this->whenLoaded('inviter', fn () => [
                'id'   => $this->inviter->id,
                'name' => $this->inviter->name,
            ]),
            'accepted_by' => $this->when($this->accepted_by, fn () => [
                'id' => $this->accepted_by,
            ]),
            'expires_at'   => $this->expires_at?->toISOString(),
            'accepted_at'  => $this->accepted_at?->toISOString(),
            'cancelled_at' => $this->cancelled_at?->toISOString(),
            'last_sent_at' => $this->last_sent_at?->toISOString(),
            'send_count'   => $this->send_count,
            'created_at'   => $this->created_at?->toISOString(),
        ];
    }
}
