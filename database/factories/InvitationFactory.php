<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class InvitationFactory extends Factory
{
    protected $model = Invitation::class;

    public function definition(): array
    {
        return [
            'account_id'  => Account::factory(),
            'email'       => fake()->unique()->safeEmail(),
            'name'        => fake()->name(),
            'role_id'     => null,
            'token'       => hash('sha256', Str::random(64) . microtime(true)),
            'status'      => Invitation::STATUS_PENDING,
            'invited_by'  => User::factory(),
            'accepted_by' => null,
            'expires_at'  => now()->addHours(72),
            'accepted_at' => null,
            'cancelled_at' => null,
            'last_sent_at' => now(),
            'send_count'  => 1,
        ];
    }

    /**
     * Set status to accepted.
     */
    public function accepted(): static
    {
        return $this->state(fn () => [
            'status'      => Invitation::STATUS_ACCEPTED,
            'accepted_at' => now(),
        ]);
    }

    /**
     * Set status to expired.
     */
    public function expired(): static
    {
        return $this->state(fn () => [
            'status'     => Invitation::STATUS_EXPIRED,
            'expires_at' => now()->subHours(1),
        ]);
    }

    /**
     * Set status to cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn () => [
            'status'       => Invitation::STATUS_CANCELLED,
            'cancelled_at' => now(),
        ]);
    }

    /**
     * Create an invitation that is pending but past its TTL (stale).
     */
    public function stale(): static
    {
        return $this->state(fn () => [
            'status'     => Invitation::STATUS_PENDING,
            'expires_at' => now()->subHours(1),
        ]);
    }
}
