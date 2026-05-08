<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class WalletFactory extends Factory
{
    public function definition(): array
    {
        $providers = ['BCA', 'BRI', 'Mandiri', 'Dana', 'Gopay', 'OVO', 'Cash'];
        $types     = ['bank', 'e_wallet', 'cash'];
        $provider  = fake()->randomElement($providers);

        return [
            'name'            => $provider,
            'slug'            => Str::slug($provider) . '-' . Str::random(4),
            'type'            => fake()->randomElement($types),
            'provider'        => $provider,
            'color'           => fake()->hexColor(),
            'balance'         => fake()->numberBetween(0, 10000000),
            'initial_balance' => 0,
            'is_active'       => true,
            'include_in_total'=> true,
        ];
    }
}
