<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'sale_price' => $this->sale_price,
            'sku' => $this->sku,
            'stock_quantity' => $this->stock_quantity,
            'in_stock' => $this->stock_quantity > 0,
            'discount_percentage' => $this->when($this->sale_price, function () {
                return round((1 - ($this->sale_price / $this->price)) * 100);
            }),
            'category_id' => $this->category_id,
            'category' => new CategoryResource($this->whenLoaded('category')),
            'is_active' => $this->is_active,
            'is_featured' => $this->is_featured,
            'is_on_sale' => $this->is_on_sale,
            'thumbnail_url' => $this->getFirstMediaUrl('product_thumbnail'),
            'thumbnail_thumb_url' => $this->getFirstMediaUrl('product_thumbnail', 'thumb'),
            'images' => $this->when($request->routeIs('*.products.show') || $request->input('with_images', false), function () {
                return $this->getMedia('product_images')->map(function ($media) {
                    return [
                        'id' => $media->id,
                        'url' => $media->getUrl(),
                    ];
                });
            }),

            'main_image_url' => $this->main_image_url,
            'comments' => ProductCommentResource::collection($this->whenLoaded('comments')),
            'comments_count' => $this->when($this->comments_count !== null, $this->comments_count),
            'average_rating' => $this->when($this->average_rating !== null, $this->average_rating),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }
}
