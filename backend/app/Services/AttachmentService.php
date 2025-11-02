<?php

namespace App\Services;

use App\Models\Attachment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AttachmentService
{
    /**
     * Upload and attach file to a model.
     */
    public function uploadAndAttach(
        UploadedFile $file, 
        Model $model, 
        string $type = 'file',
        string $disk = 'public',
        string $directory = 'attachments'
    ): Attachment {
        // Generate unique filename
        $fileName = $this->generateUniqueFileName($file);
        
        // Store the file
        $filePath = $file->storeAs($directory, $fileName, $disk);
        
        // Create attachment record
        return $model->attachments()->create([
            'original_name' => $file->getClientOriginalName(),
            'file_name' => $fileName,
            'file_path' => $filePath,
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'disk' => $disk,
            'type' => $type,
            'metadata' => $this->extractMetadata($file),
        ]);
    }

    /**
     * Upload multiple files and attach to a model.
     */
    public function uploadMultipleAndAttach(
        array $files, 
        Model $model, 
        string $type = 'file',
        string $disk = 'public',
        string $directory = 'attachments'
    ): array {
        $attachments = [];
        
        foreach ($files as $file) {
            if ($file instanceof UploadedFile) {
                $attachments[] = $this->uploadAndAttach($file, $model, $type, $disk, $directory);
            }
        }
        
        return $attachments;
    }

    /**
     * Delete attachment and its file.
     */
    public function delete(Attachment $attachment): bool
    {
        // Delete the physical file
        Storage::disk($attachment->disk)->delete($attachment->file_path);
        
        // Delete the attachment record
        return $attachment->delete();
    }

    /**
     * Delete multiple attachments.
     */
    public function deleteMultiple(array $attachmentIds): int
    {
        $attachments = Attachment::whereIn('id', $attachmentIds)->get();
        $deletedCount = 0;
        
        foreach ($attachments as $attachment) {
            if ($this->delete($attachment)) {
                $deletedCount++;
            }
        }
        
        return $deletedCount;
    }

    /**
     * Replace existing attachments with new ones.
     */
    public function replaceAttachments(
        Model $model, 
        array $files, 
        string $type = 'file',
        string $disk = 'public',
        string $directory = 'attachments'
    ): array {
        // Delete existing attachments of the same type
        $existingAttachments = $model->attachmentsByType($type)->get();
        foreach ($existingAttachments as $attachment) {
            $this->delete($attachment);
        }
        
        // Upload new attachments
        return $this->uploadMultipleAndAttach($files, $model, $type, $disk, $directory);
    }

    /**
     * Get attachment URL.
     */
    public function getUrl(Attachment $attachment): string
    {
        return $attachment->url;
    }

    /**
     * Generate unique filename.
     */
    private function generateUniqueFileName(UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension();
        $name = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
        $timestamp = now()->format('Y-m-d_H-i-s');
        $random = Str::random(8);
        
        return "{$name}_{$timestamp}_{$random}.{$extension}";
    }

    /**
     * Extract file metadata.
     */
    private function extractMetadata(UploadedFile $file): array
    {
        $metadata = [
            'original_extension' => $file->getClientOriginalExtension(),
        ];
        
        // Add image-specific metadata
        if (str_starts_with($file->getMimeType(), 'image/')) {
            $imageInfo = getimagesize($file->getPathname());
            if ($imageInfo) {
                $metadata['width'] = $imageInfo[0];
                $metadata['height'] = $imageInfo[1];
                $metadata['aspect_ratio'] = round($imageInfo[0] / $imageInfo[1], 2);
            }
        }
        
        return $metadata;
    }

    /**
     * Validate file type and size.
     */
    public function validateFile(
        UploadedFile $file, 
        array $allowedMimeTypes = [], 
        int $maxSizeInMB = 10
    ): bool {
        // Check file size
        if ($file->getSize() > ($maxSizeInMB * 1024 * 1024)) {
            return false;
        }
        
        // Check mime type if specified
        if (!empty($allowedMimeTypes) && !in_array($file->getMimeType(), $allowedMimeTypes)) {
            return false;
        }
        
        return true;
    }

    /**
     * Get allowed image mime types.
     */
    public function getAllowedImageMimeTypes(): array
    {
        return [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
            'image/svg+xml',
        ];
    }

    /**
     * Get allowed document mime types.
     */
    public function getAllowedDocumentMimeTypes(): array
    {
        return [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'text/plain',
        ];
    }
}