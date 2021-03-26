<?php

declare(strict_types=1);

class DeviceTypeMediaPlayer
{
    use HelperDeviceType;
    private static $implementedCapabilities = [
        'PowerController',
        'SpeakerMuteable',
        'PlaybackController'
    ];

    private static $displayedCategories = [
        'SPEAKER'
    ];

    private static $displayStatusPrefix = true;
    private static $skipMissingStatus = true;

    public static function getPosition()
    {
        return 39;
    }

    public static function getCaption()
    {
        return 'Mediaplayer';
    }

    public static function getTranslations()
    {
        return [
            'de' => [
                'Mediaplayer'       => 'Medienabspieler',
                'Switch Variable'   => 'Schaltervariable',
                'Volume Variable'   => 'Lautstärkevariable',
                'Mute Variable'     => 'Stummvariable',
                'Playback Variable' => 'Abspielvariable'

            ]
        ];
    }
}

DeviceTypeRegistry::register('MediaPlayer');
