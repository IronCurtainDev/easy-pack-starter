<?php

namespace EasyPack\Services;

use EasyPack\Entities\Media\Media;
use Illuminate\Http\UploadedFile;
use Spatie\MediaLibrary\HasMedia;

/**
 * MediaService
 *
 * A service class for handling media operations using Spatie Media Library.
 */
class MediaService
{
    /**
     * Upload a file to a model's media collection.
     */
    public function uploadFile(
        HasMedia $model,
        UploadedFile $file,
        string $collection,
        ?string $customName = null,
        array $customProperties = []
    ): Media {
        $mediaAdder = $model->addMedia($file);

        if ($customName) {
            $mediaAdder->usingName($customName);
        }

        if (!empty($customProperties)) {
            $mediaAdder->withCustomProperties($customProperties);
        }

        return $mediaAdder->toMediaCollection($collection);
    }

    /**
     * Upload a file with a specific file key (like privacy-policy, terms-conditions).
     */
    public function uploadFileWithKey(
        HasMedia $model,
        UploadedFile $file,
        string $fileKey,
        string $collection = 'default',
        ?string $customName = null,
        array $customProperties = []
    ): Media {
        // Check if file with this key already exists and replace it
        $existingMedia = Media::findByFileKey($fileKey);
        if ($existingMedia && $existingMedia->isDeleteAllowed()) {
            $existingMedia->forceDeleteMedia();
        }

        $customProperties['file_key'] = $fileKey;

        if (auth()->check()) {
            $customProperties['uploaded_by_user_id'] = auth()->id();
        }

        return $this->uploadFile($model, $file, $collection, $customName, $customProperties);
    }

    /**
     * Upload multiple files to a model's media collection.
     */
    public function uploadMultipleFiles(
        HasMedia $model,
        array $files,
        string $collection,
        array $customProperties = []
    ): array {
        $uploadedMedia = [];

        foreach ($files as $file) {
            if ($file instanceof UploadedFile) {
                $uploadedMedia[] = $this->uploadFile($model, $file, $collection, null, $customProperties);
            }
        }

        return $uploadedMedia;
    }

    /**
     * Upload a file from URL to a model's media collection.
     */
    public function uploadFromUrl(
        HasMedia $model,
        string $url,
        string $collection,
        ?string $customName = null,
        array $customProperties = []
    ): Media {
        $mediaAdder = $model->addMediaFromUrl($url);

        if ($customName) {
            $mediaAdder->usingName($customName);
        }

        if (!empty($customProperties)) {
            $mediaAdder->withCustomProperties($customProperties);
        }

        return $mediaAdder->toMediaCollection($collection);
    }

    /**
     * Upload a file from base64 string.
     */
    public function uploadFromBase64(
        HasMedia $model,
        string $base64Data,
        string $collection,
        string $fileName,
        ?string $customName = null,
        array $customProperties = []
    ): Media {
        $mediaAdder = $model->addMediaFromBase64($base64Data)
            ->usingFileName($fileName);

        if ($customName) {
            $mediaAdder->usingName($customName);
        }

        if (!empty($customProperties)) {
            $mediaAdder->withCustomProperties($customProperties);
        }

        return $mediaAdder->toMediaCollection($collection);
    }

    /**
     * Replace all media in a collection with a new file.
     */
    public function replaceMedia(
        HasMedia $model,
        UploadedFile $file,
        string $collection,
        ?string $customName = null
    ): Media {
        $model->clearMediaCollection($collection);

        return $this->uploadFile($model, $file, $collection, $customName);
    }

    /**
     * Delete a specific media item by ID.
     */
    public function deleteMedia(HasMedia $model, int $mediaId, ?string $collection = null): bool
    {
        if ($collection) {
            $media = $model->getMedia($collection)->where('id', $mediaId)->first();
        } else {
            $media = $model->media()->where('id', $mediaId)->first();
        }

        if (!$media) {
            return false;
        }

        $media->delete();
        return true;
    }

    /**
     * Clear all media from a collection.
     */
    public function clearCollection(HasMedia $model, string $collection): void
    {
        $model->clearMediaCollection($collection);
    }

    /**
     * Get formatted media items for API response.
     */
    public function getFormattedMedia(HasMedia $model, string $collection): array
    {
        return $model->getMedia($collection)->map(function (Media $media) {
            return $this->formatMediaItem($media);
        })->toArray();
    }

    /**
     * Format a media item for API response.
     */
    public function formatMediaItem(Media $media): array
    {
        $conversions = [];

        foreach ($media->getGeneratedConversions() as $conversionName => $generated) {
            if ($generated) {
                $conversions[$conversionName] = $media->getUrl($conversionName);
            }
        }

        return [
            'id' => $media->id,
            'uuid' => $media->uuid,
            'name' => $media->name,
            'file_name' => $media->file_name,
            'mime_type' => $media->mime_type,
            'size' => $media->size,
            'human_readable_size' => $media->human_readable_size,
            'url' => $media->getUrl(),
            'conversions' => $conversions,
            'custom_properties' => $media->custom_properties,
            'created_at' => $media->created_at?->toISOString(),
            'updated_at' => $media->updated_at?->toISOString(),
        ];
    }

    /**
     * Copy media from one model to another.
     */
    public function copyMedia(Media $media, HasMedia $targetModel, string $collection): Media
    {
        return $media->copy($targetModel, $collection);
    }

    /**
     * Move media from one model to another.
     */
    public function moveMedia(Media $media, HasMedia $targetModel, string $collection): Media
    {
        return $media->move($targetModel, $collection);
    }

    /**
     * Update custom properties of a media item.
     */
    public function updateCustomProperties(Media $media, array $properties): Media
    {
        $media->custom_properties = array_merge($media->custom_properties, $properties);
        $media->save();

        return $media;
    }

    /**
     * Get media by UUID.
     */
    public function findByUuid(string $uuid): ?Media
    {
        return Media::where('uuid', $uuid)->first();
    }

    /**
     * Regenerate conversions for a media item.
     */
    public function regenerateConversions(Media $media): void
    {
        $media->model->getRegisteredMediaCollections()
            ->first(fn ($collection) => $collection->name === $media->collection_name);

        // Regenerate media conversions
        $media->delete();
    }
}
