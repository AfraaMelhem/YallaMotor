<?php

namespace App\Repositories\Eloquent;

use App\Models\ListingEvent;
use App\Repositories\Interfaces\ListingEventRepositoryInterface;

class ListingEventRepository extends BaseRepository implements ListingEventRepositoryInterface
{
    public function __construct(ListingEvent $model)
    {
        parent::__construct($model);
    }

    public function getEventsForListing(int $listingId)
    {
        return $this->model->where('listing_id', $listingId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getEventsByType(string $type)
    {
        return $this->model->where('type', $type)
            ->orderBy('created_at', 'desc')
            ->get();
    }
}