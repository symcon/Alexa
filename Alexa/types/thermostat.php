<?php

declare(strict_types=1);

class DeviceTypeThermostat
{
    use HelperDeviceType;
    private static $implementedCapabilities = [
        'ThermostatController'
    ];

    private static $displayedCategories = [
        'THERMOSTAT'
    ];

    private static $displayStatusPrefix = false;
    private static $skipMissingStatus = false;

    public static function getPosition()
    {
        return 21;
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
