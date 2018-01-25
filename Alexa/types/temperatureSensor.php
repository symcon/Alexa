<?php

declare(strict_types=1);

class DeviceTypeTemperatureSensor
{
    private static $implementedCapabilities = [
        'TemperatureSensor'
    ];

    private static $displayedCategories = [
        'TEMPERATURE_SENSOR'
    ];

    use HelperDeviceType;

    public static function getPosition()
    {
        return 4;
    }

    public static function getCaption()
    {
        return 'Temperature Sensor';
    }
}

DeviceTypeRegistry::register('TemperatureSensor');
