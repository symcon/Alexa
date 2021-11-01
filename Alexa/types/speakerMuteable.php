<?php

declare(strict_types=1);

class DeviceTypeSpeakerMuteable extends DeviceType
{
    public function __construct(...$values)
    {
        parent::__construct(...$values);

        $this->implementedCapabilities = [
            'SpeakerMuteable'
        ];
        $this->displayedCategories = [
            'SPEAKER'
        ];
    }

    public function getPosition()
    {
        return 31;
    }

    public function getCaption()
    {
        return 'Speaker (Muteable)';
    }

    public function getTranslations()
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
