<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use App\Http\Requests\Permission\StorePermissionRequest;
use App\Http\Requests\Permission\UpdatePermissionRequest;
use App\Models\Permission;
use App\Repositories\Eloquent\PermissionRepository;
use App\Repositories\Interfaces\PermissionRepositoryInterface;
use App\Services\PermissionService;
use App\Traits\BaseResponse;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    use BaseResponse;

    public function __construct(protected PermissionRepositoryInterface $permissionRepository) {}

    public function index(Request $request)
    {
        $roles = $this->permissionRepository->getPaginatedList($request->all());
        return $this->successResponse('Permissions retrieved', $roles);
    }

    public function store(StorePermissionRequest $request)
    {
        $role = $this->permissionRepository->create($request->validated());
        return $this->successResponse('Permission created', $role, 201);
    }

    public function show($id)
    {
        $role = $this->permissionRepository->show($id);
        return $this->successResponse('Permission retrieved', $role);
    }

    public function update(UpdatePermissionRequest $request, $id)
    {
        $role = $this->permissionRepository->update($id, $request->validated());
        return $this->successResponse('Permission updated', $role);
    }

    public function destroy(int|string $id)
    {
        $this->permissionRepository->delete($id);
        return $this->successResponse('Permission deleted');
    }
}
