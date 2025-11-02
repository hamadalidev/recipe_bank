<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\CuisineType;
use App\Repositories\Base\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CuisineTypeRepository extends BaseRepository
{
    public function model(): string
    {
        return CuisineType::class;
    }

    /**
     * Get all active cuisine types for dropdown
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getActiveForDropdown()
    {
        return $this->model->where('status', true)
            ->orderBy('name', 'asc')
            ->get(['id', 'name']);
    }

}