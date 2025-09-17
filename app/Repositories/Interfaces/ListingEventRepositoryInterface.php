<?php

namespace App\Repositories\Interfaces;

interface ListingEventRepositoryInterface extends BaseRepositoryInterface
{
    public function getEventsForListing(int $listingId);

    public function getEventsByType(string $type);
}