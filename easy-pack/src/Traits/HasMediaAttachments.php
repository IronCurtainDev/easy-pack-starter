<?php

namespace EasyPack\Traits;

use EasyPack\Services\MediaService;
use Illuminate\Http\UploadedFile;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Provides convenient methods for handling media attachments.
 *
 * Requirements:
 * - Model must use InteractsWithMedia trait
 * - Model must implement HasMedia interface
 * - Model should define media collections via registerMediaCollections()
 */
trait HasMediaAttachments
{
    /**
     * Get the MediaService instance.
     */
    protected function getMediaService(): MediaService
    {
        return app(MediaService::class);
    }

    /**
     * Add a media file to a collection.
     *
     * @param UploadedFile $file
     * @param string $collection Collection name (default: 'default')
     * @param string|null $name Custom file name (optional)
     * @return Media
     */
    public function addMediaFile(
        UploadedFile $file,
        string $collection = 'default',
        ?string $name = null
    ): Media {
        return $this->getMediaService()->uploadFile($this, $file, $collection, $name);
    }

    /**
     * Add media from a URL using the MediaService.
     *
     * @param string $url
     * @param string $collection Collection name (default: 'default')
     * @param string|null $name Custom file name (optional)
     * @return Media
     */
    public function addMediaFromUrlToCollection(
        string $url,
        string $collection = 'default',
        ?string $name = null
    ): Media {
        return $this->getMediaService()->uploadFromUrl($this, $url, $collection, $name);
    }

    /**
     * Add media from base64 encoded string to a collection.
     *
     * @param string $base64Data
     * @param string $fileName
     * @param string $collection Collection name (default: 'default')
     * @return Media
     */
    public function addMediaBase64ToCollection(
        string $base64Data,
        string $fileName,
        string $collection = 'default'
    ): Media {
        return $this->getMediaService()->uploadFromBase64($this, $base64Data, $collection, $fileName);
    }

    /**
     * Replace all media in a collection with a new file.
     *
     * @param UploadedFile $file
     * @param string $collection Collection name (default: 'default')
     * @return Media
     */
    public function replaceMediaFile(
        UploadedFile $file,
        string $collection = 'default'
    ): Media {
        return $this->getMediaService()->replaceMedia($this, $file, $collection);
    }

    /**
     * Get all media URLs for a collection.
     *
     * @param string $collection Collection name (default: 'default')
     * @param string|null $conversion Optional conversion name
     * @return array<string>
     */
    public function getMediaUrls(string $collection = 'default', ?string $conversion = null): array
    {
        $media = $this->getMedia($collection);

        return $media->map(function (Media $item) use ($conversion) {
            return $conversion
                ? $item->getUrl($conversion)
                : $item->getUrl();
        })->toArray();
    }

    /**
     * Get the first media URL for a collection (custom helper).
     * Unlike Spatie's getFirstMediaUrl, this returns null instead of empty string if no media.
     *
     * @param string $collection Collection name (default: 'default')
     * @param string|null $conversion Optional conversion name
     * @return string|null
     */
    public function getFirstMediaUrlOrNull(string $collection = 'default', ?string $conversion = null): ?string
    {
        $media = $this->getFirstMedia($collection);

        if (!$media) {
            return null;
        }

        return $conversion
            ? $media->getUrl($conversion)
            : $media->getUrl();
    }

    /**
     * Get media as array suitable for API responses.
     *
     * @param string $collection Collection name (default: 'default')
     * @param array<string> $conversions List of conversions to include
     * @return array
     */
    public function getMediaForApi(string $collection = 'default', array $conversions = []): array
    {
        return $this->getMediaService()->getFormattedMedia($this, $collection);
    }

    /**
     * Delete a specific media item by ID.
     *
     * @param int $mediaId
     * @return bool
     */
    public function deleteMediaById(int $mediaId): bool
    {
        return $this->getMediaService()->deleteMedia($this, $mediaId);
    }

    /**
     * Delete all media from a collection.
     *
     * @param string $collection Collection name
     * @return int Number of deleted items
     */
    public function deleteMediaCollection(string $collection): int
    {
        $count = $this->getMedia($collection)->count();
        $this->clearMediaCollection($collection);
        return $count;
    }

    /**
     * Check if model has media in a collection.
     *
     * @param string $collection Collection name (default: 'default')
     * @return bool
     */
    public function hasMediaInCollection(string $collection = 'default'): bool
    {
        return $this->hasMedia($collection);
    }

    /**
     * Get the count of media items in a collection.
     *
     * @param string $collection Collection name (default: 'default')
     * @return int
     */
    public function getMediaCount(string $collection = 'default'): int
    {
        return $this->getMedia($collection)->count();
    }

    /**
     * Sync media from uploaded files array.
     * Replaces all media in the collection with the provided files.
     *
     * @param array<UploadedFile> $files
     * @param string $collection Collection name (default: 'default')
     * @return array<Media>
     */
    public function syncMediaFiles(array $files, string $collection = 'default'): array
    {
        // Clear existing media
        $this->clearMediaCollection($collection);

        // Add new files
        $media = [];
        foreach ($files as $file) {
            if ($file instanceof UploadedFile) {
                $media[] = $this->addMediaFile($file, $collection);
            }
        }

        return $media;
    }

    /**
     * Reorder media items in a collection.
     *
     * @param array<int> $orderedIds Array of media IDs in desired order
     * @return void
     */
    public function reorderMedia(array $orderedIds): void
    {
        // Reorder using Spatie's built-in method
        Media::setNewOrder($orderedIds);
    }
}
