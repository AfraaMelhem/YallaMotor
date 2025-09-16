<?php

namespace App\Services;

use App\Libraries\Firebase\Facades\FirebaseNotification;
use App\Repositories\Interfaces\NotificationRepositoryInterface;
use App\Repositories\Interfaces\UserRepositoryInterface;

class NotificationService
{
    public function __construct(
        protected NotificationRepositoryInterface $notificationRepositoryInterface,
        protected UserRepositoryInterface $userRepositoryInterface
    ) {}

   public function getPaginatedList(array $data = [], int $perPage = 15)
    {
        return $this->userRepositoryInterface->getPaginatedList($data, $perPage);
    }

    public function show(int $id)
    {
        return $this->notificationRepositoryInterface->show($id);
    }

    public function create(array $data)
    {
        if (!empty($data['topic'])) {
            $notification = $this->sendToTopic($data);
        } elseif (!empty($data['user_ids'])) {
            $notification = $this->sendToUsers($data);
        } else {
            throw new \InvalidArgumentException('You must provide either user_ids or topic.');
        }
        return $notification;
    }

    public function delete(int $id): bool
    {
        return $this->notificationRepositoryInterface->delete($id);
    }

    public function sendToUsers(array $data)
    {
        return FirebaseNotification::setTitle($data['title'])
            ->setBody($data['body'])
            ->setUsers($data['user_ids'], \App\Models\User::class)
            ->push();
    }

    public function sendToTopic(array $data)
    {
        return FirebaseNotification::setTitle($data['title'])
            ->setBody($data['body'])
            ->setTopic($data['topic'])
            ->push();
    }
}
