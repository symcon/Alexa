<?php

declare(strict_types=1);

class DeviceTypeLock
{
    private static $implementedCapabilities = [
        'LockController'
    ];

    private static $displayedCategories = [
        'SMARTLOCK'
    ];

    use HelperDeviceType;

    public static function getPosition()
    {
        return 3;
    }

    public static function getCaption()
    {
        return 'Lock';
    }

    public static function getTranslations()
    {
        return [
            'de' => [
                'Lock'     => 'Schloss',
                'Variable' => 'Variable'
            ]
        ];
    }
}

DeviceTypeRegistry::register('Lock');
