<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RecipeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Get the first image attachment for backward compatibility
        $mainImage = $this->images()->first();
        
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'ingredients' => $this->ingredients,
            'steps' => $this->steps,
            'image' => $mainImage?->url,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ],
            'cuisine_type' => [
                'id' => $this->cuisineType->id,
                'name' => $this->cuisineType->name,
            ],
            'attachments' => $this->attachments->map(function ($attachment) {
                return [
                    'id' => $attachment->id,
                    'type' => $attachment->type,
                    'original_name' => $attachment->original_name,
                    'file_name' => $attachment->file_name,
                    'mime_type' => $attachment->mime_type,
                    'file_size' => $attachment->file_size,
                    'formatted_size' => $attachment->formatted_size,
                    'url' => $attachment->url,
                    'is_image' => $attachment->isImage(),
                    'is_document' => $attachment->isDocument(),
                ];
            }),
        ];
    }

}
