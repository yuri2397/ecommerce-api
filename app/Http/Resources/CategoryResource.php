<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'is_active' => $this->is_active,
            'parent_id' => $this->parent_id,
            'parent' => new CategoryResource($this->whenLoaded('parent')),
            'children' => CategoryResource::collection($this->whenLoaded('children')),
            'products_count' => $this->when($this->products_count !== null, $this->products_count),
            'icon_url' => $this->icon_url,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }
}
