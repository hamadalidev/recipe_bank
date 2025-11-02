<?php

namespace Database\Seeders;

use App\Models\CuisineType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CuisineTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $cuisineTypes = [
            [
                'name' => 'Italian',
                'description' => 'Traditional Italian cuisine featuring pasta, pizza, and regional specialties',
                'status' => true,
            ],
            [
                'name' => 'Chinese',
                'description' => 'Traditional Chinese cooking with diverse regional styles and techniques',
                'status' => true,
            ],
            [
                'name' => 'Mexican',
                'description' => 'Vibrant Mexican cuisine with bold flavors and traditional ingredients',
                'status' => true,
            ],
            [
                'name' => 'Indian',
                'description' => 'Rich Indian cuisine with aromatic spices and diverse regional dishes',
                'status' => true,
            ],
            [
                'name' => 'French',
                'description' => 'Classic French cuisine known for elegant techniques and refined flavors',
                'status' => true,
            ],
            [
                'name' => 'Japanese',
                'description' => 'Traditional Japanese cuisine emphasizing fresh ingredients and presentation',
                'status' => true,
            ],
            [
                'name' => 'Thai',
                'description' => 'Thai cuisine balancing sweet, sour, salty, and spicy flavors',
                'status' => true,
            ],
            [
                'name' => 'Mediterranean',
                'description' => 'Healthy Mediterranean diet featuring olive oil, vegetables, and seafood',
                'status' => true,
            ],
        ];

        foreach ($cuisineTypes as $cuisineType) {
            CuisineType::create($cuisineType);
        }
    }
}
