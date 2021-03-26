<?php

declare(strict_types=1);

class DeviceTypeLightExpert
{
    use HelperDeviceType;
    private static $implementedCapabilities = [
        'PowerController',
        'BrightnessOnlyController',
        'ColorOnlyController',
        'ColorTemperatureOnlyController'
    ];

    private static $displayedCategories = [
        'LIGHT'
    ];

    private static $displayStatusPrefix = true;
    private static $skipMissingStatus = false;
    private static $expertDevice = true;

    public static function getPosition()
    {
        return 3;
    }

    public static function getCaption()
    {
        return 'Light (Expert)';
    }

    public static function getTranslations()
    {
        return [
            'de' => [
                'Light (Expert)'             => 'Licht (Experte)',
                'Switch Variable'            => 'Schaltervariable',
                'Brightness Variable'        => 'Helligkeitsvariable',
                'Color Variable'             => 'Farbvariable',
                'Color Temperature Variable' => 'Farbtemperaturvariable'
            ]
        ];
    }
}

DeviceTypeRegistry::register('LightExpert');
