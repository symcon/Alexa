<?php

declare(strict_types=1);

class DeviceTypeLightColor
{
    private static $implementedCapabilities = [
        'ColorController'
    ];

    private static $displayedCategories = [
        'LIGHT'
    ];

    use HelperDeviceType;

    public static function getPosition()
    {
        return 2;
    }

    public static function getCaption()
    {
        return 'Light (Color)';
    }
}

DeviceTypeRegistry::register('LightColor');
