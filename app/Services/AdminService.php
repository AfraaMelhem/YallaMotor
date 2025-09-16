<?php


namespace App\Services;

use App\Repositories\Interfaces\AdminRepositoryInterface;
use Illuminate\Support\Facades\Hash;

class AdminService
{
    protected $adminRepositoryInterface;

    public function __construct(AdminRepositoryInterface $adminRepositoryInterface)
    {
        $this->adminRepositoryInterface = $adminRepositoryInterface;
    }


    public function getPaginatedList()
    {
        return $this->adminRepositoryInterface->getPaginatedList();
    }

    public function show(int $id)
    {
        return $this->adminRepositoryInterface->show($id);
    }
    public function create(array $data)
    {
        $data['password'] = Hash::make($data['password']);

            $admin = $this->adminRepositoryInterface->create($data);

            $roleName = $admin->is_super_admin ? 'super-admin' : 'admin';
            $role = \Spatie\Permission\Models\Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'admin',
            ]);

            $admin->assignRole($role);

            return $admin;
    }

    public function update(int $id, array $data)
    {
        return $this->adminRepositoryInterface->update($id, $data);
    }

    public function delete(int $id)
    {
        return $this->adminRepositoryInterface->delete($id);
    }
public function findByEmail(string $email){
        return $this->adminRepositoryInterface->findByEmail($email);
}

}

