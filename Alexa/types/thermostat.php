<?php

declare(strict_types=1);

class DeviceTypeThermostat
{
    private static $implementedCapabilities = [
        'ThermostatController'
    ];

    private static $displayedCategories = [
        'THERMOSTAT'
    ];

    use HelperDeviceType;

    public static function getPosition()
    {
        return 5;
    }

    public static function getCaption()
    {
        return 'Thermostat';
    }

    public static function getTranslations()
    {
        return [
            'de' => [
                'Thermostat' => 'Thermostat',
                'Variable'   => 'Variable'
            ]
        ];
    }
}

DeviceTypeRegistry::register('Thermostat');
