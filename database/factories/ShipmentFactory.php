<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ShipmentFactory extends Factory
{
    protected $model = Shipment::class;

    public function definition(): array
    {
        return [
            'account_id'       => Account::factory(),
            'reference_number' => 'SHP-' . strtoupper($this->faker->unique()->bothify('########')),
            'source'           => Shipment::SOURCE_DIRECT,
            'status'           => Shipment::STATUS_DRAFT,

            'sender_name'       => $this->faker->name,
            'sender_phone'      => '+966' . $this->faker->numerify('#########'),
            'sender_address_1'  => $this->faker->streetAddress,
            'sender_city'       => 'الرياض',
            'sender_country'    => 'SA',

            'recipient_name'       => $this->faker->name,
            'recipient_phone'      => '+966' . $this->faker->numerify('#########'),
            'recipient_address_1'  => $this->faker->streetAddress,
            'recipient_city'       => 'جدة',
            'recipient_country'    => 'SA',

            'is_international'  => false,
            'is_cod'            => false,
            'is_insured'        => false,
            'is_return'         => false,
            'has_dangerous_goods' => false,
            'currency'          => 'SAR',
            'parcels_count'     => 1,
            'total_weight'      => 1.5,

            'created_by'  => User::factory(),
        ];
    }

    public function validated(): static
    {
        return $this->state(['status' => Shipment::STATUS_VALIDATED]);
    }

    public function rated(): static
    {
        return $this->state([
            'status'          => Shipment::STATUS_RATED,
            'carrier_code'    => 'dhl_express',
            'carrier_name'    => 'DHL Express',
            'service_code'    => 'express',
            'service_name'    => 'DHL Express Worldwide',
            'shipping_rate'   => 45.00,
            'total_charge'    => 52.00,
        ]);
    }

    public function purchased(): static
    {
        return $this->state([
            'status'           => Shipment::STATUS_PURCHASED,
            'carrier_code'     => 'dhl_express',
            'carrier_name'     => 'DHL Express',
            'service_code'     => 'express',
            'tracking_number'  => 'DHL' . $this->faker->numerify('##########'),
            'shipping_rate'    => 45.00,
            'total_charge'     => 52.00,
            'label_url'        => 'https://labels.example.com/' . $this->faker->uuid . '.pdf',
            'label_format'     => 'pdf',
            'label_created_at' => now(),
        ]);
    }

    public function inTransit(): static
    {
        return $this->purchased()->state([
            'status'     => Shipment::STATUS_IN_TRANSIT,
            'picked_up_at' => now()->subHours(6),
        ]);
    }

    public function delivered(): static
    {
        return $this->inTransit()->state([
            'status'              => Shipment::STATUS_DELIVERED,
            'actual_delivery_at'  => now(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state([
            'status'             => Shipment::STATUS_CANCELLED,
            'cancellation_reason' => 'User requested cancellation',
        ]);
    }

    public function international(): static
    {
        return $this->state([
            'recipient_country' => 'AE',
            'recipient_city'    => 'دبي',
            'is_international'  => true,
        ]);
    }

    public function cod(): static
    {
        return $this->state([
            'is_cod'     => true,
            'cod_amount' => 250.00,
        ]);
    }

    public function returnShipment(): static
    {
        return $this->state([
            'source'    => Shipment::SOURCE_RETURN,
            'is_return' => true,
        ]);
    }

    public function withDangerousGoods(): static
    {
        return $this->state([
            'has_dangerous_goods'    => true,
            'dg_declaration_status'  => 'pending',
        ]);
    }
}
