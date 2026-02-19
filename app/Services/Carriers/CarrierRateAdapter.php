<?php

namespace App\Services\Carriers;

/**
 * CarrierRateAdapter — FR-RT-001: Fetch net rates from carriers.
 *
 * This is an abstraction layer for carrier API integration.
 * Currently provides simulated DHL rates; will be replaced with real API calls.
 */
class CarrierRateAdapter
{
    /**
     * Fetch available rates from carrier(s).
     *
     * @return array List of raw carrier rate options
     */
    public function fetchRates(array $params): array
    {
        $carrier = $params['carrier_code'] ?? null;

        // Route to appropriate carrier
        return match ($carrier) {
            'dhl_express' => $this->fetchDhlRates($params),
            'aramex'      => $this->fetchAramexRates($params),
            null          => array_merge($this->fetchDhlRates($params), $this->fetchAramexRates($params)),
            default       => [],
        };
    }

    /**
     * Simulated DHL Express rates.
     */
    private function fetchDhlRates(array $params): array
    {
        $weight = (float) ($params['chargeable_weight'] ?? $params['total_weight'] ?? 1);
        $isIntl  = ($params['origin_country'] ?? 'SA') !== ($params['destination_country'] ?? 'SA');
        $baseMultiplier = $isIntl ? 18.0 : 8.0;

        $services = [];

        // Express
        $expressBase = round($weight * $baseMultiplier * 1.2, 2);
        $fuelSurcharge = round($expressBase * 0.145, 2);
        $services[] = [
            'carrier_code'       => 'dhl_express',
            'carrier_name'       => 'DHL Express',
            'service_code'       => 'express_worldwide',
            'service_name'       => $isIntl ? 'DHL Express Worldwide' : 'DHL Express Domestic',
            'net_rate'           => $expressBase,
            'fuel_surcharge'     => $fuelSurcharge,
            'other_surcharges'   => 0,
            'total_net_rate'     => round($expressBase + $fuelSurcharge, 2),
            'estimated_days_min' => $isIntl ? 2 : 1,
            'estimated_days_max' => $isIntl ? 4 : 2,
            'is_available'       => true,
        ];

        // Economy
        $econBase = round($weight * $baseMultiplier * 0.7, 2);
        $econFuel = round($econBase * 0.12, 2);
        $services[] = [
            'carrier_code'       => 'dhl_express',
            'carrier_name'       => 'DHL Express',
            'service_code'       => 'economy_select',
            'service_name'       => $isIntl ? 'DHL Economy Select' : 'DHL Economy Domestic',
            'net_rate'           => $econBase,
            'fuel_surcharge'     => $econFuel,
            'other_surcharges'   => 0,
            'total_net_rate'     => round($econBase + $econFuel, 2),
            'estimated_days_min' => $isIntl ? 5 : 3,
            'estimated_days_max' => $isIntl ? 8 : 5,
            'is_available'       => true,
        ];

        // Express 9:00 (premium, international only)
        if ($isIntl) {
            $premBase = round($weight * $baseMultiplier * 1.8, 2);
            $premFuel = round($premBase * 0.16, 2);
            $services[] = [
                'carrier_code'       => 'dhl_express',
                'carrier_name'       => 'DHL Express',
                'service_code'       => 'express_9_00',
                'service_name'       => 'DHL Express 9:00',
                'net_rate'           => $premBase,
                'fuel_surcharge'     => $premFuel,
                'other_surcharges'   => round($weight * 2.5, 2),
                'total_net_rate'     => round($premBase + $premFuel + $weight * 2.5, 2),
                'estimated_days_min' => 1,
                'estimated_days_max' => 2,
                'is_available'       => true,
            ];
        }

        return $services;
    }

    /**
     * Simulated Aramex rates.
     */
    private function fetchAramexRates(array $params): array
    {
        $weight = (float) ($params['chargeable_weight'] ?? $params['total_weight'] ?? 1);
        $isIntl  = ($params['origin_country'] ?? 'SA') !== ($params['destination_country'] ?? 'SA');
        $baseMultiplier = $isIntl ? 16.0 : 6.5;

        $services = [];

        // Priority Express
        $prioBase = round($weight * $baseMultiplier * 1.1, 2);
        $prioFuel = round($prioBase * 0.13, 2);
        $services[] = [
            'carrier_code'       => 'aramex',
            'carrier_name'       => 'Aramex',
            'service_code'       => 'priority_express',
            'service_name'       => 'Aramex Priority Express',
            'net_rate'           => $prioBase,
            'fuel_surcharge'     => $prioFuel,
            'other_surcharges'   => 0,
            'total_net_rate'     => round($prioBase + $prioFuel, 2),
            'estimated_days_min' => $isIntl ? 3 : 1,
            'estimated_days_max' => $isIntl ? 5 : 3,
            'is_available'       => true,
        ];

        // Deferred
        $defBase = round($weight * $baseMultiplier * 0.65, 2);
        $defFuel = round($defBase * 0.10, 2);
        $services[] = [
            'carrier_code'       => 'aramex',
            'carrier_name'       => 'Aramex',
            'service_code'       => 'deferred',
            'service_name'       => 'Aramex Deferred',
            'net_rate'           => $defBase,
            'fuel_surcharge'     => $defFuel,
            'other_surcharges'   => 0,
            'total_net_rate'     => round($defBase + $defFuel, 2),
            'estimated_days_min' => $isIntl ? 6 : 3,
            'estimated_days_max' => $isIntl ? 10 : 6,
            'is_available'       => true,
        ];

        return $services;
    }
}
