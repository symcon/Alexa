<?php

declare(strict_types=1);

class DeviceTypeShutter
{
    use HelperDeviceType;
    private static $implementedCapabilities = [
        'RangeControllerShutter'
    ];

    private static $displayedCategories = [
        'EXTERIOR_BLIND'
    ];

    private static $displayStatusPrefix = false;
    private static $skipMissingStatus = false;

    public static function getPosition()
    {
        return 40;
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
