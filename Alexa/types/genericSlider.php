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

    public static function getTranslations()
    {
        return [
            'de' => [
                'Generic Slider' => 'Generischer Slider',
                'Variable'       => 'Variable'
            ]
        ];
    }
}

DeviceTypeRegistry::register('GenericSlider');
