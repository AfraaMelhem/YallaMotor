<?php

namespace App\Repositories\Eloquent;

use App\Repositories\Interfaces\NotificationRepositoryInterface;
use App\Models\Notification;
use Illuminate\Support\Facades\Auth;

class NotificationRepository extends BaseRepository implements NotificationRepositoryInterface
{

    public function __construct(Notification $model)
    {
        parent::__construct($model);
    }
}
