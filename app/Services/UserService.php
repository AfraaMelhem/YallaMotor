<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Services\CacheService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

class UserService
{
    public function __construct(
        protected UserRepositoryInterface $userRepository,
        private CacheService $cacheService
    ) {}


    public function getPaginatedList(array $data = [], int $perPage = 15)
    {
        return $this->userRepository->getPaginatedList($data, $perPage);
    }

    public function show(int $id)
    {
        $cacheKey = "user:{$id}";
        $tags = ["user:{$id}"];

        return $this->cacheService->remember($cacheKey, $tags, 600, function () use ($id) {
            return $this->userRepository->show($id);
        });
    }

    public function showWithRelations(int $id)
    {
        $cacheKey = "user_full:{$id}";
        $tags = ["user:{$id}"];

        return $this->cacheService->remember($cacheKey, $tags, 600, function () use ($id) {
            return $this->userRepository->show($id);
        });
    }

    public function create(array $data)
    {
        $user = $this->userRepository->create($data);

        // Clear relevant caches
        Cache::tags(['user_statistics'])->flush();

        return $user;
    }

    public function update(int $id, array $data)
    {
        $user = $this->userRepository->update($id, $data);

        // Clear relevant caches
        Cache::forget("user:{$id}");
        Cache::forget("user_full:{$id}");
        Cache::tags(['user_statistics'])->flush();

        return $user;
    }

    public function delete(int $id): bool
    {
        $result = $this->userRepository->delete($id);

        // Clear relevant caches
        Cache::forget("user:{$id}");
        Cache::forget("user_full:{$id}");
        Cache::tags(['user_statistics'])->flush();

        return $result;
    }


}
