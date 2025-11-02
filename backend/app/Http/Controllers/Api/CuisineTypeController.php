<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Base\BaseController;
use App\Http\Resources\CuisineTypeCollection;
use App\Http\Resources\CuisineTypeResource;
use App\Repositories\CuisineTypeRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * @group Cuisine Types
 *
 * APIs for managing cuisine types
 */
class CuisineTypeController extends BaseController
{
    public function __construct(
        private CuisineTypeRepository $cuisineTypeRepository
    ) {}

    /**
     * Get active cuisine types for dropdown
     *
     * Get a simple list of active cuisine types for use in recipe creation dropdowns.
     * No pagination, returns all active cuisine types.
     *
     * @response 200 scenario="success" {
     *   "success": true,
     *   "message": "Success",
     *   "data": [
     *     {
     *       "id": 1,
     *       "name": "Italian"
     *     },
     *     {
     *       "id": 2,
     *       "name": "Chinese"
     *     },
     *     {
     *       "id": 3,
     *       "name": "Mexican"
     *     }
     *   ]
     * }
     */
    public function dropdown(): JsonResponse
    {
        $cuisineTypes = $this->cuisineTypeRepository->getActiveForDropdown();

        return $this->successResponse($cuisineTypes);
    }

}
