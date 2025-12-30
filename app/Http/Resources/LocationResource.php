<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LocationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->category->slug,
            'category' => $this->category->name,
            'address' => $this->address,
            'description' => $this->description,
            'coordinates' => [
                'lat' => $this->coordinates->latitude,
                'lng' => $this->coordinates->longitude,
            ],
            'registrar' => $this->whenLoaded('registrar', function () {
                return $this->registrar->name;
            }),
            'is_mine' => $request->user() ? $request->user()->id === $this->user_id : false,
            'created_at' => $this->created_at->diffForHumans(),
        ];
    }
}
