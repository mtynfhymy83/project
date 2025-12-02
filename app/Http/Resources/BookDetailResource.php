<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BookDetailResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this['id'],
            'title' => $this['title'],
            'slug' => $this['slug'],
            'excerpt' => $this['excerpt'],
            'content' => $this['content'],
            'cover_url' => $this['cover_url'],
            'thumbnail_url' => $this['thumbnail_url'],
            'pages' => $this['pages'],
            'pricing' => [
                'price' => $this['price'],
                'discount_price' => $this['discount_price'],
                'effective_price' => $this['effective_price'],
                'has_discount' => $this['has_discount'],
                'discount_percentage' => $this['discount_percentage'],
                'is_free' => $this['is_free'],
            ],
            'stats' => [
                'rating' => $this['rating'],
                'rating_count' => $this['rating_count'],
                'purchase_count' => $this['purchase_count'],
            ],
            'features' => $this['features'],
            'primary_category' => $this['primary_category'],
            'categories' => $this['categories'],
            'authors' => $this['authors'],
            'publisher' => $this['publisher'],
            'index' => $this['index'],
            'subscription_plans' => $this['subscription_plans'],
            'created_at' => $this['created_at'],
        ];
    }
}

class BookListResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'excerpt' => $this->excerpt,
            'cover_url' => $this->cover_url,
            'thumbnail_url' => $this->thumbnail_url,
            'pages' => $this->pages,
            'price' => (float) $this->price,
            'discount_price' => $this->discount_price ? (float) $this->discount_price : null,
            'effective_price' => (float) $this->getEffectivePrice(),
            'has_discount' => $this->hasDiscount(),
            'is_free' => $this->is_free,
            'rating' => (float) $this->rating,
            'rating_count' => $this->rating_count,
            'is_special' => $this->is_special,
            'primary_category' => $this->whenLoaded('primaryCategory', function () {
                return [
                    'id' => $this->primaryCategory->id,
                    'name' => $this->primaryCategory->name,
                    'slug' => $this->primaryCategory->slug,
                ];
            }),
            'authors' => $this->whenLoaded('authors', function () {
                return $this->authors->map(fn($author) => [
                    'id' => $author->id,
                    'name' => $author->name,
                ]);
            }),
        ];
    }
}

class PageContentResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'book_id' => $this['book_id'],
            'page_number' => $this['page_number'],
            'content' => $this['content'],
        ];
    }
}
