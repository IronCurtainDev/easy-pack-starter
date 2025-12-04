<?php

namespace EasyPack\Entities\Media;

use EasyPack\Entities\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaRepository extends BaseRepository
{
    protected function getModelClass(): string
    {
        return Media::class;
    }

    /**
     * Search media with optional filters.
     */
    public function searchMedia(?string $query = null, ?string $collection = null, ?string $fileKey = null, int $perPage = 15): LengthAwarePaginator
    {
        $builder = $this->fresh();

        if ($query) {
            $builder->query(function ($q) use ($query) {
                $q->where(function ($subQuery) use ($query) {
                    $subQuery->where('name', 'LIKE', "%{$query}%")
                        ->orWhere('file_name', 'LIKE', "%{$query}%")
                        ->orWhere('collection_name', 'LIKE', "%{$query}%");
                });
            });
        }

        if ($collection) {
            $builder->where('collection_name', $collection);
        }

        if ($fileKey) {
            $builder->query(function ($q) use ($fileKey) {
                $q->whereJsonContains('custom_properties->file_key', $fileKey);
            });
        }

        return $builder->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Find media by file key.
     */
    public function findByFileKey(string $key): ?Media
    {
        return $this->fresh()
            ->query(function ($q) use ($key) {
                $q->whereJsonContains('custom_properties->file_key', $key);
            })
            ->getQuery()
            ->first();
    }

    /**
     * Get available file keys.
     */
    public function getFileKeys(): array
    {
        return config('easypack.media.file_keys', [
            'privacy-policy' => 'Privacy Policy',
            'terms-conditions' => 'Terms & Conditions',
            'about-us' => 'About Us',
            'faq' => 'FAQ',
        ]);
    }

    /**
     * Get locked file keys that cannot be deleted.
     */
    public function getLockedFileKeys(): array
    {
        return config('easypack.media.locked_keys', ['privacy-policy', 'terms-conditions']);
    }

    /**
     * Update custom properties on a media item.
     */
    public function updateCustomProperties(Media $media, array $properties): Media
    {
        foreach ($properties as $key => $value) {
            $media->setCustomProperty($key, $value);
        }
        $media->save();
        return $media;
    }

    /**
     * Delete media item.
     */
    public function deleteMedia(Media $media): bool
    {
        return $media->delete();
    }

    /**
     * Get media by model type and ID.
     */
    public function getForModel(string $modelType, int $modelId): Collection
    {
        return $this->where('model_type', $modelType)
            ->where('model_id', $modelId)
            ->orderBy('order_column')
            ->all();
    }

    /**
     * Get media by collection name.
     */
    public function getByCollection(string $modelType, int $modelId, string $collection): Collection
    {
        return $this->where('model_type', $modelType)
            ->where('model_id', $modelId)
            ->where('collection_name', $collection)
            ->orderBy('order_column')
            ->all();
    }

    /**
     * Find media by UUID.
     */
    public function findByUuid(string $uuid): ?Media
    {
        return $this->findFirstBy('uuid', $uuid);
    }

    /**
     * Delete media by UUID.
     */
    public function deleteByUuid(string $uuid): bool
    {
        $media = $this->findByUuid($uuid);

        if (!$media) {
            return false;
        }

        return $media->delete();
    }

    /**
     * Update media order.
     */
    public function updateOrder(array $orderedIds): void
    {
        foreach ($orderedIds as $order => $id) {
            Media::where('id', $id)->update(['order_column' => $order + 1]);
        }
    }

    /**
     * Get media with specific mime type.
     */
    public function getByMimeType(string $mimeType): Collection
    {
        return $this->where('mime_type', $mimeType)->all();
    }

    /**
     * Get images only.
     */
    public function getImages(string $modelType, int $modelId): Collection
    {
        return $this->where('model_type', $modelType)
            ->where('model_id', $modelId)
            ->query(function ($query) {
                $query->where('mime_type', 'LIKE', 'image/%');
            })
            ->orderBy('order_column')
            ->all();
    }

    /**
     * Get documents (non-images).
     */
    public function getDocuments(string $modelType, int $modelId): Collection
    {
        return $this->where('model_type', $modelType)
            ->where('model_id', $modelId)
            ->query(function ($query) {
                $query->where('mime_type', 'NOT LIKE', 'image/%');
            })
            ->orderBy('order_column')
            ->all();
    }

    /**
     * Get total size of media for a model.
     */
    public function getTotalSize(string $modelType, int $modelId): int
    {
        return $this->fresh()
            ->where('model_type', $modelType)
            ->where('model_id', $modelId)
            ->getQuery()
            ->sum('size');
    }

    /**
     * Get orphaned media (media without an associated model).
     */
    public function getOrphaned(): Collection
    {
        return $this->fresh()
            ->query(function ($query) {
                $query->whereDoesntHave('model');
            })
            ->all();
    }

    /**
     * Delete orphaned media.
     */
    public function deleteOrphaned(): int
    {
        $orphaned = $this->getOrphaned();
        $count = $orphaned->count();

        foreach ($orphaned as $media) {
            $media->delete();
        }

        return $count;
    }
}
