<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Role\StoreRoleRequest;
use App\Http\Requests\Role\UpdateRoleRequest;
use App\Repositories\Eloquent\RoleRepository;
use App\Repositories\Interfaces\RoleRepositoryInterface;
use App\Services\RoleService;
use App\Traits\BaseResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RoleController extends Controller
{
    use BaseResponse;

    public function __construct (protected RoleRepositoryInterface $roleRepository) {}

    public function index(Request $request)
    {
        $roles = $this->roleRepository->getPaginatedList($request->all());
        return $this->successResponse('Roles retrieved', $roles);
    }

    public function store(StoreRoleRequest $request)
    {
        $role = $this->roleRepository->create($request->validated());
        return $this->successResponse('Role created', $role, 201);
    }

    public function show($id)
    {
        $role = $this->roleRepository->show($id);
        return $this->successResponse('Role retrieved', $role);
    }

    public function update(UpdateRoleRequest $request, $id)
    {
        $role = $this->roleRepository->update($id, $request->validated());
        return $this->successResponse('Role updated', $role);
    }

    public function destroy($id)
    {
        $this->roleRepository->delete($id);
        return $this->successResponse('Role deleted');
    }


    public function assignPermissions(Request $request, int|string $roleId)
    {
        $validated = $request->validate([
            'permissions' => 'required|array',
            'permissions.*' => 'exists:permissions,id',
        ]);
            $this->roleRepository->assignPermissions($roleId, $validated['permissions']);
            return $this->successResponse('Permissions assigned to role successfully');

    }

}
