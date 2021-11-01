<?php

declare(strict_types=1);

class DeviceTypeSpeaker extends DeviceType
{
    public function __construct(...$values)
    {
        parent::__construct(...$values);

        $this->implementedCapabilities = [
            'Speaker'
        ];
        $this->displayedCategories = [
            'SPEAKER'
        ];
    }

    public function getPosition()
    {
        return 30;
    }

    public function getCaption()
    {
        return 'Speaker';
    }

    public function getTranslations()
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
