<?php

declare(strict_types=1);

class DeviceTypeGenericSwitch
{
    use HelperDeviceType;
    private static $implementedCapabilities = [
        'PowerController'
    ];

    private static $displayedCategories = [
        'SWITCH'
    ];

    private static $displayStatusPrefix = false;
    private static $skipMissingStatus = false;

    public static function getPosition()
    {
        return 50;
    }

    public static function getCaption()
    {
        return 'Generic Switch';
    }

    public static function getTranslations()
    {
        return [
            'de' => [
                'Generic Switch'  => 'Generischer Schalter',
                'Switch Variable' => 'Schaltervariable'
            ]
        ];
    }
}

DeviceTypeRegistry::register('GenericSwitch');
