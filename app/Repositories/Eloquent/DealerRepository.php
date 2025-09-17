<?php

namespace App\Repositories\Eloquent;

use App\Models\Dealer;
use App\Repositories\Interfaces\DealerRepositoryInterface;

class DealerRepository extends BaseRepository implements DealerRepositoryInterface
{
    public function __construct(Dealer $model)
    {
        parent::__construct($model);
    }

    public function findByCountryCode(string $countryCode)
    {
        return $this->model->where('country_code', $countryCode)->get();
    }
}