<?php

declare(strict_types=1);

class DeviceTypeLock
{
    use HelperDeviceType;
    private static $implementedCapabilities = [
        'LockController'
    ];

    private static $displayedCategories = [
        'SMARTLOCK'
    ];

    private static $displayStatusPrefix = false;
    private static $skipMissingStatus = false;

    public static function getPosition()
    {
        return 10;
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
