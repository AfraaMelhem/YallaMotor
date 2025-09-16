<?php

namespace App\Services;

use App\Repositories\Interfaces\MediaRepositoryInterface;
use App\Utilities\FileManager;

class MediaService
{
    protected $mediaRepositoryInterface;

    public function __construct(MediaRepositoryInterface $mediaRepositoryInterface)
    {
        $this->mediaRepositoryInterface = $mediaRepositoryInterface;
    }


    public function uploadMedia($model, $file, $description = null, $order = 0)
    {
        if (!$file) {
            return null;
        }

        $filePath = FileManager::upload('media', $file);

        $mediaData = [
            'filename' => $file->getClientOriginalName(),
            'mime_type' => FileManager::getMimeType(str_replace('storage/', '', $filePath)) ?? $file->getMimeType(),
            'file_path' => str_replace('storage/', '', $filePath),
            'description' => $description,
            'order' => $order,
            'mediable_id' => $model->id,
            'mediable_type' => get_class($model)
        ];

        return $this->mediaRepositoryInterface->createForModel($model, $mediaData);
    }

}
