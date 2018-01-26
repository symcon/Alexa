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
        return 6;
    }

    public static function getCaption()
    {
        return 'Lock';
    }
}

DeviceTypeRegistry::register('Lock');
