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
}

DeviceTypeRegistry::register('Thermostat');
