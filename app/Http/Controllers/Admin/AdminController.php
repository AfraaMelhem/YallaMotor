<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAdminRequest;
use App\Http\Requests\Admin\UpdateAdminRequest;
use App\Http\Resources\BaseResource;
use App\Services\AdminService;
use Exception;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    public function __construct(protected AdminService $adminService) {}

    public function getPaginatedList()
    {
//        DB::connection()->getPdo();
//        $dbName = DB::connection()->getDatabaseName();
//        dd($dbName);
        $admins = $this->adminService->getPaginatedList();
        return $this->successResponse('Admins retrieved successfully.', BaseResource::collection($admins));
    }
    public function create(StoreAdminRequest $request)
    {
        $data = $request->validated();

        try {
            $admin = $this->adminService->create($data);

            return $this->successResponse(
                'Admin created successfully.',
                ['admin' => new BaseResource($admin)],
                201
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }


    public function show($id)
    {
        try {
            $admin = $this->adminService->show($id);
            return $this->successResponse('Admin retrieved successfully.', new BaseResource($admin));
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 404);
        }
    }

    public function update(UpdateAdminRequest $request, $id)
    {
        $data = $request->validated();

        try {
            $admin = $this->adminService->update($id, $data);
            return $this->successResponse('Admin updated successfully.', new BaseResource($admin));
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function delete($id)
    {
        try {
            $this->adminService->delete($id);
            return $this->successResponse('Admin deleted successfully.');
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

}
