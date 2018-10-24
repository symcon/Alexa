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

    private static $displayStatusPrefix = false;

    use HelperDeviceType;

    public static function getPosition()
    {
        return 20;
    }

    public static function getCaption()
    {
        return 'Temperature Sensor';
    }

    public static function getTranslations()
    {
        return [
            'de' => [
                'Temperature Sensor' => 'Temperatursensor',
                'Variable'           => 'Variable'
            ]
        ];
    }
}

DeviceTypeRegistry::register('TemperatureSensor');
