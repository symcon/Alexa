<?php

declare(strict_types=1);

class DeviceTypeSpeaker
{
    private static $implementedCapabilities = [
        'Speaker'
    ];

    private static $displayedCategories = [
        'SPEAKER'
    ];

    private static $displayStatusPrefix = false;
    private static $skipMissingStatus = false;

    use HelperDeviceType;

    public static function getPosition()
    {
        return 30;
    }

    public static function getCaption()
    {
        return 'Speaker';
    }

    public static function getTranslations()
    {
        return [
            'de' => [
                'Speaker'  => 'Lautsprecher',
                'Variable' => 'Variable'
            ]
        ];
    }
}

DeviceTypeRegistry::register('Speaker');
