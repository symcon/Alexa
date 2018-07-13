<?php

declare(strict_types=1);

class DeviceTypeGenericSlider
{
    private static $implementedCapabilities = [
        'PercentageController'
    ];

    private static $displayedCategories = [
        'SWITCH'
    ];

    use HelperDeviceType;

    public static function getPosition()
    {
        return 11;
    }

    public static function getCaption()
    {
        return 'Generic Slider';
    }
}

DeviceTypeRegistry::register('GenericSlider');
