<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use Illuminate\Database\Seeder;

/**
 * PricingParametersSeeder — Seeds default dynamic pricing parameters into SystemSetting.
 * Group: 'pricing'
 * All values match the original hardcoded defaults in DynamicPricingService.
 */
class PricingParametersSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            // Fuel surcharges per mode (fraction of dynamic price)
            'fuel_surcharges' => [
                'type'  => 'json',
                'value' => ['air' => 0.18, 'sea' => 0.08, 'land' => 0.12, 'default' => 0.15],
            ],

            // Service level multipliers
            'service_multipliers' => [
                'type'  => 'json',
                'value' => ['express' => 1.8, 'standard' => 1.0, 'economy' => 0.7],
            ],

            // Capacity limits per mode (max active shipments)
            'capacity_limits' => [
                'type'  => 'json',
                'value' => ['air' => 500, 'sea' => 2000, 'land' => 1000, 'default' => 500],
            ],

            // Dynamic factor floor/ceiling (±% from base)
            'dynamic_factor_bounds' => [
                'type'  => 'json',
                'value' => ['min' => 0.6, 'max' => 1.4],
            ],

            // Demand thresholds (24h shipment count → multiplier)
            'demand_thresholds' => [
                'type'  => 'json',
                'value' => [
                    ['min' => 101, 'max' => null, 'multiplier' => 1.25],
                    ['min' => 51,  'max' => 100,  'multiplier' => 1.15],
                    ['min' => 21,  'max' => 50,   'multiplier' => 1.05],
                    ['min' => 0,   'max' => 4,    'multiplier' => 0.90],
                ],
            ],

            // Capacity utilization thresholds → multiplier
            'capacity_thresholds' => [
                'type'  => 'json',
                'value' => [
                    ['min' => 0.9,  'max' => null, 'multiplier' => 1.30],
                    ['min' => 0.7,  'max' => 0.9,  'multiplier' => 1.15],
                    ['min' => 0.5,  'max' => 0.7,  'multiplier' => 1.05],
                    ['min' => 0.0,  'max' => 0.2,  'multiplier' => 0.85],
                ],
            ],

            // Season multipliers by month (1=Jan … 12=Dec)
            'season_multipliers' => [
                'type'  => 'json',
                'value' => [
                    '11' => 1.20, '12' => 1.20,  // Holiday peak
                    '1'  => 1.10,                 // Post-holiday
                    '6'  => 1.05, '7'  => 1.05,  // Summer
                    '2'  => 0.95, '3'  => 0.95,  // Low season
                ],
            ],

            // Time-of-day multipliers
            'time_multipliers' => [
                'type'  => 'json',
                'value' => [
                    'weekend'       => 0.92,  // Fri-Sat (SA)
                    'business_hours'=> 1.10,  // 09:00-14:00
                    'off_hours'     => 0.88,  // 22:00-06:00
                    'default'       => 1.00,
                ],
            ],

            // Business hours definition
            'business_hours' => [
                'type'  => 'json',
                'value' => ['start' => 9, 'end' => 14],
            ],

            // Off-hours definition
            'off_hours' => [
                'type'  => 'json',
                'value' => ['after' => 22, 'before' => 6],
            ],

            // Weekend days (0=Sun,1=Mon,…,5=Fri,6=Sat)
            'weekend_days' => [
                'type'  => 'json',
                'value' => [5, 6],
            ],

            // Quote validity in minutes
            'quote_validity_minutes' => [
                'type'  => 'string',
                'value' => '30',
            ],
        ];

        foreach ($defaults as $key => $config) {
            SystemSetting::setValue(
                group: 'pricing',
                key: $key,
                value: $config['value'],
                type: $config['type'],
            );
        }
    }
}
