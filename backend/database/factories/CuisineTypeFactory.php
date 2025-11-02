<?php

namespace Database\Factories;

use App\Models\CuisineType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CuisineType>
 */
class CuisineTypeFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = CuisineType::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $cuisineTypes = [
            'Italian', 'Chinese', 'Mexican', 'Indian', 'Japanese', 
            'French', 'Thai', 'Greek', 'Spanish', 'Lebanese',
            'Korean', 'Vietnamese', 'American', 'British', 'German',
            'Turkish', 'Moroccan', 'Brazilian', 'Russian', 'Ethiopian'
        ];

        return [
            'name' => $this->faker->randomElement($cuisineTypes) . ' ' . $this->faker->unique()->randomNumber(4),
            'description' => $this->faker->sentence(10),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}