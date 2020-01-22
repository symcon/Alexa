<?php

declare(strict_types=1);

class DeviceTypeShutter
{
    private static $implementedCapabilities = [
        'RangeControllerShutter'
    ];

    private static $displayedCategories = [
        'EXTERIOR_BLIND'
    ];

    private static $displayStatusPrefix = false;
    private static $skipMissingStatus = false;

    use HelperDeviceType;

    public static function getPosition()
    {
        return 39;
    }

    public static function getCaption()
    {
        return 'Shutter';
    }

    public static function getTranslations()
    {
        return [
            'de' => [
                'Shutter'          => 'Rollladen',
                'Shutter Variable' => 'Rollladen Variable'
            ]
        ];
    }
}

DeviceTypeRegistry::register('Shutter');
