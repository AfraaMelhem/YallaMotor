<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Notification\StoreNotificationRequest;
use App\Http\Resources\BaseResource;
use App\Libraries\Firebase\Facades\FirebaseNotification;
use App\Models\User;
use App\Services\NotificationService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class NotificationController extends Controller
{

    public function __construct(protected NotificationService $notificationService) {}

    public function getPaginatedList(array $data = [], int $perPage = 15)
    {
        $notifications = $this->notificationService->getPaginatedList();
        return $this->successResponse('success', BaseResource::collection($notifications));
    }
    public function create(StoreNotificationRequest $request)
    {
        $data = $request->validated();
        try {
            $notification = $this->notificationService->create($data);
            return $this->successResponse('success');
        } catch (Exception $e) {
            
            return $this->errorResponse($e->getMessage(), 500);
        }
    }
    public function delete($id)
    {
        try {
            $this->notificationService->delete($id);
            return $this->successResponse('success');
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }
}
