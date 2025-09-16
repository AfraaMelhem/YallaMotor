<?php

namespace App\Repositories\Eloquent;

use App\Repositories\Interfaces\MediaRepositoryInterface;
use App\Models\Media;

class MediaRepository extends BaseRepository implements MediaRepositoryInterface
{

 public function __construct(Media $model)
    {
        parent::__construct($model);
    }

    public function createForModel($model, array $data)
    {
        return $model->media()->create($data);
    }
}
