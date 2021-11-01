<?php

declare(strict_types=1);

class DeviceTypeTelevision extends DeviceType
{
    public function __construct(...$values)
    {
        parent::__construct(...$values);

        $this->implementedCapabilities = [
            'PowerController',
            'ChannelController',
            'SpeakerMuteable',
            'InputController'
        ];
        $this->displayedCategories = [
            'TV'
        ];
        $this->displayStatusPrefix = true;
        $this->skipMissingStatus = true;
        $this->columnWidth = '150px';
    }

    public function getPosition()
    {
        return 38;
    }

    public function getCaption()
    {
        return 'Television';
    }

    public function getTranslations()
    {
        return [
            'de' => [
                'Television'        => 'Fernsehgerät',
                'Switch Variable'   => 'Schaltervariable',
                'Volume Variable'   => 'Lautstärkevariable',
                'Mute Variable'     => 'Stummvariable',
                'Channel Variable' 	=> 'Kanalvariable',
                'Input Variable'    => 'Eingangsvariable',
                'Supported Inputs'  => 'Unterstützte Eingänge'
            ]
        ];
    }

    public function isExpertDevice()
    {
        return true;
    }
}

DeviceTypeRegistry::register('Television');
