<?php

declare(strict_types=1);

class DeviceTypeSpeakerMuteable
{
    private static $implementedCapabilities = [
        'SpeakerMuteable'
    ];

    private static $displayedCategories = [
        'SPEAKER'
    ];

    private static $displayStatusPrefix = false;
    private static $skipMissingStatus = false;

    use HelperDeviceType;

    public static function getPosition()
    {
        return 31;
    }

    public static function getCaption()
    {
        return 'Speaker (Muteable)';
    }

    public static function getTranslations()
    {
        return [
            'de' => [
                'Speaker (Muteable)'  => 'Lautsprecher mit Stummschaltung',
                'Volume Variable'     => 'Lautstärkevariable',
                'Mute Variable'	      => 'Stummvariable',
            ]
        ];
    }
}

DeviceTypeRegistry::register('SpeakerMuteable');
