<?php

declare(strict_types=1);

namespace App\Support\Storage;

use Cloudinary\Cloudinary;
use CloudinaryLabs\CloudinaryLaravel\CloudinaryStorageAdapter;
use League\MimeTypeDetection\FinfoMimeTypeDetector;
use League\MimeTypeDetection\MimeTypeDetector;

class FixedCloudinaryStorageAdapter extends CloudinaryStorageAdapter
{
    protected MimeTypeDetector $detector;

    protected string $storagePrefix;

    protected Cloudinary $clInstance;

    public function __construct(
        Cloudinary $cloudinary,
        ?MimeTypeDetector $mimeTypeDetector = null,
        ?string $prefix = null
    ) {
        parent::__construct($cloudinary, $mimeTypeDetector, $prefix);

        $this->clInstance = $cloudinary;
        $this->detector = $mimeTypeDetector ?: new FinfoMimeTypeDetector;
        $this->storagePrefix = $prefix ? str_replace('\\', '/', trim($prefix, '/')) : '';
    }

    public function prepareResource(string $path): array
    {
        $info = pathinfo($path);
        $dirname = str_replace('\\', '/', $info['dirname'] ?? '');
        $mimeType = $this->detector->detectMimeTypeFromPath($path);

        if ($mimeType && str_starts_with($mimeType, 'image/')) {
            $id = ($dirname !== '.' && $dirname !== '') ? $dirname.'/'.$info['filename'] : $info['filename'];
            $type = 'image';
        } elseif ($mimeType && str_starts_with($mimeType, 'video/')) {
            $id = ($dirname !== '.' && $dirname !== '') ? $dirname.'/'.$info['filename'] : $info['filename'];
            $type = 'video';
        } else {
            // In Cloudinary, raw files (such as PDFs) require the extension in their public_id.
            $id = ($dirname !== '.' && $dirname !== '') ? $dirname.'/'.$info['basename'] : $info['basename'];
            $type = 'raw';
        }

        if ($this->storagePrefix !== '') {
            $normalizedId = ltrim($id, './\\/');
            $id = $this->storagePrefix.($normalizedId !== '' ? '/'.$normalizedId : '');
        }

        return [$id, $type];
    }

    public function readStream(string $path)
    {
        [$id, $type] = $this->prepareResource($path);

        if ($type === 'raw') {
            $url = $this->clInstance->uploadApi()->privateDownloadUrl($id, '', [
                'resource_type' => 'raw',
                'type' => 'upload',
            ]);

            return fopen($url, 'rb');
        }

        $resource = $this->clInstance->adminApi()->asset($id, ['resource_type' => $type]);

        return fopen($resource->offsetGet('secure_url'), 'rb');
    }
}
