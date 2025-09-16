<?php


namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Resources\BaseResource;
use App\Services\UserService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(protected UserService $userService)
    {
    }

    public function getPaginatedList(Request $request): JsonResponse
    {

        $data = $this->prepareListData($request);

        $users = $this->userService->getPaginatedList($data);
        return $this->successResponse('User list fetched successfully.', BaseResource::collection($users));
    }

    public function show(int $id): JsonResponse
    {
        $user = $this->userService->show($id);
        return $this->successResponse('User fetched successfully.', new BaseResource($user));
    }

    public function create(StoreUserRequest $request): JsonResponse
    {
        $data = $request->validated();

        try {
            $user = $this->userService->create($data);
            return $this->successResponse('User created successfully.', new BaseResource($user));
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function update(UpdateUserRequest $request, int $id): JsonResponse
    {
        $data = $request->validated();

        try {
            $user = $this->userService->update($id, $data);
            return $this->successResponse('User updated successfully.', new BaseResource($user));
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function delete(int $id): JsonResponse
    {
        try {
            $this->userService->delete($id);
            return $this->successResponse('User deleted successfully.');
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }
}
