<?php

namespace EasyPack\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;

/**
 * Media Service Interface
 * 
 * Abstracts media library operations to allow easy swapping
 * when Spatie Media Library or other packages change their APIs.
 */
interface MediaServiceInterface
{
    /**
     * Add a file to a model's media collection.
     *
     * @param Model $model
     * @param UploadedFile|string $file File or path
     * @param string $collection Collection name
     * @param array $options Additional options (custom properties, etc.)
     * @return object The created media item
     */
    public function addMedia(Model $model, UploadedFile|string $file, string $collection = 'default', array $options = []): object;

    /**
     * Add multiple files to a model's media collection.
     *
     * @param Model $model
     * @param array $files Array of UploadedFile or paths
     * @param string $collection Collection name
     * @param array $options Additional options
     * @return Collection The created media items
     */
    public function addMultipleMedia(Model $model, array $files, string $collection = 'default', array $options = []): Collection;

    /**
     * Replace existing media in a collection with a new file.
     *
     * @param Model $model
     * @param UploadedFile|string $file
     * @param string $collection
     * @param array $options
     * @return object The new media item
     */
    public function replaceMedia(Model $model, UploadedFile|string $file, string $collection = 'default', array $options = []): object;

    /**
     * Get all media for a model.
     *
     * @param Model $model
     * @param string|null $collection Filter by collection name
     * @return Collection
     */
    public function getMedia(Model $model, ?string $collection = null): Collection;

    /**
     * Get first media item from a collection.
     *
     * @param Model $model
     * @param string $collection
     * @return object|null
     */
    public function getFirstMedia(Model $model, string $collection = 'default'): ?object;

    /**
     * Get URL for a media item.
     *
     * @param object $media
     * @param string $conversion Conversion name (e.g., 'thumb')
     * @return string
     */
    public function getUrl(object $media, string $conversion = ''): string;

    /**
     * Get URL for first media in collection.
     *
     * @param Model $model
     * @param string $collection
     * @param string $conversion
     * @return string|null
     */
    public function getFirstMediaUrl(Model $model, string $collection = 'default', string $conversion = ''): ?string;

    /**
     * Delete a specific media item.
     *
     * @param object $media
     * @return bool
     */
    public function deleteMedia(object $media): bool;

    /**
     * Clear all media from a collection.
     *
     * @param Model $model
     * @param string $collection
     * @return int Number of items deleted
     */
    public function clearCollection(Model $model, string $collection): int;

    /**
     * Update media item properties.
     *
     * @param object $media
     * @param array $properties
     * @return bool
     */
    public function updateMedia(object $media, array $properties): bool;

    /**
     * Find media by UUID.
     *
     * @param string $uuid
     * @return object|null
     */
    public function findByUuid(string $uuid): ?object;

    /**
     * Find media by custom file key.
     *
     * @param string $fileKey
     * @return object|null
     */
    public function findByFileKey(string $fileKey): ?object;

    /**
     * Get the path to the media file.
     *
     * @param object $media
     * @param string $conversion
     * @return string
     */
    public function getPath(object $media, string $conversion = ''): string;

    /**
     * Check if media has a specific conversion.
     *
     * @param object $media
     * @param string $conversion
     * @return bool
     */
    public function hasConversion(object $media, string $conversion): bool;
}
