<?php

declare(strict_types=1);

class DeviceTypeLightDimmer
{
    use HelperDeviceType;
    private static $implementedCapabilities = [
        'BrightnessController'
    ];

    private static $displayedCategories = [
        'LIGHT'
    ];

    private static $displayStatusPrefix = false;
    private static $skipMissingStatus = false;

    public static function getPosition()
    {
        return 1;
    }

    public static function getCaption()
    {
        return 'Light (Dimmer)';
    }

    public static function getTranslations()
    {
        return [
            'de' => [
                'Light (Dimmer)' => 'Licht (Dimmer)',
                'Variable'       => 'Variable'
            ]
        ];
    }
}

DeviceTypeRegistry::register('LightDimmer');
