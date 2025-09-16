<?php

namespace App\Repositories\Interfaces;

interface DealerRepositoryInterface extends BaseRepositoryInterface
{
    public function findByCountryCode(string $countryCode);
}