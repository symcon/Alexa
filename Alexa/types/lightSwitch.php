<?php

declare(strict_types=1);

class DeviceTypeLightSwitch
{
    private static $implementedCapabilities = [
        'PowerController'
    ];

    private static $displayedCategories = [
        'LIGHT'
    ];

    use HelperDeviceType;

    public static function getPosition()
    {
        return 0;
    }

    public static function getCaption()
    {
        return 'Light (Switch)';
    }

    public static function getTranslations()
    {
        return [
            'de' => [
                'Light (Switch)' => 'Licht (Schalter)',
                'Variable'       => 'Variable'
            ]
        ];
    }
}

DeviceTypeRegistry::register('LightSwitch');
