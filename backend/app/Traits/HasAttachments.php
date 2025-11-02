<?php

namespace App\Traits;

use App\Models\Attachment;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasAttachments
{
    /**
     * Get all of the model's attachments.
     */
    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    /**
     * Get attachments by type.
     */
    public function attachmentsByType(string $type): MorphMany
    {
        return $this->attachments()->where('type', $type);
    }

    /**
     * Get only image attachments.
     */
    public function images(): MorphMany
    {
        return $this->attachmentsByType('image');
    }

    /**
     * Get only document attachments.
     */
    public function documents(): MorphMany
    {
        return $this->attachmentsByType('document');
    }

    /**
     * Get the first image attachment.
     */
    public function firstImage(): ?Attachment
    {
        return $this->images()->first();
    }

    /**
     * Check if model has any attachments.
     */
    public function hasAttachments(): bool
    {
        return $this->attachments()->exists();
    }

    /**
     * Check if model has images.
     */
    public function hasImages(): bool
    {
        return $this->images()->exists();
    }
}