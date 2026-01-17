<?php

namespace Database\Factories;

use App\Models\DiscountCode;
use Illuminate\Database\Eloquent\Factories\Factory;

class DiscountCodeFactory extends Factory
{
    protected $model = DiscountCode::class;

    public function definition(): array
    {
        return [
            'code' => $this->faker->unique()->bothify('???#####'),
            'discount_type' => $this->faker->randomElement(['percentage', 'fixed']),
            'discount_value' => $this->faker->randomFloat(2, 5, 500),
            'is_active' => true,
        ];
    }
}
