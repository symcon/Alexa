<?php

declare(strict_types=1);

class DeviceTypeLightColor
{
    use HelperDeviceType;
    private static $implementedCapabilities = [
        'ColorController'
    ];

    private static $displayedCategories = [
        'LIGHT'
    ];

    private static $displayStatusPrefix = false;
    private static $skipMissingStatus = false;

    public static function getPosition()
    {
        return 2;
    }

    public static function getCaption()
    {
        return 'Light (Color)';
    }

    public static function getTranslations()
    {
        return [
            'de' => [
                'Light (Color)' => 'Licht (Farbe)',
                'Variable'      => 'Variable'
            ]
        ];
    }
}

DeviceTypeRegistry::register('LightColor');
