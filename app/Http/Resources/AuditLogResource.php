<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuditLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'action'      => $this->action,
            'severity'    => $this->severity,
            'category'    => $this->category,
            'entity_type' => $this->entity_type,
            'entity_id'   => $this->entity_id,
            'old_values'  => $this->old_values,
            'new_values'  => $this->new_values,
            'metadata'    => $this->metadata,
            'ip_address'  => $this->ip_address,
            'user_agent'  => $this->user_agent,
            'request_id'  => $this->request_id,
            'performer'   => $this->whenLoaded('performer', fn () => [
                'id'    => $this->performer->id,
                'name'  => $this->performer->name,
                'email' => $this->performer->email,
            ]),
            'performed_by' => $this->user_id,
            'created_at'  => $this->created_at?->toISOString(),
        ];
    }
}
