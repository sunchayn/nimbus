<?php

namespace Workbench\App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'status' => $this->status,
            'total' => $this->total,
            'payment_method' => $this->payment_method,
            'shipping_address' => $this->shipping_address,
            'notes' => $this->notes,
            'items' => $this->items->map(fn ($item) => [
                'product_id' => $item->product_id,
                'price' => $item->price,
                'quantity' => $item->quantity,
            ]),
            'created_at' => $this->created_at,
        ];
    }
}
