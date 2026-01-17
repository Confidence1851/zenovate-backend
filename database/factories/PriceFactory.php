<?php

namespace Database\Factories;

use App\Models\Price;
use Illuminate\Database\Eloquent\Factories\Factory;

class PriceFactory extends Factory
{
    protected $model = Price::class;

    public function definition(): array
    {
        return [
            'product_id' => null,
            'currency' => $this->faker->randomElement(['USD', 'CAD']),
            'value' => $this->faker->randomFloat(2, 10, 1000),
            'location' => $this->faker->randomElement(['US', 'CA', 'OTHER']),
        ];
    }
}
