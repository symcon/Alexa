<?php

declare(strict_types=1);

class DeviceTypeLightSwitch
{
    private static $implementedCapabilities = [
        'BrightnessController'
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
        return 'Light (Dim)';
    }
}

DeviceTypeRegistry::register('LightDimmer');
