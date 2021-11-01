<?php

declare(strict_types=1);

class DeviceTypeMediaPlayer extends DeviceType
{
    public function __construct(...$values)
    {
        parent::__construct(...$values);

        $this->implementedCapabilities = [
            'PowerController',
            'SpeakerMuteable',
            'PlaybackController'
        ];
        $this->displayedCategories = [
            'SPEAKER'
        ];

        $this->displayStatusPrefix = true;
        $this->skipMissingStatus = true;
    }

    public function getPosition()
    {
        return 39;
    }

    public function getCaption()
    {
        return 'Mediaplayer';
    }

    public function getTranslations()
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
