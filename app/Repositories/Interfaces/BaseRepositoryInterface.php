<?php

namespace App\Repositories\Interfaces;
use Illuminate\Database\Eloquent\Builder;

interface BaseRepositoryInterface
{
    public function list(array $request = []): Builder;

    public function show(int $id);

    public function create(array $data);


    public function update($id, array $data);

    public function delete(int|string $id): bool;

    public function getPaginatedList(array $data = [], int $perPage = 15): mixed;

}
