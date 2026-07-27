<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\TicketType */
class TicketTypeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'price' => (float) $this->price,
            'quantity_available' => $this->quantity_available,
            'quantity_sold' => $this->quantity_sold,
            'remaining' => $this->remaining(),
            'max_per_order' => $this->max_per_order,
        ];
    }
}
