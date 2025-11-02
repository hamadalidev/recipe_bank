<?php

namespace Database\Factories;

use App\Models\Recipe;
use App\Models\User;
use App\Models\CuisineType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Recipe>
 */
class RecipeFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Recipe::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $recipeNames = [
            'Delicious Pasta Carbonara',
            'Spicy Thai Green Curry',
            'Classic Chicken Tikka Masala',
            'Fresh Mediterranean Salad',
            'Homemade Pizza Margherita',
            'Creamy Mushroom Risotto',
            'Grilled Salmon with Herbs',
            'Chocolate Chip Cookies',
            'Beef Stir Fry with Vegetables',
            'Lemon Garlic Roasted Chicken'
        ];

        $ingredients = [
            'Fresh basil leaves',
            'Extra virgin olive oil',
            'Garlic cloves',
            'Onions',
            'Tomatoes',
            'Salt and pepper',
            'Parmesan cheese',
            'Fresh herbs',
            'Lemon juice',
            'Butter'
        ];

        $steps = [
            'Preheat the oven to 180°C',
            'Chop all vegetables into small pieces',
            'Heat oil in a large pan over medium heat',
            'Add garlic and onions, cook until fragrant',
            'Season with salt and pepper to taste',
            'Cook for 15-20 minutes until tender',
            'Serve hot with fresh herbs',
            'Let it rest for 5 minutes before serving'
        ];

        return [
            'name' => $this->faker->randomElement($recipeNames) . ' ' . $this->faker->numberBetween(1, 100),
            'description' => $this->faker->paragraph(3),
            'ingredients' => $this->faker->randomElements($ingredients, $this->faker->numberBetween(3, 8)),
            'steps' => $this->faker->randomElements($steps, $this->faker->numberBetween(4, 6)),
            'user_id' => User::factory(),
            'cuisine_type_id' => CuisineType::factory(),
            'created_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'updated_at' => now(),
        ];
    }
}