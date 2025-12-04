<?php

namespace EasyPack\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use EasyPack\Contracts\MediaServiceInterface;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Spatie Media Service
 * 
 * Implementation of MediaServiceInterface using Spatie Media Library.
 * This can be swapped out for different media systems in the future.
 */
class SpatieMediaService implements MediaServiceInterface
{
    /**
     * Add a file to a model's media collection.
     */
    public function addMedia(Model $model, UploadedFile|string $file, string $collection = 'default', array $options = []): object
    {
        $adder = $model->addMedia($file);

        // Apply options
        if (isset($options['name'])) {
            $adder->usingName($options['name']);
        }

        if (isset($options['file_name'])) {
            $adder->usingFileName($options['file_name']);
        }

        if (isset($options['custom_properties'])) {
            $adder->withCustomProperties($options['custom_properties']);
        }

        if (isset($options['preserve_original']) && $options['preserve_original']) {
            $adder->preservingOriginal();
        }

        return $adder->toMediaCollection($collection);
    }

    /**
     * Add multiple files to a model's media collection.
     */
    public function addMultipleMedia(Model $model, array $files, string $collection = 'default', array $options = []): Collection
    {
        $media = collect();

        foreach ($files as $file) {
            $media->push($this->addMedia($model, $file, $collection, $options));
        }

        return $media;
    }

    /**
     * Replace existing media in a collection with a new file.
     */
    public function replaceMedia(Model $model, UploadedFile|string $file, string $collection = 'default', array $options = []): object
    {
        // Clear existing media in collection
        $this->clearCollection($model, $collection);

        // Add new media
        return $this->addMedia($model, $file, $collection, $options);
    }

    /**
     * Get all media for a model.
     */
    public function getMedia(Model $model, ?string $collection = null): Collection
    {
        if ($collection) {
            return $model->getMedia($collection);
        }

        return $model->media;
    }

    /**
     * Get first media item from a collection.
     */
    public function getFirstMedia(Model $model, string $collection = 'default'): ?object
    {
        return $model->getFirstMedia($collection);
    }

    /**
     * Get URL for a media item.
     */
    public function getUrl(object $media, string $conversion = ''): string
    {
        if ($conversion && $media->hasGeneratedConversion($conversion)) {
            return $media->getUrl($conversion);
        }

        return $media->getUrl();
    }

    /**
     * Get URL for first media in collection.
     */
    public function getFirstMediaUrl(Model $model, string $collection = 'default', string $conversion = ''): ?string
    {
        $media = $this->getFirstMedia($model, $collection);

        if (!$media) {
            return null;
        }

        return $this->getUrl($media, $conversion);
    }

    /**
     * Delete a specific media item.
     */
    public function deleteMedia(object $media): bool
    {
        return (bool) $media->delete();
    }

    /**
     * Clear all media from a collection.
     */
    public function clearCollection(Model $model, string $collection): int
    {
        $count = $model->getMedia($collection)->count();
        $model->clearMediaCollection($collection);
        return $count;
    }

    /**
     * Update media item properties.
     */
    public function updateMedia(object $media, array $properties): bool
    {
        $updateData = [];

        if (isset($properties['name'])) {
            $updateData['name'] = $properties['name'];
        }

        if (isset($properties['custom_properties'])) {
            $updateData['custom_properties'] = array_merge(
                $media->custom_properties ?? [],
                $properties['custom_properties']
            );
        }

        if (isset($properties['file_key'])) {
            $customProps = $media->custom_properties ?? [];
            $customProps['file_key'] = $properties['file_key'];
            $updateData['custom_properties'] = $customProps;
        }

        if (empty($updateData)) {
            return false;
        }

        return $media->update($updateData);
    }

    /**
     * Find media by UUID.
     */
    public function findByUuid(string $uuid): ?object
    {
        return Media::where('uuid', $uuid)->first();
    }

    /**
     * Find media by custom file key.
     */
    public function findByFileKey(string $fileKey): ?object
    {
        return Media::where('custom_properties->file_key', $fileKey)->first();
    }

    /**
     * Get the path to the media file.
     */
    public function getPath(object $media, string $conversion = ''): string
    {
        if ($conversion && $media->hasGeneratedConversion($conversion)) {
            return $media->getPath($conversion);
        }

        return $media->getPath();
    }

    /**
     * Check if media has a specific conversion.
     */
    public function hasConversion(object $media, string $conversion): bool
    {
        return $media->hasGeneratedConversion($conversion);
    }
}
