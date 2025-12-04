<?php

namespace EasyPack\Http\Controllers\Api\V1;

use EasyPack\Http\Controllers\Controller;
use EasyPack\Entities\Media\MediaRepository;
use EasyPack\ApiDocs\Docs\APICall;
use EasyPack\ApiDocs\Docs\Param;
use EasyPack\ApiDocs\Docs\ParamType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MediaController extends Controller
{
    public function __construct(
        protected MediaRepository $mediaRepository
    ) {}

    /**
     * Get a paginated list of media files.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        document(function () {
            return (new APICall())
                ->setGroup('Media')
                ->setName('List Media')
                ->setDescription('Get a paginated list of media files with optional search and filters')
                ->setParams([
                    (new Param('q', ParamType::STRING, 'Search query for name, file_name, or collection'))->optional(),
                    (new Param('collection', ParamType::STRING, 'Filter by collection name'))->optional(),
                    (new Param('file_key', ParamType::STRING, 'Filter by file key (e.g., privacy-policy, terms-conditions)'))->optional(),
                    (new Param('page', ParamType::INTEGER, 'Page number'))->optional()->setDefaultValue(1),
                    (new Param('per_page', ParamType::INTEGER, 'Items per page'))->optional()->setDefaultValue(15),
                ])
                ->setSuccessPaginatedObject(Media::class);
        });

        $media = $this->mediaRepository->searchMedia(
            $request->get('q'),
            $request->get('collection'),
            $request->get('file_key'),
            $request->get('per_page', 15)
        );

        return response()->apiSuccess([
            'items' => $media->through(fn ($item) => $this->mediaToArray($item)),
            'meta' => [
                'current_page' => $media->currentPage(),
                'last_page' => $media->lastPage(),
                'per_page' => $media->perPage(),
                'total' => $media->total(),
            ],
        ], 'Success');
    }

    /**
     * Get media details by UUID.
     *
     * @param string $uuid
     * @return JsonResponse
     */
    public function show(string $uuid): JsonResponse
    {
        document(function () {
            return (new APICall())
                ->setGroup('Media')
                ->setName('Get Media Details')
                ->setDescription('Get detailed information about a specific media item by UUID')
                ->setParams([
                    (new Param('uuid', ParamType::STRING, 'The media UUID'))->required()->setLocation('path'),
                ])
                ->setSuccessObject(Media::class);
        });

        $media = $this->mediaRepository->findByUuid($uuid);

        if (!$media) {
            return response()->apiNotFound('Media not found.');
        }

        return response()->apiSuccess($this->mediaToArray($media), 'Success');
    }

    /**
     * Get media by file key.
     *
     * @param string $key
     * @return JsonResponse
     */
    public function getByFileKey(string $key): JsonResponse
    {
        document(function () {
            return (new APICall())
                ->setGroup('Media')
                ->setName('Get Media by File Key')
                ->setDescription('Get media item by predefined file key (e.g., privacy-policy, terms-conditions, about-us)')
                ->setParams([
                    (new Param('key', ParamType::STRING, 'The file key'))->required()->setLocation('path'),
                ])
                ->setSuccessObject(Media::class);
        });

        $media = $this->mediaRepository->findByFileKey($key);

        if (!$media) {
            return response()->apiNotFound('Media with this file key not found.');
        }

        return response()->apiSuccess($this->mediaToArray($media), 'Success');
    }

    /**
     * Get available file keys.
     *
     * @return JsonResponse
     */
    public function getFileKeys(): JsonResponse
    {
        document(function () {
            return (new APICall())
                ->setGroup('Media')
                ->setName('Get File Keys')
                ->setDescription('Get list of predefined file keys and their display names')
                ->setSuccessMessageOnly();
        });

        return response()->apiSuccess([
            'file_keys' => $this->mediaRepository->getFileKeys(),
            'locked_keys' => $this->mediaRepository->getLockedFileKeys(),
        ], 'Success');
    }

    /**
     * Update media properties.
     *
     * @param Request $request
     * @param string $uuid
     * @return JsonResponse
     */
    public function update(Request $request, string $uuid): JsonResponse
    {
        document(function () {
            return (new APICall())
                ->setGroup('Media')
                ->setName('Update Media')
                ->setDescription('Update media name, file key, or custom properties')
                ->setParams([
                    (new Param('uuid', ParamType::STRING, 'The media UUID'))->required()->setLocation('path'),
                    (new Param('name', ParamType::STRING, 'Display name for the media'))->optional(),
                    (new Param('file_key', ParamType::STRING, 'File key identifier (e.g., privacy-policy)'))->optional(),
                    (new Param('custom_properties', ParamType::OBJECT, 'Additional custom properties as key-value pairs'))->optional(),
                ])
                ->setSuccessObject(Media::class);
        });

        $media = $this->mediaRepository->findByUuid($uuid);

        if (!$media) {
            return response()->apiNotFound('Media not found.');
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'file_key' => 'sometimes|string|max:255',
            'custom_properties' => 'sometimes|array',
        ]);

        if (isset($validated['name'])) {
            $media->name = $validated['name'];
            $media->save();
        }

        $customProps = [];
        if (isset($validated['file_key'])) {
            $customProps['file_key'] = $validated['file_key'];
        }

        if (isset($validated['custom_properties'])) {
            $customProps = array_merge($customProps, $validated['custom_properties']);
        }

        if (!empty($customProps)) {
            $this->mediaRepository->updateCustomProperties($media, $customProps);
        }

        return response()->apiSuccess($this->mediaToArray($media->fresh()), 'Media updated successfully.');
    }

    /**
     * Delete a media file.
     *
     * @param string $uuid
     * @return JsonResponse
     */
    public function destroy(string $uuid): JsonResponse
    {
        document(function () {
            return (new APICall())
                ->setGroup('Media')
                ->setName('Delete Media')
                ->setDescription('Delete a media item. Protected items (privacy-policy, terms-conditions) cannot be deleted.')
                ->setParams([
                    (new Param('uuid', ParamType::STRING, 'The media UUID'))->required()->setLocation('path'),
                ])
                ->setSuccessMessageOnly();
        });

        $media = $this->mediaRepository->findByUuid($uuid);

        if (!$media) {
            return response()->apiNotFound('Media not found.');
        }

        // Check if deletion is allowed
        $lockedKeys = $this->mediaRepository->getLockedFileKeys();
        $fileKey = $media->getCustomProperty('file_key');
        
        if ($fileKey && in_array($fileKey, $lockedKeys)) {
            return response()->apiError('This media item is protected and cannot be deleted.', 403);
        }

        try {
            $this->mediaRepository->deleteMedia($media);
        } catch (\Exception $e) {
            return response()->apiError($e->getMessage(), 403);
        }

        return response()->apiSuccess(null, 'Media deleted successfully.');
    }

    /**
     * View/Stream a media file by UUID.
     *
     * @param string $uuid
     * @param string|null $fileName
     * @return StreamedResponse|JsonResponse
     */
    public function view(string $uuid, ?string $fileName = null): StreamedResponse|JsonResponse
    {
        document(function () {
            return (new APICall())
                ->setGroup('Media')
                ->setName('View Media File')
                ->setDescription('Stream/view a media file directly by UUID. Returns the actual file content.')
                ->setParams([
                    (new Param('uuid', ParamType::STRING, 'The media UUID'))->required()->setLocation('path'),
                    (new Param('fileName', ParamType::STRING, 'Optional file name'))->optional()->setLocation('path'),
                ])
                ->setSuccessMessageOnly();
        });

        $media = $this->mediaRepository->findByUuid($uuid);

        if (!$media) {
            return response()->apiNotFound('Media not found.');
        }

        $path = $media->getPath();
        $disk = Storage::disk($media->disk);

        if (!$disk->exists($path)) {
            return response()->apiNotFound('File not found on disk.');
        }

        return response()->stream(
            function () use ($disk, $path) {
                echo $disk->get($path);
            },
            200,
            [
                'Content-Type' => $media->mime_type,
                'Content-Disposition' => 'inline; filename="' . $media->file_name . '"',
                'Content-Length' => $media->size,
            ]
        );
    }

    /**
     * Download a media file.
     *
     * @param string $uuid
     * @return StreamedResponse|JsonResponse
     */
    public function download(string $uuid): StreamedResponse|JsonResponse
    {
        document(function () {
            return (new APICall())
                ->setGroup('Media')
                ->setName('Download Media File')
                ->setDescription('Download a media file by UUID. Returns the file as an attachment.')
                ->setParams([
                    (new Param('uuid', ParamType::STRING, 'The media UUID'))->required()->setLocation('path'),
                ])
                ->setSuccessMessageOnly();
        });

        $media = $this->mediaRepository->findByUuid($uuid);

        if (!$media) {
            return response()->apiNotFound('Media not found.');
        }

        return $media->toResponse(request());
    }

    /**
     * Convert media to array response.
     *
     * @param Media $media
     * @return array
     */
    protected function mediaToArray(Media $media): array
    {
        return [
            'uuid' => $media->uuid,
            'name' => $media->name,
            'file_name' => $media->file_name,
            'mime_type' => $media->mime_type,
            'size' => $media->size,
            'human_readable_size' => $media->human_readable_size,
            'collection_name' => $media->collection_name,
            'disk' => $media->disk,
            'url' => $media->getUrl(),
            'file_key' => $media->getCustomProperty('file_key'),
            'custom_properties' => $media->custom_properties,
            'created_at' => $media->created_at?->toIso8601String(),
            'updated_at' => $media->updated_at?->toIso8601String(),
        ];
    }
}
