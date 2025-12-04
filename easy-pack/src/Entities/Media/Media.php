<?php

namespace EasyPack\Entities\Media;

use Spatie\MediaLibrary\MediaCollections\Models\Media as BaseMedia;

class Media extends BaseMedia
{
    /**
     * Get the URL for a specific conversion.
     */
    public function getConversionUrl(string $conversion): string
    {
        return $this->getUrl($conversion);
    }

    /**
     * Get all conversion URLs as an array.
     */
    public function getAllConversionUrls(): array
    {
        $urls = ['original' => $this->getUrl()];

        foreach ($this->getGeneratedConversions()->keys() as $conversion) {
            $urls[$conversion] = $this->getUrl($conversion);
        }

        return $urls;
    }

    /**
     * Convert to API response array.
     */
    public function toApiArray(array $conversions = []): array
    {
        $data = [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'name' => $this->name,
            'file_name' => $this->file_name,
            'mime_type' => $this->mime_type,
            'size' => $this->size,
            'human_readable_size' => $this->human_readable_size,
            'url' => $this->getUrl(),
            'collection_name' => $this->collection_name,
            'custom_properties' => $this->custom_properties,
            'created_at' => $this->created_at?->toIso8601String(),
        ];

        // Add conversion URLs
        $data['conversions'] = [];
        if (!empty($conversions)) {
            foreach ($conversions as $conversion) {
                if ($this->hasGeneratedConversion($conversion)) {
                    $data['conversions'][$conversion] = $this->getUrl($conversion);
                }
            }
        } else {
            $data['conversions'] = $this->getAllConversionUrls();
        }

        return $data;
    }
}
