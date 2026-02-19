<?php

namespace Database\Factories;

use App\Models\Account;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class AccountFactory extends Factory
{
    protected $model = Account::class;

    public function definition(): array
    {
        $name = fake()->company();
        return [
            'name'           => $name,
            'type'           => fake()->randomElement(['individual', 'organization']),
            'status'         => 'active',
            'kyc_status'     => 'unverified',
            'slug'           => Str::slug($name) . '-' . Str::random(4),
            'settings'       => [],
            'language'       => 'ar',
            'currency'       => 'SAR',
            'timezone'       => 'Asia/Riyadh',
            'country'        => 'SA',
            'date_format'    => 'Y-m-d',
            'weight_unit'    => 'kg',
            'dimension_unit' => 'cm',
        ];
    }

    public function individual(): static
    {
        return $this->state(['type' => 'individual']);
    }

    public function organization(): static
    {
        return $this->state(['type' => 'organization']);
    }

    public function suspended(): static
    {
        return $this->state(['status' => 'suspended']);
    }
}
